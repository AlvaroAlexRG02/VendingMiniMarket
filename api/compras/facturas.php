<?php

/**
 * API de facturas de proveedor — VendingMiniMarket
 *
 * Tablas reales: factura_proveedor, factura_proveedor_detalle,
 * proveedor, producto, impuesto, ubicacion_inventario, pedido
 * (relación opcional), bitacora_auditoria (historial).
 *
 * HU-33  Registrar factura de proveedor      crear_factura
 *        Validación del Encargado            marcar_en_revision
 * (HU-34 agrega aprobar/rechazar, no incluido todavía)
 *
 * crear_factura recibe multipart/form-data (por el archivo PDF
 * adjunto), no JSON como el resto de los endpoints del sistema.
 *
 * Usa PDO ($pdo), igual que api/compras/pedidos.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';
require_once __DIR__ . '/../../vendor/autoload.php';

const ESTADOS_FACTURA = ['PENDIENTE', 'EN_REVISION', 'APROBADA', 'RECHAZADA', 'ANULADA'];
const CARPETA_EVIDENCIAS = __DIR__ . '/../../compras/facturas-adjuntas/';
const TAMANO_MAXIMO_EVIDENCIA = 5 * 1024 * 1024; // 5 MB

const ETIQUETAS_ESTADO_FACTURA = [
    'PENDIENTE'   => 'Pendiente',
    'EN_REVISION' => 'En revisión',
    'APROBADA'    => 'Aprobada',
    'RECHAZADA'   => 'Rechazada',
    'ANULADA'     => 'Anulada',
];

const NOMBRES_MESES = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
];


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

/** HU-33: cualquier usuario autenticado puede registrar una factura. */
function exigirPuedeCrearFactura(): array
{
    return exigirSesion();
}

/** HU-33: Encargado, Gerente General o Administrador validan (envían a revisión) una factura. */
function exigirPuedeRevisarFactura(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral() && !esEncargado()) {
        respuesta(false, null, 'No tiene permisos para revisar facturas.', 403);
    }

    return $usuario;
}

/** HU-34: el Administrador o el Gerente General aprueban o rechazan una factura en revisión. */
function exigirPuedeValidarFactura(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral()) {
        respuesta(false, null, 'No tiene permisos para validar facturas.', 403);
    }

    return $usuario;
}

/** HU-36: el Administrador o el Gerente General anulan una factura (cancelación definitiva, con trazabilidad). */
function exigirPuedeAnularFactura(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador() && !esGerenteGeneral()) {
        respuesta(false, null, 'No tiene permisos para anular facturas.', 403);
    }

    return $usuario;
}

/** HU-36: solo Administrador o Gerente General exportan el historial. */
function exigirPuedeExportarHistorial(): array
{
    $usuario = exigirSesion();

    if (!esAdministradorOGerente()) {
        respuesta(false, null, 'No tiene permisos para exportar el historial.', 403);
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

    if ($codigo === '23505') {
        respuesta(false, null, 'Ya existe una factura con ese número para este proveedor.', 409);
    }

    if ($codigo === '23503') {
        respuesta(false, null, 'La operación se relaciona con un registro que no existe.', 409);
    }

    error_log('[facturas] ' . $e->getMessage());
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
   EVIDENCIA PDF
========================================================== */

/** Valida y guarda el PDF adjunto (opcional). Devuelve la ruta relativa o null. */
function procesarEvidenciaFactura(): ?string
{
    if (empty($_FILES['evidencia']) || $_FILES['evidencia']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $archivo = $_FILES['evidencia'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        respuesta(false, null, 'No se pudo subir el archivo adjunto.', 400);
    }

    if ($archivo['size'] > TAMANO_MAXIMO_EVIDENCIA) {
        respuesta(false, null, 'El archivo no puede superar 5 MB.', 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        respuesta(false, null, 'Solo se permiten archivos en formato PDF.', 400);
    }

    if (!is_dir(CARPETA_EVIDENCIAS)) {
        mkdir(CARPETA_EVIDENCIAS, 0755, true);
    }

    $nombreArchivo = 'factura_' . bin2hex(random_bytes(12)) . '.pdf';
    $rutaDestino   = CARPETA_EVIDENCIAS . $nombreArchivo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        respuesta(false, null, 'No se pudo guardar el archivo adjunto.', 500);
    }

    return 'facturas-adjuntas/' . $nombreArchivo;
}


/* ==========================================================
   HU-33 — CREAR FACTURA
========================================================== */

function leerItemsFactura(string $json): array
{
    $items = json_decode($json, true);

    if (!is_array($items) || !$items) {
        respuesta(false, null, 'Debe agregar al menos un producto a la factura.', 400);
    }

    $limpios = [];

    foreach ($items as $item) {
        $idProducto = (int)($item['id_producto'] ?? 0);
        $cantidad   = (int)($item['cantidad'] ?? 0);
        $costo      = (float)($item['costo_unitario'] ?? -1);

        if ($idProducto <= 0 || $cantidad <= 0 || $costo < 0) {
            respuesta(false, null, 'Hay productos con datos inválidos en la factura.', 400);
        }

        if (isset($limpios[$idProducto])) {
            respuesta(false, null, 'No se puede agregar el mismo producto dos veces en la factura.', 400);
        }

        $limpios[$idProducto] = ['cantidad' => $cantidad, 'costo_unitario' => $costo];
    }

    return $limpios;
}

function accionCrearFactura(array $in): void
{
    $usuario = exigirPuedeCrearFactura();
    exigirPost();

    $idProveedor      = (int)($_POST['id_proveedor'] ?? 0);
    $idPedido         = (int)($_POST['id_pedido'] ?? 0) ?: null;
    $idUbicacion      = (int)($_POST['id_ubicacion'] ?? 0);
    $numeroFactura    = trim((string)($_POST['numero_factura'] ?? ''));
    $fechaEmision     = trim((string)($_POST['fecha_emision'] ?? ''));
    $fechaVencimiento = trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null;
    $idImpuesto       = (int)($_POST['id_impuesto'] ?? 0);
    $observaciones    = trim((string)($_POST['observaciones'] ?? ''));
    $items            = leerItemsFactura((string)($_POST['items'] ?? '[]'));

    if ($idProveedor <= 0) {
        respuesta(false, null, 'Debe seleccionar un proveedor.', 400);
    }

    if ($idUbicacion <= 0) {
        respuesta(false, null, 'Debe seleccionar una tienda.', 400);
    }

    if ($numeroFactura === '' || mb_strlen($numeroFactura, 'UTF-8') > 50) {
        respuesta(false, null, 'El número de factura es obligatorio (máx. 50 caracteres).', 400);
    }

    if ($fechaEmision === '') {
        respuesta(false, null, 'La fecha de emisión es obligatoria.', 400);
    }

    if ($idImpuesto <= 0) {
        respuesta(false, null, 'Debe seleccionar un impuesto.', 400);
    }

    // Proveedor activo.
    $proveedor = db()->prepare('SELECT 1 FROM proveedor WHERE id_proveedor = :id AND estado = TRUE');
    $proveedor->execute([':id' => $idProveedor]);
    if (!$proveedor->fetchColumn()) {
        respuesta(false, null, 'El proveedor seleccionado no existe o está inactivo.', 404);
    }

    // Ubicación tipo TIENDA y permiso de operación (HU-19).
    $ubicacion = db()->prepare('SELECT id_tienda, tipo FROM ubicacion_inventario WHERE id_ubicacion = :id');
    $ubicacion->execute([':id' => $idUbicacion]);
    $ubicacion = $ubicacion->fetch();

    if (!$ubicacion) {
        respuesta(false, null, 'La ubicación seleccionada no existe.', 404);
    }

    if ($ubicacion['tipo'] !== 'TIENDA') {
        respuesta(false, null, 'Las facturas solo se pueden registrar para una tienda.', 400);
    }

    exigirOperarEnUbicacion(db(), (int)$ubicacion['id_tienda']);

    // El pedido relacionado (si se indicó) no puede estar Pendiente ni Cancelado:
    // una factura representa una compra que ya fue enviada/recibida por el proveedor.
    if ($idPedido !== null) {
        $pedido = db()->prepare('SELECT estado FROM pedido WHERE id_pedido = :id');
        $pedido->execute([':id' => $idPedido]);
        $estadoPedido = $pedido->fetchColumn();

        if ($estadoPedido === false) {
            respuesta(false, null, 'El pedido relacionado no existe.', 404);
        }

        if ($estadoPedido === 'PENDIENTE') {
            respuesta(false, null, 'No se puede relacionar una factura con un pedido que aún está Pendiente.', 400);
        }

        if ($estadoPedido === 'CANCELADO') {
            respuesta(false, null, 'No se puede relacionar una factura con un pedido Cancelado.', 400);
        }

        // Un pedido solo puede tener una factura asociada (la lista de "pedidos
        // disponibles" ya los oculta, pero esto evita duplicados si la API se
        // llama directamente o si dos solicitudes llegan casi al mismo tiempo).
        $facturaExistente = db()->prepare('SELECT numero_factura FROM factura_proveedor WHERE id_pedido = :id LIMIT 1');
        $facturaExistente->execute([':id' => $idPedido]);
        $numeroFacturaExistente = $facturaExistente->fetchColumn();

        if ($numeroFacturaExistente !== false) {
            respuesta(false, null, "Este pedido ya tiene la factura $numeroFacturaExistente asociada.", 409);
        }
    }

    // Impuesto activo, para calcular el monto.
    $impuesto = db()->prepare('SELECT porcentaje FROM impuesto WHERE id_impuesto = :id AND estado = TRUE');
    $impuesto->execute([':id' => $idImpuesto]);
    $porcentaje = $impuesto->fetchColumn();

    if ($porcentaje === false) {
        respuesta(false, null, 'El impuesto seleccionado no existe o está inactivo.', 404);
    }

    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += $item['cantidad'] * $item['costo_unitario'];
    }

    $montoImpuesto = round($subtotal * (float)$porcentaje / 100, 2);
    $montoTotal    = round($subtotal + $montoImpuesto, 2);

    $rutaEvidencia = procesarEvidenciaFactura();

    $nuevo = enTransaccion(function () use (
        $idProveedor, $idPedido, $idUbicacion, $numeroFactura, $fechaEmision, $fechaVencimiento,
        $idImpuesto, $subtotal, $montoImpuesto, $montoTotal, $observaciones, $rutaEvidencia,
        $usuario, $items
    ) {
        $pdo = db();

        $st = $pdo->prepare("
            INSERT INTO factura_proveedor (
                id_proveedor, id_pedido, id_ubicacion, numero_factura,
                fecha_emision, fecha_vencimiento, id_impuesto,
                subtotal, monto_impuesto, monto_total,
                evidencia_url, observaciones, id_usuario_registra
            )
            VALUES (
                :id_proveedor, :id_pedido, :id_ubicacion, :numero_factura,
                :fecha_emision, :fecha_vencimiento, :id_impuesto,
                :subtotal, :monto_impuesto, :monto_total,
                :evidencia_url, :observaciones, :id_usuario_registra
            )
            RETURNING id_factura
        ");
        $st->execute([
            ':id_proveedor'        => $idProveedor,
            ':id_pedido'           => $idPedido,
            ':id_ubicacion'        => $idUbicacion,
            ':numero_factura'      => $numeroFactura,
            ':fecha_emision'       => $fechaEmision,
            ':fecha_vencimiento'   => $fechaVencimiento,
            ':id_impuesto'         => $idImpuesto,
            ':subtotal'            => $subtotal,
            ':monto_impuesto'      => $montoImpuesto,
            ':monto_total'         => $montoTotal,
            ':evidencia_url'       => $rutaEvidencia,
            ':observaciones'       => $observaciones !== '' ? $observaciones : null,
            ':id_usuario_registra' => $usuario['id_usuario'],
        ]);

        $idFactura = (int)$st->fetchColumn();

        $detalle = $pdo->prepare("
            INSERT INTO factura_proveedor_detalle (id_factura, id_producto, cantidad, costo_unitario)
            VALUES (:id_factura, :id_producto, :cantidad, :costo_unitario)
        ");

        foreach ($items as $idProducto => $item) {
            $detalle->execute([
                ':id_factura'     => $idFactura,
                ':id_producto'    => $idProducto,
                ':cantidad'       => $item['cantidad'],
                ':costo_unitario' => $item['costo_unitario'],
            ]);
        }

        $foto = fotoFactura($idFactura);
        auditar('FACTURA', 'CREAR', null, $foto);

        return $foto;
    }, 'No se pudo registrar la factura.');

    respuesta(true, $nuevo, 'Factura registrada correctamente. Queda pendiente de validación.', 201);
}


/* ==========================================================
   HU-33 — VALIDACIÓN DEL ENCARGADO (envía a revisión)
========================================================== */

function accionMarcarEnRevision(array $in): void
{
    $usuario = exigirPuedeRevisarFactura();
    exigirPost();

    $idFactura = (int)($in['id_factura'] ?? 0);

    if ($idFactura <= 0) {
        respuesta(false, null, 'Factura inválida.', 400);
    }

    $factura = db()->prepare("
        SELECT f.id_factura, f.estado, ubi.id_tienda
        FROM factura_proveedor f
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = f.id_ubicacion
        WHERE f.id_factura = :id
    ");
    $factura->execute([':id' => $idFactura]);
    $factura = $factura->fetch();

    if (!$factura) {
        respuesta(false, null, 'La factura no existe.', 404);
    }

    if ($factura['estado'] !== 'PENDIENTE') {
        respuesta(false, null, 'Solo se pueden validar las facturas pendientes.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$factura['id_tienda']);

    $nuevo = enTransaccion(function () use ($idFactura, $usuario) {
        db()->prepare("
            UPDATE factura_proveedor
            SET estado = 'EN_REVISION', id_usuario_revisa = :usuario, fecha_revision = NOW()
            WHERE id_factura = :id
        ")->execute([':usuario' => $usuario['id_usuario'], ':id' => $idFactura]);

        $foto = fotoFactura($idFactura);
        auditar('FACTURA', 'ENVIAR_A_REVISION', ['estado' => 'PENDIENTE'], ['estado' => 'EN_REVISION']);

        return $foto;
    }, 'No se pudo enviar la factura a revisión.');

    respuesta(true, $nuevo, 'Factura validada correctamente. Queda pendiente de aprobación del Administrador.');
}


/* ==========================================================
   HU-33 — EDITAR FACTURA (Encargado/Administrador)
========================================================== */

const ESTADOS_FACTURA_EDITABLES = ['PENDIENTE', 'EN_REVISION', 'RECHAZADA'];

/** Factura + su tienda, estado, evidencia actual y creador, o responde 404 si no existe. */
function obtenerFacturaParaEditar(int $idFactura): array
{
    $st = db()->prepare("
        SELECT f.id_factura, f.estado, f.id_proveedor, f.evidencia_url, f.id_usuario_registra, ubi.id_tienda
        FROM factura_proveedor f
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = f.id_ubicacion
        WHERE f.id_factura = :id
    ");
    $st->execute([':id' => $idFactura]);
    $factura = $st->fetch();

    if (!$factura) {
        respuesta(false, null, 'La factura no existe.', 404);
    }

    return $factura;
}

function accionActualizarFactura(array $in): void
{
    $usuario = exigirSesion();
    exigirPost();

    $idFactura = (int)($_POST['id_factura'] ?? 0);

    if ($idFactura <= 0) {
        respuesta(false, null, 'Factura inválida.', 400);
    }

    $factura = obtenerFacturaParaEditar($idFactura);

    $esRevisor = esAdministrador() || esGerenteGeneral() || esEncargado();
    $esCreador = (int)$usuario['id_usuario'] === (int)$factura['id_usuario_registra'];

    if (!$esRevisor && !$esCreador) {
        respuesta(false, null, 'No tiene permisos para editar esta factura.', 403);
    }

    if ($esRevisor) {
        if (!in_array($factura['estado'], ESTADOS_FACTURA_EDITABLES, true)) {
            respuesta(false, null, 'Solo se pueden editar facturas pendientes, en revisión o rechazadas.', 409);
        }
    } elseif (!in_array($factura['estado'], ['PENDIENTE', 'RECHAZADA'], true)) {
        respuesta(false, null, 'Ya no puede editar esta factura porque está en revisión o fue aprobada.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$factura['id_tienda']);

    $numeroFactura    = trim((string)($_POST['numero_factura'] ?? ''));
    $fechaEmision     = trim((string)($_POST['fecha_emision'] ?? ''));
    $fechaVencimiento = trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null;
    $idImpuesto       = (int)($_POST['id_impuesto'] ?? 0);
    $observaciones    = trim((string)($_POST['observaciones'] ?? ''));
    $items            = leerItemsFactura((string)($_POST['items'] ?? '[]'));

    if ($numeroFactura === '' || mb_strlen($numeroFactura, 'UTF-8') > 50) {
        respuesta(false, null, 'El número de factura es obligatorio (máx. 50 caracteres).', 400);
    }

    if ($fechaEmision === '') {
        respuesta(false, null, 'La fecha de emisión es obligatoria.', 400);
    }

    if ($idImpuesto <= 0) {
        respuesta(false, null, 'Debe seleccionar un impuesto.', 400);
    }

    $impuesto = db()->prepare('SELECT porcentaje FROM impuesto WHERE id_impuesto = :id AND estado = TRUE');
    $impuesto->execute([':id' => $idImpuesto]);
    $porcentaje = $impuesto->fetchColumn();

    if ($porcentaje === false) {
        respuesta(false, null, 'El impuesto seleccionado no existe o está inactivo.', 404);
    }

    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += $item['cantidad'] * $item['costo_unitario'];
    }

    $montoImpuesto = round($subtotal * (float)$porcentaje / 100, 2);
    $montoTotal    = round($subtotal + $montoImpuesto, 2);

    $evidenciaAnterior = $factura['evidencia_url'];
    $rutaEvidenciaNueva = procesarEvidenciaFactura();

    $resultado = enTransaccion(function () use (
        $idFactura, $numeroFactura, $fechaEmision, $fechaVencimiento, $idImpuesto,
        $subtotal, $montoImpuesto, $montoTotal, $observaciones, $rutaEvidenciaNueva, $items
    ) {
        $pdo = db();

        $pdo->prepare("
            UPDATE factura_proveedor
            SET numero_factura = :numero_factura,
                fecha_emision = :fecha_emision,
                fecha_vencimiento = :fecha_vencimiento,
                id_impuesto = :id_impuesto,
                subtotal = :subtotal,
                monto_impuesto = :monto_impuesto,
                monto_total = :monto_total,
                observaciones = :observaciones,
                evidencia_url = COALESCE(:evidencia_url, evidencia_url),
                estado = CASE WHEN estado = 'RECHAZADA' THEN 'PENDIENTE' ELSE estado END,
                id_usuario_revisa = CASE WHEN estado = 'RECHAZADA' THEN NULL ELSE id_usuario_revisa END,
                fecha_revision = CASE WHEN estado = 'RECHAZADA' THEN NULL ELSE fecha_revision END,
                id_usuario_valida = CASE WHEN estado = 'RECHAZADA' THEN NULL ELSE id_usuario_valida END,
                fecha_validacion = CASE WHEN estado = 'RECHAZADA' THEN NULL ELSE fecha_validacion END
            WHERE id_factura = :id
        ")->execute([
            ':numero_factura'    => $numeroFactura,
            ':fecha_emision'     => $fechaEmision,
            ':fecha_vencimiento' => $fechaVencimiento,
            ':id_impuesto'       => $idImpuesto,
            ':subtotal'          => $subtotal,
            ':monto_impuesto'    => $montoImpuesto,
            ':monto_total'       => $montoTotal,
            ':observaciones'     => $observaciones !== '' ? $observaciones : null,
            ':evidencia_url'     => $rutaEvidenciaNueva,
            ':id'                => $idFactura,
        ]);

        $pdo->prepare('DELETE FROM factura_proveedor_detalle WHERE id_factura = :id')
            ->execute([':id' => $idFactura]);

        $detalle = $pdo->prepare("
            INSERT INTO factura_proveedor_detalle (id_factura, id_producto, cantidad, costo_unitario)
            VALUES (:id_factura, :id_producto, :cantidad, :costo_unitario)
        ");

        foreach ($items as $idProducto => $item) {
            $detalle->execute([
                ':id_factura'     => $idFactura,
                ':id_producto'    => $idProducto,
                ':cantidad'       => $item['cantidad'],
                ':costo_unitario' => $item['costo_unitario'],
            ]);
        }

        $foto = fotoFactura($idFactura);
        auditar('FACTURA', 'EDITAR', null, $foto);

        return $foto;
    }, 'No se pudo actualizar la factura.');

    if ($rutaEvidenciaNueva !== null && $evidenciaAnterior && $evidenciaAnterior !== $rutaEvidenciaNueva) {
        @unlink(__DIR__ . '/../../compras/' . $evidenciaAnterior);
    }

    respuesta(true, $resultado, 'Factura actualizada correctamente.');
}


/* ==========================================================
   HU-34 — VALIDACIÓN FINAL DEL ADMINISTRADOR
========================================================== */

/** Factura + su tienda, proveedor y estado actual, o responde 404 si no existe. */
function obtenerFacturaParaValidar(int $idFactura): array
{
    $st = db()->prepare("
        SELECT f.id_factura, f.estado, f.observaciones, f.id_proveedor, ubi.id_tienda
        FROM factura_proveedor f
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = f.id_ubicacion
        WHERE f.id_factura = :id
    ");
    $st->execute([':id' => $idFactura]);
    $factura = $st->fetch();

    if (!$factura) {
        respuesta(false, null, 'La factura no existe.', 404);
    }

    return $factura;
}

/** HU-34 criterio: al aprobar una factura, su costo_unitario real reemplaza el costo vigente del proveedor. */
function actualizarCostosProveedorDesdeFactura(int $idFactura, int $idProveedor): void
{
    $detalle = db()->prepare("
        SELECT id_producto, costo_unitario
        FROM factura_proveedor_detalle
        WHERE id_factura = :id
    ");
    $detalle->execute([':id' => $idFactura]);

    $actualizar = db()->prepare("
        UPDATE producto_proveedor
        SET costo = :costo, fecha_actualizacion = NOW()
        WHERE id_producto = :id_producto AND id_proveedor = :id_proveedor
    ");

    foreach ($detalle->fetchAll() as $item) {
        $actualizar->execute([
            ':costo'        => $item['costo_unitario'],
            ':id_producto'  => $item['id_producto'],
            ':id_proveedor' => $idProveedor,
        ]);
    }
}

function accionAprobarFactura(array $in): void
{
    $usuario = exigirPuedeValidarFactura();
    exigirPost();

    $idFactura = (int)($in['id_factura'] ?? 0);

    if ($idFactura <= 0) {
        respuesta(false, null, 'Factura inválida.', 400);
    }

    $factura = obtenerFacturaParaValidar($idFactura);

    if ($factura['estado'] !== 'EN_REVISION') {
        respuesta(false, null, 'Solo se pueden aprobar facturas en revisión.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$factura['id_tienda']);

    $nuevo = enTransaccion(function () use ($idFactura, $usuario, $factura) {
        db()->prepare("
            UPDATE factura_proveedor
            SET estado = 'APROBADA', id_usuario_valida = :usuario, fecha_validacion = NOW()
            WHERE id_factura = :id
        ")->execute([':usuario' => $usuario['id_usuario'], ':id' => $idFactura]);

        actualizarCostosProveedorDesdeFactura($idFactura, (int)$factura['id_proveedor']);

        $foto = fotoFactura($idFactura);
        auditar('FACTURA', 'APROBAR', ['estado' => 'EN_REVISION'], ['estado' => 'APROBADA']);

        return $foto;
    }, 'No se pudo aprobar la factura.');

    respuesta(true, $nuevo, 'Factura aprobada correctamente.');
}

function accionRechazarFactura(array $in): void
{
    $usuario = exigirPuedeValidarFactura();
    exigirPost();

    $idFactura = (int)($in['id_factura'] ?? 0);
    $motivo    = trim((string)($in['motivo'] ?? ''));

    if ($idFactura <= 0) {
        respuesta(false, null, 'Factura inválida.', 400);
    }

    if ($motivo === '') {
        respuesta(false, null, 'Debe indicar el motivo del rechazo.', 400);
    }

    $factura = obtenerFacturaParaValidar($idFactura);

    if (!in_array($factura['estado'], ['PENDIENTE', 'EN_REVISION'], true)) {
        respuesta(false, null, 'Solo se pueden rechazar facturas pendientes o en revisión.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$factura['id_tienda']);

    $observacionesNuevas = trim((string)$factura['observaciones']) !== ''
        ? $factura['observaciones'] . "\n\nMotivo de rechazo: " . $motivo
        : 'Motivo de rechazo: ' . $motivo;

    $nuevo = enTransaccion(function () use ($idFactura, $usuario, $observacionesNuevas) {
        db()->prepare("
            UPDATE factura_proveedor
            SET estado = 'RECHAZADA', id_usuario_valida = :usuario, fecha_validacion = NOW(),
                observaciones = :observaciones
            WHERE id_factura = :id
        ")->execute([
            ':usuario'       => $usuario['id_usuario'],
            ':observaciones' => $observacionesNuevas,
            ':id'            => $idFactura,
        ]);

        $foto = fotoFactura($idFactura);
        auditar('FACTURA', 'RECHAZAR', ['estado' => 'EN_REVISION'], ['estado' => 'RECHAZADA']);

        return $foto;
    }, 'No se pudo rechazar la factura.');

    respuesta(true, $nuevo, 'Factura rechazada correctamente.');
}


/* ==========================================================
   HU-36 — ANULAR FACTURA (cancelación definitiva, con trazabilidad)
========================================================== */

function accionAnularFactura(array $in): void
{
    $usuario = exigirPuedeAnularFactura();
    exigirPost();

    $idFactura = (int)($in['id_factura'] ?? 0);
    $motivo    = trim((string)($in['motivo'] ?? ''));

    if ($idFactura <= 0) {
        respuesta(false, null, 'Factura inválida.', 400);
    }

    if ($motivo === '') {
        respuesta(false, null, 'Debe indicar el motivo de la anulación.', 400);
    }

    $factura = obtenerFacturaParaValidar($idFactura);

    if ($factura['estado'] === 'ANULADA') {
        respuesta(false, null, 'Esta factura ya está anulada.', 409);
    }

    exigirOperarEnUbicacion(db(), (int)$factura['id_tienda']);

    $observacionesNuevas = trim((string)$factura['observaciones']) !== ''
        ? $factura['observaciones'] . "\n\nMotivo de anulación: " . $motivo
        : 'Motivo de anulación: ' . $motivo;

    $nuevo = enTransaccion(function () use ($idFactura, $usuario, $observacionesNuevas, $factura) {
        db()->prepare("
            UPDATE factura_proveedor
            SET estado = 'ANULADA', id_usuario_anula = :usuario, fecha_anulacion = NOW(),
                observaciones = :observaciones
            WHERE id_factura = :id
        ")->execute([
            ':usuario'       => $usuario['id_usuario'],
            ':observaciones' => $observacionesNuevas,
            ':id'            => $idFactura,
        ]);

        $foto = fotoFactura($idFactura);
        auditar('FACTURA', 'ANULAR', ['estado' => $factura['estado']], ['estado' => 'ANULADA']);

        return $foto;
    }, 'No se pudo anular la factura.');

    respuesta(true, $nuevo, 'Factura anulada correctamente.');
}


/* ==========================================================
   PEDIDOS DISPONIBLES PARA VINCULAR (sin factura todavía)
========================================================== */

function accionListarPedidosDisponibles(array $in): void
{
    $usuario = exigirSesion();
    $idRol   = (int)($usuario['id_rol'] ?? 0);

    $where  = [
        "ped.id_pedido NOT IN (SELECT id_pedido FROM factura_proveedor WHERE id_pedido IS NOT NULL)",
        "ped.estado NOT IN ('PENDIENTE', 'CANCELADO')"
    ];
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
            ped.id_pedido,
            'PED-' || LPAD(ped.id_pedido::text, 6, '0') AS numero_pedido,
            ped.id_proveedor, ped.id_ubicacion, ped.fecha_pedido,
            prov.nombre AS proveedor_nombre
        FROM pedido ped
        JOIN proveedor prov ON prov.id_proveedor = ped.id_proveedor
        JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = ped.id_ubicacion
        WHERE " . implode(' AND ', $where) . "
        ORDER BY ped.fecha_pedido DESC
    ";

    $st = db()->prepare($sql);
    $st->execute($params);

    respuesta(true, $st->fetchAll());
}


/* ==========================================================
   CONSULTAS
========================================================== */

const SQL_FACTURA_BASE = "
    SELECT
        f.id_factura, f.numero_factura, f.fecha_emision, f.fecha_vencimiento,
        f.subtotal, f.monto_impuesto, f.monto_total, f.estado,
        f.evidencia_url, f.observaciones, f.fecha_registro,
        f.id_pedido, f.id_proveedor, f.id_ubicacion, f.id_impuesto, f.id_usuario_registra,
        prov.nombre AS proveedor_nombre,
        ubi.nombre AS ubicacion_nombre,
        ubi.id_tienda,
        imp.nombre AS impuesto_nombre,
        imp.porcentaje AS impuesto_porcentaje,
        CASE WHEN ped.id_pedido IS NOT NULL
             THEN 'PED-' || LPAD(ped.id_pedido::text, 6, '0')
             ELSE NULL
        END AS numero_pedido,
        (SELECT COUNT(*) FROM movimiento_inventario m WHERE m.id_pedido = f.id_pedido) AS movimientos_asociados,
        u1.nombre AS registra_nombre, u1.apellido AS registra_apellido
    FROM factura_proveedor f
    JOIN proveedor prov ON prov.id_proveedor = f.id_proveedor
    JOIN ubicacion_inventario ubi ON ubi.id_ubicacion = f.id_ubicacion
    JOIN impuesto imp ON imp.id_impuesto = f.id_impuesto
    LEFT JOIN pedido ped ON ped.id_pedido = f.id_pedido
    JOIN usuario u1 ON u1.id_usuario = f.id_usuario_registra
";

function fotoFactura(int $idFactura): array
{
    $st = db()->prepare(SQL_FACTURA_BASE . ' WHERE f.id_factura = :id');
    $st->execute([':id' => $idFactura]);
    $factura = $st->fetch();

    if (!$factura) {
        respuesta(false, null, 'La factura no existe.', 404);
    }

    $detalle = db()->prepare("
        SELECT fd.id_producto, p.nombre, fd.cantidad, fd.costo_unitario
        FROM factura_proveedor_detalle fd
        JOIN producto p ON p.id_producto = fd.id_producto
        WHERE fd.id_factura = :id
        ORDER BY p.nombre
    ");
    $detalle->execute([':id' => $idFactura]);
    $factura['detalle'] = $detalle->fetchAll();

    return $factura;
}

function accionObtenerFactura(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id_factura'] ?? $in['id_factura'] ?? 0);

    if ($id <= 0) {
        respuesta(false, null, 'Factura inválida.', 400);
    }

    respuesta(true, fotoFactura($id));
}

function accionListarFacturas(array $in): void
{
    [$where, $params] = filtrosHistorialFacturas();

    $sql = SQL_FACTURA_BASE;
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY f.fecha_registro DESC';

    $st = db()->prepare($sql);
    $st->execute($params);

    respuesta(true, $st->fetchAll());
}


/* ==========================================================
   HU-36 — EXPORTAR HISTORIAL (CSV)
========================================================== */

/** Construye el WHERE/params de listar_facturas y exportar_historial a partir de la sesión y $_GET. */
function filtrosHistorialFacturas(): array
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
        $where[] = '(f.numero_factura ILIKE :busqueda OR prov.nombre ILIKE :busqueda)';
        $params[':busqueda'] = '%' . $busqueda . '%';
    }

    $idProveedor = (int)($_GET['id_proveedor'] ?? 0);
    if ($idProveedor > 0) {
        $where[] = 'f.id_proveedor = :id_proveedor';
        $params[':id_proveedor'] = $idProveedor;
    }

    $idUbicacion = (int)($_GET['id_ubicacion'] ?? 0);
    if ($idUbicacion > 0) {
        $where[] = 'f.id_ubicacion = :id_ubicacion';
        $params[':id_ubicacion'] = $idUbicacion;
    }

    $estado = strtoupper(trim((string)($_GET['estado'] ?? '')));
    if ($estado !== '' && in_array($estado, ESTADOS_FACTURA, true)) {
        $where[] = 'f.estado = :estado';
        $params[':estado'] = $estado;
    }

    $fecha = trim((string)($_GET['fecha'] ?? ''));
    if ($fecha !== '') {
        $where[] = 'f.fecha_emision::date = :fecha';
        $params[':fecha'] = $fecha;
    }

    $mes = trim((string)($_GET['mes'] ?? ''));
    if ($mes !== '' && preg_match('/^\d{4}-\d{2}$/', $mes)) {
        $where[] = "to_char(f.fecha_emision, 'YYYY-MM') = :mes";
        $params[':mes'] = $mes;
    }

    return [$where, $params];
}

function accionExportarHistorial(array $in): void
{
    exigirPuedeExportarHistorial();

    [$where, $params] = filtrosHistorialFacturas();

    $sql = SQL_FACTURA_BASE;
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY f.fecha_emision ASC, f.id_factura ASC';

    $st = db()->prepare($sql);
    $st->execute($params);
    $filas = $st->fetchAll();

    $mes = trim((string)($_GET['mes'] ?? ''));
    $tituloPeriodo = 'Historial completo';
    if ($mes !== '' && preg_match('/^\d{4}-\d{2}$/', $mes)) {
        [$anio, $numMes] = explode('-', $mes);
        $tituloPeriodo = (NOMBRES_MESES[$numMes] ?? $numMes) . ' ' . $anio;
    }

    $totalSubtotal = 0.0;
    $totalImpuesto = 0.0;
    $totalGeneral  = 0.0;

    $filasHtml = '';

    foreach ($filas as $f) {
        $totalSubtotal += (float)$f['subtotal'];
        $totalImpuesto += (float)$f['monto_impuesto'];
        $totalGeneral  += (float)$f['monto_total'];

        $registradoPor = trim(($f['registra_nombre'] ?? '') . ' ' . ($f['registra_apellido'] ?? ''));
        $etiquetaEstado = ETIQUETAS_ESTADO_FACTURA[$f['estado']] ?? $f['estado'];
        $claseEstado = 'estado-' . strtolower((string)$f['estado']);

        $filasHtml .= '
            <tr>
                <td>' . htmlspecialchars($f['numero_pedido'] ?? '-') . '</td>
                <td>' . htmlspecialchars($f['numero_factura']) . '</td>
                <td>' . htmlspecialchars($f['proveedor_nombre']) . '</td>
                <td>' . htmlspecialchars($f['ubicacion_nombre']) . '</td>
                <td>' . htmlspecialchars(date('d/m/Y', strtotime((string)$f['fecha_emision']))) . '</td>
                <td><span class="badge-estado ' . $claseEstado . '">' . htmlspecialchars($etiquetaEstado) . '</span></td>
                <td>' . htmlspecialchars($registradoPor ?: '-') . '</td>
                <td class="num">₡' . number_format((float)$f['subtotal'], 2) . '</td>
                <td class="num">₡' . number_format((float)$f['monto_impuesto'], 2) . '</td>
                <td class="num">₡' . number_format((float)$f['monto_total'], 2) . '</td>
            </tr>
        ';
    }

    if ($filas === []) {
        $filasHtml = '<tr><td colspan="10" class="vacio">No hay facturas registradas para el periodo seleccionado.</td></tr>';
    }

    $fechaGeneracion = (new DateTime())->format('d/m/Y H:i');

    $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 28px 32px; }
                * { font-family: Helvetica, Arial, sans-serif; }
                body { color: #1f2937; font-size: 10px; }

                .encabezado {
                    border-bottom: 3px solid #2563eb;
                    padding-bottom: 10px;
                    margin-bottom: 14px;
                }
                .encabezado h1 {
                    margin: 0;
                    font-size: 20px;
                    color: #1e3a8a;
                }
                .encabezado p {
                    margin: 2px 0 0;
                    color: #6b7280;
                    font-size: 11px;
                }
                .meta {
                    width: 100%;
                    margin-bottom: 14px;
                    font-size: 10px;
                    color: #374151;
                }
                .meta td { padding: 2px 0; }
                .meta td.etiqueta { font-weight: bold; width: 110px; }

                table.datos {
                    width: 100%;
                    border-collapse: collapse;
                }
                table.datos thead th {
                    background: #1e3a8a;
                    color: #ffffff;
                    text-align: left;
                    padding: 6px 5px;
                    font-size: 9px;
                    text-transform: uppercase;
                }
                table.datos tbody td {
                    padding: 5px;
                    border-bottom: 1px solid #e5e7eb;
                    font-size: 9.5px;
                }
                table.datos tbody tr:nth-child(even) { background: #f3f4f6; }
                table.datos td.num { text-align: right; }
                table.datos td.vacio { text-align: center; padding: 20px; color: #6b7280; }

                .badge-estado {
                    display: inline-block;
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-size: 8.5px;
                    color: #ffffff;
                }
                .estado-pendiente { background: #d97706; }
                .estado-en_revision { background: #2563eb; }
                .estado-aprobada { background: #16a34a; }
                .estado-rechazada { background: #dc2626; }
                .estado-anulada { background: #6b7280; }

                tfoot td {
                    padding: 7px 5px;
                    font-weight: bold;
                    border-top: 2px solid #1e3a8a;
                }
                .pie {
                    margin-top: 16px;
                    font-size: 8.5px;
                    color: #9ca3af;
                    text-align: center;
                }
            </style>
        </head>
        <body>

            <div class="encabezado">
                <h1>Vending Mini Market</h1>
                <p>Historial de compras y facturas de proveedores</p>
            </div>

            <table class="meta">
                <tr>
                    <td class="etiqueta">Periodo:</td>
                    <td>' . htmlspecialchars($tituloPeriodo) . '</td>
                </tr>
                <tr>
                    <td class="etiqueta">Generado el:</td>
                    <td>' . htmlspecialchars($fechaGeneracion) . '</td>
                </tr>
                <tr>
                    <td class="etiqueta">Total de facturas:</td>
                    <td>' . count($filas) . '</td>
                </tr>
            </table>

            <table class="datos">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Factura</th>
                        <th>Proveedor</th>
                        <th>Tienda</th>
                        <th>Fecha emisión</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                        <th>Subtotal</th>
                        <th>Impuesto</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>' . $filasHtml . '</tbody>
                <tfoot>
                    <tr>
                        <td colspan="7">Totales</td>
                        <td class="num">₡' . number_format($totalSubtotal, 2) . '</td>
                        <td class="num">₡' . number_format($totalImpuesto, 2) . '</td>
                        <td class="num">₡' . number_format($totalGeneral, 2) . '</td>
                    </tr>
                </tfoot>
            </table>

            <p class="pie">Vending Mini Market &mdash; Reporte generado automáticamente por el sistema.</p>

        </body>
        </html>
    ';

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => false,
        'defaultFont' => 'Helvetica',
    ]);
    $dompdf->setPaper('letter', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="historial_compras_' . date('Y-m-d_His') . '.pdf"');
    header('Cache-Control: no-store');

    echo $dompdf->output();
    exit;
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? $_POST['accion'] ?? '');

$acciones = [
    'crear_factura'               => 'accionCrearFactura',
    'actualizar_factura'          => 'accionActualizarFactura',
    'marcar_en_revision'          => 'accionMarcarEnRevision',
    'aprobar_factura'             => 'accionAprobarFactura',
    'rechazar_factura'            => 'accionRechazarFactura',
    'anular_factura'              => 'accionAnularFactura',
    'listar_facturas'             => 'accionListarFacturas',
    'obtener_factura'             => 'accionObtenerFactura',
    'listar_pedidos_disponibles'  => 'accionListarPedidosDisponibles',
    'exportar_historial'          => 'accionExportarHistorial',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[facturas] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
