<?php

/** HU-27 - Stock mínimo por producto y ubicación. No modifica cantidades. */

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';

function sm27Respuesta(bool $ok, $data = null, string $mensaje = '', int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => $ok, 'data' => $data, 'message' => $mensaje], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function sm27Id($valor): int
{
    if (!is_scalar($valor) || is_bool($valor)) return 0;
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : (int)$id;
}
function sm27Minimo($valor): ?int
{
    if (!is_scalar($valor) || is_bool($valor)) return null;
    $n = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    return ($n === false || $n > 1000000) ? null : (int)$n;
}
function sm27Ubicacion(PDO $pdo, int $id, string $tipo): array
{
    $st = $pdo->prepare('SELECT ui.id_ubicacion,ui.id_tienda,ui.nombre,ui.tipo,t.nombre AS tienda FROM ubicacion_inventario ui JOIN tienda t ON t.id_tienda=ui.id_tienda WHERE ui.id_ubicacion=:id AND ui.estado=TRUE AND t.estado=TRUE');
    $st->execute([':id' => $id]);
    $ubicacion = $st->fetch(PDO::FETCH_ASSOC);
    if (!$ubicacion) throw new DomainException('La ubicación no existe o está inactiva.');
    $permiso = evaluarAccesoUbicacion($pdo, (int)$ubicacion['id_tienda'], $tipo);
    if (!$permiso['permitido']) throw new DomainException($permiso['motivo']);
    return $ubicacion;
}
function sm27Opciones(PDO $pdo): array
{
    $st = $pdo->query('SELECT ui.id_ubicacion,ui.id_tienda,ui.nombre,ui.tipo,t.nombre AS tienda FROM ubicacion_inventario ui JOIN tienda t ON t.id_tienda=ui.id_tienda WHERE ui.estado=TRUE AND t.estado=TRUE ORDER BY t.nombre,ui.nombre');
    $ubicaciones = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $u) {
        if (evaluarAccesoUbicacion($pdo, (int)$u['id_tienda'], 'consulta')['permitido']) $ubicaciones[] = $u;
    }
    $productos = $pdo->query('SELECT id_producto,nombre,codigo_articulo_retail,codigo_barras FROM producto WHERE estado=TRUE ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    return ['ubicaciones' => $ubicaciones, 'productos' => $productos];
}
function sm27Registros(PDO $pdo, int $ubicacion): array
{
    $u = sm27Ubicacion($pdo, $ubicacion, 'consulta');
    $st = $pdo->prepare('SELECT e.id_existencia,e.id_producto,e.id_ubicacion,e.cantidad,e.stock_minimo,p.nombre AS producto,p.codigo_articulo_retail,p.codigo_barras FROM existencia e JOIN producto p ON p.id_producto=e.id_producto AND p.estado=TRUE WHERE e.id_ubicacion=:ubi ORDER BY p.nombre');
    $st->execute([':ubi' => $ubicacion]);
    $filas = [];
    $alertas = 0;
    $sugeridas = 0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $actual = (int)$f['cantidad'];
        $min = (int)$f['stock_minimo'];
        // 0 = sin umbral configurado: no genera alerta.
        $estado = $min > 0 && $actual <= $min ? ($actual <= 0 ? 'AGOTADO' : 'STOCK_BAJO') : 'NORMAL';
        $sugerencia = $min > 0 ? max(0, $min - $actual) : 0;
        if ($estado !== 'NORMAL') $alertas++;
        $sugeridas += $sugerencia;
        $filas[] = [
            'id_existencia' => (int)$f['id_existencia'],
            'id_producto' => (int)$f['id_producto'],
            'producto' => $f['producto'],
            'codigo_retail' => $f['codigo_articulo_retail'],
            'codigo_barras' => $f['codigo_barras'],
            'cantidad' => $actual,
            'stock_minimo' => $min,
            'estado' => $estado,
            'sugerencia' => $sugerencia
        ];
    }
    return ['ubicacion' => $u, 'registros' => $filas, 'alertas' => $alertas, 'unidades_sugeridas' => $sugeridas];
}
function sm27Guardar(PDO $pdo, array $body, array $usuario): array
{
    if (!in_array((int)($usuario['id_rol'] ?? 0), [1, 2, 3], true)) throw new DomainException('Su rol no permite configurar mínimos.');
    $ubicacion = sm27Id($body['id_ubicacion'] ?? null);
    $producto = sm27Id($body['id_producto'] ?? null);
    $minimo = sm27Minimo($body['stock_minimo'] ?? null);
    if (!$ubicacion || !$producto || $minimo === null) throw new DomainException('Seleccione ubicación, producto y un mínimo válido (0 a 1000000).');
    $u = sm27Ubicacion($pdo, $ubicacion, 'operacion');
    $check = $pdo->prepare('SELECT 1 FROM producto WHERE id_producto=:id AND estado=TRUE');
    $check->execute([':id' => $producto]);
    if (!$check->fetchColumn()) throw new DomainException('El producto no existe o está inactivo.');
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT pg_advisory_xact_lock(CAST(:ubicacion AS integer),CAST(:producto AS integer))');
        $lock->execute([':ubicacion' => $ubicacion, ':producto' => $producto]);
        $q = $pdo->prepare('SELECT id_existencia,cantidad,stock_minimo FROM existencia WHERE id_ubicacion=:ubicacion AND id_producto=:producto FOR UPDATE');
        $q->execute([':ubicacion' => $ubicacion, ':producto' => $producto]);
        $antes = $q->fetch(PDO::FETCH_ASSOC);
        if ($antes) {
            $upd = $pdo->prepare('UPDATE existencia SET stock_minimo=:minimo,fecha_actualizacion=NOW() WHERE id_existencia=:id');
            $upd->execute([':minimo' => $minimo, ':id' => $antes['id_existencia']]);
            $actual = (int)$antes['cantidad'];
            $previo = (int)$antes['stock_minimo'];
        } else {
            $ins = $pdo->prepare('INSERT INTO existencia(id_ubicacion,id_producto,cantidad,stock_minimo,fecha_actualizacion) VALUES(:ubicacion,:producto,0,:minimo,NOW())');
            $ins->execute([':ubicacion' => $ubicacion, ':producto' => $producto, ':minimo' => $minimo]);
            $actual = 0;
            $previo = null;
        }
        registrarOperacion($pdo, 'EXISTENCIA', 'CONFIGURAR_STOCK_MINIMO', (int)$u['id_tienda'], ['id_producto' => $producto, 'id_ubicacion' => $ubicacion, 'stock_minimo' => $previo], ['id_producto' => $producto, 'id_ubicacion' => $ubicacion, 'stock_minimo' => $minimo]);
        $pdo->commit();
        return ['id_ubicacion' => $ubicacion, 'id_producto' => $producto, 'stock_minimo' => $minimo, 'cantidad' => $actual, 'sugerencia' => $minimo > 0 ? max(0, $minimo - $actual) : 0];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
$usuario = $_SESSION['usuario'] ?? null;
if (!is_array($usuario) || sm27Id($usuario['id_usuario'] ?? null) === 0) sm27Respuesta(false, null, 'Debe iniciar sesión.', 401);
if (!in_array((int)($usuario['id_rol'] ?? 0), [1, 2, 3, 4], true)) sm27Respuesta(false, null, 'No tiene acceso al inventario.', 403);
try {
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $accion = (string)($_GET['accion'] ?? '');
    if ($metodo === 'GET') {
        if ($accion === 'opciones') sm27Respuesta(true, sm27Opciones($pdo));
        if ($accion === 'registros') {
            $ubicacion = sm27Id($_GET['id_ubicacion'] ?? null);
            if (!$ubicacion) throw new DomainException('Seleccione una ubicación.');
            sm27Respuesta(true, sm27Registros($pdo, $ubicacion));
        }
        throw new DomainException('Acción no disponible.');
    }
    if ($metodo === 'POST' && $accion === 'guardar') {
        $datos = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($datos)) throw new DomainException('Datos no válidos.');
        sm27Respuesta(true, sm27Guardar($pdo, $datos, $usuario), 'Stock mínimo actualizado.');
    }
    sm27Respuesta(false, null, 'Método o acción no permitidos.', 405);
} catch (DomainException | JsonException $e) {
    sm27Respuesta(false, null, $e instanceof JsonException ? 'JSON inválido.' : $e->getMessage(), 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[HU27] ' . $e->getMessage());
    sm27Respuesta(false, null, 'No se pudo completar la operación. Consulte la terminal PHP.', 500);
}
