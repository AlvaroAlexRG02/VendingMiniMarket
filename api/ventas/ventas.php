<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';

function respuestaVenta(bool $success, $data = null, string $message = '', int $status = 200): void
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

function exigirSesionVenta(): array
{
    if (empty($_SESSION['usuario'])) {
        respuestaVenta(false, null, 'Debe iniciar sesión.', 401);
    }

    return $_SESSION['usuario'];
}

function exigirPuedeVender(): array
{
    $usuario = exigirSesionVenta();

    if (!esAdministrador() && !esEncargado() && !esDependiente()) {
        respuestaVenta(false, null, 'No tiene permisos para registrar ventas.', 403);
    }

    return $usuario;
}

function entradaVenta(): array
{
    $entrada = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($entrada) ? $entrada : [];
}

function obtenerTiendaVenta(PDO $pdo, int $idTienda): array
{
    $st = $pdo->prepare("
        SELECT id_tienda, nombre
        FROM tienda
        WHERE id_tienda = :id AND estado = TRUE
    ");
    $st->execute([':id' => $idTienda]);
    $tienda = $st->fetch();

    if (!$tienda) {
        respuestaVenta(false, null, 'La tienda seleccionada no existe o está inactiva.', 404);
    }

    exigirOperarEnUbicacion($pdo, $idTienda);

    return $tienda;
}

function ubicacionPredeterminadaVenta(PDO $pdo, int $idTienda): ?array
{
    $st = $pdo->prepare("
        SELECT id_ubicacion, nombre
        FROM ubicacion_inventario
        WHERE id_tienda = :tienda
          AND tipo = 'TIENDA'
          AND estado = TRUE
        ORDER BY LOWER(nombre), id_ubicacion
        LIMIT 1
    ");
    $st->execute([':tienda' => $idTienda]);
    $ubicacion = $st->fetch();

    return $ubicacion ?: null;
}

function tiendasOperablesVenta(PDO $pdo, array $usuario): array
{
    if (in_array((int)($usuario['id_rol'] ?? 0), [1, 2], true)) {
        return $pdo->query("
            SELECT id_tienda, nombre
            FROM tienda
            WHERE estado = TRUE
            ORDER BY nombre
        ")->fetchAll();
    }

    $st = $pdo->prepare("
        SELECT t.id_tienda, t.nombre
        FROM tienda t
        JOIN usuario_tienda ut ON ut.id_tienda = t.id_tienda
        WHERE ut.id_usuario = :usuario
          AND ut.estado = TRUE
          AND t.estado = TRUE
        ORDER BY t.nombre
    ");
    $st->execute([':usuario' => (int)$usuario['id_usuario']]);

    return $st->fetchAll();
}

function accionTiendasVenta(PDO $pdo): void
{
    $usuario = exigirSesionVenta();
    respuestaVenta(true, tiendasOperablesVenta($pdo, $usuario));
}

function accionProductosVenta(PDO $pdo): void
{
    $usuario = exigirPuedeVender();
    $idTienda = (int)($_GET['id_tienda'] ?? 0);

    if ($idTienda <= 0) {
        respuestaVenta(false, null, 'Seleccione una tienda válida.', 400);
    }

    obtenerTiendaVenta($pdo, $idTienda);
    $ubicacion = ubicacionPredeterminadaVenta($pdo, $idTienda);

    if (!$ubicacion) {
        respuestaVenta(false, null, 'La tienda no tiene una ubicación de inventario activa.', 409);
    }

    $st = $pdo->prepare("
        SELECT
            p.id_producto,
            p.nombre,
            p.codigo_barras,
            p.codigo_articulo_retail,
            COALESCE(e.cantidad, 0) AS stock,
            pt.precio_venta,
            i.porcentaje AS impuesto_porcentaje
        FROM producto p
        JOIN impuesto i ON i.id_impuesto = p.id_impuesto
        JOIN LATERAL (
            SELECT precio_venta
            FROM precio_tienda
            WHERE id_producto = p.id_producto
              AND id_tienda = :tienda
              AND estado = TRUE
              AND fecha_fin IS NULL
            ORDER BY fecha_inicio DESC, id_precio DESC
            LIMIT 1
        ) pt ON TRUE
        LEFT JOIN existencia e
          ON e.id_producto = p.id_producto
         AND e.id_ubicacion = :ubicacion
        WHERE p.estado = TRUE
        ORDER BY LOWER(p.nombre), p.id_producto
    ");
    $st->execute([
        ':tienda' => $idTienda,
        ':ubicacion' => (int)$ubicacion['id_ubicacion'],
    ]);

    $productos = array_map(static function (array $fila): array {
        return [
            'id_producto' => (int)$fila['id_producto'],
            'nombre' => $fila['nombre'],
            'codigo_barras' => $fila['codigo_barras'],
            'codigo_articulo_retail' => $fila['codigo_articulo_retail'],
            'stock' => (int)$fila['stock'],
            'precio_venta' => (float)$fila['precio_venta'],
            'impuesto_porcentaje' => (float)$fila['impuesto_porcentaje'],
        ];
    }, $st->fetchAll());

    respuestaVenta(true, [
        'ubicacion' => $ubicacion,
        'productos' => $productos,
    ]);
}

function accionListarVentas(PDO $pdo): void
{
    $usuario = exigirSesionVenta();
    $esGerencia = in_array((int)($usuario['id_rol'] ?? 0), [1, 2], true);

    $sql = "
        SELECT
            v.id_venta,
            v.id_tienda,
            t.nombre AS tienda,
            v.id_usuario,
            TRIM(CONCAT_WS(' ', u.nombre, u.apellido)) AS usuario,
            v.fecha,
            v.metodo_pago,
            v.referencia_sinpe,
            v.subtotal,
            v.impuesto,
            v.total,
            v.observaciones,
            COALESCE(
                json_agg(
                    json_build_object(
                        'id_producto', d.id_producto,
                        'nombre', p.nombre,
                        'cantidad', d.cantidad,
                        'precio_unitario', d.precio_unitario,
                        'porcentaje_impuesto', d.porcentaje_impuesto,
                        'subtotal', d.subtotal,
                        'impuesto', d.impuesto,
                        'total', d.total
                    )
                    ORDER BY p.nombre
                ) FILTER (WHERE d.id_detalle IS NOT NULL),
                '[]'::json
            ) AS productos
        FROM venta_manual v
        JOIN tienda t ON t.id_tienda = v.id_tienda
        JOIN usuario u ON u.id_usuario = v.id_usuario
        LEFT JOIN venta_manual_detalle d ON d.id_venta = v.id_venta
        LEFT JOIN producto p ON p.id_producto = d.id_producto
    ";

    if (!$esGerencia) {
        $sql .= "
            JOIN usuario_tienda ut
              ON ut.id_tienda = v.id_tienda
             AND ut.id_usuario = :usuario
             AND ut.estado = TRUE
        ";
    }

    $sql .= "
        GROUP BY v.id_venta, t.nombre, u.nombre, u.apellido
        ORDER BY v.fecha DESC, v.id_venta DESC
        LIMIT 500
    ";
    $st = $pdo->prepare($sql);
    $st->execute($esGerencia ? [] : [':usuario' => (int)$usuario['id_usuario']]);

    $ventas = [];
    foreach ($st->fetchAll() as $fila) {
        $productos = json_decode((string)$fila['productos'], true);
        $productos = is_array($productos) ? $productos : [];

        $ventas[] = [
            'id' => (int)$fila['id_venta'],
            'numero' => 'VM-' . str_pad((string)$fila['id_venta'], 6, '0', STR_PAD_LEFT),
            'id_tienda' => (int)$fila['id_tienda'],
            'tienda' => $fila['tienda'],
            'usuario' => trim((string)$fila['usuario']),
            'fecha' => $fila['fecha'],
            'origen' => 'Manual',
            'metodoPago' => $fila['metodo_pago'],
            'referenciaSinpe' => $fila['referencia_sinpe'] ?? '',
            'subtotal' => (float)$fila['subtotal'],
            'impuesto' => (float)$fila['impuesto'],
            'total' => (float)$fila['total'],
            'estado' => 'Confirmada',
            'observaciones' => $fila['observaciones'] ?? '',
            'productos' => $productos,
            'productoNombre' => implode(', ', array_column($productos, 'nombre')),
        ];
    }

    respuestaVenta(true, $ventas);
}

function accionRegistrarVenta(PDO $pdo): void
{
    $usuario = exigirPuedeVender();

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respuestaVenta(false, null, 'Método no permitido.', 405);
    }

    $entrada = entradaVenta();
    $idTienda = (int)($entrada['id_tienda'] ?? 0);
    $metodo = trim((string)($entrada['metodo_pago'] ?? ''));
    $referencia = trim((string)($entrada['referencia_sinpe'] ?? ''));
    $observaciones = trim((string)($entrada['observaciones'] ?? ''));
    $itemsEntrada = $entrada['items'] ?? null;

    if ($idTienda <= 0 || !in_array($metodo, ['Efectivo', 'SINPE'], true)) {
        respuestaVenta(false, null, 'Seleccione una tienda y un método de pago permitido.', 400);
    }

    if ($metodo === 'SINPE' && $referencia === '') {
        respuestaVenta(false, null, 'Ingrese la referencia del SINPE.', 400);
    }

    if (mb_strlen($referencia, 'UTF-8') > 60 || mb_strlen($observaciones, 'UTF-8') > 300) {
        respuestaVenta(false, null, 'La referencia o las observaciones exceden el límite permitido.', 400);
    }

    if (!is_array($itemsEntrada) || count($itemsEntrada) < 1 || count($itemsEntrada) > 100) {
        respuestaVenta(false, null, 'Agregue al menos un producto a la venta.', 400);
    }

    $items = [];
    foreach ($itemsEntrada as $item) {
        if (!is_array($item)) {
            respuestaVenta(false, null, 'El detalle de productos no es válido.', 400);
        }

        $idProducto = (int)($item['id_producto'] ?? 0);
        $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);

        if ($idProducto <= 0 || $cantidad === false || $cantidad <= 0) {
            respuestaVenta(false, null, 'Cada producto debe tener una cantidad entera mayor que cero.', 400);
        }

        $items[$idProducto] = ($items[$idProducto] ?? 0) + $cantidad;
    }

    $tienda = obtenerTiendaVenta($pdo, $idTienda);
    $ubicacion = ubicacionPredeterminadaVenta($pdo, $idTienda);

    if (!$ubicacion) {
        respuestaVenta(false, null, 'La tienda no tiene una ubicación de inventario activa.', 409);
    }

    try {
        $pdo->beginTransaction();
        $detalleVenta = [];
        $subtotalVenta = 0.0;
        $impuestoVenta = 0.0;
        $totalVenta = 0.0;

        $productoSt = $pdo->prepare("
            SELECT
                p.id_producto,
                p.nombre,
                p.estado,
                i.porcentaje AS impuesto_porcentaje,
                pt.precio_venta
            FROM producto p
            JOIN impuesto i ON i.id_impuesto = p.id_impuesto
            JOIN LATERAL (
                SELECT precio_venta
                FROM precio_tienda
                WHERE id_producto = p.id_producto
                  AND id_tienda = :tienda
                  AND estado = TRUE
                  AND fecha_fin IS NULL
                ORDER BY fecha_inicio DESC, id_precio DESC
                LIMIT 1
            ) pt ON TRUE
            WHERE p.id_producto = :producto
              AND p.estado = TRUE
            FOR SHARE OF p
        ");
        $existenciaSt = $pdo->prepare("
            SELECT id_existencia, cantidad
            FROM existencia
            WHERE id_ubicacion = :ubicacion
              AND id_producto = :producto
            FOR UPDATE
        ");

        foreach ($items as $idProducto => $cantidad) {
            $productoSt->execute([':tienda' => $idTienda, ':producto' => $idProducto]);
            $producto = $productoSt->fetch();

            if (!$producto) {
                throw new DomainException("Uno de los productos no está activo o no tiene precio para esta tienda.");
            }

            $existenciaSt->execute([
                ':ubicacion' => (int)$ubicacion['id_ubicacion'],
                ':producto' => $idProducto,
            ]);
            $existencia = $existenciaSt->fetch();
            $disponible = $existencia ? (int)$existencia['cantidad'] : 0;

            if ($cantidad > $disponible) {
                throw new DomainException(
                    "Existencias insuficientes para {$producto['nombre']}. Disponible: $disponible."
                );
            }

            $precioBase = (float)$producto['precio_venta'];
            $porcentajeImpuesto = (float)$producto['impuesto_porcentaje'];
            $base = round($precioBase * $cantidad, 2);
            $impuesto = round($base * $porcentajeImpuesto / 100, 2);
            $total = $base + $impuesto;

            $detalleVenta[] = [
                'id_producto' => $idProducto,
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioBase,
                'porcentaje_impuesto' => $porcentajeImpuesto,
                'subtotal' => $base,
                'impuesto' => $impuesto,
                'total' => $total,
                'id_existencia' => $existencia ? (int)$existencia['id_existencia'] : 0,
                'stock_anterior' => $disponible,
            ];

            $subtotalVenta += $base;
            $impuestoVenta += $impuesto;
            $totalVenta += $total;
        }

        $subtotalVenta = round($subtotalVenta, 2);
        $impuestoVenta = round($impuestoVenta, 2);
        $totalVenta = round($totalVenta, 2);

        $insertVenta = $pdo->prepare("
            INSERT INTO venta_manual
                (id_tienda, id_ubicacion, id_usuario, metodo_pago, referencia_sinpe,
                 subtotal, impuesto, total, observaciones)
            VALUES
                (:tienda, :ubicacion, :usuario, :metodo, :referencia,
                 :subtotal, :impuesto, :total, :observaciones)
            RETURNING id_venta, fecha
        ");
        $insertVenta->execute([
            ':tienda' => $idTienda,
            ':ubicacion' => (int)$ubicacion['id_ubicacion'],
            ':usuario' => (int)$usuario['id_usuario'],
            ':metodo' => $metodo,
            ':referencia' => $metodo === 'SINPE' ? $referencia : null,
            ':subtotal' => $subtotalVenta,
            ':impuesto' => $impuestoVenta,
            ':total' => $totalVenta,
            ':observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);
        $ventaInsertada = $insertVenta->fetch();
        $idVenta = (int)$ventaInsertada['id_venta'];

        $insertDetalle = $pdo->prepare("
            INSERT INTO venta_manual_detalle
                (id_venta, id_producto, cantidad, precio_unitario, porcentaje_impuesto,
                 subtotal, impuesto, total)
            VALUES
                (:venta, :producto, :cantidad, :precio, :porcentaje, :subtotal, :impuesto, :total)
        ");
        $actualizarExistencia = $pdo->prepare("
            UPDATE existencia
            SET cantidad = cantidad - :cantidad,
                fecha_actualizacion = NOW()
            WHERE id_existencia = :id
              AND cantidad >= :cantidad_validacion
        ");

        foreach ($detalleVenta as $detalle) {
            $insertDetalle->execute([
                ':venta' => $idVenta,
                ':producto' => $detalle['id_producto'],
                ':cantidad' => $detalle['cantidad'],
                ':precio' => $detalle['precio_unitario'],
                ':porcentaje' => $detalle['porcentaje_impuesto'],
                ':subtotal' => $detalle['subtotal'],
                ':impuesto' => $detalle['impuesto'],
                ':total' => $detalle['total'],
            ]);

            $actualizarExistencia->execute([
                ':cantidad' => $detalle['cantidad'],
                ':id' => $detalle['id_existencia'],
                ':cantidad_validacion' => $detalle['cantidad'],
            ]);

            if ($actualizarExistencia->rowCount() !== 1) {
                throw new DomainException("El inventario cambió mientras se registraba la venta. Vuelva a intentarlo.");
            }
        }

        registrarOperacion($pdo, 'VENTA_MANUAL', 'REGISTRAR', $idTienda, null, [
            'id_venta' => $idVenta,
            'id_ubicacion' => (int)$ubicacion['id_ubicacion'],
            'metodo_pago' => $metodo,
            'subtotal' => $subtotalVenta,
            'impuesto' => $impuestoVenta,
            'total' => $totalVenta,
            'productos' => array_map(static function (array $detalle): array {
                return [
                    'id_producto' => $detalle['id_producto'],
                    'cantidad' => $detalle['cantidad'],
                    'stock_anterior' => $detalle['stock_anterior'],
                    'stock_resultante' => $detalle['stock_anterior'] - $detalle['cantidad'],
                ];
            }, $detalleVenta),
        ]);

        $pdo->commit();
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        respuestaVenta(false, null, $e->getMessage(), 409);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[ventas] ' . $e->getMessage());
        respuestaVenta(false, null, 'No fue posible registrar la venta. Intente nuevamente.', 500);
    }

    respuestaVenta(true, [
        'id' => $idVenta,
        'numero' => 'VM-' . str_pad((string)$idVenta, 6, '0', STR_PAD_LEFT),
        'id_tienda' => $idTienda,
        'tienda' => $tienda['nombre'],
        'fecha' => $ventaInsertada['fecha'],
        'origen' => 'Manual',
        'metodoPago' => $metodo,
        'referenciaSinpe' => $metodo === 'SINPE' ? $referencia : '',
        'subtotal' => $subtotalVenta,
        'impuesto' => $impuestoVenta,
        'total' => $totalVenta,
        'estado' => 'Confirmada',
        'usuario' => trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? '')),
        'observaciones' => $observaciones,
        'productos' => $detalleVenta,
        'productoNombre' => implode(', ', array_column($detalleVenta, 'nombre')),
    ], 'Venta registrada correctamente.');
}

$pdo = $pdo ?? null;
if (!$pdo instanceof PDO) {
    respuestaVenta(false, null, 'No fue posible conectar con la base de datos.', 500);
}

$accion = (string)($_GET['accion'] ?? '');

try {
    switch ($accion) {
        case 'tiendas':
            accionTiendasVenta($pdo);
            break;
        case 'productos':
            accionProductosVenta($pdo);
            break;
        case 'listar':
            accionListarVentas($pdo);
            break;
        case 'registrar':
            accionRegistrarVenta($pdo);
            break;
        default:
            respuestaVenta(false, null, 'Acción no reconocida.', 404);
    }
} catch (Throwable $e) {
    error_log('[ventas] ' . $e->getMessage());
    respuestaVenta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
