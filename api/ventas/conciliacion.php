<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';

function respuestaConciliacion(bool $success, $data = null, string $message = '', int $status = 200): void
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

function usuarioConciliacion(): array
{
    if (empty($_SESSION['usuario'])) {
        respuestaConciliacion(false, null, 'Debe iniciar sesión.', 401);
    }

    $usuario = $_SESSION['usuario'];
    if (!esAdministradorOGerente()) {
        respuestaConciliacion(false, null, 'Solo Administración o Gerencia pueden conciliar ventas.', 403);
    }

    return $usuario;
}

function entradaConciliacion(): array
{
    $entrada = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($entrada) ? $entrada : [];
}

function exigirPostConciliacion(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respuestaConciliacion(false, null, 'Método no permitido.', 405);
    }
}

function tiendasConciliacion(PDO $pdo): void
{
    usuarioConciliacion();
    $tiendas = $pdo->query("
        SELECT id_tienda, nombre
        FROM tienda
        WHERE estado = TRUE
        ORDER BY nombre
    ")->fetchAll();

    respuestaConciliacion(true, $tiendas);
}

function validarPeriodoConciliacion(array $entrada): array
{
    $inicio = trim((string)($entrada['fecha_inicio'] ?? ''));
    $fin = trim((string)($entrada['fecha_fin'] ?? ''));

    $fechaValida = static function (string $fecha): bool {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    };

    if (!$fechaValida($inicio) || !$fechaValida($fin) || $inicio > $fin) {
        respuestaConciliacion(false, null, 'Indique un periodo válido: la fecha inicial debe ser anterior o igual a la final.', 400);
    }

    return [$inicio, $fin];
}

function consultaConciliacion(PDO $pdo, string $inicio, string $fin, int $idTienda): array
{
    $sql = "
        WITH ventas AS (
            SELECT
                v.id_tienda,
                v.id_ubicacion,
                d.id_producto,
                SUM(d.cantidad)::INTEGER AS cantidad_vendida,
                SUM(d.subtotal - COALESCE(d.costo_unitario * d.cantidad, 0)) AS utilidad_estimada,
                COUNT(*) FILTER (WHERE d.costo_unitario IS NULL)::INTEGER AS lineas_sin_costo,
                SUM(md.cantidad) FILTER (
                    WHERE m.tipo_movimiento = 'SALIDA' AND m.estado = 'CONFIRMADO'
                )::INTEGER
                    AS cantidad_movimiento
            FROM venta_manual v
            JOIN venta_manual_detalle d ON d.id_venta = v.id_venta
            LEFT JOIN movimiento_inventario m ON m.id_movimiento = v.id_movimiento
            LEFT JOIN detalle_movimiento md
              ON md.id_movimiento = m.id_movimiento
             AND md.id_producto = d.id_producto
            WHERE v.fecha >= CAST(:inicio AS DATE)
              AND v.fecha < CAST(:fin AS DATE) + INTERVAL '1 day'
              AND (:tienda_filtro = 0 OR v.id_tienda = :tienda_venta)
            GROUP BY v.id_tienda, v.id_ubicacion, d.id_producto
        ),
        salidas_sin_venta AS (
            SELECT
                u.id_tienda,
                u.id_ubicacion,
                d.id_producto,
                SUM(d.cantidad)::INTEGER AS cantidad_movimiento
            FROM movimiento_inventario m
            JOIN detalle_movimiento d ON d.id_movimiento = m.id_movimiento
            JOIN ubicacion_inventario u ON u.id_ubicacion = m.id_ubicacion_origen
            WHERE m.tipo_movimiento = 'SALIDA'
              AND m.estado = 'CONFIRMADO'
              AND m.motivo = 'Venta manual'
              AND m.fecha_confirmacion >= CAST(:inicio_salida AS DATE)
              AND m.fecha_confirmacion < CAST(:fin_salida AS DATE) + INTERVAL '1 day'
              AND (:tienda_salida_filtro = 0 OR u.id_tienda = :tienda_salida)
              AND NOT EXISTS (
                  SELECT 1
                  FROM venta_manual v
                  WHERE v.id_movimiento = m.id_movimiento
              )
            GROUP BY u.id_tienda, u.id_ubicacion, d.id_producto
        ),
        conciliados AS (
            SELECT
                id_tienda, id_ubicacion, id_producto,
                cantidad_vendida, COALESCE(cantidad_movimiento, 0) AS cantidad_movimiento,
                utilidad_estimada, lineas_sin_costo
            FROM ventas
            UNION ALL
            SELECT
                id_tienda, id_ubicacion, id_producto,
                0 AS cantidad_vendida, cantidad_movimiento,
                NULL::NUMERIC AS utilidad_estimada, 0 AS lineas_sin_costo
            FROM salidas_sin_venta
        ),
        saldos_movimiento AS (
            SELECT
                m.id_ubicacion_destino AS id_ubicacion,
                d.id_producto,
                d.saldo_destino_resultante::INTEGER AS saldo,
                m.fecha_confirmacion,
                m.id_movimiento
            FROM movimiento_inventario m
            JOIN detalle_movimiento d ON d.id_movimiento = m.id_movimiento
            WHERE m.estado = 'CONFIRMADO'
              AND m.fecha_confirmacion IS NOT NULL
              AND m.id_ubicacion_destino IS NOT NULL
            UNION ALL
            SELECT
                m.id_ubicacion_origen AS id_ubicacion,
                d.id_producto,
                d.saldo_origen_resultante::INTEGER AS saldo,
                m.fecha_confirmacion,
                m.id_movimiento
            FROM movimiento_inventario m
            JOIN detalle_movimiento d ON d.id_movimiento = m.id_movimiento
            WHERE m.estado = 'CONFIRMADO'
              AND m.fecha_confirmacion IS NOT NULL
              AND m.id_ubicacion_origen IS NOT NULL
        ),
        saldos AS (
            SELECT DISTINCT ON (id_ubicacion, id_producto)
                id_ubicacion, id_producto, saldo
            FROM saldos_movimiento
            ORDER BY id_ubicacion, id_producto, fecha_confirmacion DESC, id_movimiento DESC
        )
        SELECT
            c.id_tienda,
            t.nombre AS tienda,
            c.id_ubicacion,
            u.nombre AS ubicacion,
            c.id_producto,
            p.nombre AS producto,
            c.cantidad_vendida,
            c.cantidad_movimiento,
            c.cantidad_vendida - c.cantidad_movimiento AS diferencia_venta_movimiento,
            COALESCE(e.cantidad, 0)::INTEGER AS existencia_actual,
            s.saldo AS saldo_ultimo_movimiento,
            CASE
                WHEN s.saldo IS NULL THEN NULL
                ELSE COALESCE(e.cantidad, 0)::INTEGER - s.saldo
            END AS diferencia_existencia,
            c.utilidad_estimada,
            c.lineas_sin_costo
        FROM conciliados c
        JOIN tienda t ON t.id_tienda = c.id_tienda
        JOIN ubicacion_inventario u ON u.id_ubicacion = c.id_ubicacion
        JOIN producto p ON p.id_producto = c.id_producto
        LEFT JOIN existencia e
          ON e.id_ubicacion = c.id_ubicacion
         AND e.id_producto = c.id_producto
        LEFT JOIN saldos s
          ON s.id_ubicacion = c.id_ubicacion
         AND s.id_producto = c.id_producto
        ORDER BY t.nombre, u.nombre, p.nombre
    ";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':inicio' => $inicio,
        ':fin' => $fin,
        ':inicio_salida' => $inicio,
        ':fin_salida' => $fin,
        ':tienda_filtro' => $idTienda,
        ':tienda_venta' => $idTienda,
        ':tienda_salida_filtro' => $idTienda,
        ':tienda_salida' => $idTienda,
    ]);

    return array_map(static function (array $fila): array {
        foreach (['id_tienda', 'id_ubicacion', 'id_producto', 'cantidad_vendida', 'cantidad_movimiento', 'diferencia_venta_movimiento', 'existencia_actual', 'lineas_sin_costo'] as $campo) {
            $fila[$campo] = (int)$fila[$campo];
        }
        foreach (['saldo_ultimo_movimiento', 'diferencia_existencia'] as $campo) {
            $fila[$campo] = $fila[$campo] === null ? null : (int)$fila[$campo];
        }
        $fila['utilidad_estimada'] = $fila['utilidad_estimada'] === null
            ? null
            : (float)$fila['utilidad_estimada'];
        $fila['id_detalle'] = null;
        $fila['observacion'] = '';
        $fila['ajustes'] = [];
        return $fila;
    }, $st->fetchAll());
}

function accionGenerarConciliacion(PDO $pdo): void
{
    $usuario = usuarioConciliacion();
    exigirPostConciliacion();
    $entrada = entradaConciliacion();
    [$inicio, $fin] = validarPeriodoConciliacion($entrada);
    $idTienda = (int)($entrada['id_tienda'] ?? 0);

    if ($idTienda < 0) {
        respuestaConciliacion(false, null, 'La tienda seleccionada no es válida.', 400);
    }
    if ($idTienda > 0) {
        exigirOperarEnUbicacion($pdo, $idTienda);
    }

    try {
        $pdo->beginTransaction();
        $resultados = consultaConciliacion($pdo, $inicio, $fin, $idTienda);

        $insertar = $pdo->prepare("
            INSERT INTO conciliacion_venta
                (fecha_inicio, fecha_fin, id_tienda, id_usuario)
            VALUES
                (:inicio, :fin, :tienda, :usuario)
            RETURNING id_conciliacion
        ");
        $insertar->execute([
            ':inicio' => $inicio,
            ':fin' => $fin,
            ':tienda' => $idTienda ?: null,
            ':usuario' => (int)$usuario['id_usuario'],
        ]);
        $idConciliacion = (int)$insertar->fetchColumn();

        $detalle = $pdo->prepare("
            INSERT INTO conciliacion_venta_detalle
                (id_conciliacion, id_tienda, id_ubicacion, id_producto,
                 cantidad_vendida, cantidad_movimiento, diferencia_venta_movimiento,
                 existencia_actual, saldo_ultimo_movimiento, diferencia_existencia,
                 utilidad_estimada, lineas_sin_costo)
            VALUES
                (:conciliacion, :tienda, :ubicacion, :producto,
                 :vendida, :movimiento, :diferencia_movimiento,
                 :existencia, :saldo_movimiento, :diferencia_existencia,
                 :utilidad, :sin_costo)
            RETURNING id_detalle
        ");

        foreach ($resultados as &$fila) {
            $detalle->execute([
                ':conciliacion' => $idConciliacion,
                ':tienda' => $fila['id_tienda'],
                ':ubicacion' => $fila['id_ubicacion'],
                ':producto' => $fila['id_producto'],
                ':vendida' => $fila['cantidad_vendida'],
                ':movimiento' => $fila['cantidad_movimiento'],
                ':diferencia_movimiento' => $fila['diferencia_venta_movimiento'],
                ':existencia' => $fila['existencia_actual'],
                ':saldo_movimiento' => $fila['saldo_ultimo_movimiento'],
                ':diferencia_existencia' => $fila['diferencia_existencia'],
                ':utilidad' => $fila['utilidad_estimada'],
                ':sin_costo' => $fila['lineas_sin_costo'],
            ]);
            $fila['id_detalle'] = (int)$detalle->fetchColumn();
        }
        unset($fila);

        foreach (array_unique(array_column($resultados, 'id_tienda')) as $tiendaAfectada) {
            registrarOperacion($pdo, 'CONCILIACION_VENTA', 'GENERAR', (int)$tiendaAfectada, null, [
                'id_conciliacion' => $idConciliacion,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'productos' => count($resultados),
            ]);
        }

        $pdo->commit();
        respuestaConciliacion(true, [
            'id_conciliacion' => $idConciliacion,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'id_tienda' => $idTienda ?: null,
            'fuente' => 'Ventas manuales',
            'resultados' => $resultados,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[conciliacion-ventas] ' . $e->getMessage());
        if ($e instanceof PDOException && in_array((string)$e->getCode(), ['42P01', '42703'], true)) {
            respuestaConciliacion(
                false,
                null,
                'Falta actualizar el esquema de ventas. Ejecuta Ventas.sql y luego Conciliacion.sql en la base de datos.',
                500
            );
        }
        respuestaConciliacion(false, null, 'No fue posible generar la conciliación. Verifique que las tablas de HU-43 y del inventario estén creadas.', 500);
    }
}

function obtenerDetalleConciliacion(PDO $pdo, int $idDetalle): array
{
    $st = $pdo->prepare("
        SELECT d.*, c.id_tienda AS tienda_filtro
        FROM conciliacion_venta_detalle d
        JOIN conciliacion_venta c ON c.id_conciliacion = d.id_conciliacion
        WHERE d.id_detalle = :id
        FOR UPDATE OF d
    ");
    $st->execute([':id' => $idDetalle]);
    $detalle = $st->fetch();
    if (!$detalle) {
        throw new DomainException('El producto no pertenece a una conciliación registrada.');
    }
    return $detalle;
}

function accionGuardarObservacion(PDO $pdo): void
{
    usuarioConciliacion();
    exigirPostConciliacion();
    $entrada = entradaConciliacion();
    $idDetalle = (int)($entrada['id_detalle'] ?? 0);
    $observacion = trim((string)($entrada['observacion'] ?? ''));

    if ($idDetalle <= 0 || $observacion === '' || mb_strlen($observacion, 'UTF-8') > 500) {
        respuestaConciliacion(false, null, 'La observación es obligatoria y no puede superar 500 caracteres.', 400);
    }

    try {
        $pdo->beginTransaction();
        $detalle = obtenerDetalleConciliacion($pdo, $idDetalle);
        $pdo->prepare("
            UPDATE conciliacion_venta_detalle
            SET observacion = :observacion
            WHERE id_detalle = :id
        ")->execute([':observacion' => $observacion, ':id' => $idDetalle]);

        registrarOperacion($pdo, 'CONCILIACION_VENTA', 'OBSERVACION', (int)$detalle['id_tienda'], null, [
            'id_detalle' => $idDetalle,
            'observacion' => $observacion,
        ]);
        $pdo->commit();
        respuestaConciliacion(true, ['observacion' => $observacion], 'Observación guardada.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof DomainException) {
            respuestaConciliacion(false, null, $e->getMessage(), 404);
        }
        error_log('[conciliacion-ventas] ' . $e->getMessage());
        respuestaConciliacion(false, null, 'No fue posible guardar la observación.', 500);
    }
}

function accionAjustarInventario(PDO $pdo): void
{
    $usuario = usuarioConciliacion();
    exigirPostConciliacion();
    $entrada = entradaConciliacion();
    $idDetalle = (int)($entrada['id_detalle'] ?? 0);
    $cantidadAjuste = filter_var($entrada['cantidad_ajuste'] ?? null, FILTER_VALIDATE_INT);
    $observacion = trim((string)($entrada['observacion'] ?? ''));

    if ($idDetalle <= 0 || $cantidadAjuste === false || $cantidadAjuste === 0) {
        respuestaConciliacion(false, null, 'Indique un ajuste entero distinto de cero.', 400);
    }
    if ($observacion === '' || mb_strlen($observacion, 'UTF-8') > 500) {
        respuestaConciliacion(false, null, 'Justifique el ajuste con una observación de hasta 500 caracteres.', 400);
    }

    try {
        $pdo->beginTransaction();
        $detalle = obtenerDetalleConciliacion($pdo, $idDetalle);
        $stock = $pdo->prepare("
            SELECT id_existencia, cantidad
            FROM existencia
            WHERE id_ubicacion = :ubicacion AND id_producto = :producto
            FOR UPDATE
        ");
        $stock->execute([
            ':ubicacion' => (int)$detalle['id_ubicacion'],
            ':producto' => (int)$detalle['id_producto'],
        ]);
        $existencia = $stock->fetch();
        $antes = $existencia ? (int)$existencia['cantidad'] : 0;
        $despues = $antes + $cantidadAjuste;

        if ($despues < 0) {
            throw new DomainException('El ajuste no puede dejar existencias negativas.');
        }

        $esEntrada = $cantidadAjuste > 0;
        $ubicacionCampo = $esEntrada ? 'id_ubicacion_destino' : 'id_ubicacion_origen';
        $stMovimiento = $pdo->prepare("
            INSERT INTO movimiento_inventario
                (id_usuario, $ubicacionCampo, tipo_movimiento, motivo, observaciones,
                 estado, id_usuario_autoriza, fecha_confirmacion, fecha_autorizacion)
            VALUES
                (:usuario, :ubicacion, :tipo, 'Ajuste de conciliación', :observaciones,
                 'CONFIRMADO', :autorizador, NOW(), NOW())
            RETURNING id_movimiento
        ");
        $stMovimiento->execute([
            ':usuario' => (int)$usuario['id_usuario'],
            ':ubicacion' => (int)$detalle['id_ubicacion'],
            ':tipo' => $esEntrada ? 'ENTRADA' : 'SALIDA',
            ':observaciones' => $observacion,
            ':autorizador' => (int)$usuario['id_usuario'],
        ]);
        $idMovimiento = (int)$stMovimiento->fetchColumn();

        $stDetalle = $pdo->prepare("
            INSERT INTO detalle_movimiento
                (id_movimiento, id_producto, cantidad,
                 saldo_destino_anterior, saldo_destino_resultante,
                 saldo_origen_anterior, saldo_origen_resultante)
            VALUES
                (:movimiento, :producto, :cantidad,
                 :destino_anterior, :destino_resultante,
                 :origen_anterior, :origen_resultante)
        ");
        $stDetalle->execute([
            ':movimiento' => $idMovimiento,
            ':producto' => (int)$detalle['id_producto'],
            ':cantidad' => abs($cantidadAjuste),
            ':destino_anterior' => $esEntrada ? $antes : null,
            ':destino_resultante' => $esEntrada ? $despues : null,
            ':origen_anterior' => $esEntrada ? null : $antes,
            ':origen_resultante' => $esEntrada ? null : $despues,
        ]);

        if ($existencia) {
            $pdo->prepare("
                UPDATE existencia
                SET cantidad = :cantidad, fecha_actualizacion = NOW()
                WHERE id_existencia = :id
            ")->execute([':cantidad' => $despues, ':id' => $existencia['id_existencia']]);
        } elseif ($esEntrada) {
            $pdo->prepare("
                INSERT INTO existencia (id_ubicacion, id_producto, cantidad, stock_minimo)
                VALUES (:ubicacion, :producto, :cantidad, 0)
            ")->execute([
                ':ubicacion' => (int)$detalle['id_ubicacion'],
                ':producto' => (int)$detalle['id_producto'],
                ':cantidad' => $despues,
            ]);
        } else {
            throw new DomainException('No hay existencias para aplicar el ajuste de salida.');
        }

        $pdo->prepare("
            INSERT INTO ajuste_conciliacion_inventario
                (id_detalle, id_movimiento, id_usuario_autoriza,
                 cantidad_antes, cantidad_ajustada, cantidad_despues, observacion)
            VALUES
                (:detalle, :movimiento, :usuario, :antes, :ajuste, :despues, :observacion)
        ")->execute([
            ':detalle' => $idDetalle,
            ':movimiento' => $idMovimiento,
            ':usuario' => (int)$usuario['id_usuario'],
            ':antes' => $antes,
            ':ajuste' => $cantidadAjuste,
            ':despues' => $despues,
            ':observacion' => $observacion,
        ]);

        $pdo->prepare("
            UPDATE conciliacion_venta_detalle
            SET existencia_actual = :actual,
                diferencia_existencia = CASE
                    WHEN saldo_ultimo_movimiento IS NULL THEN NULL
                    ELSE :diferencia
                END,
                observacion = :observacion
            WHERE id_detalle = :id
        ")->execute([
            ':actual' => $despues,
            ':diferencia' => $despues - (int)($detalle['saldo_ultimo_movimiento'] ?? $despues),
            ':observacion' => $observacion,
            ':id' => $idDetalle,
        ]);

        registrarOperacion($pdo, 'CONCILIACION_VENTA', 'AJUSTAR_INVENTARIO', (int)$detalle['id_tienda'], [
            'existencia' => $antes,
        ], [
            'existencia' => $despues,
            'ajuste' => $cantidadAjuste,
            'id_movimiento' => $idMovimiento,
            'id_usuario_autoriza' => (int)$usuario['id_usuario'],
        ]);

        $pdo->commit();
        respuestaConciliacion(true, [
            'id_movimiento' => $idMovimiento,
            'cantidad_antes' => $antes,
            'cantidad_ajustada' => $cantidadAjuste,
            'cantidad_despues' => $despues,
        ], 'Ajuste autorizado y registrado en el inventario.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e instanceof DomainException) {
            respuestaConciliacion(false, null, $e->getMessage(), 409);
        }
        error_log('[conciliacion-ventas] ' . $e->getMessage());
        respuestaConciliacion(false, null, 'No fue posible aplicar el ajuste de inventario.', 500);
    }
}

try {
    if (!isset($pdo) || !$pdo instanceof PDO) {
        respuestaConciliacion(false, null, 'No fue posible conectar con la base de datos.', 500);
    }

    $accion = (string)($_GET['accion'] ?? '');
    switch ($accion) {
        case 'tiendas':
            tiendasConciliacion($pdo);
            break;
        case 'generar':
            accionGenerarConciliacion($pdo);
            break;
        case 'observacion':
            accionGuardarObservacion($pdo);
            break;
        case 'ajustar':
            accionAjustarInventario($pdo);
            break;
        default:
            respuestaConciliacion(false, null, 'Acción no reconocida.', 404);
    }
} catch (Throwable $e) {
    error_log('[conciliacion-ventas] ' . $e->getMessage());
    respuestaConciliacion(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
