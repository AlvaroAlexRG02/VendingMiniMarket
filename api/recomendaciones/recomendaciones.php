<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

ob_start();
register_shutdown_function(static function (): void {
    $salida = ob_get_contents();
    $error = error_get_last();
    $tiposFatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    $esFatal = $error !== null && in_array($error['type'], $tiposFatales, true);
    $esJsonValido = is_string($salida)
        && json_decode(trim($salida), true) !== null
        && json_last_error() === JSON_ERROR_NONE;

    if ($esFatal || (is_string($salida) && trim($salida) !== '' && !$esJsonValido)) {
        if ($esFatal) {
            error_log('[recomendaciones] Error fatal: ' . $error['message']);
        } else {
            error_log('[recomendaciones] La API terminó con una salida que no es JSON.');
        }
        ob_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'data' => null,
            'message' => 'La API de recomendaciones encontró un error interno. Revise el registro de errores de PHP.',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        return;
    }

    ob_end_flush();
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/reportes_lib.php';

date_default_timezone_set(ZONA_REPORTES);

function responderRecomendaciones(bool $success, $data = null, string $message = '', int $status = 200): void
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

function exigirAdministradorRecomendaciones(): int
{
    if (empty($_SESSION['usuario'])) {
        responderRecomendaciones(false, null, 'Debe iniciar sesión.', 401);
    }

    if (!esAdministradorOGerente()) {
        responderRecomendaciones(false, null, 'Solo Administración o Gerencia pueden gestionar recomendaciones.', 403);
    }

    return (int)$_SESSION['usuario']['id_usuario'];
}

function auditarRecomendacion(
    PDO $pdo,
    int $idUsuario,
    ?int $idTienda,
    string $accion,
    array $anterior,
    array $nuevo
): void {
    $consulta = $pdo->prepare("
        INSERT INTO public.bitacora_auditoria (
            id_usuario, id_tienda, entidad, accion, valor_anterior,
            valor_nuevo, resultado, ip_origen, fecha
        )
        VALUES (
            :id_usuario, :id_tienda, 'RECOMENDACION_PRODUCTO', :accion,
            CAST(:anterior AS JSONB), CAST(:nuevo AS JSONB), 'EXITOSO',
            CAST(:ip AS INET), NOW()
        )
    ");
    $consulta->execute([
        ':id_usuario' => $idUsuario,
        ':id_tienda' => $idTienda,
        ':accion' => $accion,
        ':anterior' => json_encode($anterior, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ':nuevo' => json_encode($nuevo, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function leerConfiguracionRecomendaciones(PDO $pdo): array
{
    $fila = $pdo->query("
        SELECT umbral_unidades, fecha_actualizacion
        FROM public.recomendacion_producto_config
        WHERE id_configuracion = 1
    ")->fetch();

    if (!$fila) {
        throw new RuntimeException('No existe la configuración de recomendaciones. Ejecute RecomendacionesProductos.sql.');
    }

    return $fila;
}

function listarRecomendaciones(PDO $pdo): array
{
    $recomendaciones = $pdo->query("
        SELECT
            r.id_recomendacion,
            r.id_tienda,
            t.nombre AS tienda,
            r.id_producto,
            p.nombre AS producto,
            c.nombre AS categoria,
            r.periodo_inicio,
            r.periodo_fin,
            r.unidades_vendidas,
            r.umbral_unidades,
            r.ventas_por_dia,
            r.stock_actual,
            r.ubicaciones,
            r.estado,
            r.fecha_generacion,
            r.fecha_actualizacion
        FROM public.recomendacion_producto r
        JOIN public.tienda t ON t.id_tienda = r.id_tienda
        JOIN public.producto p ON p.id_producto = r.id_producto
        LEFT JOIN public.categoria c ON c.id_categoria = p.id_categoria
        ORDER BY
            CASE r.estado WHEN 'PENDIENTE' THEN 0 WHEN 'REVISADA' THEN 1 ELSE 2 END,
            r.unidades_vendidas ASC,
            r.fecha_generacion DESC,
            r.id_recomendacion DESC
        LIMIT 500
    ")->fetchAll();

    if (!$recomendaciones) {
        return [];
    }

    $ids = array_map(static fn(array $fila): int => (int)$fila['id_recomendacion'], $recomendaciones);
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $historial = $pdo->prepare("
        SELECT
            h.id_recomendacion,
            h.estado_anterior,
            h.estado_nuevo,
            h.comentario,
            h.fecha,
            COALESCE(NULLIF(BTRIM(CONCAT_WS(' ', u.nombre, u.apellido)), ''), 'Sistema') AS responsable
        FROM public.recomendacion_producto_historial h
        LEFT JOIN public.usuario u ON u.id_usuario = h.id_usuario
        WHERE h.id_recomendacion IN ($marcadores)
        ORDER BY h.fecha, h.id_historial
    ");
    $historial->execute($ids);

    $porRecomendacion = [];
    foreach ($historial->fetchAll() as $fila) {
        $porRecomendacion[(int)$fila['id_recomendacion']][] = $fila;
    }

    foreach ($recomendaciones as &$fila) {
        $fila['id_recomendacion'] = (int)$fila['id_recomendacion'];
        $fila['id_tienda'] = (int)$fila['id_tienda'];
        $fila['id_producto'] = (int)$fila['id_producto'];
        $fila['unidades_vendidas'] = (int)$fila['unidades_vendidas'];
        $fila['umbral_unidades'] = (int)$fila['umbral_unidades'];
        $fila['stock_actual'] = (int)$fila['stock_actual'];
        $fila['ventas_por_dia'] = json_decode((string)$fila['ventas_por_dia'], true) ?: [];
        $fila['historial'] = $porRecomendacion[$fila['id_recomendacion']] ?? [];
    }
    unset($fila);

    return $recomendaciones;
}

function generarRecomendaciones(PDO $pdo, int $idUsuario): array
{
    $config = leerConfiguracionRecomendaciones($pdo);
    $hoy = hoyCostaRica();
    $inicio = (new DateTimeImmutable($hoy, new DateTimeZone(ZONA_REPORTES)))
        ->modify('-29 days')
        ->format('Y-m-d');
    $tiendas = array_map(
        'intval',
        $pdo->query('SELECT id_tienda FROM public.tienda WHERE estado = TRUE')->fetchAll(PDO::FETCH_COLUMN)
    );
    if (!$tiendas) {
        return ['creadas' => 0, 'umbral_unidades' => (int)$config['umbral_unidades']];
    }

    $idsTiendas = listaEnteros($tiendas);
    $desdeManual = "(CAST(:desde_manual AS DATE)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
    $hastaManual = "((CAST(:hasta_manual AS DATE) + 1)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
    $ventas = "
        SELECT
            v.id_tienda,
            d.id_producto,
            (v.fecha AT TIME ZONE 'America/Costa_Rica')::DATE AS fecha,
            SUM(d.cantidad)::INTEGER AS unidades
        FROM public.venta_manual v
        JOIN public.venta_manual_detalle d ON d.id_venta = v.id_venta
        JOIN public.movimiento_inventario m
          ON m.id_movimiento = v.id_movimiento
         AND m.estado = 'CONFIRMADO'
        WHERE v.id_tienda IN ($idsTiendas)
          AND v.fecha >= $desdeManual
          AND v.fecha < $hastaManual
        GROUP BY v.id_tienda, d.id_producto, (v.fecha AT TIME ZONE 'America/Costa_Rica')::DATE
    ";
    $parametrosVentas = [
        ':desde_manual' => $inicio,
        ':hasta_manual' => $hoy,
    ];
    if (tablaNayaxExiste($pdo)) {
        $desdeNayax = "(CAST(:desde_nayax AS DATE)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
        $hastaNayax = "((CAST(:hasta_nayax AS DATE) + 1)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
        $ventas .= "
            UNION ALL
            SELECT
                n.id_tienda,
                n.id_producto,
                (n.fecha AT TIME ZONE 'America/Costa_Rica')::DATE AS fecha,
                SUM(n.cantidad)::INTEGER AS unidades
            FROM public.venta_nayax n
            WHERE n.estado = 'CONFIRMADA'
              AND n.id_tienda IN ($idsTiendas)
              AND n.fecha >= $desdeNayax
              AND n.fecha < $hastaNayax
            GROUP BY n.id_tienda, n.id_producto, (n.fecha AT TIME ZONE 'America/Costa_Rica')::DATE
        ";
        $parametrosVentas[':desde_nayax'] = $inicio;
        $parametrosVentas[':hasta_nayax'] = $hoy;
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            WITH ventas_fuente AS ($ventas),
            ventas_dia AS (
                SELECT id_tienda, id_producto, fecha, SUM(unidades)::INTEGER AS unidades
                FROM ventas_fuente
                GROUP BY id_tienda, id_producto, fecha
            ),
            ventas AS (
                SELECT
                    id_tienda,
                    id_producto,
                    SUM(unidades)::INTEGER AS unidades_vendidas,
                    JSONB_AGG(
                        JSONB_BUILD_OBJECT('fecha', fecha, 'unidades', unidades)
                        ORDER BY fecha
                    ) AS ventas_por_dia
                FROM ventas_dia
                GROUP BY id_tienda, id_producto
            ),
            inventario AS (
                SELECT
                    u.id_tienda,
                    e.id_producto,
                    SUM(e.cantidad)::INTEGER AS stock_actual,
                    STRING_AGG(DISTINCT u.nombre, ', ' ORDER BY u.nombre) AS ubicaciones
                FROM public.existencia e
                JOIN public.ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion
                WHERE u.estado = TRUE
                GROUP BY u.id_tienda, e.id_producto
            ),
            candidatos AS (
                SELECT id_tienda, id_producto FROM inventario
                UNION
                SELECT id_tienda, id_producto FROM ventas
            ),
            insertadas AS (
                INSERT INTO public.recomendacion_producto (
                    id_tienda, id_producto, periodo_inicio, periodo_fin,
                    unidades_vendidas, umbral_unidades, ventas_por_dia,
                    stock_actual, ubicaciones
                )
                SELECT
                    c.id_tienda,
                    p.id_producto,
                    CAST(:periodo_inicio AS DATE),
                    CAST(:periodo_fin AS DATE),
                    COALESCE(v.unidades_vendidas, 0),
                    CAST(:umbral AS INTEGER),
                    COALESCE(v.ventas_por_dia, '[]'::JSONB),
                    COALESCE(i.stock_actual, 0),
                    i.ubicaciones
                FROM candidatos c
                JOIN public.tienda t
                  ON t.id_tienda = c.id_tienda AND t.estado = TRUE
                JOIN public.producto p
                  ON p.id_producto = c.id_producto AND p.estado = TRUE
                LEFT JOIN ventas v
                  ON v.id_tienda = c.id_tienda AND v.id_producto = c.id_producto
                LEFT JOIN inventario i
                  ON i.id_tienda = c.id_tienda AND i.id_producto = c.id_producto
                WHERE COALESCE(v.unidades_vendidas, 0) < CAST(:umbral_filtro AS INTEGER)
                ON CONFLICT (id_tienda, id_producto)
                    WHERE estado IN ('PENDIENTE', 'REVISADA')
                DO NOTHING
                RETURNING id_recomendacion, id_tienda, id_producto,
                          unidades_vendidas, umbral_unidades
            ),
            historiales AS (
                INSERT INTO public.recomendacion_producto_historial (
                    id_recomendacion, id_usuario, estado_anterior, estado_nuevo, comentario
                )
                SELECT
                    id_recomendacion,
                    NULL,
                    NULL,
                    'PENDIENTE',
                    'Generada automáticamente: ' || unidades_vendidas
                        || ' unidades en 30 días; umbral ' || umbral_unidades || '.'
                FROM insertadas
                RETURNING id_recomendacion
            ),
            auditorias AS (
                INSERT INTO public.bitacora_auditoria (
                    id_usuario, id_tienda, entidad, accion, valor_anterior,
                    valor_nuevo, resultado, ip_origen, fecha
                )
                SELECT
                    CAST(:id_usuario AS BIGINT),
                    id_tienda,
                    'RECOMENDACION_PRODUCTO',
                    'GENERACION',
                    NULL,
                    JSONB_BUILD_OBJECT(
                        'id_recomendacion', id_recomendacion,
                        'id_producto', id_producto,
                        'unidades_vendidas', unidades_vendidas,
                        'umbral_unidades', umbral_unidades,
                        'estado', 'PENDIENTE'
                    ),
                    'EXITOSO',
                    NULL,
                    NOW()
                FROM insertadas
                RETURNING id_evento
            )
            SELECT COUNT(*)::INTEGER AS creadas FROM insertadas
        ");
        $consulta->execute([
            ...$parametrosVentas,
            ':periodo_inicio' => $inicio,
            ':periodo_fin' => $hoy,
            ':umbral' => (int)$config['umbral_unidades'],
            ':umbral_filtro' => (int)$config['umbral_unidades'],
            ':id_usuario' => $idUsuario,
        ]);
        $creadas = (int)$consulta->fetchColumn();

        $pdo->commit();
        return [
            'creadas' => $creadas,
            'umbral_unidades' => (int)$config['umbral_unidades'],
            'periodo_inicio' => $inicio,
            'periodo_fin' => $hoy,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function cambiarEstadoRecomendacion(PDO $pdo, int $idUsuario, array $entrada): void
{
    $idRecomendacion = filter_var(
        $entrada['id_recomendacion'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $estadoNuevo = strtoupper(trim((string)($entrada['estado'] ?? '')));
    $comentario = trim((string)($entrada['comentario'] ?? ''));
    if ($idRecomendacion === false || !in_array($estadoNuevo, ['REVISADA', 'ACEPTADA', 'DESCARTADA'], true)) {
        responderRecomendaciones(false, null, 'La recomendación o el estado indicados no son válidos.', 400);
    }
    if (mb_strlen($comentario, 'UTF-8') > 1000) {
        responderRecomendaciones(false, null, 'El comentario no puede exceder 1000 caracteres.', 400);
    }
    if ($estadoNuevo === 'REVISADA' && $comentario === '') {
        responderRecomendaciones(false, null, 'Agregue una observación para registrar la revisión.', 400);
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            SELECT id_tienda, estado
            FROM public.recomendacion_producto
            WHERE id_recomendacion = :id
            FOR UPDATE
        ");
        $consulta->execute([':id' => $idRecomendacion]);
        $actual = $consulta->fetch();
        if (!$actual) {
            $pdo->rollBack();
            responderRecomendaciones(false, null, 'No se encontró la recomendación.', 404);
        }

        $estadoAnterior = (string)$actual['estado'];
        $transiciones = [
            'PENDIENTE' => ['REVISADA'],
            'REVISADA' => ['ACEPTADA', 'DESCARTADA'],
        ];
        if (!in_array($estadoNuevo, $transiciones[$estadoAnterior] ?? [], true)) {
            $pdo->rollBack();
            responderRecomendaciones(
                false,
                null,
                'La recomendación debe revisarse antes de aceptarla o descartarla, y los estados finales no se pueden cambiar.',
                409
            );
        }

        $actualizar = $pdo->prepare("
            UPDATE public.recomendacion_producto
            SET estado = :estado, fecha_actualizacion = NOW()
            WHERE id_recomendacion = :id
        ");
        $actualizar->execute([':estado' => $estadoNuevo, ':id' => $idRecomendacion]);

        $guardarHistorial = $pdo->prepare("
            INSERT INTO public.recomendacion_producto_historial (
                id_recomendacion, id_usuario, estado_anterior, estado_nuevo, comentario
            )
            VALUES (:id, :usuario, :anterior, :nuevo, :comentario)
        ");
        $guardarHistorial->execute([
            ':id' => $idRecomendacion,
            ':usuario' => $idUsuario,
            ':anterior' => $estadoAnterior,
            ':nuevo' => $estadoNuevo,
            ':comentario' => $comentario !== '' ? $comentario : null,
        ]);
        auditarRecomendacion(
            $pdo,
            $idUsuario,
            (int)$actual['id_tienda'],
            'CAMBIO_ESTADO',
            ['estado' => $estadoAnterior],
            [
                'id_recomendacion' => (int)$idRecomendacion,
                'estado' => $estadoNuevo,
                'comentario' => $comentario !== '' ? $comentario : null,
            ]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$idUsuario = exigirAdministradorRecomendaciones();
$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

try {
    if ($metodo === 'GET') {
        responderRecomendaciones(true, [
            'configuracion' => leerConfiguracionRecomendaciones($pdo),
            'recomendaciones' => listarRecomendaciones($pdo),
        ]);
    }

    if ($metodo !== 'POST') {
        responderRecomendaciones(false, null, 'Método no permitido.', 405);
    }

    $entrada = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responderRecomendaciones(false, null, 'La solicitud debe contener JSON válido.', 400);
    }

    $accion = (string)($entrada['accion'] ?? '');
    if ($accion === 'generar') {
        $resultado = generarRecomendaciones($pdo, $idUsuario);
        responderRecomendaciones(true, $resultado, 'Análisis completado.');
    }

    if ($accion === 'umbral') {
        $umbral = filter_var(
            $entrada['umbral_unidades'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100000]]
        );
        if ($umbral === false) {
            responderRecomendaciones(false, null, 'El umbral debe ser un entero entre 1 y 100000.', 400);
        }

        $pdo->beginTransaction();
        try {
            $consulta = $pdo->prepare("
                SELECT umbral_unidades
                FROM public.recomendacion_producto_config
                WHERE id_configuracion = 1
                FOR UPDATE
            ");
            $consulta->execute();
            $anterior = (int)$consulta->fetchColumn();
            if ($anterior !== $umbral) {
                $actualizar = $pdo->prepare("
                    UPDATE public.recomendacion_producto_config
                    SET umbral_unidades = :umbral,
                        id_usuario_actualizacion = :usuario,
                        fecha_actualizacion = NOW()
                    WHERE id_configuracion = 1
                ");
                $actualizar->execute([':umbral' => $umbral, ':usuario' => $idUsuario]);
                auditarRecomendacion(
                    $pdo,
                    $idUsuario,
                    null,
                    'CAMBIO_UMBRAL',
                    ['umbral_unidades' => $anterior],
                    ['umbral_unidades' => $umbral]
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        responderRecomendaciones(true, ['umbral_unidades' => $umbral], 'Umbral guardado.');
    }

    if ($accion === 'estado') {
        cambiarEstadoRecomendacion($pdo, $idUsuario, $entrada);
        responderRecomendaciones(true, null, 'Estado de la recomendación actualizado.');
    }

    responderRecomendaciones(false, null, 'La acción solicitada no es válida.', 400);
} catch (Throwable $e) {
    error_log('[recomendaciones] ' . $e->getMessage());
    responderRecomendaciones(false, null, 'No fue posible procesar las recomendaciones. Revise que la migración esté aplicada.', 500);
}
