<?php

/**
 * API de recepción de pedidos — VendingMiniMarket
 *
 * HU-32  Recepción de pedidos (parcial o total)
 *
 * No crea tablas nuevas de "recepción": reutiliza la
 * infraestructura de inventario que ya existía sin usarse
 * (movimiento_inventario, detalle_movimiento, existencia).
 *
 *   registrar_recepcion  -> crea movimiento_inventario
 *                           (tipo ENTRADA) + detalle_movimiento.
 *                           Si quien registra es Encargado o
 *                           Administrador, se confirma de una vez.
 *   confirmar_recepcion  -> aplica la recepción: actualiza
 *                           existencia, pedido_detalle.cantidad_recibida
 *                           y recalcula pedido.estado.
 *   listar_recepciones_pendientes -> movimientos ENTRADA en
 *                           estado PENDIENTE que un Encargado o
 *                           Administrador debe confirmar.
 *   listar_recepciones_pedido -> historial de recepciones de
 *                           un pedido puntual.
 *
 * Usa PDO ($pdo), igual que api/compras/pedidos.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';

const ESTADOS_PEDIDO_FINALES_RECEPCION = ['RECIBIDO', 'CANCELADO'];


/* ==========================================================
   UTILIDADES GENERALES (mismo patrón que api/compras/pedidos.php)
========================================================== */

function db(): PDO
{
    global $pdo;
    return $pdo;
}

function respuesta(bool $success, $data = null, string $message = '', int $status = 200): void
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

function exigirSesion(): array
{
    if (empty($_SESSION['usuario'])) {
        respuesta(false, null, 'Debe iniciar sesión.', 401);
    }

    return $_SESSION['usuario'];
}

/** HU-32: Dependiente, Encargado o Administrador pueden registrar una recepción. */
function exigirPuedeRegistrarRecepcion(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esEncargado() && !esDependiente()) {
        respuesta(false, null, 'No tiene permisos para registrar recepciones.', 403);
    }

    return $usuario;
}

/** HU-32: solo Encargado o Administrador finalizan (confirman) una recepción. */
function exigirPuedeConfirmarRecepcion(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esEncargado()) {
        respuesta(false, null, 'No tiene permisos para confirmar recepciones.', 403);
    }

    return $usuario;
}

function exigirPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respuesta(false, null, 'Método no permitido.', 405);
    }
}

function leerEntrada(): array
{
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo === false ? '' : $crudo, true);

    return is_array($datos) ? $datos : $_POST;
}

function manejarErrorBD(PDOException $e, string $mensajeGeneral): void
{
    $codigo = (string)$e->getCode();

    if ($codigo === '23503') {
        respuesta(false, null, 'La operación se relaciona con un registro que no existe.', 409);
    }

    error_log('[recepciones] ' . $e->getMessage());
    respuesta(false, null, $mensajeGeneral, 500);
}

function enTransaccion(callable $accion, string $mensajeError)
{
    $pdo = db();

    try {
        $pdo->beginTransaction();
        $resultado = $accion();
        $pdo->commit();

        return $resultado;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        manejarErrorBD($e, $mensajeError);
    }
}

function auditar(string $entidad, string $accion, ?array $antes, ?array $despues): void
{
    $st = db()->prepare("
        INSERT INTO bitacora_auditoria
            (id_usuario, entidad, accion, valor_anterior, valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:id_usuario, :entidad, :accion, CAST(:anterior AS jsonb), CAST(:nuevo AS jsonb), 'EXITOSO', :ip, NOW())
    ");

    $st->execute([
        ':id_usuario' => (int)($_SESSION['usuario']['id_usuario'] ?? 0) ?: null,
        ':entidad'    => $entidad,
        ':accion'     => $accion,
        ':anterior'   => $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
        ':nuevo'      => $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}


/* ==========================================================
   HELPERS DE PEDIDO
========================================================== */

/** Pedido + su ubicación/tienda, o responde 404 si no existe. */
function obtenerPedidoParaRecepcion(int $idPedido): array
{
    $st = db()->prepare("
        SELECT ped.id_pedido, ped.estado, ped.id_ubicacion, ubi.id_tienda
        FROM pedido ped
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = ped.id_ubicacion
        WHERE ped.id_pedido = :id
    ");
    $st->execute([':id' => $idPedido]);
    $pedido = $st->fetch();

    if (!$pedido) {
        respuesta(false, null, 'El pedido no existe.', 404);
    }

    return $pedido;
}

/** Cantidades solicitadas/recibidas por producto de un pedido, indexadas por id_producto. */
function pedidoDetallePendiente(int $idPedido): array
{
    $st = db()->prepare("
        SELECT id_producto, cantidad_solicitada, cantidad_recibida
        FROM pedido_detalle
        WHERE id_pedido = :id
    ");
    $st->execute([':id' => $idPedido]);

    $detalle = [];
    foreach ($st->fetchAll() as $fila) {
        $detalle[(int)$fila['id_producto']] = [
            'cantidad_solicitada' => (int)$fila['cantidad_solicitada'],
            'cantidad_recibida'   => (int)$fila['cantidad_recibida'],
        ];
    }

    return $detalle;
}

function leerItemsRecepcion(array $in, array $detallePendiente): array
{
    $items = $in['items'] ?? [];

    if (!is_array($items) || !$items) {
        respuesta(false, null, 'Debe indicar al menos un producto recibido.', 400);
    }

    $limpios = [];

    foreach ($items as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        $cantidad   = (int)($item['cantidad'] ?? 0);

        if ($idProducto <= 0 || $cantidad <= 0) {
            respuesta(false, null, 'Hay productos con datos inválidos en la recepción.', 400);
        }

        if (!isset($detallePendiente[$idProducto])) {
            respuesta(false, null, 'Uno de los productos no pertenece a este pedido.', 400);
        }

        $pendiente = $detallePendiente[$idProducto]['cantidad_solicitada']
            - $detallePendiente[$idProducto]['cantidad_recibida'];

        if ($cantidad > $pendiente) {
            respuesta(false, null, "La cantidad recibida no puede superar lo pendiente ($pendiente).", 400);
        }

        $limpios[$idProducto] = $cantidad;
    }

    return $limpios;
}


/* ==========================================================
   HU-32 — REGISTRAR RECEPCIÓN
========================================================== */

function accionRegistrarRecepcion(array $in): void
{
    $usuario = exigirPuedeRegistrarRecepcion();
    exigirPost();

    $idPedido      = (int)($in['id_pedido'] ?? 0);
    $observaciones = trim((string)($in['observaciones'] ?? ''));

    if ($idPedido <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    $pedido = obtenerPedidoParaRecepcion($idPedido);

    if (in_array($pedido['estado'], ESTADOS_PEDIDO_FINALES_RECEPCION, true)) {
        respuesta(false, null, 'Este pedido ya está cerrado y no admite más recepciones.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$pedido['id_tienda']);

    $detallePendiente = pedidoDetallePendiente($idPedido);
    $items = leerItemsRecepcion($in, $detallePendiente);

    $autoConfirmar = esAdministrador() || esEncargado();

    $resultado = enTransaccion(function () use ($idPedido, $pedido, $usuario, $observaciones, $items, $autoConfirmar) {
        $pdo = db();

        $st = $pdo->prepare("
            INSERT INTO movimiento_inventario
                (id_usuario, id_ubicacion_destino, id_pedido, tipo_movimiento,
                 motivo, observaciones, estado, id_usuario_autoriza, fecha_confirmacion, fecha_autorizacion)
            VALUES
                (:id_usuario, :id_ubicacion, :id_pedido, 'ENTRADA',
                 'Recepción de pedido', :observaciones, :estado, :id_usuario_autoriza,
                 CASE WHEN :estado2 = 'CONFIRMADO' THEN NOW() ELSE NULL END,
                 CASE WHEN :estado3 = 'CONFIRMADO' THEN NOW() ELSE NULL END)
            RETURNING id_movimiento
        ");
        $estado = $autoConfirmar ? 'CONFIRMADO' : 'PENDIENTE';
        $st->execute([
            ':id_usuario'          => $usuario['id_usuario'],
            ':id_ubicacion'        => $pedido['id_ubicacion'],
            ':id_pedido'           => $idPedido,
            ':observaciones'       => $observaciones !== '' ? $observaciones : null,
            ':estado'              => $estado,
            ':id_usuario_autoriza' => $autoConfirmar ? $usuario['id_usuario'] : null,
            ':estado2'             => $estado,
            ':estado3'             => $estado,
        ]);

        $idMovimiento = (int)$st->fetchColumn();

        $detalle = $pdo->prepare("
            INSERT INTO detalle_movimiento (id_movimiento, id_producto, cantidad)
            VALUES (:id_movimiento, :id_producto, :cantidad)
        ");

        foreach ($items as $idProducto => $cantidad) {
            $detalle->execute([
                ':id_movimiento' => $idMovimiento,
                ':id_producto'   => $idProducto,
                ':cantidad'      => $cantidad,
            ]);
        }

        auditar('PEDIDO', 'REGISTRAR_RECEPCION', null, [
            'id_pedido'     => $idPedido,
            'id_movimiento' => $idMovimiento,
            'items'         => $items,
            'confirmado'    => $autoConfirmar,
        ]);

        if ($autoConfirmar) {
            aplicarRecepcion($idMovimiento, $usuario['id_usuario']);
        }

        return ['id_movimiento' => $idMovimiento, 'confirmado' => $autoConfirmar];
    }, 'No se pudo registrar la recepción.');

    respuesta(
        true,
        $resultado,
        $autoConfirmar
            ? 'Recepción registrada y confirmada correctamente.'
            : 'Recepción registrada. Debe ser confirmada por un Encargado o Administrador.',
        201
    );
}


/* ==========================================================
   HU-32 — CONFIRMAR RECEPCIÓN
========================================================== */

/**
 * Aplica los efectos de una recepción ya registrada: suma a existencia,
 * actualiza pedido_detalle.cantidad_recibida y recalcula pedido.estado.
 * Debe llamarse dentro de una transacción. No valida permisos ni estado
 * del movimiento: eso lo hace quien la invoca.
 */
function aplicarRecepcion(int $idMovimiento, int $idUsuarioAutoriza): void
{
    $pdo = db();

    $movimiento = $pdo->prepare("
        SELECT id_pedido, id_ubicacion_destino
        FROM movimiento_inventario
        WHERE id_movimiento = :id
    ");
    $movimiento->execute([':id' => $idMovimiento]);
    $movimiento = $movimiento->fetch();

    $idPedido    = (int)$movimiento['id_pedido'];
    $idUbicacion = (int)$movimiento['id_ubicacion_destino'];

    $detalle = $pdo->prepare("
        SELECT id_detalle_movimiento, id_producto, cantidad
        FROM detalle_movimiento
        WHERE id_movimiento = :id
    ");
    $detalle->execute([':id' => $idMovimiento]);

    foreach ($detalle->fetchAll() as $fila) {
        $idProducto = (int)$fila['id_producto'];
        $cantidad   = (int)$fila['cantidad'];

        $existencia = $pdo->prepare("
            SELECT id_existencia, cantidad
            FROM existencia
            WHERE id_ubicacion = :ubicacion AND id_producto = :producto
        ");
        $existencia->execute([':ubicacion' => $idUbicacion, ':producto' => $idProducto]);
        $existencia = $existencia->fetch();

        $anterior = $existencia ? (int)$existencia['cantidad'] : 0;
        $nuevo    = $anterior + $cantidad;

        if ($existencia) {
            $pdo->prepare("UPDATE existencia SET cantidad = :cantidad, fecha_actualizacion = NOW() WHERE id_existencia = :id")
                ->execute([':cantidad' => $nuevo, ':id' => $existencia['id_existencia']]);
        } else {
            $pdo->prepare("
                INSERT INTO existencia (id_ubicacion, id_producto, cantidad, stock_minimo)
                VALUES (:ubicacion, :producto, :cantidad, 0)
            ")->execute([':ubicacion' => $idUbicacion, ':producto' => $idProducto, ':cantidad' => $nuevo]);
        }

        $pdo->prepare("
            UPDATE detalle_movimiento
            SET saldo_destino_anterior = :anterior, saldo_destino_resultante = :nuevo
            WHERE id_detalle_movimiento = :id
        ")->execute([':anterior' => $anterior, ':nuevo' => $nuevo, ':id' => $fila['id_detalle_movimiento']]);

        $pdo->prepare("
            UPDATE pedido_detalle
            SET cantidad_recibida = cantidad_recibida + :cantidad
            WHERE id_pedido = :pedido AND id_producto = :producto
        ")->execute([':cantidad' => $cantidad, ':pedido' => $idPedido, ':producto' => $idProducto]);
    }

    // Recalcular el estado del pedido según lo recibido hasta ahora.
    $totales = $pdo->prepare("
        SELECT
            SUM(cantidad_solicitada) AS total_solicitado,
            SUM(cantidad_recibida) AS total_recibido
        FROM pedido_detalle
        WHERE id_pedido = :id
    ");
    $totales->execute([':id' => $idPedido]);
    $totales = $totales->fetch();

    $nuevoEstado = (int)$totales['total_recibido'] >= (int)$totales['total_solicitado']
        ? 'RECIBIDO'
        : 'PARCIAL';

    $pdo->prepare("
        UPDATE pedido
        SET estado = :estado, id_usuario_actualiza = :usuario, fecha_actualizacion = NOW()
        WHERE id_pedido = :id
    ")->execute([':estado' => $nuevoEstado, ':usuario' => $idUsuarioAutoriza, ':id' => $idPedido]);

    $pdo->prepare("
        UPDATE movimiento_inventario
        SET estado = 'CONFIRMADO', fecha_confirmacion = NOW(),
            id_usuario_autoriza = :usuario, fecha_autorizacion = NOW()
        WHERE id_movimiento = :id
    ")->execute([':usuario' => $idUsuarioAutoriza, ':id' => $idMovimiento]);
}

function accionConfirmarRecepcion(array $in): void
{
    $usuario = exigirPuedeConfirmarRecepcion();
    exigirPost();

    $idMovimiento = (int)($in['id_movimiento'] ?? 0);

    if ($idMovimiento <= 0) {
        respuesta(false, null, 'Movimiento inválido.', 400);
    }

    $movimiento = db()->prepare("
        SELECT m.id_movimiento, m.estado, m.tipo_movimiento, m.id_pedido, ubi.id_tienda
        FROM movimiento_inventario m
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = m.id_ubicacion_destino
        WHERE m.id_movimiento = :id
    ");
    $movimiento->execute([':id' => $idMovimiento]);
    $movimiento = $movimiento->fetch();

    if (!$movimiento || $movimiento['tipo_movimiento'] !== 'ENTRADA' || !$movimiento['id_pedido']) {
        respuesta(false, null, 'La recepción indicada no existe.', 404);
    }

    if ($movimiento['estado'] !== 'PENDIENTE') {
        respuesta(false, null, 'Esta recepción ya fue confirmada o anulada.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$movimiento['id_tienda']);

    enTransaccion(function () use ($idMovimiento, $usuario, $movimiento) {
        aplicarRecepcion($idMovimiento, $usuario['id_usuario']);

        auditar('PEDIDO', 'CONFIRMAR_RECEPCION', null, [
            'id_pedido'     => (int)$movimiento['id_pedido'],
            'id_movimiento' => $idMovimiento,
        ]);
    }, 'No se pudo confirmar la recepción.');

    respuesta(true, null, 'Recepción confirmada correctamente.');
}


/* ==========================================================
   CONSULTAS
========================================================== */

function accionListarRecepcionesPendientes(array $in): void
{
    $usuario = exigirPuedeConfirmarRecepcion();
    $idRol   = (int)($usuario['id_rol'] ?? 0);

    $where  = ["m.tipo_movimiento = 'ENTRADA'", "m.estado = 'PENDIENTE'", "m.id_pedido IS NOT NULL"];
    $params = [];

    if (!in_array($idRol, [1, 2], true)) {
        $asignadas = array_column(ubicacionesAsignadas(db(), (int)$usuario['id_usuario']), 'id_tienda');

        if (!$asignadas) {
            respuesta(true, []);
        }

        $marcas = [];
        foreach ($asignadas as $i => $idTienda) {
            $clave = ":tienda$i";
            $marcas[] = $clave;
            $params[$clave] = $idTienda;
        }
        $where[] = 'ubi.id_tienda IN (' . implode(',', $marcas) . ')';
    }

    $sql = "
        SELECT
            m.id_movimiento, m.id_pedido, m.fecha_movimiento, m.observaciones,
            u.nombre AS registrado_por_nombre, u.apellido AS registrado_por_apellido,
            prov.nombre AS proveedor_nombre, ubi.nombre AS ubicacion_nombre
        FROM movimiento_inventario m
        JOIN usuario u ON u.id_usuario = m.id_usuario
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = m.id_ubicacion_destino
        JOIN pedido ped ON ped.id_pedido = m.id_pedido
        JOIN proveedor prov ON prov.id_proveedor = ped.id_proveedor
        WHERE " . implode(' AND ', $where) . "
        ORDER BY m.fecha_movimiento
    ";

    $st = db()->prepare($sql);
    $st->execute($params);

    respuesta(true, $st->fetchAll());
}

function accionListarRecepcionesPedido(array $in): void
{
    exigirSesion();

    $idPedido = (int)($_GET['id_pedido'] ?? $in['id_pedido'] ?? 0);

    if ($idPedido <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    $st = db()->prepare("
        SELECT
            m.id_movimiento, m.estado, m.fecha_movimiento, m.fecha_confirmacion,
            m.observaciones,
            u.nombre AS registrado_por_nombre, u.apellido AS registrado_por_apellido,
            ua.nombre AS confirmado_por_nombre, ua.apellido AS confirmado_por_apellido
        FROM movimiento_inventario m
        JOIN usuario u ON u.id_usuario = m.id_usuario
        LEFT JOIN usuario ua ON ua.id_usuario = m.id_usuario_autoriza
        WHERE m.id_pedido = :id AND m.tipo_movimiento = 'ENTRADA'
        ORDER BY m.fecha_movimiento DESC
    ");
    $st->execute([':id' => $idPedido]);
    $movimientos = $st->fetchAll();

    if ($movimientos) {
        $ids = array_column($movimientos, 'id_movimiento');
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $detalle = db()->prepare("
            SELECT dm.id_movimiento, dm.id_producto, p.nombre, dm.cantidad
            FROM detalle_movimiento dm
            JOIN producto p ON p.id_producto = dm.id_producto
            WHERE dm.id_movimiento IN ($marcas)
        ");
        $detalle->execute($ids);

        $detallePorMovimiento = [];
        foreach ($detalle->fetchAll() as $fila) {
            $detallePorMovimiento[(int)$fila['id_movimiento']][] = $fila;
        }

        foreach ($movimientos as &$mov) {
            $mov['detalle'] = $detallePorMovimiento[(int)$mov['id_movimiento']] ?? [];
        }
        unset($mov);
    }

    respuesta(true, $movimientos);
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? '');

$acciones = [
    'registrar_recepcion'            => 'accionRegistrarRecepcion',
    'confirmar_recepcion'            => 'accionConfirmarRecepcion',
    'listar_recepciones_pendientes'  => 'accionListarRecepcionesPendientes',
    'listar_recepciones_pedido'      => 'accionListarRecepcionesPedido',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[recepciones] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
