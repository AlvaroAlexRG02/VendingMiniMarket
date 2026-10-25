<?php

/**
 * HU-20 — Consulta de stock actual por producto y ubicación.
 *
 * Lee el inventario real desde existencia + ubicacion_inventario y aplica
 * restricciones de ubicación según el usuario autenticado.
 *
 * Administrador y Gerente General: todas las ubicaciones.
 * Encargado y Dependiente: únicamente tiendas asignadas en usuario_tienda.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';


/* ==========================================================
   UTILIDADES GENERALES
========================================================== */

function responder(bool $success, $data = null, string $message = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode(
        [
            'success' => $success,
            'data' => $data,
            'message' => $message,
        ],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function exigirSesionInventario(): array
{
    if (empty($_SESSION['usuario'])) {
        responder(false, null, 'Debe iniciar sesión.', 401);
    }

    return $_SESSION['usuario'];
}

function enteroPositivo($valor, int $defecto = 0): int
{
    if ($valor === null || $valor === '') {
        return $defecto;
    }

    $entero = filter_var($valor, FILTER_VALIDATE_INT);

    return ($entero !== false && $entero >= 0) ? $entero : $defecto;
}

/**
 * null = el usuario puede consultar todas las tiendas.
 * []   = no tiene tiendas asignadas.
 * [1,2] = únicamente esas tiendas.
 */
function tiendasVisiblesInventario(PDO $pdo): ?array
{
    $idRol = (int)($_SESSION['usuario']['id_rol'] ?? 0);

    if (in_array($idRol, UBICACION_ROLES_VEN_TODAS, true)) {
        return null;
    }

    $idUsuario = (int)($_SESSION['usuario']['id_usuario'] ?? 0);
    $asignadas = ubicacionesAsignadas($pdo, $idUsuario);

    return array_map(
        static fn(array $tienda): int => (int)$tienda['id_tienda'],
        $asignadas
    );
}

function agregarRestriccionTiendas(?array $tiendasVisibles, array &$condiciones, array &$params): void
{
    if ($tiendasVisibles === null) {
        return;
    }

    if (!$tiendasVisibles) {
        $condiciones[] = 'FALSE';
        return;
    }

    $marcadores = [];

    foreach ($tiendasVisibles as $i => $idTienda) {
        $clave = ':tienda_visible_' . $i;
        $marcadores[] = $clave;
        $params[$clave] = (int)$idTienda;
    }

    $condiciones[] = 'ui.id_tienda IN (' . implode(', ', $marcadores) . ')';
}

function estadoStock(int $cantidad, int $stockMinimo): array
{
    if ($cantidad < 0) {
        return [
            'codigo' => 'stock-negativo',
            'texto' => 'Stock negativo',
        ];
    }

    if ($cantidad === 0) {
        return [
            'codigo' => 'agotado',
            'texto' => 'Agotado',
        ];
    }

    if ($stockMinimo > 0 && $cantidad <= $stockMinimo) {
        return [
            'codigo' => 'stock-bajo',
            'texto' => 'Stock bajo',
        ];
    }

    return [
        'codigo' => 'normal',
        'texto' => 'Normal',
    ];
}


/* ==========================================================
   VALIDACIÓN DE SOLICITUD
========================================================== */

exigirSesionInventario();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    responder(false, null, 'Método no permitido.', 405);
}

$busqueda = trim((string)($_GET['busqueda'] ?? ''));
$idTienda = enteroPositivo($_GET['id_tienda'] ?? null);
$idUbicacion = enteroPositivo($_GET['id_ubicacion'] ?? null);
$idCategoria = enteroPositivo($_GET['id_categoria'] ?? null);
$estado = trim((string)($_GET['estado'] ?? ''));
$pagina = max(1, enteroPositivo($_GET['pagina'] ?? null, 1));
$porPagina = enteroPositivo($_GET['por_pagina'] ?? null, 25);
$porPagina = min(max($porPagina, 1), 100);
$offset = ($pagina - 1) * $porPagina;

$estadosPermitidos = ['', 'normal', 'stock-bajo', 'agotado', 'stock-negativo'];

if (!in_array($estado, $estadosPermitidos, true)) {
    responder(false, null, 'El estado de stock indicado no es válido.', 400);
}


/* ==========================================================
   FILTROS Y PERMISOS
========================================================== */

$condiciones = [
    'ui.estado = TRUE',
    'p.estado = TRUE',
];
$params = [];

$tiendasVisibles = tiendasVisiblesInventario($pdo);
agregarRestriccionTiendas($tiendasVisibles, $condiciones, $params);

if ($idTienda > 0) {
    if ($tiendasVisibles !== null && !in_array($idTienda, $tiendasVisibles, true)) {
        responder(false, null, 'No tiene permiso para consultar la tienda seleccionada.', 403);
    }

    $condiciones[] = 'ui.id_tienda = :id_tienda';
    $params[':id_tienda'] = $idTienda;
}

if ($idUbicacion > 0) {
    $condiciones[] = 'ui.id_ubicacion = :id_ubicacion';
    $params[':id_ubicacion'] = $idUbicacion;
}

if ($idCategoria > 0) {
    $condiciones[] = 'p.id_categoria = :id_categoria';
    $params[':id_categoria'] = $idCategoria;
}

if ($busqueda !== '') {
    $condiciones[] = "(
        p.nombre ILIKE :busqueda
        OR COALESCE(p.codigo_articulo_retail, '') ILIKE :busqueda
        OR COALESCE(p.codigo_barras, '') ILIKE :busqueda
        OR EXISTS (
            SELECT 1
            FROM producto_proveedor pp_busqueda
            JOIN proveedor pr_busqueda
              ON pr_busqueda.id_proveedor = pp_busqueda.id_proveedor
            WHERE pp_busqueda.id_producto = p.id_producto
              AND pp_busqueda.estado = TRUE
              AND pr_busqueda.nombre ILIKE :busqueda
        )
    )";
    $params[':busqueda'] = '%' . $busqueda . '%';
}

switch ($estado) {
    case 'stock-negativo':
        $condiciones[] = 'e.cantidad < 0';
        break;

    case 'agotado':
        $condiciones[] = 'e.cantidad = 0';
        break;

    case 'stock-bajo':
        $condiciones[] = 'e.cantidad > 0 AND e.stock_minimo > 0 AND e.cantidad <= e.stock_minimo';
        break;

    case 'normal':
        $condiciones[] = '(e.cantidad > e.stock_minimo OR e.stock_minimo = 0) AND e.cantidad > 0';
        break;
}

$where = 'WHERE ' . implode("\n AND ", $condiciones);


/* ==========================================================
   CONSULTA PRINCIPAL
========================================================== */

try {
    $sqlBase = "
        FROM existencia e
        JOIN ubicacion_inventario ui
          ON ui.id_ubicacion = e.id_ubicacion
        JOIN tienda t
          ON t.id_tienda = ui.id_tienda
        JOIN producto p
          ON p.id_producto = e.id_producto
        LEFT JOIN categoria c
          ON c.id_categoria = p.id_categoria
        $where
    ";

    $sqlTotal = 'SELECT COUNT(*) ' . $sqlBase;
    $consultaTotal = $pdo->prepare($sqlTotal);

    foreach ($params as $clave => $valor) {
        $consultaTotal->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $consultaTotal->execute();
    $totalRegistros = (int)$consultaTotal->fetchColumn();
    $totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

    if ($pagina > $totalPaginas && $totalRegistros > 0) {
        $pagina = $totalPaginas;
        $offset = ($pagina - 1) * $porPagina;
    }

    $sql = "
        SELECT
            e.id_existencia,
            e.id_ubicacion,
            e.id_producto,
            e.cantidad,
            e.stock_minimo,
            e.fecha_actualizacion,

            ui.id_tienda,
            ui.id_maquina,
            ui.nombre AS ubicacion_nombre,
            ui.tipo AS ubicacion_tipo,

            t.nombre AS tienda_nombre,

            p.nombre AS producto_nombre,
            p.codigo_articulo_retail,
            p.codigo_barras,

            c.id_categoria,
            c.nombre AS categoria_nombre,

            (
                SELECT string_agg(pr.nombre, ', ' ORDER BY pr.nombre)
                FROM producto_proveedor pp
                JOIN proveedor pr
                  ON pr.id_proveedor = pp.id_proveedor
                WHERE pp.id_producto = p.id_producto
                  AND pp.estado = TRUE
            ) AS proveedores,

            ultimo.tipo_movimiento AS ultimo_tipo_movimiento,
            ultimo.fecha_movimiento AS ultimo_movimiento

        FROM existencia e
        JOIN ubicacion_inventario ui
          ON ui.id_ubicacion = e.id_ubicacion
        JOIN tienda t
          ON t.id_tienda = ui.id_tienda
        JOIN producto p
          ON p.id_producto = e.id_producto
        LEFT JOIN categoria c
          ON c.id_categoria = p.id_categoria

        LEFT JOIN LATERAL (
            SELECT
                mi.tipo_movimiento,
                mi.fecha_movimiento
            FROM detalle_movimiento dm
            JOIN movimiento_inventario mi
              ON mi.id_movimiento = dm.id_movimiento
            WHERE dm.id_producto = e.id_producto
              AND mi.estado = 'CONFIRMADO'
              AND (
                    mi.id_ubicacion_origen = e.id_ubicacion
                    OR mi.id_ubicacion_destino = e.id_ubicacion
                  )
            ORDER BY mi.fecha_movimiento DESC, mi.id_movimiento DESC
            LIMIT 1
        ) ultimo ON TRUE

        $where

        ORDER BY
            t.nombre,
            ui.nombre,
            p.nombre

        LIMIT :limite
        OFFSET :offset
    ";

    $consulta = $pdo->prepare($sql);

    foreach ($params as $clave => $valor) {
        $consulta->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $consulta->bindValue(':limite', $porPagina, PDO::PARAM_INT);
    $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consulta->execute();

    $filas = $consulta->fetchAll();

    $inventario = array_map(static function (array $fila): array {
        $cantidad = (int)$fila['cantidad'];
        $stockMinimo = (int)$fila['stock_minimo'];
        $estadoCalculado = estadoStock($cantidad, $stockMinimo);

        return [
            'id_existencia' => (int)$fila['id_existencia'],
            'id_producto' => (int)$fila['id_producto'],
            'producto' => $fila['producto_nombre'],
            'codigo' => $fila['codigo_articulo_retail'] ?: $fila['codigo_barras'],
            'codigo_articulo_retail' => $fila['codigo_articulo_retail'],
            'codigo_barras' => $fila['codigo_barras'],
            'id_categoria' => $fila['id_categoria'] !== null ? (int)$fila['id_categoria'] : null,
            'categoria' => $fila['categoria_nombre'],
            'proveedores' => $fila['proveedores'] ?? '',

            'id_tienda' => (int)$fila['id_tienda'],
            'tienda' => $fila['tienda_nombre'],
            'id_ubicacion' => (int)$fila['id_ubicacion'],
            'ubicacion' => $fila['ubicacion_nombre'],
            'tipo_ubicacion' => $fila['ubicacion_tipo'],
            'id_maquina' => $fila['id_maquina'] !== null ? (int)$fila['id_maquina'] : null,

            'stock_actual' => $cantidad,
            'stock_minimo' => $stockMinimo,
            'estado' => $estadoCalculado['codigo'],
            'estado_texto' => $estadoCalculado['texto'],

            'ultimo_tipo_movimiento' => $fila['ultimo_tipo_movimiento'],
            'ultimo_movimiento' => $fila['ultimo_movimiento'],
            'fecha_actualizacion' => $fila['fecha_actualizacion'],
        ];
    }, $filas);


    /* ==========================================================
       RESUMEN GENERAL VISIBLE PARA EL USUARIO
    ========================================================== */

    $condicionesResumen = [
        'ui.estado = TRUE',
        'p.estado = TRUE',
    ];
    $paramsResumen = [];
    agregarRestriccionTiendas($tiendasVisibles, $condicionesResumen, $paramsResumen);
    $whereResumen = 'WHERE ' . implode("\n AND ", $condicionesResumen);

    $sqlResumen = "
        SELECT
            COALESCE(SUM(e.cantidad), 0) AS total_stock,
            COUNT(*) FILTER (
                WHERE e.cantidad > 0
                  AND e.stock_minimo > 0
                  AND e.cantidad <= e.stock_minimo
            ) AS productos_stock_bajo,
            COUNT(*) FILTER (WHERE e.cantidad = 0) AS productos_agotados,
            COUNT(*) FILTER (WHERE e.cantidad < 0) AS productos_negativos
        FROM existencia e
        JOIN ubicacion_inventario ui
          ON ui.id_ubicacion = e.id_ubicacion
        JOIN producto p
          ON p.id_producto = e.id_producto
        $whereResumen
    ";

    $consultaResumen = $pdo->prepare($sqlResumen);

    foreach ($paramsResumen as $clave => $valor) {
        $consultaResumen->bindValue($clave, $valor, PDO::PARAM_INT);
    }

    $consultaResumen->execute();
    $resumen = $consultaResumen->fetch() ?: [];


    /* ==========================================================
       MOVIMIENTOS CONFIRMADOS DEL DÍA
    ========================================================== */

    $condicionesMovimientos = ["mi.estado = 'CONFIRMADO'", 'mi.fecha_movimiento::date = CURRENT_DATE'];
    $paramsMovimientos = [];

    if ($tiendasVisibles !== null) {
        if (!$tiendasVisibles) {
            $condicionesMovimientos[] = 'FALSE';
        } else {
            $marcadores = [];

            foreach ($tiendasVisibles as $i => $idTiendaVisible) {
                $clave = ':mov_tienda_' . $i;
                $marcadores[] = $clave;
                $paramsMovimientos[$clave] = (int)$idTiendaVisible;
            }

            $lista = implode(', ', $marcadores);
            $condicionesMovimientos[] = "(
                EXISTS (
                    SELECT 1
                    FROM ubicacion_inventario uio
                    WHERE uio.id_ubicacion = mi.id_ubicacion_origen
                      AND uio.id_tienda IN ($lista)
                )
                OR EXISTS (
                    SELECT 1
                    FROM ubicacion_inventario uid
                    WHERE uid.id_ubicacion = mi.id_ubicacion_destino
                      AND uid.id_tienda IN ($lista)
                )
            )";
        }
    }

    $sqlMovimientos = '
        SELECT COUNT(*)
        FROM movimiento_inventario mi
        WHERE ' . implode("\n AND ", $condicionesMovimientos);

    $consultaMovimientos = $pdo->prepare($sqlMovimientos);

    foreach ($paramsMovimientos as $clave => $valor) {
        $consultaMovimientos->bindValue($clave, $valor, PDO::PARAM_INT);
    }

    $consultaMovimientos->execute();
    $movimientosDia = (int)$consultaMovimientos->fetchColumn();



    /* ==========================================================
       OPCIONES DE FILTRO VISIBLES PARA EL USUARIO
    ========================================================== */

    $condicionesFiltros = [
        'ui.estado = TRUE',
        'p.estado = TRUE',
    ];
    $paramsFiltros = [];

    agregarRestriccionTiendas(
        $tiendasVisibles,
        $condicionesFiltros,
        $paramsFiltros
    );

    $whereFiltros =
        'WHERE ' . implode("\n AND ", $condicionesFiltros);

    $sqlUbicaciones = "
        SELECT DISTINCT
            ui.id_ubicacion,
            ui.id_tienda,
            t.nombre AS tienda,
            ui.nombre AS ubicacion,
            ui.tipo
        FROM existencia e
        JOIN ubicacion_inventario ui
          ON ui.id_ubicacion = e.id_ubicacion
        JOIN tienda t
          ON t.id_tienda = ui.id_tienda
        JOIN producto p
          ON p.id_producto = e.id_producto
        $whereFiltros
        ORDER BY
            t.nombre,
            ui.nombre
    ";

    $consultaUbicaciones = $pdo->prepare($sqlUbicaciones);

    foreach ($paramsFiltros as $clave => $valor) {
        $consultaUbicaciones->bindValue(
            $clave,
            $valor,
            PDO::PARAM_INT
        );
    }

    $consultaUbicaciones->execute();

    $ubicacionesFiltro = array_map(
        static function (array $fila): array {
            return [
                'id_ubicacion' =>
                    (int)$fila['id_ubicacion'],

                'id_tienda' =>
                    (int)$fila['id_tienda'],

                'tienda' =>
                    $fila['tienda'],

                'ubicacion' =>
                    $fila['ubicacion'],

                'tipo' =>
                    $fila['tipo'],
            ];
        },
        $consultaUbicaciones->fetchAll()
    );

    $sqlCategorias = "
        SELECT DISTINCT
            c.id_categoria,
            c.nombre
        FROM existencia e
        JOIN ubicacion_inventario ui
          ON ui.id_ubicacion = e.id_ubicacion
        JOIN producto p
          ON p.id_producto = e.id_producto
        JOIN categoria c
          ON c.id_categoria = p.id_categoria
        $whereFiltros
        ORDER BY c.nombre
    ";

    $consultaCategorias = $pdo->prepare($sqlCategorias);

    foreach ($paramsFiltros as $clave => $valor) {
        $consultaCategorias->bindValue(
            $clave,
            $valor,
            PDO::PARAM_INT
        );
    }

    $consultaCategorias->execute();

    $categoriasFiltro = array_map(
        static function (array $fila): array {
            return [
                'id_categoria' =>
                    (int)$fila['id_categoria'],

                'nombre' =>
                    $fila['nombre'],
            ];
        },
        $consultaCategorias->fetchAll()
    );


    responder(true, [
        'inventario' => $inventario,
        'resumen' => [
            'total_stock' => (int)($resumen['total_stock'] ?? 0),
            'productos_stock_bajo' => (int)($resumen['productos_stock_bajo'] ?? 0),
            'productos_agotados' => (int)($resumen['productos_agotados'] ?? 0),
            'productos_negativos' => (int)($resumen['productos_negativos'] ?? 0),
            'movimientos_dia' => $movimientosDia,
        ],
        'paginacion' => [
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_registros' => $totalRegistros,
            'total_paginas' => $totalPaginas,
        ],
        'filtros' => [
            'ubicaciones' => $ubicacionesFiltro,
            'categorias' => $categoriasFiltro,
        ],
        'permisos' => [
            'todas_las_tiendas' => $tiendasVisibles === null,
            'tiendas_visibles' => $tiendasVisibles ?? [],
        ],
    ], 'Stock consultado correctamente.');

} catch (PDOException $e) {
    error_log('[inventario/stock] ' . $e->getMessage());
    responder(false, null, 'No fue posible consultar el inventario.', 500);
}
