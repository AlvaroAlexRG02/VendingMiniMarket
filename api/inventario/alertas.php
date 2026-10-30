<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/ubicaciones.php';

function respuestaAlertasInventario(bool $success, $data = null, string $message = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(
        ['success' => $success, 'data' => $data, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function tiendasVisiblesAlertas(PDO $pdo, array $usuario): ?array
{
    if (in_array((int)($usuario['id_rol'] ?? 0), [1, 2], true)) {
        return null;
    }

    return array_map(
        'intval',
        array_column(ubicacionesAsignadas($pdo, (int)($usuario['id_usuario'] ?? 0)), 'id_tienda')
    );
}

function listarTiendasAlertas(PDO $pdo, ?array $tiendas): array
{
    if ($tiendas === null) {
        return $pdo->query("
            SELECT id_tienda, nombre
            FROM public.tienda
            ORDER BY nombre
        ")->fetchAll();
    }
    if (!$tiendas) {
        return [];
    }

    $parametros = [];
    $marcas = [];
    foreach ($tiendas as $indice => $idTienda) {
        $clave = ':tienda_' . $indice;
        $marcas[] = $clave;
        $parametros[$clave] = $idTienda;
    }
    $consulta = $pdo->prepare("
        SELECT id_tienda, nombre
        FROM public.tienda
        WHERE id_tienda IN (" . implode(', ', $marcas) . ")
        ORDER BY nombre
    ");
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}

function condicionUbicacionesAlertas(?array $tiendas, array &$parametros): string
{
    if ($tiendas === null) {
        return '';
    }
    if (!$tiendas) {
        return ' AND FALSE';
    }

    $marcas = [];
    foreach ($tiendas as $indice => $idTienda) {
        $clave = ':tienda_alerta_' . $indice;
        $marcas[] = $clave;
        $parametros[$clave] = $idTienda;
    }

    return ' AND t.id_tienda IN (' . implode(', ', $marcas) . ')';
}

function eventoAlerta(PDO $pdo, int $idAlerta, ?int $idUsuario, string $accion, array $datos): void
{
    $consulta = $pdo->prepare("
        INSERT INTO public.alerta_inventario_gestion
            (id_alerta, id_usuario, accion, evidencia, observaciones, justificacion)
        VALUES
            (:alerta, :usuario, :accion, :evidencia, :observaciones, :justificacion)
    ");
    $consulta->execute([
        ':alerta' => $idAlerta,
        ':usuario' => $idUsuario,
        ':accion' => $accion,
        ':evidencia' => $datos['evidencia'] ?? null,
        ':observaciones' => $datos['observaciones'] ?? null,
        ':justificacion' => $datos['justificacion'] ?? null,
    ]);
}

function sincronizarAlertas(PDO $pdo, ?array $tiendas): void
{
    $parametros = [];
    $condicionTiendas = condicionUbicacionesAlertas($tiendas, $parametros);

    $pdo->beginTransaction();
    try {
        $pdo->query("SELECT pg_advisory_xact_lock(hashtext('vending-alertas-inventario-sync'))");

        $sincronizar = $pdo->prepare("
            WITH actuales AS MATERIALIZED (
                SELECT
                    e.id_ubicacion,
                    e.id_producto,
                    e.cantidad AS stock_actual,
                    COALESCE(e.stock_minimo, 0) AS stock_minimo,
                    CASE
                        WHEN e.cantidad < 0 THEN 'negativo'
                        WHEN e.cantidad = 0 THEN 'cero'
                        ELSE 'bajo'
                    END AS tipo_alerta
                FROM public.existencia e
                JOIN public.producto p ON p.id_producto = e.id_producto
                JOIN public.ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion
                JOIN public.tienda t ON t.id_tienda = u.id_tienda
                WHERE p.estado = TRUE
                  AND t.estado = TRUE
                  AND (e.cantidad < 0 OR e.cantidad = 0
                       OR (e.cantidad > 0 AND e.cantidad <= COALESCE(e.stock_minimo, 0)))
                  $condicionTiendas
            ),
            previas AS MATERIALIZED (
                SELECT
                    a.id_alerta,
                    a.id_ubicacion,
                    a.id_producto,
                    a.tipo_alerta
                FROM public.alerta_inventario a
                JOIN actuales c
                  ON c.id_ubicacion = a.id_ubicacion
                 AND c.id_producto = a.id_producto
                WHERE a.estado <> 'RESUELTA'
            ),
            upsertadas AS (
                INSERT INTO public.alerta_inventario AS existente
                    (id_ubicacion, id_producto, tipo_alerta, estado,
                     stock_generacion, stock_minimo_generacion)
                SELECT id_ubicacion, id_producto, tipo_alerta, 'PENDIENTE',
                       stock_actual, stock_minimo
                FROM actuales
                ON CONFLICT (id_ubicacion, id_producto) WHERE estado <> 'RESUELTA'
                DO UPDATE SET
                    tipo_alerta = EXCLUDED.tipo_alerta,
                    estado = CASE
                        WHEN existente.tipo_alerta IS DISTINCT FROM EXCLUDED.tipo_alerta THEN 'PENDIENTE'
                        ELSE existente.estado
                    END,
                    fecha_generacion = CASE
                        WHEN existente.tipo_alerta IS DISTINCT FROM EXCLUDED.tipo_alerta THEN NOW()
                        ELSE existente.fecha_generacion
                    END,
                    fecha_actualizacion = CASE
                        WHEN existente.tipo_alerta IS DISTINCT FROM EXCLUDED.tipo_alerta THEN NOW()
                        ELSE existente.fecha_actualizacion
                    END,
                    fecha_cierre = CASE
                        WHEN existente.tipo_alerta IS DISTINCT FROM EXCLUDED.tipo_alerta THEN NULL
                        ELSE existente.fecha_cierre
                    END,
                    stock_generacion = EXCLUDED.stock_generacion,
                    stock_minimo_generacion = EXCLUDED.stock_minimo_generacion
                RETURNING id_alerta, id_ubicacion, id_producto, tipo_alerta
            ),
            eventos AS (
                INSERT INTO public.alerta_inventario_gestion
                    (id_alerta, accion, observaciones)
                SELECT
                    u.id_alerta,
                    CASE WHEN p.id_alerta IS NULL THEN 'GENERADA' ELSE 'ACTUALIZADA' END,
                    CASE
                        WHEN p.id_alerta IS NULL THEN NULL
                        ELSE 'La condición de stock cambió a ' || u.tipo_alerta || '.'
                    END
                FROM upsertadas u
                LEFT JOIN previas p
                  ON p.id_ubicacion = u.id_ubicacion
                 AND p.id_producto = u.id_producto
                WHERE p.id_alerta IS NULL
                   OR p.tipo_alerta IS DISTINCT FROM u.tipo_alerta
                RETURNING id_gestion
            )
            SELECT COUNT(*) FROM eventos
        ");
        $sincronizar->execute($parametros);
        $sincronizar->fetchColumn();

        $resolver = $pdo->prepare("
            WITH resueltas AS (
                UPDATE public.alerta_inventario a
                SET estado = 'RESUELTA',
                    fecha_actualizacion = NOW(),
                    fecha_cierre = NOW()
                FROM public.ubicacion_inventario u
                JOIN public.tienda t ON t.id_tienda = u.id_tienda
                WHERE u.id_ubicacion = a.id_ubicacion
                  AND a.estado <> 'RESUELTA'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM public.existencia e
                      JOIN public.producto p ON p.id_producto = e.id_producto
                      WHERE e.id_ubicacion = a.id_ubicacion
                        AND e.id_producto = a.id_producto
                        AND p.estado = TRUE
                        AND (e.cantidad < 0 OR e.cantidad = 0
                             OR (e.cantidad > 0 AND e.cantidad <= COALESCE(e.stock_minimo, 0)))
                  )
                  $condicionTiendas
                RETURNING a.id_alerta
            )
            INSERT INTO public.alerta_inventario_gestion (id_alerta, accion, observaciones)
            SELECT id_alerta, 'RESUELTA', 'El stock volvió al rango permitido.'
            FROM resueltas
        ");
        $resolver->execute($parametros);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function obtenerAlertas(PDO $pdo, ?array $tiendas, array $filtros): array
{
    $parametros = [];
    $condiciones = ['p.id_producto IS NOT NULL'];
    $condicionTiendas = condicionUbicacionesAlertas($tiendas, $parametros);
    $condiciones[] = $condicionTiendas === ''
        ? 'TRUE'
        : preg_replace('/^\s*AND\s+/', '', $condicionTiendas);

    if ($filtros['estado'] === 'ACTIVAS') {
        $condiciones[] = "a.estado IN ('PENDIENTE', 'ATENDIDA')";
    } elseif ($filtros['estado'] !== 'TODAS') {
        $condiciones[] = 'a.estado = :estado';
        $parametros[':estado'] = $filtros['estado'];
    }
    if ($filtros['tipo'] !== '') {
        $condiciones[] = 'a.tipo_alerta = :tipo';
        $parametros[':tipo'] = $filtros['tipo'];
    }
    if ($filtros['tienda'] > 0) {
        $condiciones[] = 't.id_tienda = :tienda_filtro';
        $parametros[':tienda_filtro'] = $filtros['tienda'];
    }
    if ($filtros['producto'] !== '') {
        $condiciones[] = 'p.nombre ILIKE :producto';
        $parametros[':producto'] = '%' . $filtros['producto'] . '%';
    }

    $consulta = $pdo->prepare("
        SELECT
            a.id_alerta, a.tipo_alerta, a.estado, a.fecha_generacion,
            a.fecha_actualizacion, a.fecha_cierre,
            a.stock_generacion, a.stock_minimo_generacion,
            a.id_ubicacion, u.nombre AS ubicacion,
            t.id_tienda, t.nombre AS tienda,
            a.id_producto, p.nombre AS producto,
            e.cantidad AS stock_actual, COALESCE(e.stock_minimo, 0) AS stock_minimo,
            ultimo.tipo_movimiento AS ultimo_tipo_movimiento,
            ultimo.estado AS ultimo_estado_movimiento,
            ultimo.fecha AS ultimo_movimiento,
            ultima.accion AS ultima_accion,
            ultima.evidencia AS ultima_evidencia,
            ultima.observaciones AS ultimas_observaciones,
            ultima.justificacion AS ultima_justificacion,
            ultima.fecha AS fecha_ultima_gestion,
            concat_ws(' ', usuario.nombre, usuario.apellido) AS responsable
        FROM public.alerta_inventario a
        JOIN public.producto p ON p.id_producto = a.id_producto
        JOIN public.ubicacion_inventario u ON u.id_ubicacion = a.id_ubicacion
        JOIN public.tienda t ON t.id_tienda = u.id_tienda
        LEFT JOIN public.existencia e
          ON e.id_producto = a.id_producto AND e.id_ubicacion = a.id_ubicacion
        LEFT JOIN LATERAL (
            SELECT
                m.tipo_movimiento,
                m.estado,
                COALESCE(m.fecha_confirmacion, m.fecha_movimiento) AS fecha
            FROM public.movimiento_inventario m
            JOIN public.detalle_movimiento d ON d.id_movimiento = m.id_movimiento
            WHERE d.id_producto = a.id_producto
              AND (m.id_ubicacion_destino = a.id_ubicacion
                   OR m.id_ubicacion_origen = a.id_ubicacion)
            ORDER BY COALESCE(m.fecha_confirmacion, m.fecha_movimiento) DESC,
                     m.id_movimiento DESC
            LIMIT 1
        ) ultimo ON TRUE
        LEFT JOIN LATERAL (
            SELECT g.accion, g.evidencia, g.observaciones, g.justificacion,
                   g.fecha, g.id_usuario
            FROM public.alerta_inventario_gestion g
            WHERE g.id_alerta = a.id_alerta
            ORDER BY g.fecha DESC, g.id_gestion DESC
            LIMIT 1
        ) ultima ON TRUE
        LEFT JOIN public.usuario usuario ON usuario.id_usuario = ultima.id_usuario
        WHERE " . implode(' AND ', $condiciones) . "
        ORDER BY
            CASE a.tipo_alerta WHEN 'negativo' THEN 0 WHEN 'cero' THEN 1 ELSE 2 END,
            a.fecha_generacion DESC, t.nombre, u.nombre
    ");
    $consulta->execute($parametros);

    return array_map(static function (array $fila): array {
        foreach (['id_alerta', 'id_ubicacion', 'id_tienda', 'id_producto', 'stock_generacion', 'stock_minimo_generacion'] as $campo) {
            $fila[$campo] = (int)$fila[$campo];
        }
        foreach (['stock_actual', 'stock_minimo'] as $campo) {
            $fila[$campo] = $fila[$campo] === null ? null : (int)$fila[$campo];
        }
        return $fila;
    }, $consulta->fetchAll());
}

if (empty($_SESSION['usuario'])) {
    respuestaAlertasInventario(false, null, 'Debe iniciar sesión.', 401);
}

$usuario = $_SESSION['usuario'];
$idUsuario = (int)($usuario['id_usuario'] ?? 0);
if (!in_array((int)($usuario['id_rol'] ?? 0), [1, 2, 3, 4], true) || $idUsuario <= 0) {
    respuestaAlertasInventario(false, null, 'No tiene permiso para gestionar alertas.', 403);
}
$tiendas = tiendasVisiblesAlertas($pdo, $usuario);
$accion = (string)($_GET['accion'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $entrada = json_decode(file_get_contents('php://input') ?: '', true);
    $entrada = is_array($entrada) ? $entrada : [];
    $accion = (string)($entrada['accion'] ?? $accion);
}

try {
    if ($accion === 'tiendas') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            respuestaAlertasInventario(false, null, 'Método no permitido.', 405);
        }
        respuestaAlertasInventario(true, listarTiendasAlertas($pdo, $tiendas));
    }

    if ($accion === 'gestionar') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            respuestaAlertasInventario(false, null, 'Método no permitido.', 405);
        }

        $idAlerta = (int)($entrada['id_alerta'] ?? 0);
        $estado = strtoupper(trim((string)($entrada['estado'] ?? '')));
        $evidencia = trim((string)($entrada['evidencia'] ?? ''));
        $observaciones = trim((string)($entrada['observaciones'] ?? ''));
        $justificacion = trim((string)($entrada['justificacion'] ?? ''));

        if ($idAlerta <= 0 || !in_array($estado, ['ATENDIDA', 'CERRADA', 'DESCARTADA'], true)) {
            respuestaAlertasInventario(false, null, 'La alerta o la acción no son válidas.', 400);
        }
        if (mb_strlen($evidencia, 'UTF-8') > 1000
            || mb_strlen($observaciones, 'UTF-8') > 1000
            || mb_strlen($justificacion, 'UTF-8') > 1000) {
            respuestaAlertasInventario(false, null, 'Los campos de gestión no pueden superar 1000 caracteres.', 400);
        }
        if ($estado === 'ATENDIDA' && ($evidencia === '' || $observaciones === '')) {
            respuestaAlertasInventario(false, null, 'Para atender la alerta, registre la evidencia y las observaciones.', 400);
        }
        if (in_array($estado, ['CERRADA', 'DESCARTADA'], true) && $justificacion === '') {
            respuestaAlertasInventario(false, null, 'La justificación es obligatoria para cerrar o descartar una alerta.', 400);
        }

        $pdo->beginTransaction();
        $consulta = $pdo->prepare("
            SELECT a.id_alerta, a.estado, a.tipo_alerta, a.id_ubicacion,
                   u.id_tienda, p.nombre AS producto
            FROM public.alerta_inventario a
            JOIN public.ubicacion_inventario u ON u.id_ubicacion = a.id_ubicacion
            JOIN public.producto p ON p.id_producto = a.id_producto
            WHERE a.id_alerta = :id
            FOR UPDATE OF a
        ");
        $consulta->execute([':id' => $idAlerta]);
        $alerta = $consulta->fetch();

        if (!$alerta) {
            $pdo->rollBack();
            respuestaAlertasInventario(false, null, 'La alerta no existe.', 404);
        }
        if (!usuarioPuedeVerUbicacion($pdo, (int)$alerta['id_tienda'])) {
            $pdo->rollBack();
            respuestaAlertasInventario(false, null, 'No tiene permiso para gestionar alertas en esta tienda.', 403);
        }
        if (in_array($alerta['estado'], ['CERRADA', 'DESCARTADA', 'RESUELTA'], true)) {
            $pdo->rollBack();
            respuestaAlertasInventario(false, null, 'La alerta ya no está activa y no puede modificarse.', 409);
        }

        $actualizar = $pdo->prepare("
            UPDATE public.alerta_inventario
            SET estado = :estado,
                fecha_actualizacion = NOW(),
                fecha_cierre = CASE WHEN :terminal THEN NOW() ELSE NULL END
            WHERE id_alerta = :id
        ");
        $terminal = in_array($estado, ['CERRADA', 'DESCARTADA'], true);
        $actualizar->execute([
            ':estado' => $estado,
            ':terminal' => $terminal,
            ':id' => $idAlerta,
        ]);
        $accionGestion = $estado === 'ATENDIDA' ? 'ATENDIDA' : $estado;
        eventoAlerta($pdo, $idAlerta, $idUsuario, $accionGestion, [
            'evidencia' => $evidencia !== '' ? $evidencia : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
            'justificacion' => $justificacion !== '' ? $justificacion : null,
        ]);
        registrarOperacion($pdo, 'ALERTA_INVENTARIO', $accionGestion, (int)$alerta['id_tienda'], [
            'id_alerta' => $idAlerta,
            'estado' => $alerta['estado'],
            'tipo_alerta' => $alerta['tipo_alerta'],
            'producto' => $alerta['producto'],
        ], [
            'id_alerta' => $idAlerta,
            'estado' => $estado,
            'evidencia' => $evidencia !== '' ? $evidencia : null,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
            'justificacion' => $justificacion !== '' ? $justificacion : null,
        ]);
        $pdo->commit();
        respuestaAlertasInventario(true, ['id_alerta' => $idAlerta, 'estado' => $estado], 'Gestión de alerta guardada.');
    }

    if ($accion === 'historial') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            respuestaAlertasInventario(false, null, 'Método no permitido.', 405);
        }
        $idAlerta = (int)($_GET['id_alerta'] ?? 0);
        $consulta = $pdo->prepare("
            SELECT a.id_alerta, u.id_tienda
            FROM public.alerta_inventario a
            JOIN public.ubicacion_inventario u ON u.id_ubicacion = a.id_ubicacion
            WHERE a.id_alerta = :id
        ");
        $consulta->execute([':id' => $idAlerta]);
        $alerta = $consulta->fetch();
        if (!$alerta) {
            respuestaAlertasInventario(false, null, 'La alerta no existe.', 404);
        }
        if (!usuarioPuedeVerUbicacion($pdo, (int)$alerta['id_tienda'])) {
            respuestaAlertasInventario(false, null, 'No tiene permiso para consultar esta alerta.', 403);
        }

        $historial = $pdo->prepare("
            SELECT g.accion, g.evidencia, g.observaciones, g.justificacion, g.fecha,
                   concat_ws(' ', u.nombre, u.apellido) AS responsable
            FROM public.alerta_inventario_gestion g
            LEFT JOIN public.usuario u ON u.id_usuario = g.id_usuario
            WHERE g.id_alerta = :id
            ORDER BY g.fecha DESC, g.id_gestion DESC
        ");
        $historial->execute([':id' => $idAlerta]);
        respuestaAlertasInventario(true, $historial->fetchAll());
    }

    if ($accion !== 'listar') {
        respuestaAlertasInventario(false, null, 'Acción no válida.', 400);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        respuestaAlertasInventario(false, null, 'Método no permitido.', 405);
    }

    $estadoPermitido = ['ACTIVAS', 'TODAS', 'PENDIENTE', 'ATENDIDA', 'CERRADA', 'DESCARTADA', 'RESUELTA'];
    $estado = strtoupper(trim((string)($_GET['estado'] ?? 'ACTIVAS')));
    $tipo = strtolower(trim((string)($_GET['tipo'] ?? '')));
    if (!in_array($estado, $estadoPermitido, true)
        || ($tipo !== '' && !in_array($tipo, ['bajo', 'cero', 'negativo'], true))) {
        respuestaAlertasInventario(false, null, 'Los filtros de alerta no son válidos.', 400);
    }

    $filtros = [
        'estado' => $estado,
        'tipo' => $tipo,
        'tienda' => max(0, (int)($_GET['tienda'] ?? 0)),
        'producto' => trim((string)($_GET['producto'] ?? '')),
    ];
    if (mb_strlen($filtros['producto'], 'UTF-8') > 100) {
        respuestaAlertasInventario(false, null, 'El filtro de producto no puede superar 100 caracteres.', 400);
    }

    if ($estado === 'ACTIVAS'
        && $filtros['tipo'] === ''
        && $filtros['producto'] === ''
        && $filtros['tienda'] === 0) {
        sincronizarAlertas($pdo, $tiendas);
    }
    respuestaAlertasInventario(true, obtenerAlertas($pdo, $tiendas, $filtros));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[alertas-inventario] ' . $e->getMessage());
    if ($e instanceof PDOException && in_array((string)$e->getCode(), ['42P01', '42703'], true)) {
        respuestaAlertasInventario(
            false,
            null,
            'Falta actualizar el esquema de alertas. Ejecuta Alertas.sql despues de Seguridad.sql y Conciliacion.sql.',
            500
        );
    }
    respuestaAlertasInventario(false, null, 'No fue posible procesar las alertas de inventario.', 500);
}
