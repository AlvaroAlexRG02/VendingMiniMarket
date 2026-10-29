<?php

/** VendingMiniMarket — HU-26: bajas por merma, robo, pérdida o devolución. */

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';

function bajaResponder(bool $ok, $data = null, string $message = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => $ok, 'data' => $data, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function bajaEntero($v): int
{
    if (!is_scalar($v) || is_bool($v)) return 0;
    $n = filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $n === false ? 0 : (int)$n;
}
function bajaUbicacion(PDO $pdo, int $id, bool $operar = true): array
{
    $q = $pdo->prepare('SELECT u.id_ubicacion,u.id_tienda,u.nombre,u.estado,t.estado AS tienda_activa
      FROM ubicacion_inventario u JOIN tienda t ON t.id_tienda=u.id_tienda
      WHERE u.id_ubicacion=:id LIMIT 1');
    $q->execute([':id' => $id]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    $activo = static fn($x): bool => in_array($x, [true, 1, '1', 't', 'true'], true);
    if (!$r || !$activo($r['estado']) || !$activo($r['tienda_activa'])) throw new DomainException('La ubicación no existe o está inactiva.');
    $permiso = evaluarAccesoUbicacion($pdo, (int)$r['id_tienda'], $operar ? 'operacion' : 'consulta');
    if (!$permiso['permitido']) throw new DomainException($permiso['motivo']);
    return $r;
}
function bajaOpciones(PDO $pdo): array
{
    $q = $pdo->query('SELECT ui.id_ubicacion,ui.id_tienda,ui.nombre,ui.tipo,t.nombre AS tienda
      FROM ubicacion_inventario ui JOIN tienda t ON t.id_tienda=ui.id_tienda
      WHERE ui.estado=TRUE AND t.estado=TRUE ORDER BY t.nombre,ui.nombre');
    $ubis = array_values(array_filter($q->fetchAll(PDO::FETCH_ASSOC), static function ($u) use ($pdo): bool {
        return evaluarAccesoUbicacion($pdo, (int)$u['id_tienda'], 'consulta')['permitido'];
    }));
    $prods = $pdo->query('SELECT id_producto,nombre,codigo_articulo_retail,codigo_barras
      FROM producto WHERE estado=TRUE ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    $ids = array_map(static fn($u) => (int)$u['id_ubicacion'], $ubis);
    $stocks = [];
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT id_ubicacion,id_producto,cantidad FROM existencia WHERE id_ubicacion IN ($marks)");
        $st->execute($ids);
        $stocks = $st->fetchAll(PDO::FETCH_ASSOC);
    }
    return ['ubicaciones' => $ubis, 'productos' => $prods, 'existencias' => $stocks];
}
function bajaHistorial(PDO $pdo): array
{
    $q = $pdo->query("SELECT m.id_movimiento,m.id_ubicacion_origen,m.fecha_movimiento,m.motivo,m.observaciones,
         m.evidencia_url,m.estado,ui.id_tienda,ui.nombre AS ubicacion,
         p.nombre AS producto,d.cantidad,d.saldo_origen_anterior,d.saldo_origen_resultante,
         TRIM(COALESCE(usr.nombre,'') || ' ' || COALESCE(usr.apellido,'')) AS responsable
      FROM movimiento_inventario m
      JOIN detalle_movimiento d ON d.id_movimiento=m.id_movimiento
      JOIN ubicacion_inventario ui ON ui.id_ubicacion=m.id_ubicacion_origen
      JOIN producto p ON p.id_producto=d.id_producto
      LEFT JOIN usuario usr ON usr.id_usuario=m.id_usuario
      WHERE m.tipo_movimiento='BAJA' ORDER BY m.id_movimiento DESC LIMIT 120");
    return array_values(array_filter($q->fetchAll(PDO::FETCH_ASSOC), static function ($r) use ($pdo): bool {
        return evaluarAccesoUbicacion($pdo, (int)$r['id_tienda'], 'consulta')['permitido'];
    }));
}
/** Guardar evidencias fuera de la carpeta pública del proyecto. */
function bajaCarpetaEvidencias(): string
{
    // La carpeta es hermana del directorio que contiene al proyecto en instalaciones normales.
    // No se sirven archivos directamente: sólo por la API con sesión y autorización.
    $dir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'VendingMiniMarket_evidencias_privadas' . DIRECTORY_SEPARATOR . 'mermas';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('No fue posible crear la carpeta privada de evidencias.');
    }
    if (!is_writable($dir)) throw new RuntimeException('La carpeta de evidencias no permite escribir archivos.');
    return $dir;
}
function bajaValidarArchivo(?array $archivo): ?array
{
    if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
    if (is_array($error) || $error !== UPLOAD_ERR_OK) {
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new DomainException('La evidencia supera el límite de archivos permitido por PHP.');
        }
        throw new DomainException('No se pudo recibir la evidencia. Selecciona el archivo otra vez.');
    }
    $tmp = $archivo['tmp_name'] ?? '';
    $tamano = (int)($archivo['size'] ?? 0);
    if (!is_string($tmp) || !is_uploaded_file($tmp)) throw new DomainException('La evidencia no se recibió correctamente.');
    if ($tamano < 1 || $tamano > 2 * 1024 * 1024) throw new DomainException('La evidencia debe pesar 2 MB o menos.');
    $extension = strtolower(pathinfo((string)($archivo['name'] ?? ''), PATHINFO_EXTENSION));
    $mimeEsperado = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
    if (!isset($mimeEsperado[$extension])) throw new DomainException('La evidencia debe ser JPG, PNG o PDF.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    if ($mime !== $mimeEsperado[$extension]) throw new DomainException('El archivo no coincide con el formato seleccionado.');
    if (in_array($extension, ['jpg', 'jpeg', 'png'], true) && getimagesize($tmp) === false) {
        throw new DomainException('No se pudo validar la imagen de evidencia.');
    }
    if ($extension === 'pdf') {
        $f = fopen($tmp, 'rb');
        $cabecera = $f ? fread($f, 5) : false;
        if ($f) fclose($f);
        if ($cabecera !== '%PDF-') throw new DomainException('El documento PDF no es válido.');
    }
    return ['temporal' => $tmp, 'extension' => $extension === 'jpeg' ? 'jpg' : $extension];
}
function bajaGuardarArchivo(array $archivo): array
{
    $nombre = bin2hex(random_bytes(16)) . '.' . $archivo['extension'];
    $ruta = bajaCarpetaEvidencias() . DIRECTORY_SEPARATOR . $nombre;
    if (!move_uploaded_file($archivo['temporal'], $ruta)) {
        throw new RuntimeException('No fue posible guardar el archivo de evidencia en el equipo servidor.');
    }
    @chmod($ruta, 0600);
    return ['referencia' => 'merma:' . $nombre, 'ruta' => $ruta];
}
function bajaDescargarEvidencia(PDO $pdo, int $movimiento): void
{
    if (!$movimiento) throw new DomainException('Movimiento de baja inválido.');
    $q = $pdo->prepare("SELECT id_ubicacion_origen,evidencia_url FROM movimiento_inventario
      WHERE id_movimiento=:id AND tipo_movimiento='BAJA' LIMIT 1");
    $q->execute([':id' => $movimiento]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    if (!$r) throw new DomainException('No se encontró la baja solicitada.');
    bajaUbicacion($pdo, (int)$r['id_ubicacion_origen'], false);
    if (!preg_match('/^merma:([a-f0-9]{32}\\.(jpg|png|pdf))$/', (string)$r['evidencia_url'], $m)) {
        throw new DomainException('Esta baja no tiene evidencia local adjunta.');
    }
    $ruta = bajaCarpetaEvidencias() . DIRECTORY_SEPARATOR . $m[1];
    if (!is_file($ruta) || !is_readable($ruta)) throw new DomainException('El archivo de evidencia ya no está disponible.');
    $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
    $mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'][$ext];
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header('Content-Disposition: attachment; filename="evidencia-merma-' . $movimiento . '.' . $ext . '"');
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit;
}

function bajaCrear(PDO $pdo, array $input, int $usuario, ?array $archivo = null): array
{
    $ubi = bajaEntero($input['id_ubicacion'] ?? null);
    $motivos = ['VENCIMIENTO', 'DAÑO', 'ROBO', 'PÉRDIDA', 'DEVOLUCIÓN'];
    $motivo = mb_strtoupper(trim((string)($input['motivo'] ?? '')), 'UTF-8');
    $observ = trim((string)($input['observaciones'] ?? ''));
    $evidencia = null;
    $archivoPreparado = bajaValidarArchivo($archivo);
    $archivoGuardado = null;
    $items = $input['items'] ?? null;
    if (!$ubi) throw new DomainException('Seleccione una ubicación.');
    if (!in_array($motivo, $motivos, true)) throw new DomainException('Seleccione un motivo de baja válido.');
    if (mb_strlen($observ, 'UTF-8') > 500) throw new DomainException('Las observaciones no pueden superar 500 caracteres.');
    if (!is_array($items) || count($items) < 1 || count($items) > 100) throw new DomainException('Agregue entre 1 y 100 productos.');
    $cantidades = [];
    foreach ($items as $item) {
        if (!is_array($item)) throw new DomainException('Producto inválido.');
        $prod = bajaEntero($item['id_producto'] ?? null);
        $cant = bajaEntero($item['cantidad'] ?? null);
        if (!$prod || !$cant || isset($cantidades[$prod])) throw new DomainException('Indique productos distintos y cantidades enteras positivas.');
        $cantidades[$prod] = $cant;
    }
    ksort($cantidades, SORT_NUMERIC);
    $pdo->beginTransaction();
    try {
        $u = bajaUbicacion($pdo, $ubi, true);
        $marks = implode(',', array_fill(0, count($cantidades), '?'));
        $q = $pdo->prepare("SELECT id_producto FROM producto WHERE estado=TRUE AND id_producto IN ($marks)");
        $q->execute(array_keys($cantidades));
        if (count($q->fetchAll(PDO::FETCH_COLUMN)) !== count($cantidades)) throw new DomainException('Hay productos inexistentes o inactivos.');
        $bloqueo = $pdo->prepare('SELECT pg_advisory_xact_lock(CAST(:ubi AS integer),CAST(:prod AS integer))');
        $stock = $pdo->prepare('SELECT id_existencia,cantidad FROM existencia WHERE id_ubicacion=:ubi AND id_producto=:prod FOR UPDATE');
        $update = $pdo->prepare('UPDATE existencia SET cantidad=:cantidad,fecha_actualizacion=NOW() WHERE id_existencia=:id');
        $saldo = [];
        foreach ($cantidades as $prod => $cant) {
            $bloqueo->execute([':ubi' => $ubi, ':prod' => $prod]);
            $stock->execute([':ubi' => $ubi, ':prod' => $prod]);
            $r = $stock->fetch(PDO::FETCH_ASSOC);
            $antes = $r ? (int)$r['cantidad'] : 0;
            if ($antes < $cant) throw new DomainException("Stock insuficiente para producto #$prod: hay $antes y se intentan retirar $cant.");
            $saldo[$prod] = ['id_existencia' => (int)$r['id_existencia'], 'antes' => $antes, 'despues' => $antes - $cant];
        }
        if ($archivoPreparado !== null) {
            $archivoGuardado = bajaGuardarArchivo($archivoPreparado);
            $evidencia = $archivoGuardado['referencia'];
        }
        $q = $pdo->prepare("INSERT INTO movimiento_inventario
          (id_usuario,id_ubicacion_origen,id_ubicacion_destino,id_relacion_abastecimiento,
           id_usuario_autoriza,tipo_movimiento,motivo,observaciones,evidencia_url,estado,
           fecha_movimiento,fecha_confirmacion,fecha_autorizacion,id_pedido)
          VALUES(:usuario,:ubi,NULL,NULL,:autoriza,'BAJA',:motivo,:observ,:evidencia,'CONFIRMADO',NOW(),NOW(),NOW(),NULL)
          RETURNING id_movimiento");
        $q->execute([
            ':usuario' => $usuario,
            ':ubi' => $ubi,
            ':autoriza' => $usuario,
            ':motivo' => $motivo,
            ':observ' => $observ ?: null,
            ':evidencia' => $evidencia ?: null
        ]);
        $id = (int)$q->fetchColumn();
        if (!$id) throw new RuntimeException('No se pudo registrar la baja.');
        $detalle = $pdo->prepare('INSERT INTO detalle_movimiento
          (id_movimiento,id_producto,cantidad,saldo_origen_anterior,saldo_origen_resultante)
          VALUES(:mov,:prod,:cant,:antes,:despues)');
        $auditoria = [];
        foreach ($cantidades as $prod => $cant) {
            $r = $saldo[$prod];
            $update->execute([':cantidad' => $r['despues'], ':id' => $r['id_existencia']]);
            $detalle->execute([':mov' => $id, ':prod' => $prod, ':cant' => $cant, ':antes' => $r['antes'], ':despues' => $r['despues']]);
            $auditoria[] = ['id_producto' => $prod, 'cantidad' => $cant, 'stock_anterior' => $r['antes'], 'stock_final' => $r['despues']];
        }
        registrarOperacion(
            $pdo,
            'MOVIMIENTO_INVENTARIO',
            'BAJA',
            (int)$u['id_tienda'],
            null,
            ['id_movimiento' => $id, 'ubicacion' => $ubi, 'motivo' => $motivo, 'items' => $auditoria, 'evidencia_url' => $evidencia ?: null]
        );
        $pdo->commit();
        return ['id_movimiento' => $id, 'unidades' => array_sum($cantidades), 'productos' => count($cantidades), 'saldos' => $auditoria];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($archivoGuardado !== null && is_file($archivoGuardado['ruta'])) @unlink($archivoGuardado['ruta']);
        throw $e;
    }
}

$sesion = $_SESSION['usuario'] ?? null;
if (!is_array($sesion) || !bajaEntero($sesion['id_usuario'] ?? null)) bajaResponder(false, null, 'Debe iniciar sesión.', 401);
if (!usuarioTieneRol([1, 2, 3])) bajaResponder(false, null, 'Solo Administrador, Gerente o Encargado pueden registrar bajas.', 403);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
try {
    if ($method === 'GET') {
        $action = (string)($_GET['accion'] ?? 'datos');
        if ($action === 'datos') bajaResponder(true, bajaOpciones($pdo));
        if ($action === 'historial') bajaResponder(true, bajaHistorial($pdo));
        if ($action === 'evidencia') bajaDescargarEvidencia($pdo, bajaEntero($_GET['id_movimiento'] ?? null));
        throw new DomainException('Acción no válida.');
    }
    if ($method !== 'POST') bajaResponder(false, null, 'Método no permitido.', 405);
    $tipo = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    $archivo = null;
    if (str_starts_with($tipo, 'multipart/form-data')) {
        if (!isset($_POST['datos'])) throw new DomainException('No se recibieron los datos. Revisa el tamaño máximo del archivo permitido por PHP.');
        $datos = json_decode((string)$_POST['datos'], true, 512, JSON_THROW_ON_ERROR);
        $archivo = $_FILES['evidencia'] ?? null;
    } else {
        // Mantener compatibilidad con solicitudes JSON anteriores sin evidencia.
        $datos = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
    }
    if (!is_array($datos)) throw new DomainException('La solicitud está incompleta.');
    bajaResponder(true, bajaCrear($pdo, $datos, (int)$sesion['id_usuario'], $archivo), 'Baja de inventario registrada.', 201);
} catch (DomainException | JsonException $e) {
    bajaResponder(false, null, $e instanceof JsonException ? 'La solicitud no contiene JSON válido.' : $e->getMessage(), 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[HU26] ' . $e->getMessage());
    bajaResponder(false, null, 'No fue posible registrar la baja. Revise el registro PHP.', 500);
}
