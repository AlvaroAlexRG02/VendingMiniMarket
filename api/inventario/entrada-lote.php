<?php
/**
 * HU-21 — entrada directa de varios productos en un solo movimiento.
 * Orígenes permitidos: Proveedor / Devolución.
 * Los pedidos de compra se registran exclusivamente por api/compras/recepciones.php.
 * Este endpoint no reemplaza entrada.php (que sigue dando las listas GET).
 */
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';

function respuestaLote(bool $exito, $datos = null, string $mensaje = '', int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => $exito, 'data' => $datos, 'message' => $mensaje],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respuestaLote(false, null, 'Método no permitido.', 405);
}
$usuario = $_SESSION['usuario'] ?? null;
if (!is_array($usuario) || (int)($usuario['id_usuario'] ?? 0) <= 0) {
    respuestaLote(false, null, 'Debe iniciar sesión.', 401);
}
if (!esAdministrador() && !esGerenteGeneral() && !esEncargado() && !esDependiente()) {
    respuestaLote(false, null, 'No tiene permisos para registrar entradas.', 403);
}

try {
    $datos = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($datos)) throw new DomainException('La solicitud no es válida.');

    $idUbicacion = filter_var($datos['id_ubicacion'] ?? null, FILTER_VALIDATE_INT);
    if (!$idUbicacion || $idUbicacion <= 0) throw new DomainException('Seleccione una ubicación válida.');

    $origen = trim((string)($datos['origen'] ?? ''));
    if (!in_array($origen, ['Proveedor', 'Devolución'], true)) {
        throw new DomainException('El origen indicado no está permitido en entradas directas.');
    }

    $referencia = trim((string)($datos['referencia'] ?? ''));
    $observaciones = trim((string)($datos['observaciones'] ?? ''));
    if (mb_strlen($referencia, 'UTF-8') > 150 || mb_strlen($observaciones, 'UTF-8') > 500) {
        throw new DomainException('La referencia o las observaciones exceden la longitud permitida.');
    }
    if ($origen === 'Proveedor' && $referencia === '') {
        throw new DomainException('Debe indicar la referencia del proveedor.');
    }
    if ($origen === 'Devolución' && $observaciones === '') {
        throw new DomainException('Debe indicar el motivo de la devolución.');
    }

    $idProveedor = null;
    if ($origen === 'Proveedor') {
        $idProveedor = filter_var($datos['id_proveedor'] ?? null, FILTER_VALIDATE_INT);
        if (!$idProveedor || $idProveedor <= 0) throw new DomainException('Seleccione un proveedor válido.');
    }

    $items = $datos['items'] ?? null;
    if (!is_array($items) || !$items || count($items) > 200) {
        throw new DomainException('Debe agregar entre 1 y 200 productos.');
    }
    $cantidades = [];
    foreach ($items as $fila) {
        if (!is_array($fila)) throw new DomainException('Hay un producto con datos inválidos.');
        $id = filter_var($fila['id_producto'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($fila['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !$cantidad || $id <= 0 || $cantidad <= 0) {
            throw new DomainException('Todos los productos deben tener cantidad entera mayor que cero.');
        }
        if (isset($cantidades[$id])) throw new DomainException('No repita productos en una misma entrada.');
        $cantidades[$id] = $cantidad;
    }
    ksort($cantidades, SORT_NUMERIC);

    $consultaUbicacion = $pdo->prepare("SELECT ui.id_ubicacion, ui.id_tienda, ui.estado, ui.nombre
        FROM ubicacion_inventario ui WHERE ui.id_ubicacion = :id LIMIT 1");
    $consultaUbicacion->execute([':id' => $idUbicacion]);
    $ubicacion = $consultaUbicacion->fetch();
    if (!$ubicacion || !in_array($ubicacion['estado'], [true, 1, '1', 't', 'true'], true)) {
        throw new DomainException('La ubicación no existe o está inactiva.');
    }
    // Valida rol, tienda activa y asignación actualizada desde usuario_tienda.
    exigirOperarEnUbicacion($pdo, (int)$ubicacion['id_tienda']);

    $proveedorNombre = null;
    if ($idProveedor !== null) {
        $consultaProveedor = $pdo->prepare('SELECT nombre FROM proveedor WHERE id_proveedor = :id AND estado = TRUE');
        $consultaProveedor->execute([':id' => $idProveedor]);
        $proveedorNombre = $consultaProveedor->fetchColumn();
        if ($proveedorNombre === false) throw new DomainException('El proveedor no existe o está inactivo.');
    }

    $marcadores = implode(',', array_fill(0, count($cantidades), '?'));
    $st = $pdo->prepare("SELECT id_producto FROM producto WHERE estado = TRUE AND id_producto IN ($marcadores)");
    $st->execute(array_keys($cantidades));
    if (count($st->fetchAll()) !== count($cantidades)) {
        throw new DomainException('Uno de los productos no existe o está inactivo.');
    }

    if ($origen === 'Proveedor') {
        // No se confía únicamente en el filtro JS: todos los productos
        // deben estar asociados ACTIVAMENTE al proveedor de esta entrada.
        $verificarAsociados = $pdo->prepare("
            SELECT DISTINCT id_producto
            FROM producto_proveedor
            WHERE id_proveedor = ? AND estado = TRUE
              AND id_producto IN ($marcadores)
        ");
        $verificarAsociados->execute(array_merge([$idProveedor], array_keys($cantidades)));
        $idsAsociados = array_map('intval', $verificarAsociados->fetchAll(PDO::FETCH_COLUMN));
        if (count($idsAsociados) !== count($cantidades)) {
            throw new DomainException(
                'El proveedor elegido no está asociado a todos los productos de la entrada.'
            );
        }
    }

    $observacionFinal = [];
    if ($proveedorNombre !== null) {
        $observacionFinal[] = 'Proveedor: ' . $proveedorNombre . ' (#' . $idProveedor . ')';
    }
    if ($referencia !== '') $observacionFinal[] = 'Referencia: ' . $referencia;
    if ($observaciones !== '') $observacionFinal[] = $observaciones;
    $texto = implode(' | ', $observacionFinal);

    $pdo->beginTransaction();
    try {
        $insertarMovimiento = $pdo->prepare("
            INSERT INTO movimiento_inventario
                (id_usuario, id_ubicacion_origen, id_ubicacion_destino,
                 id_relacion_abastecimiento, id_usuario_autoriza,
                 tipo_movimiento, motivo, observaciones, evidencia_url,
                 estado, fecha_movimiento, fecha_confirmacion, id_pedido)
            VALUES (:usuario, NULL, :destino, NULL, NULL, 'ENTRADA', :origen,
                    :observaciones, NULL, 'CONFIRMADO', NOW(), NOW(), NULL)
            RETURNING id_movimiento
        ");
        $insertarMovimiento->execute([
            ':usuario' => (int)$usuario['id_usuario'], ':destino' => $idUbicacion,
            ':origen' => $origen, ':observaciones' => $texto ?: null,
        ]);
        $idMovimiento = (int)$insertarMovimiento->fetchColumn();
        if ($idMovimiento <= 0) throw new RuntimeException('No se generó el movimiento.');

        $consultarStock = $pdo->prepare('SELECT id_existencia, cantidad FROM existencia
            WHERE id_ubicacion = :ubicacion AND id_producto = :producto FOR UPDATE');
        $actualizarStock = $pdo->prepare('UPDATE existencia SET cantidad = :cantidad,
            fecha_actualizacion = NOW() WHERE id_existencia = :id');
        $crearStock = $pdo->prepare('INSERT INTO existencia
            (id_ubicacion, id_producto, cantidad, stock_minimo, fecha_actualizacion)
            VALUES (:ubicacion, :producto, :cantidad, 0, NOW())');
        $insertarDetalle = $pdo->prepare('INSERT INTO detalle_movimiento
            (id_movimiento, id_producto, cantidad, saldo_origen_anterior,
             saldo_origen_resultante, saldo_destino_anterior, saldo_destino_resultante)
            VALUES (:movimiento, :producto, :cantidad, NULL, NULL, :antes, :despues)');

        foreach ($cantidades as $idProducto => $cantidad) {
            // Serializa registros de este endpoint por ubicación/producto.
            $bloqueo = $pdo->prepare('SELECT pg_advisory_xact_lock(CAST(:ubi AS integer), CAST(:prod AS integer))');
            $bloqueo->execute([':ubi' => $idUbicacion, ':prod' => $idProducto]);
            $consultarStock->execute([':ubicacion' => $idUbicacion, ':producto' => $idProducto]);
            $existencia = $consultarStock->fetch();
            $antes = $existencia ? (int)$existencia['cantidad'] : 0;
            $despues = $antes + $cantidad;
            if ($existencia) {
                $actualizarStock->execute([':cantidad' => $despues, ':id' => (int)$existencia['id_existencia']]);
            } else {
                $crearStock->execute([':ubicacion' => $idUbicacion,
                    ':producto' => $idProducto, ':cantidad' => $despues]);
            }
            $insertarDetalle->execute([
                ':movimiento' => $idMovimiento, ':producto' => $idProducto,
                ':cantidad' => $cantidad, ':antes' => $antes, ':despues' => $despues,
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    respuestaLote(true, [
        'id_movimiento' => $idMovimiento,
        'productos' => count($cantidades),
        'cantidad_entrada' => array_sum($cantidades),
    ], 'Entrada registrada correctamente.', 201);
} catch (DomainException | JsonException $e) {
    respuestaLote(false, null, $e instanceof JsonException ? 'JSON inválido.' : $e->getMessage(), 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[HU-21 entrada-lote] ' . $e->getMessage());
    respuestaLote(false, null, 'No fue posible registrar la entrada. Consulte el registro del servidor.', 500);
}
