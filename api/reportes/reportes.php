<?php

/**
 * API de reportes — VendingMiniMarket
 *
 * HU-49  Ventas por tienda y periodo           accion=ventas
 * HU-50  Utilidad bruta estimada por tienda    accion=utilidad
 * HU-51  Inventario por ubicacion              accion=inventario
 * HU-52  Rotacion, bajas y perdidas            accion=rotacion
 * HU-53  Cierre operativo diario              accion=cierre_preparar|cierre_confirmar|cierre_reabrir|cierre_historial
 * Apoyo: accion=filtros (tiendas permitidas, categorias, proveedores)
 *
 * Alcance por rol:
 *   Administrador / Gerente General -> cualquier tienda o todas.
 *   Encargado                        -> solo sus tiendas asignadas (usuario_tienda).
 *   Dependiente                      -> sin acceso.
 *
 * Las ventas salen de venta_manual (con movimiento CONFIRMADO) y, si existe la
 * tabla venta_nayax (ver basededatos/ReportesNayax.sql), de las ventas Nayax
 * confirmadas.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';
require_once __DIR__ . '/../../config/reportes_lib.php';
require_once __DIR__ . '/../../config/reportes_pdf.php';


/** Usuario con acceso a reportes: Administrador, Gerente General o Encargado. */
function exigirAccesoReportes(): array
{
    if (empty($_SESSION['usuario'])) {
        respuesta(false, null, 'Debe iniciar sesión.', 401);
    }

    if (!esAdministrador() && !esGerenteGeneral() && !esEncargado()) {
        respuesta(false, null, 'No tiene permisos para consultar reportes.', 403);
    }

    return $_SESSION['usuario'];
}

/** Lee y valida los filtros comunes. Devuelve el arreglo de filtros ya normalizado. */
function leerFiltros(PDO $pdo, bool $requierePeriodo): array
{
    $tiendas = tiendasPermitidas($pdo);

    if (!$tiendas) {
        respuesta(false, null, 'No tiene tiendas asignadas para consultar reportes.', 403);
    }

    $idTienda = (int)($_GET['id_tienda'] ?? 0);

    // Selección múltiple (exportación): id_tiendas=1,2,3
    $seleccion = array_values(array_unique(array_filter(
        array_map('intval', explode(',', (string)($_GET['id_tiendas'] ?? ''))),
        fn ($id) => $id > 0
    )));

    foreach ($seleccion as $id) {
        if (!isset($tiendas[$id])) {
            respuesta(false, null, 'No tiene acceso a una de las tiendas seleccionadas.', 403);
        }
    }

    if ($idTienda > 0 && !isset($tiendas[$idTienda])) {
        respuesta(false, null, 'No tiene acceso a la tienda seleccionada.', 403);
    }

    if ($seleccion) {
        $idsTiendas = $seleccion;
        $idTienda = count($seleccion) === 1 ? $seleccion[0] : 0;
    } else {
        $idsTiendas = $idTienda > 0 ? [$idTienda] : array_keys($tiendas);
    }

    $inicio = trim((string)($_GET['fecha_inicio'] ?? ''));
    $fin    = trim((string)($_GET['fecha_fin'] ?? ''));

    if ($requierePeriodo) {
        if (!fechaValida($inicio) || !fechaValida($fin) || $inicio > $fin) {
            respuesta(false, null, 'Indique un periodo válido: la fecha inicial debe ser anterior o igual a la final.', 400);
        }
    }

    return [
        'tiendas'      => $tiendas,
        'ids_tiendas'  => $idsTiendas,
        'id_tienda'    => $idTienda,
        'inicio'       => $inicio,
        'fin'          => $fin,
        'id_categoria' => (int)($_GET['id_categoria'] ?? 0),
        'id_proveedor' => (int)($_GET['id_proveedor'] ?? 0),
        'ids_proveedores' => idsProveedoresSolicitados(),
        'ids_productos' => idsDeParametro('ids_productos'),
        'id_ubicacion' => (int)($_GET['id_ubicacion'] ?? 0),
        'busqueda'     => trim((string)($_GET['busqueda'] ?? '')),
        'estado'       => strtoupper(trim((string)($_GET['estado'] ?? ''))),
    ];
}

/** Lista de ids positivos de un parámetro GET "a,b,c". */
function idsDeParametro(string $nombre): array
{
    $ids = array_map('intval', explode(',', (string)($_GET[$nombre] ?? '')));

    return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
}

/** Proveedores pedidos: ids_proveedores=1,2,3 (uno, varios o ninguno = todos) o el id_proveedor antiguo. */
function idsProveedoresSolicitados(): array
{
    $ids = array_map('intval', explode(',', (string)($_GET['ids_proveedores'] ?? '')));
    $ids[] = (int)($_GET['id_proveedor'] ?? 0);

    return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
}

/** Condiciones SQL sobre el producto (alias $p) según categoría, proveedor y búsqueda. */
function condicionesProducto(array $f, Parametros $par, string $p = 'p'): array
{
    $donde = [];

    if ($f['id_categoria'] > 0) {
        $donde[] = "$p.id_categoria = " . $par->agregar($f['id_categoria']);
    }

    // Solo productos relacionados (activamente) con alguno de los proveedores elegidos.
    if (!empty($f['ids_proveedores'])) {
        $marcas = array_map(fn ($id) => $par->agregar($id), $f['ids_proveedores']);
        $donde[] = "EXISTS (SELECT 1 FROM producto_proveedor pp
                            WHERE pp.id_producto = $p.id_producto
                              AND pp.id_proveedor IN (" . implode(',', $marcas) . ')
                              AND pp.estado = TRUE)';
    }

    // Productos elegidos de forma explícita (se combinan con los proveedores: ambos deben cumplirse).
    if (!empty($f['ids_productos'])) {
        $marcas = array_map(fn ($id) => $par->agregar($id), $f['ids_productos']);
        $donde[] = "$p.id_producto IN (" . implode(',', $marcas) . ')';
    }

    if ($f['busqueda'] !== '') {
        $marca = $par->agregar('%' . $f['busqueda'] . '%');
        $donde[] = "($p.nombre ILIKE $marca OR COALESCE($p.codigo_barras, '') ILIKE $marca)";
    }

    return $donde;
}

/** Ejecuta "WITH base AS (...) SELECT ... FROM base ..." con los parámetros acumulados. */
function consultarSobreBase(PDO $pdo, string $base, Parametros $par, string $select, string $extra = ''): array
{
    $st = $pdo->prepare("WITH base AS ($base) $select FROM base $extra");
    $st->execute($par->valores);
    return $st->fetchAll();
}

/* ==========================================================
   FILTROS
========================================================== */

function accionFiltros(PDO $pdo): void
{
    exigirAccesoReportes();

    $tiendas = [];
    foreach (tiendasPermitidas($pdo) as $id => $nombre) {
        $tiendas[] = ['id_tienda' => $id, 'nombre' => $nombre];
    }

    $idsTiendas = listaEnteros(array_column($tiendas, 'id_tienda'));

    // Ubicaciones, categorías, proveedores y disponibilidad de Nayax en una sola consulta.
    $fila = $pdo->query("
        SELECT
            COALESCE((SELECT json_agg(x ORDER BY x.nombre) FROM (
                SELECT id_ubicacion, id_tienda, nombre, tipo FROM ubicacion_inventario
                WHERE estado = TRUE AND id_tienda IN ($idsTiendas)) x), '[]'::json) AS ubicaciones,
            COALESCE((SELECT json_agg(x ORDER BY x.nombre) FROM (
                SELECT id_categoria, nombre FROM categoria WHERE estado = TRUE) x), '[]'::json) AS categorias,
            COALESCE((SELECT json_agg(x ORDER BY x.nombre) FROM (
                SELECT id_proveedor, nombre FROM proveedor WHERE estado = TRUE) x), '[]'::json) AS proveedores,
            (to_regclass('public.venta_nayax') IS NOT NULL) AS nayax
    ")->fetch();

    respuesta(true, [
        'tiendas'      => $tiendas,
        'ubicaciones'  => json_decode($fila['ubicaciones'], true),
        'categorias'   => json_decode($fila['categorias'], true),
        'proveedores'  => json_decode($fila['proveedores'], true),
        'puede_elegir_tienda' => count($tiendas) > 1,
        'puede_ver_todas'     => esAdministradorOGerente(),
        'puede_ver_utilidad'  => esAdministradorOGerente(),
        'puede_exportar'      => esAdministradorOGerente(),
        'nayax_disponible'    => in_array($fila['nayax'], [true, 't', 1, '1'], true),
    ]);
}

/** Productos activos relacionados con los proveedores indicados (para elegirlos en los filtros). */
function accionProductosProveedor(PDO $pdo): void
{
    exigirAccesoReportes();

    $ids = idsProveedoresSolicitados();

    if (!$ids) {
        respuesta(true, []);
    }

    $par = new Parametros();
    $marcas = array_map(fn ($id) => $par->agregar($id), $ids);

    $st = $pdo->prepare('
        SELECT DISTINCT p.id_producto, p.nombre
        FROM producto p
        JOIN producto_proveedor pp ON pp.id_producto = p.id_producto
        WHERE pp.estado = TRUE
          AND p.estado = TRUE
          AND pp.id_proveedor IN (' . implode(',', $marcas) . ')
        ORDER BY p.nombre, p.id_producto
    ');
    $st->execute($par->valores);

    respuesta(true, $st->fetchAll());
}

/* ==========================================================
   BASE COMÚN DE VENTAS (HU-49 y HU-50)
========================================================== */

function baseVentas(PDO $pdo, array $f, Parametros $par, bool $conUbicacion): string
{
    $condProducto = condicionesProducto($f, $par);
    $filtroProducto = $condProducto ? 'AND ' . implode(' AND ', $condProducto) : '';
    $lineas = sqlLineasVenta($pdo, $f, $par);

    $filtroUbicacion = '';
    if ($conUbicacion && $f['id_ubicacion'] > 0) {
        $filtroUbicacion = 'AND l.id_tienda = (SELECT id_tienda FROM ubicacion_inventario WHERE id_ubicacion = '
            . $par->agregar($f['id_ubicacion']) . ')';
    }

    return "
        WITH lineas AS ($lineas)
        SELECT l.*, p.nombre AS producto, COALESCE(c.nombre, 'Sin categoría') AS categoria, t.nombre AS tienda
        FROM lineas l
        JOIN producto p ON p.id_producto = l.id_producto
        LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
        JOIN tienda t ON t.id_tienda = l.id_tienda
        WHERE TRUE $filtroProducto $filtroUbicacion
    ";
}

/* ==========================================================
   HU-49 — VENTAS
========================================================== */

function accionVentas(PDO $pdo): void
{
    exigirAccesoReportes();
    $f = leerFiltros($pdo, true);
    $par = new Parametros();
    $base = baseVentas($pdo, $f, $par, false);

    $resumen = consultarSobreBase($pdo, $base, $par, "
        SELECT COUNT(DISTINCT fuente || id_venta) AS ventas,
               COALESCE(SUM(cantidad), 0) AS unidades,
               COALESCE(SUM(subtotal), 0) AS ingresos,
               COALESCE(SUM(impuesto), 0) AS impuesto,
               COALESCE(SUM(total), 0) AS total,
               COALESCE(SUM(costo_total), 0) AS costo,
               COUNT(*) FILTER (WHERE sin_costo) AS lineas_sin_costo,
               COUNT(*) FILTER (WHERE fuente = 'NAYAX') AS lineas_nayax
    ")[0];

    $porTienda = consultarSobreBase($pdo, $base, $par, "
        SELECT tienda, id_tienda, COUNT(DISTINCT fuente || id_venta) AS ventas,
               SUM(cantidad) AS unidades, SUM(subtotal) AS ingresos, SUM(total) AS total, SUM(costo_total) AS costo
    ", 'GROUP BY tienda, id_tienda ORDER BY SUM(subtotal) DESC');

    $porDia = consultarSobreBase($pdo, $base, $par, "
        SELECT fecha, SUM(cantidad) AS unidades, SUM(subtotal) AS ingresos
    ", 'GROUP BY fecha ORDER BY fecha');

    $detalle = consultarSobreBase($pdo, $base, $par, "
        SELECT tienda, producto, categoria, SUM(cantidad) AS unidades, SUM(subtotal) AS ingresos,
               SUM(impuesto) AS impuesto, SUM(total) AS total, SUM(costo_total) AS costo,
               STRING_AGG(DISTINCT fuente, ', ') AS fuente
    ", 'GROUP BY tienda, producto, categoria ORDER BY SUM(subtotal) DESC, producto LIMIT 1000');

    respuesta(true, [
        'resumen'    => numerico([$resumen], ['ventas', 'unidades', 'ingresos', 'impuesto', 'total', 'costo', 'lineas_sin_costo', 'lineas_nayax'])[0],
        'por_tienda' => numerico($porTienda, ['ventas', 'unidades', 'ingresos', 'total', 'costo']),
        'por_dia'    => numerico($porDia, ['unidades', 'ingresos']),
        'detalle'    => numerico($detalle, ['unidades', 'ingresos', 'impuesto', 'total', 'costo']),
        'nayax_disponible' => tablaNayaxExiste($pdo),
    ]);
}

/* ==========================================================
   HU-50 — UTILIDAD BRUTA ESTIMADA
========================================================== */

function accionUtilidad(PDO $pdo): void
{
    exigirAccesoReportes();

    if (!esAdministradorOGerente()) {
        respuesta(false, null, 'Solo Administración o Gerencia pueden consultar la utilidad.', 403);
    }

    $f = leerFiltros($pdo, true);
    $par = new Parametros();
    $base = baseVentas($pdo, $f, $par, true);

    $utilidad = 'SUM(subtotal) - SUM(costo_total)';
    $margen   = 'CASE WHEN SUM(subtotal) > 0 THEN ROUND((SUM(subtotal) - SUM(costo_total)) / SUM(subtotal) * 100, 2) ELSE 0 END';

    $resumen = consultarSobreBase($pdo, $base, $par, "
        SELECT COALESCE(SUM(subtotal), 0) AS ingresos, COALESCE(SUM(costo_total), 0) AS costo,
               COALESCE($utilidad, 0) AS utilidad, COALESCE($margen, 0) AS margen,
               COUNT(*) FILTER (WHERE sin_costo) AS lineas_sin_costo
    ")[0];

    $porTienda = consultarSobreBase($pdo, $base, $par, "
        SELECT tienda, id_tienda, SUM(cantidad) AS unidades, SUM(subtotal) AS ingresos, SUM(costo_total) AS costo,
               $utilidad AS utilidad, $margen AS margen
    ", "GROUP BY tienda, id_tienda ORDER BY $utilidad DESC");

    $detalle = consultarSobreBase($pdo, $base, $par, "
        SELECT tienda, producto, categoria, SUM(cantidad) AS unidades, SUM(subtotal) AS ingresos,
               SUM(costo_total) AS costo, $utilidad AS utilidad, $margen AS margen
    ", "GROUP BY tienda, producto, categoria ORDER BY $utilidad DESC, producto LIMIT 1000");

    respuesta(true, [
        'resumen'    => numerico([$resumen], ['ingresos', 'costo', 'utilidad', 'margen', 'lineas_sin_costo'])[0],
        'por_tienda' => numerico($porTienda, ['unidades', 'ingresos', 'costo', 'utilidad', 'margen']),
        'detalle'    => numerico($detalle, ['unidades', 'ingresos', 'costo', 'utilidad', 'margen']),
    ]);
}

/* ==========================================================
   HU-51 — INVENTARIO POR UBICACIÓN
========================================================== */

function accionInventario(PDO $pdo): void
{
    exigirAccesoReportes();
    $f = leerFiltros($pdo, false);
    $par = new Parametros();

    $donde = [
        'u.estado = TRUE',
        'p.estado = TRUE',
        'u.id_tienda IN (' . listaEnteros($f['ids_tiendas']) . ')',
    ];

    if ($f['id_ubicacion'] > 0) {
        $donde[] = 'u.id_ubicacion = ' . $par->agregar($f['id_ubicacion']);
    }

    $donde = array_merge($donde, condicionesProducto($f, $par));

    $estadoSql = "CASE
        WHEN e.cantidad < 0 THEN 'NEGATIVO'
        WHEN e.cantidad = 0 THEN 'AGOTADO'
        WHEN e.cantidad <= e.stock_minimo THEN 'BAJO_MINIMO'
        ELSE 'NORMAL' END";

    $base = "
        SELECT t.nombre AS tienda, u.id_ubicacion, u.nombre AS ubicacion, u.tipo,
               p.nombre AS producto, COALESCE(c.nombre, 'Sin categoría') AS categoria,
               e.cantidad, e.stock_minimo, $estadoSql AS estado
        FROM existencia e
        JOIN ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion
        JOIN tienda t ON t.id_tienda = u.id_tienda
        JOIN producto p ON p.id_producto = e.id_producto
        LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
        WHERE " . implode(' AND ', $donde);

    $resumen = consultarSobreBase($pdo, $base, $par, "
        SELECT COUNT(*) AS lineas, COALESCE(SUM(GREATEST(cantidad, 0)), 0) AS unidades,
               COUNT(*) FILTER (WHERE estado = 'AGOTADO') AS agotados,
               COUNT(*) FILTER (WHERE estado = 'BAJO_MINIMO') AS bajo_minimo,
               COUNT(*) FILTER (WHERE estado = 'NEGATIVO') AS negativos,
               COUNT(DISTINCT id_ubicacion) AS ubicaciones
    ")[0];

    $porUbicacion = consultarSobreBase($pdo, $base, $par, "
        SELECT tienda, ubicacion, tipo, COALESCE(SUM(GREATEST(cantidad, 0)), 0) AS unidades,
               COUNT(*) FILTER (WHERE estado <> 'NORMAL') AS criticos, COUNT(*) AS productos
    ", 'GROUP BY tienda, ubicacion, tipo ORDER BY tienda, tipo, ubicacion');

    $filtroEstado = '';
    if (in_array($f['estado'], ['NORMAL', 'AGOTADO', 'BAJO_MINIMO', 'NEGATIVO'], true)) {
        $filtroEstado = 'WHERE estado = ' . $par->agregar($f['estado']);
    } elseif ($f['estado'] === 'CRITICO') {
        $filtroEstado = "WHERE estado <> 'NORMAL'";
    }

    $detalle = consultarSobreBase($pdo, $base, $par, '
        SELECT tienda, ubicacion, tipo, producto, categoria, cantidad, stock_minimo, estado
    ', "$filtroEstado ORDER BY CASE estado WHEN 'NEGATIVO' THEN 0 WHEN 'AGOTADO' THEN 1 WHEN 'BAJO_MINIMO' THEN 2 ELSE 3 END,
        tienda, ubicacion, producto LIMIT 3000");

    respuesta(true, [
        'resumen'       => numerico([$resumen], ['lineas', 'unidades', 'agotados', 'bajo_minimo', 'negativos', 'ubicaciones'])[0],
        'por_ubicacion' => numerico($porUbicacion, ['unidades', 'criticos', 'productos']),
        'detalle'       => numerico($detalle, ['cantidad', 'stock_minimo']),
    ]);
}

/* ==========================================================
   HU-52 — ROTACIÓN, BAJAS Y PÉRDIDAS
========================================================== */

function accionRotacion(PDO $pdo): void
{
    exigirAccesoReportes();
    $f = leerFiltros($pdo, true);
    $par = new Parametros();

    $condProducto = condicionesProducto($f, $par);
    $filtroProducto = $condProducto ? 'AND ' . implode(' AND ', $condProducto) : '';
    $tiendas = listaEnteros($f['ids_tiendas']);
    $lineas = sqlLineasVenta($pdo, $f, $par);

    // Pares tienda-producto con existencia, para listar también lo que no se vendió.
    $st = $pdo->prepare("
        WITH lineas AS ($lineas),
        ventas AS (
            SELECT id_tienda, id_producto, SUM(cantidad) AS unidades, SUM(subtotal) AS ingresos
            FROM lineas GROUP BY id_tienda, id_producto
        ),
        stock AS (
            SELECT u.id_tienda, e.id_producto, SUM(GREATEST(e.cantidad, 0)) AS stock
            FROM existencia e
            JOIN ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion AND u.estado = TRUE
            WHERE u.id_tienda IN ($tiendas)
            GROUP BY u.id_tienda, e.id_producto
        ),
        pares AS (
            SELECT COALESCE(s.id_tienda, v.id_tienda) AS id_tienda, COALESCE(s.id_producto, v.id_producto) AS id_producto,
                   COALESCE(s.stock, 0) AS stock, COALESCE(v.unidades, 0) AS unidades, COALESCE(v.ingresos, 0) AS ingresos
            FROM stock s FULL JOIN ventas v ON v.id_tienda = s.id_tienda AND v.id_producto = s.id_producto
        )
        SELECT t.nombre AS tienda, p.nombre AS producto, COALESCE(c.nombre, 'Sin categoría') AS categoria,
               pa.unidades, pa.ingresos, pa.stock
        FROM pares pa
        JOIN producto p ON p.id_producto = pa.id_producto
        JOIN tienda t ON t.id_tienda = pa.id_tienda
        LEFT JOIN categoria c ON c.id_categoria = p.id_categoria
        WHERE p.estado = TRUE AND (pa.stock > 0 OR pa.unidades > 0) $filtroProducto
    ");
    $st->execute($par->valores);
    $pares = numerico($st->fetchAll(), ['unidades', 'ingresos', 'stock']);

    $masVendidos = $pares;
    usort($masVendidos, fn ($a, $b) => [$b['unidades'], $b['ingresos']] <=> [$a['unidades'], $a['ingresos']]);
    $limite = max(5, min(50, (int)($_GET['limite'] ?? 15)));
    $masVendidos = array_slice(array_values(array_filter($masVendidos, fn ($x) => $x['unidades'] > 0)), 0, $limite);

    $menosVendidos = array_values(array_filter($pares, fn ($x) => $x['stock'] > 0));
    usort($menosVendidos, fn ($a, $b) => [$a['unidades'], -$a['stock']] <=> [$b['unidades'], -$b['stock']]);
    $menosVendidos = array_slice($menosVendidos, 0, $limite);

    // Bajas confirmadas del periodo, valoradas al costo vigente de la tienda.
    $par2 = new Parametros();
    $condProductoBajas = condicionesProducto($f, $par2);
    $filtroProductoBajas = $condProductoBajas ? 'AND ' . implode(' AND ', $condProductoBajas) : '';
    $inicio = $par2->agregar($f['inicio']);
    $fin = $par2->agregar($f['fin']);
    $zona = ZONA_REPORTES;
    $costo = sqlCostoVigente('d.id_producto', 'u.id_tienda');

    $bajasSt = $pdo->prepare("
        SELECT (m.fecha_movimiento AT TIME ZONE '$zona')::date AS fecha, t.nombre AS tienda, u.nombre AS ubicacion,
               p.nombre AS producto, m.motivo, d.cantidad, COALESCE($costo, 0) AS costo_unitario,
               d.cantidad * COALESCE($costo, 0) AS valor
        FROM movimiento_inventario m
        JOIN detalle_movimiento d ON d.id_movimiento = m.id_movimiento
        JOIN ubicacion_inventario u ON u.id_ubicacion = m.id_ubicacion_origen
        JOIN tienda t ON t.id_tienda = u.id_tienda
        JOIN producto p ON p.id_producto = d.id_producto
        WHERE m.tipo_movimiento = 'BAJA' AND m.estado = 'CONFIRMADO'
          AND u.id_tienda IN ($tiendas)
          AND (m.fecha_movimiento AT TIME ZONE '$zona')::date BETWEEN CAST($inicio AS DATE) AND CAST($fin AS DATE)
          $filtroProductoBajas
        ORDER BY m.fecha_movimiento DESC, p.nombre
        LIMIT 1000
    ");
    $bajasSt->execute($par2->valores);
    $bajas = numerico($bajasSt->fetchAll(), ['cantidad', 'costo_unitario', 'valor']);

    $porMotivo = [];
    foreach ($bajas as $baja) {
        $motivo = $baja['motivo'] ?: 'SIN MOTIVO';
        $porMotivo[$motivo] ??= ['motivo' => $motivo, 'unidades' => 0.0, 'valor' => 0.0, 'es_perdida' => in_array($motivo, MOTIVOS_PERDIDA, true)];
        $porMotivo[$motivo]['unidades'] += $baja['cantidad'];
        $porMotivo[$motivo]['valor'] += $baja['valor'];
    }
    $porMotivo = array_values($porMotivo);
    usort($porMotivo, fn ($a, $b) => $b['valor'] <=> $a['valor']);

    respuesta(true, [
        'mas_vendidos'     => $masVendidos,
        'menos_vendidos'   => $menosVendidos,
        'bajas'            => $bajas,
        'bajas_por_motivo' => $porMotivo,
        'resumen' => [
            'unidades_vendidas'   => array_sum(array_column($pares, 'unidades')),
            'productos_sin_venta' => count(array_filter($pares, fn ($x) => $x['unidades'] == 0 && $x['stock'] > 0)),
            'unidades_baja'       => array_sum(array_column($bajas, 'cantidad')),
            'valor_bajas'         => array_sum(array_column($bajas, 'valor')),
            'valor_perdidas'      => array_sum(array_map(fn ($b) => in_array($b['motivo'], MOTIVOS_PERDIDA, true) ? $b['valor'] : 0, $bajas)),
        ],
    ]);
}

/* ==========================================================
   HU-53 — CIERRE OPERATIVO DIARIO POR TIENDA
   (tablas y bloqueo: basededatos/CierreDiario.sql)
========================================================== */

function cierreDisponible(PDO $pdo): bool
{
    return in_array(
        $pdo->query("SELECT to_regclass('public.cierre_diario') IS NOT NULL")->fetchColumn(),
        [true, 't', 1, '1'],
        true
    );
}

function exigirCierreDisponible(PDO $pdo): void
{
    if (!cierreDisponible($pdo)) {
        respuesta(false, null, 'El cierre diario aún no está habilitado en la base de datos.', 503);
    }
}

function entradaJson(): array
{
    $entrada = json_decode(file_get_contents('php://input') ?: '', true);
    return is_array($entrada) ? $entrada : [];
}

function exigirMetodoPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respuesta(false, null, 'Método no permitido.', 405);
    }
}

/** Valida tienda (dentro del alcance del usuario) y fecha (no futura) del cierre. */
function validarTiendaFechaCierre(PDO $pdo, $idTienda, $fecha): array
{
    $tiendas  = tiendasPermitidas($pdo);
    $idTienda = (int)$idTienda;
    $fecha    = trim((string)$fecha);

    if ($idTienda <= 0 || !isset($tiendas[$idTienda])) {
        respuesta(false, null, 'Seleccione una tienda a la que tenga acceso.', 403);
    }

    if (!fechaValida($fecha) || $fecha > hoyCostaRica()) {
        respuesta(false, null, 'Indique una fecha válida que no sea futura.', 400);
    }

    return [$idTienda, $tiendas[$idTienda], $fecha];
}

/** Consolida el día de una tienda: ventas, movimientos, diferencias y alertas. */
function calcularResumenCierre(PDO $pdo, int $idTienda, string $nombreTienda, string $fecha): array
{
    $zona = ZONA_REPORTES;
    $f = ['inicio' => $fecha, 'fin' => $fecha, 'ids_tiendas' => [$idTienda]];

    // Ventas confirmadas (manuales + Nayax) por fuente.
    $par = new Parametros();
    $lineas = sqlLineasVenta($pdo, $f, $par);
    $st = $pdo->prepare("
        WITH lineas AS ($lineas)
        SELECT fuente, COUNT(DISTINCT id_venta) AS ventas, COALESCE(SUM(cantidad), 0) AS unidades,
               COALESCE(SUM(subtotal), 0) AS subtotal, COALESCE(SUM(impuesto), 0) AS impuesto,
               COALESCE(SUM(total), 0) AS total, COALESCE(SUM(costo_total), 0) AS costo
        FROM lineas GROUP BY fuente ORDER BY fuente
    ");
    $st->execute($par->valores);
    $porFuente = numerico($st->fetchAll(), ['ventas', 'unidades', 'subtotal', 'impuesto', 'total', 'costo']);

    $totales = ['ventas' => 0.0, 'unidades' => 0.0, 'subtotal' => 0.0, 'impuesto' => 0.0, 'total' => 0.0, 'costo' => 0.0];
    foreach ($porFuente as $fila) {
        foreach ($totales as $clave => $_) {
            $totales[$clave] += $fila[$clave];
        }
    }

    // Ventas manuales del día que todavía no tienen su movimiento de inventario confirmado.
    $st = $pdo->prepare("
        SELECT COUNT(*) AS cantidad, COALESCE(SUM(v.total), 0) AS total
        FROM venta_manual v
        LEFT JOIN movimiento_inventario m ON m.id_movimiento = v.id_movimiento
        WHERE v.id_tienda = :tienda
          AND (v.fecha AT TIME ZONE '$zona')::date = CAST(:fecha AS DATE)
          AND (m.id_movimiento IS NULL OR m.estado <> 'CONFIRMADO')
    ");
    $st->execute([':tienda' => $idTienda, ':fecha' => $fecha]);
    $sinConfirmar = numerico([$st->fetch()], ['cantidad', 'total'])[0];

    // Movimientos de inventario del día en las ubicaciones de la tienda.
    $st = $pdo->prepare("
        SELECT m.tipo_movimiento AS tipo, m.estado, COUNT(DISTINCT m.id_movimiento) AS movimientos,
               COALESCE(SUM(d.cantidad), 0) AS unidades
        FROM movimiento_inventario m
        JOIN detalle_movimiento d ON d.id_movimiento = m.id_movimiento
        WHERE (m.fecha_movimiento AT TIME ZONE '$zona')::date = CAST(:fecha AS DATE)
          AND (m.id_ubicacion_origen IN (SELECT id_ubicacion FROM ubicacion_inventario WHERE id_tienda = :tienda_o)
            OR m.id_ubicacion_destino IN (SELECT id_ubicacion FROM ubicacion_inventario WHERE id_tienda = :tienda_d))
        GROUP BY m.tipo_movimiento, m.estado
        ORDER BY m.tipo_movimiento, m.estado
    ");
    $st->execute([':fecha' => $fecha, ':tienda_o' => $idTienda, ':tienda_d' => $idTienda]);
    $movimientos = numerico($st->fetchAll(), ['movimientos', 'unidades']);
    $movimientosPendientes = array_sum(array_map(
        fn ($m) => $m['estado'] === 'PENDIENTE' ? $m['movimientos'] : 0,
        $movimientos
    ));

    // Diferencias: unidades vendidas vs. salidas de inventario registradas por venta.
    $par = new Parametros();
    $lineas = sqlLineasVenta($pdo, $f, $par);
    $fechaSalidas = $par->agregar($fecha);
    $st = $pdo->prepare("
        WITH lineas AS ($lineas),
        vendido AS (SELECT id_producto, SUM(cantidad) AS vendida FROM lineas GROUP BY id_producto),
        salidas AS (
            SELECT d.id_producto, SUM(d.cantidad) AS salida
            FROM movimiento_inventario m
            JOIN detalle_movimiento d ON d.id_movimiento = m.id_movimiento
            JOIN ubicacion_inventario u ON u.id_ubicacion = m.id_ubicacion_origen
            WHERE m.tipo_movimiento = 'SALIDA' AND m.estado = 'CONFIRMADO' AND m.motivo = 'Venta manual'
              AND u.id_tienda = $idTienda
              AND (m.fecha_movimiento AT TIME ZONE '$zona')::date = CAST($fechaSalidas AS DATE)
            GROUP BY d.id_producto
        )
        SELECT p.nombre AS producto, COALESCE(v.vendida, 0) AS vendida, COALESCE(s.salida, 0) AS salida,
               COALESCE(v.vendida, 0) - COALESCE(s.salida, 0) AS diferencia
        FROM vendido v
        FULL JOIN salidas s ON s.id_producto = v.id_producto
        JOIN producto p ON p.id_producto = COALESCE(v.id_producto, s.id_producto)
        WHERE COALESCE(v.vendida, 0) <> COALESCE(s.salida, 0)
        ORDER BY ABS(COALESCE(v.vendida, 0) - COALESCE(s.salida, 0)) DESC, p.nombre
        LIMIT 100
    ");
    $st->execute($par->valores);
    $diferencias = numerico($st->fetchAll(), ['vendida', 'salida', 'diferencia']);

    // Alertas de inventario de las ubicaciones de la tienda.
    $st = $pdo->prepare("
        SELECT COUNT(*) FILTER (WHERE (a.fecha_generacion AT TIME ZONE '$zona')::date = CAST(:fecha AS DATE)) AS generadas,
               COUNT(*) FILTER (WHERE a.estado = 'PENDIENTE') AS pendientes
        FROM alerta_inventario a
        JOIN ubicacion_inventario u ON u.id_ubicacion = a.id_ubicacion
        WHERE u.id_tienda = :tienda
    ");
    $st->execute([':fecha' => $fecha, ':tienda' => $idTienda]);
    $alertas = numerico([$st->fetch()], ['generadas', 'pendientes'])[0];

    return [
        'fecha'       => $fecha,
        'id_tienda'   => $idTienda,
        'tienda'      => $nombreTienda,
        'ventas'      => ['por_fuente' => $porFuente, 'totales' => $totales],
        'ventas_sin_confirmar' => $sinConfirmar,
        'movimientos' => $movimientos,
        'movimientos_pendientes' => $movimientosPendientes,
        'diferencias' => ['cantidad' => count($diferencias), 'detalle' => $diferencias],
        'alertas'     => $alertas,
    ];
}

function cierreExistente(PDO $pdo, int $idTienda, string $fecha): ?array
{
    $st = $pdo->prepare("
        SELECT c.id_cierre, c.estado, c.version, c.fecha_cierre, c.observaciones, c.resumen,
               TRIM(u.nombre || ' ' || COALESCE(u.apellido, '')) AS usuario_cierra
        FROM cierre_diario c
        JOIN usuario u ON u.id_usuario = c.id_usuario_cierra
        WHERE c.id_tienda = :tienda AND c.fecha = CAST(:fecha AS DATE)
    ");
    $st->execute([':tienda' => $idTienda, ':fecha' => $fecha]);
    $fila = $st->fetch();

    if (!$fila) {
        return null;
    }

    $fila['resumen'] = json_decode($fila['resumen'], true);
    return $fila;
}

function eventosCierre(PDO $pdo, int $idCierre): array
{
    $st = $pdo->prepare("
        SELECT e.accion, e.version, e.fecha, e.justificacion, e.observaciones,
               TRIM(u.nombre || ' ' || COALESCE(u.apellido, '')) AS usuario
        FROM cierre_diario_evento e
        JOIN usuario u ON u.id_usuario = e.id_usuario
        WHERE e.id_cierre = :id
        ORDER BY e.fecha, e.id_evento
    ");
    $st->execute([':id' => $idCierre]);
    return $st->fetchAll();
}

function accionCierrePreparar(PDO $pdo): void
{
    exigirAccesoReportes();
    exigirCierreDisponible($pdo);

    [$idTienda, $nombre, $fecha] = validarTiendaFechaCierre($pdo, $_GET['id_tienda'] ?? 0, $_GET['fecha'] ?? '');
    $cierre = cierreExistente($pdo, $idTienda, $fecha);

    // Un día cerrado muestra lo que se consolidó al cerrar; el resto, el cálculo en vivo.
    $esSnapshot = $cierre !== null && $cierre['estado'] === 'CERRADO';
    $resumen = $esSnapshot ? $cierre['resumen'] : calcularResumenCierre($pdo, $idTienda, $nombre, $fecha);

    respuesta(true, [
        'resumen'      => $resumen,
        'es_snapshot'  => $esSnapshot,
        'cierre'       => $cierre ? [
            'id_cierre'      => (int)$cierre['id_cierre'],
            'estado'         => $cierre['estado'],
            'version'        => (int)$cierre['version'],
            'fecha_cierre'   => $cierre['fecha_cierre'],
            'usuario_cierra' => $cierre['usuario_cierra'],
            'observaciones'  => $cierre['observaciones'],
        ] : null,
        'eventos'      => $cierre ? eventosCierre($pdo, (int)$cierre['id_cierre']) : [],
        'puede_cerrar' => $cierre === null || $cierre['estado'] === 'REABIERTO',
        'puede_reabrir' => esAdministrador() && $esSnapshot,
    ]);
}

function accionCierreConfirmar(PDO $pdo): void
{
    $usuario = exigirAccesoReportes();
    exigirMetodoPost();
    exigirCierreDisponible($pdo);

    $in = entradaJson();
    [$idTienda, $nombre, $fecha] = validarTiendaFechaCierre($pdo, $in['id_tienda'] ?? 0, $in['fecha'] ?? '');
    $observaciones = trim((string)($in['observaciones'] ?? ''));

    if (mb_strlen($observaciones, 'UTF-8') > 1000) {
        respuesta(false, null, 'Las observaciones no pueden superar 1000 caracteres.', 400);
    }

    $existente = cierreExistente($pdo, $idTienda, $fecha);

    if ($existente !== null && $existente['estado'] === 'CERRADO') {
        respuesta(false, null, 'Este día ya está cerrado.', 409);
    }

    $resumen = calcularResumenCierre($pdo, $idTienda, $nombre, $fecha);

    if ($resumen['diferencias']['cantidad'] > 0 && mb_strlen($observaciones, 'UTF-8') < 5) {
        respuesta(false, null, 'Hay diferencias en el día: registre una observación que las explique antes de cerrar.', 400);
    }

    $idUsuario = (int)$usuario['id_usuario'];
    $json = json_encode($resumen, JSON_UNESCAPED_UNICODE);

    try {
        $pdo->beginTransaction();

        if ($existente === null) {
            $st = $pdo->prepare("
                INSERT INTO cierre_diario (id_tienda, fecha, estado, version, id_usuario_cierra, observaciones, resumen)
                VALUES (:tienda, CAST(:fecha AS DATE), 'CERRADO', 1, :usuario, :obs, CAST(:resumen AS JSONB))
                RETURNING id_cierre
            ");
            $st->execute([':tienda' => $idTienda, ':fecha' => $fecha, ':usuario' => $idUsuario,
                          ':obs' => $observaciones !== '' ? $observaciones : null, ':resumen' => $json]);
            $idCierre = (int)$st->fetchColumn();
            $version = 1;
            $accion = 'CIERRE';
        } else {
            $idCierre = (int)$existente['id_cierre'];
            $version = (int)$existente['version'] + 1;
            $pdo->prepare("
                UPDATE cierre_diario
                SET estado = 'CERRADO', version = :version, id_usuario_cierra = :usuario, fecha_cierre = NOW(),
                    observaciones = :obs, resumen = CAST(:resumen AS JSONB)
                WHERE id_cierre = :id
            ")->execute([':version' => $version, ':usuario' => $idUsuario,
                         ':obs' => $observaciones !== '' ? $observaciones : null, ':resumen' => $json, ':id' => $idCierre]);
            $accion = 'RECIERRE';
        }

        $pdo->prepare("
            INSERT INTO cierre_diario_evento (id_cierre, accion, version, id_usuario, observaciones)
            VALUES (:cierre, :accion, :version, :usuario, :obs)
        ")->execute([':cierre' => $idCierre, ':accion' => $accion, ':version' => $version, ':usuario' => $idUsuario,
                     ':obs' => $observaciones !== '' ? $observaciones : null]);

        registrarOperacion($pdo, 'CIERRE_DIARIO', $accion, $idTienda, null, [
            'fecha' => $fecha, 'version' => $version,
            'ventas_total' => $resumen['ventas']['totales']['total'],
            'diferencias' => $resumen['diferencias']['cantidad'],
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    respuesta(true, ['id_cierre' => $idCierre, 'version' => $version], 'El día quedó cerrado correctamente.', 201);
}

function accionCierreReabrir(PDO $pdo): void
{
    $usuario = exigirAccesoReportes();
    exigirMetodoPost();
    exigirCierreDisponible($pdo);

    if (!esAdministrador()) {
        respuesta(false, null, 'Solo un Administrador puede reabrir un cierre.', 403);
    }

    $in = entradaJson();
    $idCierre = (int)($in['id_cierre'] ?? 0);
    $justificacion = trim((string)($in['justificacion'] ?? ''));

    if ($idCierre <= 0) {
        respuesta(false, null, 'Cierre inválido.', 400);
    }

    if (mb_strlen($justificacion, 'UTF-8') < 10 || mb_strlen($justificacion, 'UTF-8') > 1000) {
        respuesta(false, null, 'Indique una justificación de entre 10 y 1000 caracteres.', 400);
    }

    $st = $pdo->prepare("SELECT id_cierre, id_tienda, fecha, estado, version FROM cierre_diario WHERE id_cierre = :id");
    $st->execute([':id' => $idCierre]);
    $cierre = $st->fetch();

    if (!$cierre) {
        respuesta(false, null, 'El cierre no existe.', 404);
    }

    if ($cierre['estado'] !== 'CERRADO') {
        respuesta(false, null, 'Este cierre ya está reabierto.', 409);
    }

    $idUsuario = (int)$usuario['id_usuario'];

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE cierre_diario SET estado = 'REABIERTO' WHERE id_cierre = :id")->execute([':id' => $idCierre]);

        $pdo->prepare("
            INSERT INTO cierre_diario_evento (id_cierre, accion, version, id_usuario, justificacion)
            VALUES (:cierre, 'REAPERTURA', :version, :usuario, :just)
        ")->execute([':cierre' => $idCierre, ':version' => (int)$cierre['version'], ':usuario' => $idUsuario, ':just' => $justificacion]);

        registrarOperacion($pdo, 'CIERRE_DIARIO', 'REAPERTURA', (int)$cierre['id_tienda'],
            ['estado' => 'CERRADO', 'fecha' => $cierre['fecha']],
            ['estado' => 'REABIERTO', 'justificacion' => $justificacion, 'autorizado_por' => $idUsuario]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    respuesta(true, null, 'El cierre fue reabierto. Ya se pueden corregir los movimientos del día.');
}

function accionCierreHistorial(PDO $pdo): void
{
    exigirAccesoReportes();

    if (!cierreDisponible($pdo)) {
        respuesta(true, []);
    }

    $tiendas = tiendasPermitidas($pdo);
    $idTienda = (int)($_GET['id_tienda'] ?? 0);

    if ($idTienda > 0 && !isset($tiendas[$idTienda])) {
        respuesta(false, null, 'No tiene acceso a la tienda seleccionada.', 403);
    }

    $ids = $idTienda > 0 ? [$idTienda] : array_keys($tiendas);
    $inicio = fechaValida((string)($_GET['fecha_inicio'] ?? '')) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-30 days'));
    $fin = fechaValida((string)($_GET['fecha_fin'] ?? '')) ? $_GET['fecha_fin'] : hoyCostaRica();

    $st = $pdo->prepare("
        SELECT c.id_cierre, c.id_tienda, t.nombre AS tienda, c.fecha, c.estado, c.version, c.fecha_cierre,
               c.observaciones, TRIM(u.nombre || ' ' || COALESCE(u.apellido, '')) AS usuario_cierra,
               (c.resumen->'ventas'->'totales'->>'total')::numeric AS ventas_total,
               (c.resumen->'diferencias'->>'cantidad')::int AS diferencias,
               (SELECT COUNT(*) FROM cierre_diario_evento e WHERE e.id_cierre = c.id_cierre AND e.accion = 'REAPERTURA') AS reaperturas
        FROM cierre_diario c
        JOIN tienda t ON t.id_tienda = c.id_tienda
        JOIN usuario u ON u.id_usuario = c.id_usuario_cierra
        WHERE c.id_tienda IN (" . listaEnteros($ids) . ")
          AND c.fecha BETWEEN CAST(:inicio AS DATE) AND CAST(:fin AS DATE)
        ORDER BY c.fecha DESC, t.nombre
        LIMIT 200
    ");
    $st->execute([':inicio' => $inicio, ':fin' => $fin]);

    respuesta(true, numerico($st->fetchAll(), ['version', 'ventas_total', 'diferencias', 'reaperturas']));
}

/* ==========================================================
   EXPORTACIÓN A PDF
   accion=exportar_pdf&tipo=ventas|utilidad|inventario|rotacion
     &mes=YYYY-MM  &id_tiendas=1,2  &id_categoria= &id_proveedor= &estado=
     &limite=15  &detalle=1
========================================================== */

const NOMBRES_MESES_REPORTE = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
    '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
];

const ETIQUETAS_ESTADO_STOCK = [
    'NORMAL'      => ['Normal', 'verde'],
    'AGOTADO'     => ['Agotado', 'rojo'],
    'BAJO_MINIMO' => ['Bajo mínimo', 'naranja'],
    'NEGATIVO'    => ['Negativo', 'rojo'],
];

/** Ejecuta una acción de reporte y devuelve sus datos en lugar de imprimirlos. */
function capturarReporte(callable $accion, PDO $pdo): array
{
    $GLOBALS['REPORTES_CAPTURAR'] = true;

    try {
        $accion($pdo);
    } catch (RespuestaCapturada $r) {
        $GLOBALS['REPORTES_CAPTURAR'] = false;

        if (!$r->exito) {
            respuesta(false, null, $r->texto, $r->estado);
        }

        return $r->datos;
    } finally {
        $GLOBALS['REPORTES_CAPTURAR'] = false;
    }

    respuesta(false, null, 'No se pudo generar el reporte.', 500);
}

function nombreTiendasExportacion(PDO $pdo, array $ids): string
{
    $permitidas = tiendasPermitidas($pdo);
    sort($ids);
    $todas = array_keys($permitidas);
    sort($todas);

    if ($ids === $todas && count($todas) > 1) {
        return 'Todas las tiendas';
    }

    return implode(', ', array_map(fn ($id) => $permitidas[$id] ?? "#$id", $ids));
}

function accionExportarPdf(PDO $pdo): void
{
    $usuario = exigirAccesoReportes();

    // El Encargado solo consulta y genera reportes en pantalla; no descarga PDF.
    if (!esAdministradorOGerente()) {
        respuesta(false, null, 'Solo Administración o Gerencia pueden exportar reportes a PDF.', 403);
    }

    $tipo = (string)($_GET['tipo'] ?? '');
    $titulos = [
        'ventas'     => ['Ventas por tienda y periodo', 'Ingresos, unidades y costo de las ventas confirmadas.'],
        'utilidad'   => ['Utilidad bruta estimada', 'Ingresos menos el costo de los productos vendidos, por tienda y producto.'],
        'inventario' => ['Inventario por ubicación', 'Existencias actuales por tienda, bodega y exhibición, con los productos críticos.'],
        'rotacion'   => ['Rotación, bajas y pérdidas', 'Productos más y menos vendidos, y valor estimado de las bajas y pérdidas.'],
        'cierre'     => ['Cierre operativo diario', 'Consolidado de ventas, movimientos, diferencias y alertas del día.'],
    ];

    if (!isset($titulos[$tipo])) {
        respuesta(false, null, 'Seleccione un tipo de reporte válido.', 400);
    }

    if ($tipo === 'utilidad' && !esAdministradorOGerente()) {
        respuesta(false, null, 'Solo Administración o Gerencia pueden exportar la utilidad.', 403);
    }

    $ahora = new DateTimeImmutable('now', new DateTimeZone(ZONA_REPORTES));
    $textoPeriodo = 'Existencias al ' . $ahora->format('d/m/Y H:i');
    $sufijoArchivo = $ahora->format('Y-m-d');
    $incluirDetalle = ($_GET['detalle'] ?? '1') !== '0';

    $usaPeriodo = in_array($tipo, ['ventas', 'utilidad', 'rotacion'], true);

    if ($usaPeriodo) {
        $mes = trim((string)($_GET['mes'] ?? ''));

        if ($mes !== '') {
            if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $mes, $m)) {
                respuesta(false, null, 'Seleccione un mes válido.', 400);
            }

            $inicio = new DateTimeImmutable("{$m[1]}-{$m[2]}-01");
            $_GET['fecha_inicio'] = $inicio->format('Y-m-d');
            $_GET['fecha_fin'] = $inicio->modify('last day of this month')->format('Y-m-d');
            $textoPeriodo = NOMBRES_MESES_REPORTE[$m[2]] . ' ' . $m[1];
            $sufijoArchivo = "{$m[1]}-{$m[2]}";
        } else {
            $ini = (string)($_GET['fecha_inicio'] ?? '');
            $fin = (string)($_GET['fecha_fin'] ?? '');

            if (!fechaValida($ini) || !fechaValida($fin) || $ini > $fin) {
                respuesta(false, null, 'Indique un periodo válido.', 400);
            }

            if ((new DateTimeImmutable($ini))->diff(new DateTimeImmutable($fin))->days > 366) {
                respuesta(false, null, 'El periodo no puede superar un año.', 400);
            }

            $textoPeriodo = date('d/m/Y', strtotime($ini)) . ' al ' . date('d/m/Y', strtotime($fin));
            $sufijoArchivo = "$ini-a-$fin";
        }
    }

    $cierreVarias = false;

    if ($tipo === 'cierre') {
        $fechaCierre = (string)($_GET['fecha'] ?? '');
        $_GET['fecha'] = $fechaCierre;
        $textoPeriodo = 'Día ' . (fechaValida($fechaCierre) ? date('d/m/Y', strtotime($fechaCierre)) : '-');
        $sufijoArchivo = $fechaCierre;
        $idTiendaCierre = (int)($_GET['id_tienda'] ?? 0);
        $idsCierre = [$idTiendaCierre];

        // Sin tienda concreta: comparativo de varias (o todas) las tiendas, solo para Administración y Gerencia.
        if ($idTiendaCierre === 0) {
            if (!esAdministradorOGerente()) {
                respuesta(false, null, 'Solo Administración o Gerencia pueden exportar el cierre de varias tiendas.', 403);
            }

            $permitidas = tiendasPermitidas($pdo);
            $seleccion = idsDeParametro('id_tiendas');

            foreach ($seleccion as $idSeleccion) {
                if (!isset($permitidas[$idSeleccion])) {
                    respuesta(false, null, 'No tiene acceso a una de las tiendas seleccionadas.', 403);
                }
            }

            $idsCierre = $seleccion ?: array_keys($permitidas);

            if (!$idsCierre) {
                respuesta(false, null, 'No hay tiendas disponibles para exportar.', 400);
            }

            if (count($idsCierre) === 1) {
                $idTiendaCierre = $idsCierre[0];
            } else {
                $cierreVarias = true;
            }
        }

        $_GET['id_tienda'] = $idTiendaCierre;
        $f = ['ids_tiendas' => $idsCierre, 'id_categoria' => 0, 'id_proveedor' => 0, 'ids_proveedores' => [], 'ids_productos' => [], 'busqueda' => '', 'estado' => ''];
    } else {
        $f = leerFiltros($pdo, $usaPeriodo);
    }

    $filtros = [];
    $nombreDe = function (string $sql, int $id) use ($pdo): ?string {
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $id]);
        $valor = $st->fetchColumn();
        return $valor === false ? null : (string)$valor;
    };

    if ($f['id_categoria'] > 0) {
        $filtros[] = 'Categoría: ' . ($nombreDe('SELECT nombre FROM categoria WHERE id_categoria = :id', $f['id_categoria']) ?? '-');
    }
    if (!empty($f['ids_proveedores'])) {
        $nombres = array_map(
            fn ($id) => $nombreDe('SELECT nombre FROM proveedor WHERE id_proveedor = :id', $id) ?? '-',
            $f['ids_proveedores']
        );
        $filtros[] = (count($nombres) > 1 ? 'Proveedores: ' : 'Proveedor: ') . implode(', ', $nombres);
    }
    if (!empty($f['ids_productos'])) {
        $nombresProductos = array_map(
            fn ($id) => $nombreDe('SELECT nombre FROM producto WHERE id_producto = :id', $id) ?? '-',
            $f['ids_productos']
        );
        $filtros[] = (count($nombresProductos) > 3
            ? 'Productos: ' . count($nombresProductos) . ' seleccionados'
            : (count($nombresProductos) > 1 ? 'Productos: ' : 'Producto: ') . implode(', ', $nombresProductos));
    }
    if ($f['busqueda'] !== '') {
        $filtros[] = 'Producto: ' . $f['busqueda'];
    }
    if ($tipo === 'inventario' && $f['estado'] !== '') {
        $etiquetaEstado = ['CRITICO' => 'Solo críticos'] + array_map(fn ($e) => $e[0], ETIQUETAS_ESTADO_STOCK);
        $filtros[] = 'Estado: ' . ($etiquetaEstado[$f['estado']] ?? $f['estado']);
    }
    $limiteRanking = max(5, min(50, (int)($_GET['limite'] ?? 15)));
    if ($tipo === 'rotacion') {
        $filtros[] = "Top $limiteRanking por ranking";
    }

    $tienda = fn (array $extra) => array_merge([['titulo' => 'Tienda', 'campo' => 'tienda']], $extra);
    $coloresTienda = ['azul', 'verde', 'naranja', 'morado', 'rojo', 'gris'];
    $secciones = [];
    $resumen = [];

    if ($tipo === 'ventas') {
        $d = capturarReporte('accionVentas', $pdo);
        $r = $d['resumen'];
        $resumen = [
            ['Ventas (sin IVA)', pdfMoneda($r['ingresos']), pdfMoneda($r['total']) . ' con IVA'],
            ['Unidades vendidas', number_format($r['unidades']), number_format($r['ventas']) . ' venta(s)'],
            ['Costo asociado', pdfMoneda($r['costo']), 'Costo de lo vendido'],
            ['Venta promedio', pdfMoneda($r['ventas'] > 0 ? $r['ingresos'] / $r['ventas'] : 0), 'Ingreso por venta'],
        ];

        if ($r['lineas_sin_costo'] > 0) {
            $secciones[] = ['tipo' => 'aviso', 'nivel' => 'alerta', 'texto' => "{$r['lineas_sin_costo']} línea(s) vendida(s) no tienen costo registrado y se valoraron en ₡0."];
        }

        $porDia = [];
        foreach ($d['por_dia'] as $x) {
            $porDia[substr((string)$x['fecha'], 0, 10)] = $x['ingresos'];
        }
        $serie = [];
        for ($dia = new DateTimeImmutable($_GET['fecha_inicio']), $fin = new DateTimeImmutable($_GET['fecha_fin']); $dia <= $fin && count($serie) < 93; $dia = $dia->modify('+1 day')) {
            $serie[] = ['etiqueta' => $dia->format('d/m'), 'valor' => $porDia[$dia->format('Y-m-d')] ?? 0];
        }

        $secciones[] = ['tipo' => 'par',
            'izquierda' => ['tipo' => 'columnas', 'titulo' => 'Ventas por día', 'descripcion' => 'Ingresos diarios del periodo (sin IVA).', 'items' => $serie],
            'derecha' => ['tipo' => 'barras', 'titulo' => 'Participación por tienda', 'descripcion' => 'Ingresos de cada punto de venta.',
                'items' => array_map(fn ($t, $i) => ['etiqueta' => $t['tienda'], 'valor' => $t['ingresos'], 'texto' => pdfMonedaCorta($t['ingresos']), 'color' => $coloresTienda[$i % 6]], $d['por_tienda'], array_keys($d['por_tienda']))],
        ];
        $secciones[] = [
            'titulo' => 'Ventas por tienda', 'descripcion' => 'Resumen numérico de cada punto de venta.',
            'vacio' => 'No hay ventas confirmadas en el periodo seleccionado.',
            'columnas' => $tienda([
                ['titulo' => 'Ventas', 'campo' => 'ventas', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Ingresos', 'campo' => 'ingresos', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Total con IVA', 'campo' => 'total', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Costo', 'campo' => 'costo', 'tipo' => 'moneda', 'sumar' => true],
            ]),
            'filas' => $d['por_tienda'],
        ];
        if ($incluirDetalle) {
            $secciones[] = [
                'titulo' => 'Detalle por producto', 'descripcion' => 'Unidades, ingresos y costo de cada producto vendido.',
                'vacio' => 'No hay ventas confirmadas con estos filtros.',
                'columnas' => $tienda([
                    ['titulo' => 'Producto', 'campo' => 'producto'], ['titulo' => 'Categoría', 'campo' => 'categoria'],
                    ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                    ['titulo' => 'Ingresos', 'campo' => 'ingresos', 'tipo' => 'moneda', 'sumar' => true],
                    ['titulo' => 'IVA', 'campo' => 'impuesto', 'tipo' => 'moneda', 'sumar' => true],
                    ['titulo' => 'Total', 'campo' => 'total', 'tipo' => 'moneda', 'sumar' => true],
                    ['titulo' => 'Costo', 'campo' => 'costo', 'tipo' => 'moneda', 'sumar' => true],
                ]),
                'filas' => $d['detalle'],
            ];
        }
    } elseif ($tipo === 'utilidad') {
        $d = capturarReporte('accionUtilidad', $pdo);
        $r = $d['resumen'];
        $resumen = [
            ['Ingresos', pdfMoneda($r['ingresos']), 'Ventas sin IVA'],
            ['Costo de lo vendido', pdfMoneda($r['costo']), 'Costo de los productos'],
            ['Utilidad bruta', pdfMoneda($r['utilidad']), 'Ingresos − costo'],
            ['Margen', number_format($r['margen'], 1) . '%', 'Utilidad sobre ingresos'],
        ];

        if ($r['lineas_sin_costo'] > 0) {
            $secciones[] = ['tipo' => 'aviso', 'nivel' => 'alerta', 'texto' => "{$r['lineas_sin_costo']} línea(s) sin costo registrado: su utilidad puede estar sobrevalorada."];
        }

        $secciones[] = ['tipo' => 'par',
            'izquierda' => ['tipo' => 'barras', 'titulo' => 'Utilidad por tienda', 'descripcion' => 'Utilidad bruta estimada de cada tienda.',
                'items' => array_map(fn ($t) => ['etiqueta' => $t['tienda'], 'valor' => $t['utilidad'], 'texto' => pdfMonedaCorta($t['utilidad']), 'color' => 'verde'], $d['por_tienda'])],
            'derecha' => ['tipo' => 'barras', 'titulo' => 'Margen por tienda', 'descripcion' => 'Utilidad como porcentaje de los ingresos.',
                'items' => array_map(fn ($t) => ['etiqueta' => $t['tienda'], 'valor' => $t['margen'], 'texto' => number_format($t['margen'], 1) . '%', 'color' => 'morado'], $d['por_tienda'])],
        ];
        $secciones[] = [
            'titulo' => 'Utilidad por tienda (detalle)', 'descripcion' => 'Utilidad bruta estimada = ingresos (sin IVA) − costo de los productos vendidos.',
            'vacio' => 'No hay ventas confirmadas en el periodo seleccionado.',
            'columnas' => $tienda([
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Ingresos', 'campo' => 'ingresos', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Costo', 'campo' => 'costo', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Utilidad', 'campo' => 'utilidad', 'tipo' => 'positivo_negativo', 'sumar' => true],
                ['titulo' => 'Margen', 'campo' => 'margen', 'tipo' => 'porcentaje'],
            ]),
            'filas' => $d['por_tienda'],
        ];
        if ($incluirDetalle) {
            $secciones[] = [
                'titulo' => 'Utilidad por producto', 'descripcion' => 'Productos que más y menos aportan a la utilidad.',
                'vacio' => 'No hay ventas confirmadas con estos filtros.',
                'columnas' => $tienda([
                    ['titulo' => 'Producto', 'campo' => 'producto'], ['titulo' => 'Categoría', 'campo' => 'categoria'],
                    ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                    ['titulo' => 'Ingresos', 'campo' => 'ingresos', 'tipo' => 'moneda', 'sumar' => true],
                    ['titulo' => 'Costo', 'campo' => 'costo', 'tipo' => 'moneda', 'sumar' => true],
                    ['titulo' => 'Utilidad', 'campo' => 'utilidad', 'tipo' => 'positivo_negativo', 'sumar' => true],
                    ['titulo' => 'Margen', 'campo' => 'margen', 'tipo' => 'porcentaje'],
                ]),
                'filas' => $d['detalle'],
            ];
        }
    } elseif ($tipo === 'inventario') {
        $d = capturarReporte('accionInventario', $pdo);
        $r = $d['resumen'];
        $criticos = $r['agotados'] + $r['bajo_minimo'] + $r['negativos'];
        $resumen = [
            ['Unidades en stock', number_format($r['unidades']), number_format($r['ubicaciones']) . ' ubicación(es)'],
            ['Agotados', number_format($r['agotados']), 'Existencia en cero'],
            ['Bajo mínimo', number_format($r['bajo_minimo']), 'Requieren reposición'],
            ['Saldo negativo', number_format($r['negativos']), 'Revisar movimientos'],
        ];

        if ($criticos > 0) {
            $secciones[] = ['tipo' => 'aviso', 'nivel' => 'alerta', 'texto' => "Hay " . number_format($criticos) . " producto(s) críticos de " . number_format($r['lineas']) . " líneas de inventario."];
        }

        $secciones[] = ['tipo' => 'par',
            'izquierda' => ['tipo' => 'barras', 'titulo' => 'Stock por ubicación', 'descripcion' => 'Unidades disponibles en cada ubicación.',
                'items' => array_map(fn ($u) => ['etiqueta' => $u['ubicacion'], 'valor' => $u['unidades'], 'texto' => number_format($u['unidades']) . ' u.', 'color' => 'azul'], $d['por_ubicacion'])],
            'derecha' => ['tipo' => 'barras', 'titulo' => 'Estado del inventario', 'descripcion' => 'Productos según su nivel de existencia.',
                'items' => [
                    ['etiqueta' => 'Normal', 'valor' => max(0, $r['lineas'] - $criticos), 'texto' => number_format(max(0, $r['lineas'] - $criticos)), 'color' => 'verde'],
                    ['etiqueta' => 'Bajo mínimo', 'valor' => $r['bajo_minimo'], 'texto' => number_format($r['bajo_minimo']), 'color' => 'naranja'],
                    ['etiqueta' => 'Agotado', 'valor' => $r['agotados'], 'texto' => number_format($r['agotados']), 'color' => 'rojo'],
                    ['etiqueta' => 'Negativo', 'valor' => $r['negativos'], 'texto' => number_format($r['negativos']), 'color' => 'morado'],
                ]],
        ];
        $secciones[] = [
            'titulo' => 'Stock por ubicación (detalle)', 'descripcion' => 'Existencias en tiendas, bodegas y exhibiciones.',
            'vacio' => 'No hay existencias con estos filtros.',
            'columnas' => [
                ['titulo' => 'Tienda', 'campo' => 'tienda'], ['titulo' => 'Ubicación', 'campo' => 'ubicacion'], ['titulo' => 'Tipo', 'campo' => 'tipo'],
                ['titulo' => 'Productos', 'campo' => 'productos', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Críticos', 'campo' => 'criticos', 'tipo' => 'entero', 'sumar' => true],
            ],
            'filas' => $d['por_ubicacion'],
        ];
        if ($incluirDetalle) {
            $limiteFilas = 1500;
            $secciones[] = [
                'titulo' => 'Detalle de existencias', 'descripcion' => 'Los productos críticos aparecen primero.',
                'vacio' => 'No hay existencias con estos filtros.',
                'nota' => count($d['detalle']) > $limiteFilas ? "Se muestran las primeras $limiteFilas filas de " . count($d['detalle']) . '. Use los filtros (por ejemplo "Solo críticos") para acotar el reporte.' : null,
                'columnas' => [
                    ['titulo' => 'Tienda', 'campo' => 'tienda'], ['titulo' => 'Ubicación', 'campo' => 'ubicacion'],
                    ['titulo' => 'Producto', 'campo' => 'producto'], ['titulo' => 'Categoría', 'campo' => 'categoria'],
                    ['titulo' => 'Existencia', 'campo' => 'cantidad', 'tipo' => 'entero'],
                    ['titulo' => 'Mínimo', 'campo' => 'stock_minimo', 'tipo' => 'entero'],
                    ['titulo' => 'Estado', 'campo' => 'estado', 'tipo' => 'badge', 'etiquetas' => ETIQUETAS_ESTADO_STOCK],
                ],
                'filas' => array_slice($d['detalle'], 0, $limiteFilas),
            ];
        }
    } elseif ($tipo === 'rotacion') {
        $d = capturarReporte('accionRotacion', $pdo);
        $r = $d['resumen'];
        $resumen = [
            ['Unidades vendidas', number_format($r['unidades_vendidas']), 'En el periodo'],
            ['Productos sin ventas', number_format($r['productos_sin_venta']), 'Con stock y sin rotación'],
            ['Unidades dadas de baja', number_format($r['unidades_baja']), pdfMoneda($r['valor_bajas']) . ' en total'],
            ['Pérdida estimada', pdfMoneda($r['valor_perdidas']), 'Valor económico'],
        ];
        $secciones[] = ['tipo' => 'aviso', 'texto' => 'Las pérdidas incluyen bajas por vencimiento, daño, robo y pérdida, valoradas al costo vigente. Las devoluciones se muestran como baja pero no cuentan como pérdida.'];

        $secciones[] = ['tipo' => 'par',
            'izquierda' => ['tipo' => 'barras', 'titulo' => 'Más vendidos', 'descripcion' => 'Productos de mayor rotación.',
                'items' => array_map(fn ($p) => ['etiqueta' => $p['producto'], 'valor' => $p['unidades'], 'texto' => number_format($p['unidades']) . ' u.', 'color' => 'verde'], array_slice($d['mas_vendidos'], 0, 10))],
            'derecha' => ['tipo' => 'barras', 'titulo' => 'Bajas y pérdidas por motivo', 'descripcion' => 'Valor estimado de lo dado de baja.',
                'items' => array_map(fn ($m) => ['etiqueta' => $m['motivo'], 'valor' => $m['valor'], 'texto' => pdfMonedaCorta($m['valor']), 'color' => $m['es_perdida'] ? 'rojo' : 'gris'], $d['bajas_por_motivo'])],
        ];

        $ranking = $tienda([
            ['titulo' => 'Producto', 'campo' => 'producto'], ['titulo' => 'Categoría', 'campo' => 'categoria'],
            ['titulo' => 'Unidades vendidas', 'campo' => 'unidades', 'tipo' => 'entero'],
            ['titulo' => 'Ingresos', 'campo' => 'ingresos', 'tipo' => 'moneda'],
            ['titulo' => 'Stock actual', 'campo' => 'stock', 'tipo' => 'entero'],
        ]);
        $secciones[] = ['titulo' => 'Ranking de más vendidos', 'descripcion' => 'Mayor rotación por tienda y periodo.',
            'vacio' => 'No hubo ventas confirmadas en el periodo.', 'columnas' => $ranking, 'filas' => $d['mas_vendidos']];
        $secciones[] = ['titulo' => 'Menos vendidos', 'descripcion' => 'Productos con stock y la menor rotación del periodo.',
            'vacio' => 'No hay productos con stock para analizar.', 'columnas' => $ranking, 'filas' => $d['menos_vendidos']];
        $secciones[] = [
            'titulo' => 'Bajas y pérdidas por motivo (detalle)', 'descripcion' => 'Las devoluciones no cuentan como pérdida.',
            'vacio' => 'No se registraron bajas en el periodo.',
            'columnas' => [
                ['titulo' => 'Motivo', 'campo' => 'motivo'],
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Valor estimado', 'campo' => 'valor', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Tipo', 'campo' => 'es_perdida', 'tipo' => 'badge', 'etiquetas' => [0 => ['Devolución', 'gris'], 1 => ['Pérdida', 'rojo']]],
            ],
            'filas' => array_map(fn ($x) => $x + ['es_perdida' => $x['es_perdida'] ? 1 : 0], $d['bajas_por_motivo']),
        ];
        if ($incluirDetalle) {
            $secciones[] = [
                'titulo' => 'Detalle de bajas', 'descripcion' => 'Cada baja confirmada del periodo.',
                'vacio' => 'No se registraron bajas en el periodo.',
                'columnas' => [
                    ['titulo' => 'Fecha', 'campo' => 'fecha', 'tipo' => 'fecha'], ['titulo' => 'Tienda', 'campo' => 'tienda'],
                    ['titulo' => 'Ubicación', 'campo' => 'ubicacion'], ['titulo' => 'Producto', 'campo' => 'producto'],
                    ['titulo' => 'Motivo', 'campo' => 'motivo'],
                    ['titulo' => 'Cantidad', 'campo' => 'cantidad', 'tipo' => 'entero', 'sumar' => true],
                    ['titulo' => 'Costo unitario', 'campo' => 'costo_unitario', 'tipo' => 'moneda'],
                    ['titulo' => 'Valor estimado', 'campo' => 'valor', 'tipo' => 'moneda', 'sumar' => true],
                ],
                'filas' => $d['bajas'],
            ];
        }
    } elseif ($cierreVarias) {
        $permitidas = tiendasPermitidas($pdo);
        $comparativo = [];

        foreach ($idsCierre as $idCierreTienda) {
            $_GET['id_tienda'] = $idCierreTienda;
            $datos = capturarReporte('accionCierrePreparar', $pdo);
            $r = $datos['resumen'];
            $comparativo[] = [
                'tienda'       => $permitidas[$idCierreTienda] ?? "#$idCierreTienda",
                'estado'       => $datos['cierre']['estado'] ?? 'ABIERTO',
                'ventas'       => (int)$r['ventas']['totales']['ventas'],
                'unidades'     => (int)$r['ventas']['totales']['unidades'],
                'total'        => (float)$r['ventas']['totales']['total'],
                'movimientos'  => (int)array_sum(array_column($r['movimientos'], 'movimientos')),
                'diferencias'  => (int)$r['diferencias']['cantidad'],
                'alertas'      => (int)$r['alertas']['pendientes'],
            ];
        }

        $suma = fn (string $campo) => array_sum(array_column($comparativo, $campo));
        $cerradas = count(array_filter($comparativo, fn ($x) => $x['estado'] === 'CERRADO'));
        $colores = ['azul', 'verde', 'naranja', 'morado', 'rojo', 'gris'];
        $barras = fn (string $campo, callable $texto, ?string $color = null) => array_map(
            fn ($x, $i) => ['etiqueta' => $x['tienda'], 'valor' => $x[$campo], 'texto' => $texto($x[$campo]), 'color' => $color ?? $colores[$i % 6]],
            $comparativo,
            array_keys($comparativo)
        );

        $resumen = [
            ['Ventas del día', pdfMoneda($suma('total')), number_format($suma('unidades')) . ' unidad(es) · ' . number_format($suma('ventas')) . ' venta(s)'],
            ['Movimientos', number_format($suma('movimientos')), 'Entre todas las tiendas'],
            ['Diferencias', number_format($suma('diferencias')), 'Ventas vs. salidas de inventario'],
            ['Cierres confirmados', $cerradas . ' de ' . count($comparativo), number_format($suma('alertas')) . ' alerta(s) pendiente(s)'],
        ];

        $secciones[] = ['tipo' => 'aviso', 'texto' => 'Comparativo del cierre de ' . count($comparativo) . ' tiendas. Los días no cerrados muestran el cálculo en vivo y no un consolidado confirmado.'];
        $secciones[] = ['tipo' => 'par',
            'izquierda' => ['tipo' => 'barras', 'titulo' => 'Ventas por tienda', 'descripcion' => 'Ventas confirmadas del día (con IVA).', 'items' => $barras('total', 'pdfMonedaCorta')],
            'derecha' => ['tipo' => 'barras', 'titulo' => 'Movimientos por tienda', 'descripcion' => 'Movimientos de inventario del día.', 'items' => $barras('movimientos', fn ($v) => number_format($v))],
        ];
        $secciones[] = ['tipo' => 'par',
            'izquierda' => ['tipo' => 'barras', 'titulo' => 'Diferencias por tienda', 'descripcion' => 'Ventas que no coinciden con las salidas.', 'items' => $barras('diferencias', fn ($v) => number_format($v), 'rojo')],
            'derecha' => ['tipo' => 'barras', 'titulo' => 'Alertas pendientes por tienda', 'descripcion' => 'Alertas sin atender al cierre del día.', 'items' => $barras('alertas', fn ($v) => number_format($v), 'naranja')],
        ];
        $secciones[] = ['titulo' => 'Comparativo por tienda', 'descripcion' => 'Cierre diario de cada tienda, lado a lado.', 'vacio' => 'No hay datos.',
            'columnas' => [
                ['titulo' => 'Tienda', 'campo' => 'tienda'],
                ['titulo' => 'Cierre', 'campo' => 'estado', 'tipo' => 'badge', 'etiquetas' => ['CERRADO' => ['Cerrado', 'verde'], 'REABIERTO' => ['Reabierto', 'naranja'], 'ABIERTO' => ['Abierto', 'gris']]],
                ['titulo' => 'Ventas', 'campo' => 'ventas', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Total', 'campo' => 'total', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Movimientos', 'campo' => 'movimientos', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Diferencias', 'campo' => 'diferencias', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Alertas', 'campo' => 'alertas', 'tipo' => 'entero', 'sumar' => true],
            ],
            'filas' => $comparativo];
        $sufijoArchivo .= '_comparativo';
    } else {
        $d = capturarReporte('accionCierrePreparar', $pdo);
        $res = $d['resumen'];
        $t = $res['ventas']['totales'];
        $c = $d['cierre'];
        $movs = array_sum(array_column($res['movimientos'], 'movimientos'));
        $resumen = [
            ['Ventas del día', pdfMoneda($t['total']), number_format($t['unidades']) . ' unidad(es) · ' . number_format($t['ventas']) . ' venta(s)'],
            ['Movimientos', number_format($movs), number_format(array_sum(array_column($res['movimientos'], 'unidades'))) . ' unidad(es) movidas'],
            ['Diferencias', number_format($res['diferencias']['cantidad']), 'Ventas vs. salidas de inventario'],
            ['Alertas pendientes', number_format($res['alertas']['pendientes']), number_format($res['alertas']['generadas']) . ' generada(s) en el día'],
        ];

        $secciones[] = $c
            ? ['tipo' => 'aviso', 'texto' => 'Día ' . ($c['estado'] === 'CERRADO' ? 'cerrado' : 'reabierto') . " por {$c['usuario_cierra']} el " . date('d/m/Y H:i', strtotime((string)$c['fecha_cierre'])) . " (versión {$c['version']})." . ($c['observaciones'] ? " Observaciones: {$c['observaciones']}" : '')]
            : ['tipo' => 'aviso', 'nivel' => 'alerta', 'texto' => 'Este día aún está abierto: el consolidado es una vista previa y no ha sido cerrado.'];

        if ($res['ventas_sin_confirmar']['cantidad'] > 0) {
            $secciones[] = ['tipo' => 'aviso', 'nivel' => 'alerta', 'texto' => $res['ventas_sin_confirmar']['cantidad'] . ' venta(s) manual(es) por ' . pdfMoneda($res['ventas_sin_confirmar']['total']) . ' no tienen movimiento de inventario confirmado y no se cuentan.'];
        }

        $secciones[] = ['titulo' => 'Ventas del día', 'descripcion' => 'Ventas confirmadas por origen.', 'vacio' => 'No hubo ventas confirmadas en este día.',
            'columnas' => [
                ['titulo' => 'Origen', 'campo' => 'fuente'],
                ['titulo' => 'Ventas', 'campo' => 'ventas', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Subtotal', 'campo' => 'subtotal', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'IVA', 'campo' => 'impuesto', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Total', 'campo' => 'total', 'tipo' => 'moneda', 'sumar' => true],
                ['titulo' => 'Costo', 'campo' => 'costo', 'tipo' => 'moneda', 'sumar' => true],
            ],
            'filas' => array_map(fn ($x) => $x + ['fuente' => $x['fuente'] === 'NAYAX' ? 'Nayax' : 'Manual'], $res['ventas']['por_fuente'])];

        $secciones[] = ['titulo' => 'Movimientos de inventario', 'descripcion' => 'Entradas, salidas, ajustes, bajas y traslados del día.', 'vacio' => 'No hubo movimientos de inventario en este día.',
            'columnas' => [
                ['titulo' => 'Tipo', 'campo' => 'tipo'],
                ['titulo' => 'Estado', 'campo' => 'estado', 'tipo' => 'badge', 'etiquetas' => ['CONFIRMADO' => ['Confirmado', 'verde'], 'PENDIENTE' => ['Pendiente', 'naranja']]],
                ['titulo' => 'Movimientos', 'campo' => 'movimientos', 'tipo' => 'entero', 'sumar' => true],
                ['titulo' => 'Unidades', 'campo' => 'unidades', 'tipo' => 'entero', 'sumar' => true],
            ],
            'filas' => $res['movimientos']];

        $secciones[] = ['titulo' => 'Diferencias del día', 'descripcion' => 'Unidades vendidas que no coinciden con las salidas de inventario registradas por venta.',
            'vacio' => 'Sin diferencias: las ventas coinciden con las salidas de inventario.',
            'columnas' => [
                ['titulo' => 'Producto', 'campo' => 'producto'],
                ['titulo' => 'Vendidas', 'campo' => 'vendida', 'tipo' => 'entero'],
                ['titulo' => 'Salidas de inventario', 'campo' => 'salida', 'tipo' => 'entero'],
                ['titulo' => 'Diferencia', 'campo' => 'diferencia', 'tipo' => 'entero'],
            ],
            'filas' => $res['diferencias']['detalle']];

        if ($d['eventos']) {
            $secciones[] = ['titulo' => 'Historial del cierre', 'descripcion' => 'Cierres, reaperturas y nuevos cierres.',
                'columnas' => [
                    ['titulo' => 'Acción', 'campo' => 'accion'], ['titulo' => 'Versión', 'campo' => 'version', 'tipo' => 'entero'],
                    ['titulo' => 'Usuario', 'campo' => 'usuario'],
                    ['titulo' => 'Fecha y hora', 'campo' => 'fecha'],
                    ['titulo' => 'Justificación / observaciones', 'campo' => 'detalle'],
                ],
                'filas' => array_map(fn ($e) => [
                    'accion' => ['CIERRE' => 'Cierre', 'RECIERRE' => 'Nuevo cierre', 'REAPERTURA' => 'Reapertura'][$e['accion']] ?? $e['accion'],
                    'version' => $e['version'], 'usuario' => $e['usuario'],
                    'fecha' => date('d/m/Y H:i', strtotime((string)$e['fecha'])),
                    'detalle' => $e['justificacion'] ?: ($e['observaciones'] ?? ''),
                ], $d['eventos'])];
        }
    }

    $generadoPor = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
    $meta = [
        ['Periodo', $textoPeriodo],
        ['Tiendas', nombreTiendasExportacion($pdo, $f['ids_tiendas'])],
    ];
    if ($filtros) {
        $meta[] = ['Filtros', implode(' | ', $filtros)];
    }
    $meta[] = ['Generado el', $ahora->format('d/m/Y H:i')];
    if ($generadoPor !== '') {
        $meta[] = ['Generado por', $generadoPor];
    }

    pdfReporte([
        'tipo'        => $tipo,
        'subtitulo'   => $titulos[$tipo][0],
        'descripcion' => $titulos[$tipo][1],
        'archivo'     => 'reporte-' . $tipo . '_' . $sufijoArchivo,
        'meta'        => $meta,
        'resumen'     => $resumen,
        'secciones'   => $secciones,
    ]);
}

/* ==========================================================
   ENRUTADOR
========================================================== */

$acciones = [
    'filtros'    => 'accionFiltros',
    'productos_proveedor' => 'accionProductosProveedor',
    'ventas'     => 'accionVentas',
    'utilidad'   => 'accionUtilidad',
    'inventario' => 'accionInventario',
    'rotacion'   => 'accionRotacion',
    'cierre_preparar'  => 'accionCierrePreparar',
    'cierre_confirmar' => 'accionCierreConfirmar',
    'cierre_reabrir'   => 'accionCierreReabrir',
    'cierre_historial' => 'accionCierreHistorial',
    'exportar_pdf'     => 'accionExportarPdf',
];

$accion = (string)($_GET['accion'] ?? '');

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($pdo);
} catch (Throwable $e) {
    error_log('[reportes] ' . $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
