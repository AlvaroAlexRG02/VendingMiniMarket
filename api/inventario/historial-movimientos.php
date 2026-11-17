<?php
declare(strict_types=1);

/** HU-29: historial consultable de movimientos reales. API de solo lectura. */
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';

function hmResponder(bool $ok, $data = null, string $mensaje = '', int $codigo = 200): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, no-store');
    echo json_encode(['success' => $ok, 'data' => $data, 'message' => $mensaje], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function hmEntero($v, int $defecto = 0): int
{
    if ($v === null || $v === '') return $defecto;
    $n = filter_var($v, FILTER_VALIDATE_INT);
    return $n !== false && $n >= 0 ? (int)$n : $defecto;
}

function hmFecha($valor): string
{
    $valor = trim((string)$valor);
    if ($valor === '') return '';
    $obj = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    if (!$obj || $obj->format('Y-m-d') !== $valor) {
        hmResponder(false, null, 'Indique una fecha válida.', 400);
    }
    return $valor;
}

/** Restricción por tienda derivada de la sesión, nunca del navegador. */
function hmTiendasVisibles(PDO $pdo): ?array
{
    $rol = (int)($_SESSION['usuario']['id_rol'] ?? 0);
    if (in_array($rol, UBICACION_ROLES_VEN_TODAS, true)) return null;
    return array_values(array_unique(array_map(
        static fn(array $t): int => (int)$t['id_tienda'],
        ubicacionesAsignadas($pdo, (int)($_SESSION['usuario']['id_usuario'] ?? 0))
    )));
}

function hmFiltros(PDO $pdo): array
{
    $visible = hmTiendasVisibles($pdo);
    $w = ['1=1'];
    $p = [];

    if ($visible !== null) {
        if (!$visible) {
            $w[] = 'FALSE';
        } else {
            $origen = [];
            $destino = [];
            foreach ($visible as $n => $id) {
                $ko = ':visible_o_' . $n;
                $kd = ':visible_d_' . $n;
                $origen[] = $ko;
                $destino[] = $kd;
                $p[$ko] = $id;
                $p[$kd] = $id;
            }
            $w[] = '(uo.id_tienda IN (' . implode(',', $origen) .
                ') OR ud.id_tienda IN (' . implode(',', $destino) . '))';
        }
    }

    $tipo = mb_strtoupper(trim((string)($_GET['tipo'] ?? '')), 'UTF-8');
    if ($tipo === 'MERMA') $tipo = 'BAJA';
    if ($tipo !== '') {
        if (!in_array($tipo, ['ENTRADA','SALIDA','AJUSTE','BAJA','TRASLADO'], true)) {
            hmResponder(false, null, 'Tipo de movimiento inválido.', 400);
        }
        $w[] = 'm.tipo_movimiento = :tipo';
        $p[':tipo'] = $tipo;
    }

    $tienda = hmEntero($_GET['id_tienda'] ?? '');
    if ($tienda > 0) {
        if ($visible !== null && !in_array($tienda, $visible, true)) {
            hmResponder(false, null, 'No puede consultar esa tienda.', 403);
        }
        $w[] = '(uo.id_tienda = :tienda_o OR ud.id_tienda = :tienda_d)';
        $p[':tienda_o'] = $tienda;
        $p[':tienda_d'] = $tienda;
    }
    $ubicacion = hmEntero($_GET['id_ubicacion'] ?? '');
    if ($ubicacion > 0) {
        $w[] = '(m.id_ubicacion_origen = :ubi_o OR m.id_ubicacion_destino = :ubi_d)';
        $p[':ubi_o'] = $ubicacion;
        $p[':ubi_d'] = $ubicacion;
    }
    $usuario = hmEntero($_GET['id_usuario'] ?? '');
    if ($usuario > 0) {
        $w[] = 'm.id_usuario = :usuario';
        $p[':usuario'] = $usuario;
    }
    $desde = hmFecha($_GET['desde'] ?? '');
    $hasta = hmFecha($_GET['hasta'] ?? '');
    if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
        hmResponder(false, null, 'La fecha inicial no puede ser posterior a la final.', 400);
    }
    // PostgreSQL almacena las fechas de los movimientos y se consultan según Costa Rica.
    if ($desde !== '') {
        $w[] = "(COALESCE(m.fecha_confirmacion,m.fecha_movimiento) AT TIME ZONE 'America/Costa_Rica')::date >= CAST(:desde AS date)";
        $p[':desde'] = $desde;
    }
    if ($hasta !== '') {
        $w[] = "(COALESCE(m.fecha_confirmacion,m.fecha_movimiento) AT TIME ZONE 'America/Costa_Rica')::date <= CAST(:hasta AS date)";
        $p[':hasta'] = $hasta;
    }
    $q = trim((string)($_GET['busqueda'] ?? ''));
    if (mb_strlen($q, 'UTF-8') > 120) {
        hmResponder(false, null, 'La búsqueda es demasiado larga.', 400);
    }
    if ($q !== '') {
        $w[] = "(p.nombre ILIKE :q1 OR COALESCE(p.codigo_articulo_retail,'') ILIKE :q2
                OR COALESCE(p.codigo_barras,'') ILIKE :q3
                OR COALESCE(m.motivo,'') ILIKE :q4
                OR COALESCE(m.observaciones,'') ILIKE :q5
                OR TRIM(CONCAT_WS(' ',usr.nombre,usr.apellido)) ILIKE :q6
                OR CAST(m.id_movimiento AS text) ILIKE :q7)";
        foreach (range(1, 7) as $n) $p[':q'.$n] = '%'.$q.'%';
    }
    return ['where' => implode(' AND ', $w), 'params' => $p];
}

function hmBind(PDOStatement $st, array $params): void
{
    foreach ($params as $k => $v) {
        $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function hmBase(): string
{
    return " FROM public.movimiento_inventario m
      JOIN public.detalle_movimiento d ON d.id_movimiento = m.id_movimiento
      JOIN public.producto p ON p.id_producto = d.id_producto
      LEFT JOIN public.usuario usr ON usr.id_usuario = m.id_usuario
      LEFT JOIN public.ubicacion_inventario uo ON uo.id_ubicacion = m.id_ubicacion_origen
      LEFT JOIN public.ubicacion_inventario ud ON ud.id_ubicacion = m.id_ubicacion_destino
      LEFT JOIN public.tienda to_ ON to_.id_tienda = uo.id_tienda
      LEFT JOIN public.tienda td ON td.id_tienda = ud.id_tienda";
}

function hmCampos(): string
{
    return "SELECT m.id_movimiento, m.tipo_movimiento, m.estado, m.motivo, m.observaciones,
          m.id_pedido, m.id_usuario,
          COALESCE(m.fecha_confirmacion,m.fecha_movimiento) AS fecha,
          d.id_producto, d.cantidad,
          d.saldo_origen_anterior, d.saldo_origen_resultante,
          d.saldo_destino_anterior, d.saldo_destino_resultante,
          p.nombre AS producto, p.codigo_articulo_retail AS codigo_retail,
          p.codigo_barras, 
          uo.nombre AS origen, to_.nombre AS tienda_origen, uo.id_ubicacion AS id_origen,
          ud.nombre AS destino, td.nombre AS tienda_destino, ud.id_ubicacion AS id_destino,
          TRIM(CONCAT_WS(' ',usr.nombre,usr.apellido)) AS responsable";
}

function hmOpciones(PDO $pdo): void
{
    $visible = hmTiendasVisibles($pdo);
    $w = $visible === null ? '' : (!$visible ? 'WHERE FALSE' : 'WHERE t.id_tienda IN (' . implode(',', array_map('intval', $visible)) . ')');
    $tiendas = $pdo->query("SELECT t.id_tienda,t.nombre FROM public.tienda t $w ORDER BY t.nombre")->fetchAll(PDO::FETCH_ASSOC);
    $w2 = $visible === null ? '' : (!$visible ? 'WHERE FALSE' : 'WHERE u.id_tienda IN (' . implode(',', array_map('intval', $visible)) . ')');
    $ubicaciones = $pdo->query("SELECT u.id_ubicacion,u.id_tienda,u.nombre,t.nombre AS tienda
        FROM public.ubicacion_inventario u
        JOIN public.tienda t ON t.id_tienda=u.id_tienda $w2
        ORDER BY t.nombre,u.nombre")->fetchAll(PDO::FETCH_ASSOC);
    // Solo los responsables de movimientos que el usuario puede consultar.
    // Se ignoran los filtros elegidos para cargar las opciones de responsables.
    $uCond = $visible === null ? 'TRUE' : (!$visible ? 'FALSE' :
        '(uo.id_tienda IN (' . implode(',', array_map('intval', $visible)) . ') OR ud.id_tienda IN (' . implode(',', array_map('intval', $visible)) . '))');
    $usuarios = $pdo->query("SELECT DISTINCT usr.id_usuario,
        TRIM(CONCAT_WS(' ',usr.nombre,usr.apellido)) AS nombre
        FROM public.movimiento_inventario m
        JOIN public.usuario usr ON usr.id_usuario=m.id_usuario
        LEFT JOIN public.ubicacion_inventario uo ON uo.id_ubicacion=m.id_ubicacion_origen
        LEFT JOIN public.ubicacion_inventario ud ON ud.id_ubicacion=m.id_ubicacion_destino
        WHERE $uCond ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
    hmResponder(true, [
        'tiendas' => $tiendas,
        'ubicaciones' => $ubicaciones,
        'usuarios' => $usuarios,
        'puede_exportar' => in_array((int)($_SESSION['usuario']['id_rol'] ?? 0), [1,2], true)
    ]);
}

function hmFilas(PDO $pdo, bool $exportar = false): void
{
    if ($exportar && !in_array((int)($_SESSION['usuario']['id_rol'] ?? 0), [1,2], true)) {
        hmResponder(false, null, 'No tiene permiso para exportar movimientos.', 403);
    }
    $f = hmFiltros($pdo);
    $base = hmBase();
    $where = $f['where'];
    $params = $f['params'];

    if (!$exportar) {
        $count = $pdo->prepare('SELECT COUNT(*) ' . $base . ' WHERE ' . $where);
        hmBind($count, $params);
        $count->execute();
        $total = (int)$count->fetchColumn();
        $pagina = max(1, hmEntero($_GET['pagina'] ?? 1, 1));
        $porPagina = min(75, max(10, hmEntero($_GET['por_pagina'] ?? 30, 30)));
        $paginas = max(1, (int)ceil($total / $porPagina));
        $pagina = min($pagina, $paginas);
        $sql = hmCampos() . $base . ' WHERE ' . $where .
            ' ORDER BY COALESCE(m.fecha_confirmacion,m.fecha_movimiento) DESC, m.id_movimiento DESC, p.nombre ASC
              LIMIT :limite OFFSET :offset';
        $st = $pdo->prepare($sql);
        hmBind($st, $params);
        $st->bindValue(':limite', $porPagina, PDO::PARAM_INT);
        $st->bindValue(':offset', ($pagina-1)*$porPagina, PDO::PARAM_INT);
        $st->execute();
        hmResponder(true, [
            'movimientos' => $st->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'por_pagina' => $porPagina,
        ]);
    }

    // Descarga únicamente movimientos visibles según rol y filtros activos.
    $sql = hmCampos() . $base . ' WHERE ' . $where .
        ' ORDER BY COALESCE(m.fecha_confirmacion,m.fecha_movimiento) DESC, m.id_movimiento DESC LIMIT 50000';
    $st = $pdo->prepare($sql);
    hmBind($st, $params);
    $st->execute();
    $nombre = 'historial-movimientos-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Cache-Control: private, no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Fecha','Referencia','Código retail','Producto','Tipo','Origen','Destino','Cantidad','Saldo anterior origen','Saldo final origen','Saldo anterior destino','Saldo final destino','Responsable','Estado','Motivo','Observaciones'], ';');
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $ref = !empty($r['id_pedido']) ? 'PED-' . str_pad((string)$r['id_pedido'],6,'0',STR_PAD_LEFT)
              : 'MOV-' . str_pad((string)$r['id_movimiento'],6,'0',STR_PAD_LEFT);
        $celdas = [
          $r['fecha'],$ref,$r['codigo_retail'],$r['producto'],$r['tipo_movimiento'],
          trim((string)($r['tienda_origen'] ?? '') . ' / ' . (string)($r['origen'] ?? ''),' / '),
          trim((string)($r['tienda_destino'] ?? '') . ' / ' . (string)($r['destino'] ?? ''),' / '),
          $r['cantidad'],$r['saldo_origen_anterior'],$r['saldo_origen_resultante'],
          $r['saldo_destino_anterior'],$r['saldo_destino_resultante'],
          $r['responsable'],$r['estado'],$r['motivo'],$r['observaciones'],
        ];
        // Proteger al abrir el CSV en Excel contra fórmulas incrustadas en datos de texto.
        foreach ($celdas as $i => $valor) {
            if ($valor !== null && $i !== 7 && !in_array($i,[8,9,10,11],true)) {
                $cadena = (string)$valor;
                if (preg_match('/^[\s]*[=+@\-]/u', $cadena)) $celdas[$i] = "'" . $cadena;
            }
        }
        fputcsv($out, $celdas, ';');
    }
    fclose($out);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') hmResponder(false, null, 'Método no permitido.', 405);
if (empty($_SESSION['usuario'])) hmResponder(false, null, 'Debe iniciar sesión.', 401);

try {
    $accion = (string)($_GET['accion'] ?? 'listar');
    if ($accion === 'opciones') hmOpciones($pdo);
    if ($accion === 'listar') hmFilas($pdo);
    if ($accion === 'exportar') hmFilas($pdo, true);
    if (in_array($accion, ['xlsx','pdf','imprimir'], true)) {
        require_once __DIR__ . '/historial-movimientos-formatos.php';
        hmExportar29($pdo, $accion);
    }
    hmResponder(false, null, 'Acción no reconocida.', 404);
} catch (Throwable $e) {
    error_log('[historial-movimientos] ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    hmResponder(false, null, 'No se pudo consultar el historial. Revise el registro del servidor.', 500);
}
