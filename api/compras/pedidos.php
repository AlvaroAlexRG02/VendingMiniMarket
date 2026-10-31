<?php

/**
 * API de pedidos a proveedor — VendingMiniMarket
 *
 * Tablas reales: pedido, pedido_detalle, configuracion_compras,
 * proveedor, producto, producto_proveedor, ubicacion_inventario,
 * bitacora_auditoria (historial).
 *
 * HU-30  Registrar pedido a proveedor      crear_pedido
 * HU-31  Consultar y actualizar estado     listar_pedidos, obtener_pedido, actualizar_estado_pedido
 *
 * Listas de apoyo para el formulario: listar_proveedores,
 * listar_ubicaciones, listar_productos_proveedor.
 *
 * Usa PDO ($pdo), igual que api/catalogo/catalogo.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';    // define $pdo
require_once __DIR__ . '/../../config/session.php';     // inicia la sesión
require_once __DIR__ . '/../../config/permisos.php';    // esAdministrador(), esEncargado(), esDependiente()
require_once __DIR__ . '/../../config/ubicaciones.php'; // exigirOperarEnUbicacion(), ubicacionesAsignadas()

const ESTADOS_PEDIDO = ['PENDIENTE', 'EN_CAMINO', 'PARCIAL', 'RECIBIDO', 'CANCELADO'];
const ESTADOS_PEDIDO_FINALES = ['RECIBIDO', 'CANCELADO'];


/* ==========================================================
   UTILIDADES GENERALES (mismo patrón que api/catalogo/catalogo.php)
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

/** HU-30: Dependiente, Encargado, Gerente General o Administrador pueden crear pedidos (todos los roles). */
function exigirPuedeCrearPedido(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral() && !esEncargado() && !esDependiente()) {
        respuesta(false, null, 'No tiene permisos para registrar pedidos.', 403);
    }

    return $usuario;
}

/** HU-31: Encargado, Gerente General o Administrador pueden actualizar el estado. */
function exigirPuedeActualizarEstado(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral() && !esEncargado()) {
        respuesta(false, null, 'No tiene permisos para actualizar el estado del pedido.', 403);
    }

    return $usuario;
}

/** Editar un pedido ya registrado: todos los roles (el Dependiente solo sus propios pedidos, ver accionEditarPedido). */
function exigirPuedeEditarPedido(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral() && !esEncargado() && !esDependiente()) {
        respuesta(false, null, 'No tiene permisos para editar pedidos.', 403);
    }

    return $usuario;
}

/** Aprobar pedidos: Administrador, Gerente General o Encargado (también los suyos). */
function exigirPuedeAprobarPedido(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral() && !esEncargado()) {
        respuesta(false, null, 'No tiene permisos para aprobar pedidos.', 403);
    }

    return $usuario;
}

/** Pedido + tienda + estado de aprobación, o responde 404 si no existe. */
function obtenerPedidoParaAprobacion(int $idPedido): array
{
    $st = db()->prepare("
        SELECT ped.id_pedido, ped.estado, ped.estado_aprobacion, ped.id_usuario_solicita, ubi.id_tienda
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

    error_log('[pedidos] ' . $e->getMessage());
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

/** Ubicaciones de tipo TIENDA visibles para el usuario de la sesión (HU-19). */
function ubicacionesTiendaVisibles(): array
{
    $usuario = $_SESSION['usuario'];
    $idRol   = (int)($usuario['id_rol'] ?? 0);

    $sql = "
        SELECT u.id_ubicacion, u.id_tienda, u.nombre
        FROM ubicacion_inventario u
        WHERE u.tipo = 'TIENDA' AND u.estado = TRUE
    ";

    if (in_array($idRol, [1, 2], true)) {
        $st = db()->query($sql . ' ORDER BY u.nombre');
        return $st->fetchAll();
    }

    $asignadas = array_column(ubicacionesAsignadas(db(), (int)$usuario['id_usuario']), 'id_tienda');

    if (!$asignadas) {
        return [];
    }

    $marcas = implode(',', array_fill(0, count($asignadas), '?'));
    $st = db()->prepare($sql . " AND u.id_tienda IN ($marcas) ORDER BY u.nombre");
    $st->execute($asignadas);

    return $st->fetchAll();
}


/* ==========================================================
   LISTAS DE APOYO (proveedores, ubicaciones, productos)
========================================================== */

function accionListarProveedores(array $in): void
{
    exigirSesion();

    $st = db()->query("
        SELECT id_proveedor, nombre
        FROM proveedor
        WHERE estado = TRUE
        ORDER BY nombre
    ");

    respuesta(true, $st->fetchAll());
}

function accionListarUbicaciones(array $in): void
{
    exigirSesion();

    respuesta(true, ubicacionesTiendaVisibles());
}

function accionListarProductosProveedor(array $in): void
{
    exigirSesion();

    $idProveedor = (int)($_GET['id_proveedor'] ?? $in['id_proveedor'] ?? 0);

    if ($idProveedor <= 0) {
        respuesta(false, null, 'Proveedor inválido.', 400);
    }

    $st = db()->prepare("
        SELECT p.id_producto, p.nombre, pp.costo
        FROM producto_proveedor pp
        JOIN producto p ON p.id_producto = pp.id_producto
        WHERE pp.id_proveedor = :id_proveedor
          AND pp.estado = TRUE
          AND p.estado = TRUE
        ORDER BY p.nombre
    ");
    $st->execute([':id_proveedor' => $idProveedor]);

    respuesta(true, $st->fetchAll());
}


/* ==========================================================
   CONFIGURACIÓN DE COMPRAS (monto de aprobación)
========================================================== */

function accionObtenerConfiguracionCompras(array $in): void
{
    exigirSesion();

    $fila = db()->query('SELECT monto_aprobacion FROM configuracion_compras WHERE id_configuracion = 1')->fetch();

    respuesta(true, ['monto_aprobacion' => (float)($fila['monto_aprobacion'] ?? 0)]);
}

function accionActualizarConfiguracionCompras(array $in): void
{
    $usuario = exigirSesion();
    exigirPost();

    if (!esAdministrador()) {
        respuesta(false, null, 'Solo el Administrador puede cambiar esta configuración.', 403);
    }

    $monto = (float)($in['monto_aprobacion'] ?? -1);

    if ($monto < 0) {
        respuesta(false, null, 'El monto de aprobación no es válido.', 400);
    }

    $st = db()->prepare("
        UPDATE configuracion_compras
        SET monto_aprobacion = :monto, id_usuario_actualiza = :id_usuario, fecha_actualizacion = NOW()
        WHERE id_configuracion = 1
    ");
    $st->execute([':monto' => $monto, ':id_usuario' => $usuario['id_usuario']]);

    respuesta(true, ['monto_aprobacion' => $monto], 'Configuración actualizada correctamente.');
}


/* ==========================================================
   HU-30 — CREAR PEDIDO
========================================================== */

function leerItemsPedido(array $in): array
{
    $items = $in['items'] ?? [];

    if (!is_array($items) || !$items) {
        respuesta(false, null, 'Debe agregar al menos un producto al pedido.', 400);
    }

    $limpios = [];

    foreach ($items as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        $cantidad   = (int)($item['cantidad'] ?? 0);

        if ($idProducto <= 0 || $cantidad <= 0) {
            respuesta(false, null, 'Hay productos con datos inválidos en el pedido.', 400);
        }

        if (isset($limpios[$idProducto])) {
            respuesta(false, null, 'No se puede agregar el mismo producto dos veces en el pedido.', 400);
        }

        $limpios[$idProducto] = $cantidad;
    }

    return $limpios;
}

function accionCrearPedido(array $in): void
{
    $usuario = exigirPuedeCrearPedido();
    exigirPost();

    $idProveedor  = (int)($in['id_proveedor'] ?? 0);
    $idUbicacion  = (int)($in['id_ubicacion'] ?? 0);
    $observaciones = trim((string)($in['observaciones'] ?? ''));
    $cantidades   = leerItemsPedido($in);

    if ($idProveedor <= 0) {
        respuesta(false, null, 'Debe seleccionar un proveedor.', 400);
    }

    if ($idUbicacion <= 0) {
        respuesta(false, null, 'Debe seleccionar una tienda solicitante.', 400);
    }

    if (mb_strlen($observaciones, 'UTF-8') > 500) {
        respuesta(false, null, 'Las observaciones no pueden superar 500 caracteres.', 400);
    }

    // La ubicación debe existir, ser de tipo TIENDA, y el usuario debe poder operar en su tienda (HU-19).
    $ubicacion = db()->prepare("
        SELECT id_ubicacion, id_tienda, tipo, estado
        FROM ubicacion_inventario
        WHERE id_ubicacion = :id
    ");
    $ubicacion->execute([':id' => $idUbicacion]);
    $ubicacion = $ubicacion->fetch();

    if (!$ubicacion) {
        respuesta(false, null, 'La ubicación seleccionada no existe.', 404);
    }

    if ($ubicacion['tipo'] !== 'TIENDA') {
        respuesta(false, null, 'Los pedidos solo se pueden entregar en una tienda.', 400);
    }

    exigirOperarEnUbicacion(db(), (int)$ubicacion['id_tienda']);

    // Proveedor activo.
    $proveedor = db()->prepare('SELECT id_proveedor FROM proveedor WHERE id_proveedor = :id AND estado = TRUE');
    $proveedor->execute([':id' => $idProveedor]);

    if (!$proveedor->fetchColumn()) {
        respuesta(false, null, 'El proveedor seleccionado no existe o está inactivo.', 404);
    }

    // Costo vigente de cada producto para ESTE proveedor (producto_proveedor.costo).
    $marcas = implode(',', array_fill(0, count($cantidades), '?'));
    $costos = db()->prepare("
        SELECT id_producto, costo
        FROM producto_proveedor
        WHERE id_proveedor = ? AND estado = TRUE AND id_producto IN ($marcas)
    ");
    $costos->execute(array_merge([$idProveedor], array_keys($cantidades)));

    $costosPorProducto = [];
    foreach ($costos->fetchAll() as $fila) {
        $costosPorProducto[(int)$fila['id_producto']] = (float)($fila['costo'] ?? 0);
    }

    if (count($costosPorProducto) !== count($cantidades)) {
        respuesta(false, null, 'Uno o más productos no están asociados a ese proveedor.', 400);
    }

    // El Dependiente guarda un borrador y lo envía a aprobar cuando quiera;
    // Encargado/Administrador/Gerente lo dejan directamente por aprobar (pueden aprobar el suyo).
    $estadoAprobacion = esDependiente() ? 'BORRADOR' : 'POR_APROBAR';

    $nuevo = enTransaccion(function () use ($idProveedor, $idUbicacion, $usuario, $observaciones, $cantidades, $costosPorProducto, $estadoAprobacion) {
        $pdo = db();

        $st = $pdo->prepare("
            INSERT INTO pedido (id_proveedor, id_ubicacion, id_usuario_solicita, observaciones, requiere_aprobacion, estado_aprobacion)
            VALUES (:id_proveedor, :id_ubicacion, :id_usuario, :observaciones, TRUE, :estado_aprobacion)
            RETURNING id_pedido
        ");
        $st->execute([
            ':id_proveedor'      => $idProveedor,
            ':id_ubicacion'      => $idUbicacion,
            ':id_usuario'        => $usuario['id_usuario'],
            ':observaciones'     => $observaciones !== '' ? $observaciones : null,
            ':estado_aprobacion' => $estadoAprobacion,
        ]);

        $idPedido = (int)$st->fetchColumn();

        $detalle = $pdo->prepare("
            INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad_solicitada, costo_unitario)
            VALUES (:id_pedido, :id_producto, :cantidad, :costo)
        ");

        foreach ($cantidades as $idProducto => $cantidad) {
            $detalle->execute([
                ':id_pedido'   => $idPedido,
                ':id_producto' => $idProducto,
                ':cantidad'    => $cantidad,
                ':costo'       => $costosPorProducto[$idProducto],
            ]);
        }

        $foto = fotoPedido($idPedido);
        auditar('PEDIDO', 'CREAR', null, $foto);

        return $foto;
    }, 'No se pudo registrar el pedido.');

    respuesta(true, $nuevo, 'Pedido registrado correctamente.', 201);
}


function accionEditarPedido(array $in): void
{
    $usuario = exigirPuedeEditarPedido();
    exigirPost();

    $idPedido      = (int)($in['id_pedido'] ?? 0);
    $idProveedor   = (int)($in['id_proveedor'] ?? 0);
    $idUbicacion   = (int)($in['id_ubicacion'] ?? 0);
    $observaciones = trim((string)($in['observaciones'] ?? ''));
    $cantidades    = leerItemsPedido($in);

    if ($idPedido <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    if ($idProveedor <= 0) {
        respuesta(false, null, 'Debe seleccionar un proveedor.', 400);
    }

    if ($idUbicacion <= 0) {
        respuesta(false, null, 'Debe seleccionar una tienda solicitante.', 400);
    }

    if (mb_strlen($observaciones, 'UTF-8') > 500) {
        respuesta(false, null, 'Las observaciones no pueden superar 500 caracteres.', 400);
    }

    $actual = obtenerPedidoParaAprobacion($idPedido);

    exigirOperarEnUbicacion(db(), (int)$actual['id_tienda']);

    if (!in_array($actual['estado'], ['PENDIENTE', 'EN_CAMINO'], true)) {
        respuesta(false, null, 'Solo se pueden editar pedidos Pendientes o En camino (sin mercadería recibida).', 409);
    }

    if (esDependiente()) {
        if ((int)$actual['id_usuario_solicita'] !== (int)$usuario['id_usuario']) {
            respuesta(false, null, 'Solo puede editar los pedidos que usted registró.', 403);
        }

        if ($actual['estado_aprobacion'] === 'POR_APROBAR') {
            respuesta(false, null, 'El pedido está pendiente de aprobación y no se puede editar hasta que sea aprobado.', 409);
        }
    }

    // Editar reemplaza el detalle del pedido: no se puede con recepciones aún sin confirmar.
    $recepcionesPendientes = db()->prepare("
        SELECT 1 FROM movimiento_inventario
        WHERE id_pedido = :id AND tipo_movimiento = 'ENTRADA' AND estado = 'PENDIENTE'
        LIMIT 1
    ");
    $recepcionesPendientes->execute([':id' => $idPedido]);
    if ($recepcionesPendientes->fetchColumn()) {
        respuesta(false, null, 'El pedido tiene recepciones pendientes de confirmar y no se puede editar.', 409);
    }

    // La ubicación debe existir, ser de tipo TIENDA, y el usuario debe poder operar en su tienda (HU-19).
    $ubicacion = db()->prepare("
        SELECT id_ubicacion, id_tienda, tipo, estado
        FROM ubicacion_inventario
        WHERE id_ubicacion = :id
    ");
    $ubicacion->execute([':id' => $idUbicacion]);
    $ubicacion = $ubicacion->fetch();

    if (!$ubicacion) {
        respuesta(false, null, 'La ubicación seleccionada no existe.', 404);
    }

    if ($ubicacion['tipo'] !== 'TIENDA') {
        respuesta(false, null, 'Los pedidos solo se pueden entregar en una tienda.', 400);
    }

    exigirOperarEnUbicacion(db(), (int)$ubicacion['id_tienda']);

    // Proveedor activo.
    $proveedor = db()->prepare('SELECT id_proveedor FROM proveedor WHERE id_proveedor = :id AND estado = TRUE');
    $proveedor->execute([':id' => $idProveedor]);

    if (!$proveedor->fetchColumn()) {
        respuesta(false, null, 'El proveedor seleccionado no existe o está inactivo.', 404);
    }

    // Costo vigente de cada producto para ESTE proveedor (producto_proveedor.costo).
    $marcas = implode(',', array_fill(0, count($cantidades), '?'));
    $costos = db()->prepare("
        SELECT id_producto, costo
        FROM producto_proveedor
        WHERE id_proveedor = ? AND estado = TRUE AND id_producto IN ($marcas)
    ");
    $costos->execute(array_merge([$idProveedor], array_keys($cantidades)));

    $costosPorProducto = [];
    foreach ($costos->fetchAll() as $fila) {
        $costosPorProducto[(int)$fila['id_producto']] = (float)($fila['costo'] ?? 0);
    }

    if (count($costosPorProducto) !== count($cantidades)) {
        respuesta(false, null, 'Uno o más productos no están asociados a ese proveedor.', 400);
    }

    // Todo pedido editado vuelve a aprobación: el Dependiente debe enviarlo de nuevo;
    // Encargado/Administrador/Gerente lo dejan por aprobar (pueden aprobar el suyo).
    $estadoAprobacion = esDependiente() ? 'BORRADOR' : 'POR_APROBAR';

    $actualizado = enTransaccion(function () use ($idPedido, $idProveedor, $idUbicacion, $usuario, $observaciones, $cantidades, $costosPorProducto, $estadoAprobacion) {
        $pdo = db();

        $st = $pdo->prepare("
            UPDATE pedido
            SET id_proveedor = :id_proveedor, id_ubicacion = :id_ubicacion, observaciones = :observaciones,
                estado = 'PENDIENTE', requiere_aprobacion = TRUE, estado_aprobacion = :estado_aprobacion, id_usuario_actualiza = :id_usuario, fecha_actualizacion = NOW()
            WHERE id_pedido = :id_pedido
        ");
        $st->execute([
            ':id_proveedor'        => $idProveedor,
            ':id_ubicacion'        => $idUbicacion,
            ':observaciones'       => $observaciones !== '' ? $observaciones : null,
            ':estado_aprobacion'   => $estadoAprobacion,
            ':id_usuario'          => $usuario['id_usuario'],
            ':id_pedido'           => $idPedido,
        ]);

        $pdo->prepare('DELETE FROM pedido_detalle WHERE id_pedido = :id')->execute([':id' => $idPedido]);

        $detalle = $pdo->prepare("
            INSERT INTO pedido_detalle (id_pedido, id_producto, cantidad_solicitada, costo_unitario)
            VALUES (:id_pedido, :id_producto, :cantidad, :costo)
        ");

        foreach ($cantidades as $idProducto => $cantidad) {
            $detalle->execute([
                ':id_pedido'   => $idPedido,
                ':id_producto' => $idProducto,
                ':cantidad'    => $cantidad,
                ':costo'       => $costosPorProducto[$idProducto],
            ]);
        }

        $foto = fotoPedido($idPedido);
        auditar('PEDIDO', 'EDITAR', null, $foto);

        return $foto;
    }, 'No se pudo editar el pedido.');

    respuesta(true, $actualizado, 'Pedido actualizado correctamente.');
}


/* ==========================================================
   HU-31 — CONSULTAR Y ACTUALIZAR ESTADO
========================================================== */

const SQL_PEDIDO_BASE = "
    SELECT
        ped.id_pedido,
        'PED-' || LPAD(ped.id_pedido::text, 6, '0') AS numero_pedido,
        ped.id_proveedor,
        prov.nombre AS proveedor_nombre,
        ped.id_ubicacion,
        ubi.nombre AS ubicacion_nombre,
        ubi.id_tienda,
        ped.estado,
        ped.origen,
        ped.requiere_aprobacion,
        ped.estado_aprobacion,
        ped.id_usuario_solicita,
        ped.observaciones,
        ped.fecha_pedido,
        ped.fecha_actualizacion,
        (SELECT f.numero_factura FROM factura_proveedor f WHERE f.id_pedido = ped.id_pedido LIMIT 1) AS numero_factura,
        u1.nombre AS solicita_nombre,
        u1.apellido AS solicita_apellido,
        u2.nombre AS actualiza_nombre,
        u2.apellido AS actualiza_apellido
    FROM pedido ped
    JOIN proveedor prov ON prov.id_proveedor = ped.id_proveedor
    JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = ped.id_ubicacion
    JOIN usuario u1 ON u1.id_usuario = ped.id_usuario_solicita
    LEFT JOIN usuario u2 ON u2.id_usuario = ped.id_usuario_actualiza
";

function fotoPedido(int $idPedido): array
{
    $st = db()->prepare(SQL_PEDIDO_BASE . ' WHERE ped.id_pedido = :id');
    $st->execute([':id' => $idPedido]);
    $pedido = $st->fetch();

    if (!$pedido) {
        respuesta(false, null, 'El pedido no existe.', 404);
    }

    $detalle = db()->prepare("
        SELECT pd.id_producto, p.nombre, pd.cantidad_solicitada, pd.cantidad_recibida, pd.costo_unitario
        FROM pedido_detalle pd
        JOIN producto p ON p.id_producto = pd.id_producto
        WHERE pd.id_pedido = :id
        ORDER BY p.nombre
    ");
    $detalle->execute([':id' => $idPedido]);

    $pedido['detalle'] = $detalle->fetchAll();

    // Cantidades ya reclamadas por recepciones registradas pero aún sin
    // confirmar, para que el formulario de recepción no permita reservar
    // de nuevo lo que ya está pendiente de aprobación (ver HU-32).
    $reservado = db()->prepare("
        SELECT dm.id_producto, SUM(dm.cantidad) AS total
        FROM detalle_movimiento dm
        JOIN movimiento_inventario m ON m.id_movimiento = dm.id_movimiento
        WHERE m.id_pedido = :id AND m.tipo_movimiento = 'ENTRADA' AND m.estado = 'PENDIENTE'
        GROUP BY dm.id_producto
    ");
    $reservado->execute([':id' => $idPedido]);
    $reservadoPorProducto = [];
    foreach ($reservado->fetchAll() as $fila) {
        $reservadoPorProducto[(int)$fila['id_producto']] = (int)$fila['total'];
    }

    foreach ($pedido['detalle'] as &$item) {
        $item['cantidad_reservada_pendiente'] = $reservadoPorProducto[(int)$item['id_producto']] ?? 0;
    }
    unset($item);

    $pedido['monto_total'] = array_reduce(
        $pedido['detalle'],
        fn ($acumulado, $fila) => $acumulado + ($fila['cantidad_solicitada'] * $fila['costo_unitario']),
        0
    );

    return $pedido;
}

function accionObtenerPedido(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id_pedido'] ?? $in['id_pedido'] ?? 0);

    if ($id <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    respuesta(true, fotoPedido($id));
}

function accionListarPedidos(array $in): void
{
    $usuario = exigirSesion();
    $idRol   = (int)($usuario['id_rol'] ?? 0);

    $where  = [];
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

    $busqueda = trim((string)($_GET['busqueda'] ?? ''));
    if ($busqueda !== '') {
        $where[] = '(prov.nombre ILIKE :busqueda OR ubi.nombre ILIKE :busqueda)';
        $params[':busqueda'] = '%' . $busqueda . '%';
    }

    $idProveedor = (int)($_GET['id_proveedor'] ?? 0);
    if ($idProveedor > 0) {
        $where[] = 'ped.id_proveedor = :id_proveedor';
        $params[':id_proveedor'] = $idProveedor;
    }

    $idUbicacion = (int)($_GET['id_ubicacion'] ?? 0);
    if ($idUbicacion > 0) {
        $where[] = 'ped.id_ubicacion = :id_ubicacion';
        $params[':id_ubicacion'] = $idUbicacion;
    }

    $estado = strtoupper(trim((string)($_GET['estado'] ?? '')));
    if ($estado !== '' && in_array($estado, ESTADOS_PEDIDO, true)) {
        $where[] = 'ped.estado = :estado';
        $params[':estado'] = $estado;
    }

    $fecha = trim((string)($_GET['fecha'] ?? ''));
    if ($fecha !== '') {
        $where[] = 'ped.fecha_pedido::date = :fecha';
        $params[':fecha'] = $fecha;
    }

    $sql = SQL_PEDIDO_BASE;
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ped.fecha_pedido DESC';

    $st = db()->prepare($sql);
    $st->execute($params);
    $pedidos = $st->fetchAll();

    // Totales y cantidad de productos por pedido, en una sola consulta adicional.
    if ($pedidos) {
        $ids = array_column($pedidos, 'id_pedido');
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $totales = db()->prepare("
            SELECT id_pedido,
                   SUM(cantidad_solicitada) AS total_unidades,
                   SUM(cantidad_solicitada * costo_unitario) AS monto_total
            FROM pedido_detalle
            WHERE id_pedido IN ($marcas)
            GROUP BY id_pedido
        ");
        $totales->execute($ids);
        $totalesPorPedido = [];
        foreach ($totales->fetchAll() as $fila) {
            $totalesPorPedido[(int)$fila['id_pedido']] = $fila;
        }

        foreach ($pedidos as &$pedido) {
            $t = $totalesPorPedido[(int)$pedido['id_pedido']] ?? ['total_unidades' => 0, 'monto_total' => 0];
            $pedido['total_unidades'] = (int)$t['total_unidades'];
            $pedido['monto_total']    = (float)$t['monto_total'];
        }
        unset($pedido);
    }

    respuesta(true, $pedidos);
}

function accionActualizarEstadoPedido(array $in): void
{
    $usuario = exigirPuedeActualizarEstado();
    exigirPost();

    $idPedido    = (int)($in['id_pedido'] ?? 0);
    $nuevoEstado = strtoupper(trim((string)($in['estado'] ?? '')));

    if ($idPedido <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    // El estado avanza solo (aprobar -> En camino; recepciones -> Parcial/Recibido).
    // Manualmente únicamente se puede cancelar.
    if ($nuevoEstado !== 'CANCELADO') {
        respuesta(false, null, 'El estado del pedido avanza automáticamente; solo se puede cancelar manualmente.', 400);
    }

    $actual = obtenerPedidoParaAprobacion($idPedido);

    exigirOperarEnUbicacion(db(), (int)$actual['id_tienda']);

    if (in_array($actual['estado'], ESTADOS_PEDIDO_FINALES, true)) {
        respuesta(false, null, 'El pedido ya está en un estado final y no se puede modificar.', 409);
    }

    $recepcionesPendientes = db()->prepare("
        SELECT 1 FROM movimiento_inventario
        WHERE id_pedido = :id AND tipo_movimiento = 'ENTRADA' AND estado = 'PENDIENTE'
        LIMIT 1
    ");
    $recepcionesPendientes->execute([':id' => $idPedido]);
    if ($recepcionesPendientes->fetchColumn()) {
        respuesta(false, null, 'El pedido tiene recepciones pendientes de confirmar y no se puede cancelar.', 409);
    }

    $nuevo = enTransaccion(function () use ($idPedido, $usuario, $actual) {
        db()->prepare("
            UPDATE pedido
            SET estado = 'CANCELADO', id_usuario_actualiza = :id_usuario, fecha_actualizacion = NOW()
            WHERE id_pedido = :id
        ")->execute([':id_usuario' => $usuario['id_usuario'], ':id' => $idPedido]);

        $foto = fotoPedido($idPedido);
        auditar('PEDIDO', 'ACTUALIZAR_ESTADO', ['estado' => $actual['estado']], ['estado' => 'CANCELADO']);

        return $foto;
    }, 'No se pudo cancelar el pedido.');

    respuesta(true, $nuevo, 'Pedido cancelado correctamente.');
}


/* ==========================================================
   FLUJO DE APROBACIÓN
========================================================== */

function accionEnviarAprobacionPedido(array $in): void
{
    $usuario = exigirPuedeEditarPedido();
    exigirPost();

    $idPedido = (int)($in['id_pedido'] ?? 0);

    if ($idPedido <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    $actual = obtenerPedidoParaAprobacion($idPedido);
    exigirOperarEnUbicacion(db(), (int)$actual['id_tienda']);

    if (esDependiente() && (int)$actual['id_usuario_solicita'] !== (int)$usuario['id_usuario']) {
        respuesta(false, null, 'Solo puede enviar a aprobación los pedidos que usted registró.', 403);
    }

    if ($actual['estado'] !== 'PENDIENTE') {
        respuesta(false, null, 'Solo se pueden enviar a aprobación pedidos en estado Pendiente.', 409);
    }

    if ($actual['estado_aprobacion'] !== 'BORRADOR') {
        respuesta(false, null, 'El pedido ya fue enviado a aprobación o ya está aprobado.', 409);
    }

    $nuevo = enTransaccion(function () use ($idPedido, $usuario) {
        db()->prepare("
            UPDATE pedido
            SET estado_aprobacion = 'POR_APROBAR', requiere_aprobacion = TRUE,
                id_usuario_actualiza = :usuario, fecha_actualizacion = NOW()
            WHERE id_pedido = :id AND estado_aprobacion = 'BORRADOR'
        ")->execute([':usuario' => $usuario['id_usuario'], ':id' => $idPedido]);

        auditar('PEDIDO', 'ENVIAR_APROBACION', ['estado_aprobacion' => 'BORRADOR'], ['estado_aprobacion' => 'POR_APROBAR']);

        return fotoPedido($idPedido);
    }, 'No se pudo enviar el pedido a aprobación.');

    respuesta(true, $nuevo, 'Pedido enviado a aprobación.');
}

function accionAprobarPedido(array $in): void
{
    $usuario = exigirPuedeAprobarPedido();
    exigirPost();

    $idPedido = (int)($in['id_pedido'] ?? 0);

    if ($idPedido <= 0) {
        respuesta(false, null, 'Pedido inválido.', 400);
    }

    $actual = obtenerPedidoParaAprobacion($idPedido);
    exigirOperarEnUbicacion(db(), (int)$actual['id_tienda']);

    if ($actual['estado'] !== 'PENDIENTE') {
        respuesta(false, null, 'Solo se pueden aprobar pedidos en estado Pendiente.', 409);
    }

    if ($actual['estado_aprobacion'] === 'BORRADOR') {
        respuesta(false, null, 'El pedido todavía es un borrador: debe enviarse a aprobación primero.', 409);
    }

    if ($actual['estado_aprobacion'] === 'APROBADO') {
        respuesta(false, null, 'El pedido ya está aprobado.', 409);
    }

    $nuevo = enTransaccion(function () use ($idPedido, $usuario) {
        db()->prepare("
            UPDATE pedido
            SET estado_aprobacion = 'APROBADO', requiere_aprobacion = FALSE, estado = 'EN_CAMINO',
                id_usuario_actualiza = :usuario, fecha_actualizacion = NOW()
            WHERE id_pedido = :id AND estado_aprobacion = 'POR_APROBAR'
        ")->execute([':usuario' => $usuario['id_usuario'], ':id' => $idPedido]);

        auditar('PEDIDO', 'APROBAR', ['estado' => 'PENDIENTE', 'estado_aprobacion' => 'POR_APROBAR'], ['estado' => 'EN_CAMINO', 'estado_aprobacion' => 'APROBADO']);

        return fotoPedido($idPedido);
    }, 'No se pudo aprobar el pedido.');

    respuesta(true, $nuevo, 'Pedido aprobado correctamente.');
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? '');

$acciones = [
    'listar_proveedores'            => 'accionListarProveedores',
    'listar_ubicaciones'            => 'accionListarUbicaciones',
    'listar_productos_proveedor'    => 'accionListarProductosProveedor',

    'obtener_configuracion_compras'    => 'accionObtenerConfiguracionCompras',
    'actualizar_configuracion_compras' => 'accionActualizarConfiguracionCompras',

    'crear_pedido'            => 'accionCrearPedido',
    'editar_pedido'           => 'accionEditarPedido',
    'listar_pedidos'          => 'accionListarPedidos',
    'obtener_pedido'          => 'accionObtenerPedido',
    'actualizar_estado_pedido' => 'accionActualizarEstadoPedido',
    'enviar_aprobacion_pedido' => 'accionEnviarAprobacionPedido',
    'aprobar_pedido'           => 'accionAprobarPedido',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[pedidos] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
