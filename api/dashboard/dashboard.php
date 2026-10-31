<?php

/**
 * API del dashboard — VendingMiniMarket
 *
 * HU-48  Dashboard principal segun rol.
 *   Administrador / Gerente General -> vista consolidada de todas las ubicaciones
 *                                      (pueden filtrar por tienda), con utilidad estimada.
 *   Encargado / Dependiente         -> solo la informacion de su ubicacion autorizada.
 *
 * accion=resumen&id_tienda=&fecha=YYYY-MM-DD
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/ubicaciones.php';
require_once __DIR__ . '/../../config/reportes_lib.php';

function accionResumen(PDO $pdo): void
{
    if (empty($_SESSION['usuario'])) {
        respuesta(false, null, 'Debe iniciar sesión.', 401);
    }

    $tiendas = tiendasPermitidas($pdo);
    $consolidado = esAdministradorOGerente();

    $listaTiendas = [];
    foreach ($tiendas as $id => $nombre) {
        $listaTiendas[] = ['id_tienda' => $id, 'nombre' => $nombre];
    }

    $fecha = trim((string)($_GET['fecha'] ?? ''));
    if (!fechaValida($fecha)) {
        $fecha = hoyCostaRica();
    }

    $idTienda = (int)($_GET['id_tienda'] ?? 0);
    if ($idTienda > 0 && !isset($tiendas[$idTienda])) {
        respuesta(false, null, 'No tiene acceso a la tienda seleccionada.', 403);
    }

    $base = [
        'fecha'               => $fecha,
        'consolidado'         => $consolidado,
        'tiendas'             => $listaTiendas,
        'puede_elegir_tienda' => count($tiendas) > 1,
        'puede_ver_utilidad'  => $consolidado,
    ];

    if (!$tiendas) {
        respuesta(true, $base + [
            'ventas' => ['total' => 0, 'unidades' => 0, 'ventas' => 0],
            'utilidad' => null,
            'por_tienda' => [],
            'tendencia' => [],
            'inventario' => ['criticos' => 0, 'agotados' => 0, 'bajo_minimo' => 0, 'negativos' => 0],
            'alertas' => ['pendientes' => 0],
            'movimientos' => ['total' => 0, 'ultimos' => []],
        ]);
    }

    $ids = $idTienda > 0 ? [$idTienda] : array_keys($tiendas);
    $lista = listaEnteros($ids);
    $zona = ZONA_REPORTES;

    // Ventas confirmadas (manuales + Nayax) de los 7 días que terminan en la fecha elegida, en una sola consulta.
    $inicioTendencia = (new DateTimeImmutable($fecha))->modify('-6 days')->format('Y-m-d');
    $par = new Parametros();
    $lineas = sqlLineasVenta($pdo, ['inicio' => $inicioTendencia, 'fin' => $fecha, 'ids_tiendas' => $ids], $par);

    $st = $pdo->prepare("
        WITH lineas AS ($lineas)
        SELECT fecha, id_tienda, COUNT(DISTINCT fuente || id_venta) AS ventas, SUM(cantidad) AS unidades,
               SUM(subtotal) AS subtotal, SUM(total) AS total, SUM(costo_total) AS costo
        FROM lineas GROUP BY fecha, id_tienda
    ");
    $st->execute($par->valores);

    $ventas = ['total' => 0.0, 'subtotal' => 0.0, 'unidades' => 0.0, 'ventas' => 0.0, 'costo' => 0.0];
    $ventasPorTienda = [];
    $ingresosPorDia = [];

    foreach (numerico($st->fetchAll(), ['ventas', 'unidades', 'subtotal', 'total', 'costo']) as $fila) {
        $dia = substr((string)$fila['fecha'], 0, 10);
        $ingresosPorDia[$dia] = ($ingresosPorDia[$dia] ?? 0.0) + $fila['subtotal'];

        if ($dia !== $fecha) {
            continue;
        }

        $ventasPorTienda[(int)$fila['id_tienda']] = $fila;
        foreach ($ventas as $clave => $_) {
            $ventas[$clave] += $fila[$clave];
        }
    }

    $porTienda = [];
    foreach ($ids as $id) {
        $fila = $ventasPorTienda[$id] ?? ['total' => 0.0, 'unidades' => 0.0, 'ventas' => 0.0];
        $porTienda[] = ['tienda' => $tiendas[$id], 'total' => $fila['total'], 'unidades' => $fila['unidades'], 'ventas' => $fila['ventas']];
    }
    usort($porTienda, fn ($a, $b) => $b['total'] <=> $a['total']);

    $tendencia = [];
    for ($i = 6; $i >= 0; $i--) {
        $dia = (new DateTimeImmutable($fecha))->modify("-$i days")->format('Y-m-d');
        $tendencia[] = ['fecha' => $dia, 'ingresos' => $ingresosPorDia[$dia] ?? 0.0];
    }

    $utilidad = null;
    if ($consolidado) {
        $valor = $ventas['subtotal'] - $ventas['costo'];
        $utilidad = [
            'valor'  => round($valor, 2),
            'margen' => $ventas['subtotal'] > 0 ? round($valor / $ventas['subtotal'] * 100, 1) : 0.0,
        ];
    }

    // Inventario crítico, alertas pendientes y total de movimientos del día en una sola consulta.
    $ubicaciones = "SELECT id_ubicacion FROM ubicacion_inventario WHERE id_tienda IN ($lista)";
    $parMov = new Parametros();
    $fechaMov = $parMov->agregar($fecha);
    $filtroMov = "(m.fecha_movimiento AT TIME ZONE '$zona')::date = CAST($fechaMov AS DATE)
                  AND (m.id_ubicacion_origen IN ($ubicaciones) OR m.id_ubicacion_destino IN ($ubicaciones))";

    $st = $pdo->prepare("
        SELECT
            (SELECT COUNT(*) FROM existencia e
               JOIN ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion AND u.estado = TRUE
               JOIN producto p ON p.id_producto = e.id_producto AND p.estado = TRUE
              WHERE u.id_tienda IN ($lista) AND e.cantidad = 0) AS agotados,
            (SELECT COUNT(*) FROM existencia e
               JOIN ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion AND u.estado = TRUE
               JOIN producto p ON p.id_producto = e.id_producto AND p.estado = TRUE
              WHERE u.id_tienda IN ($lista) AND e.cantidad > 0 AND e.cantidad <= e.stock_minimo) AS bajo_minimo,
            (SELECT COUNT(*) FROM existencia e
               JOIN ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion AND u.estado = TRUE
               JOIN producto p ON p.id_producto = e.id_producto AND p.estado = TRUE
              WHERE u.id_tienda IN ($lista) AND e.cantidad < 0) AS negativos,
            (SELECT COUNT(*) FROM alerta_inventario a
               JOIN ubicacion_inventario u ON u.id_ubicacion = a.id_ubicacion
              WHERE a.estado = 'PENDIENTE' AND u.id_tienda IN ($lista)) AS alertas,
            (SELECT COUNT(*) FROM movimiento_inventario m WHERE $filtroMov) AS movimientos
    ");
    $st->execute($parMov->valores);
    $conteos = numerico([$st->fetch()], ['agotados', 'bajo_minimo', 'negativos', 'alertas', 'movimientos'])[0];

    $inv = [
        'agotados'    => $conteos['agotados'],
        'bajo_minimo' => $conteos['bajo_minimo'],
        'negativos'   => $conteos['negativos'],
        'criticos'    => $conteos['agotados'] + $conteos['bajo_minimo'] + $conteos['negativos'],
    ];
    $alertasPendientes = (int)$conteos['alertas'];
    $totalMov = (int)$conteos['movimientos'];

    $st = $pdo->prepare("
        SELECT m.fecha_movimiento AS fecha, m.tipo_movimiento AS tipo, m.estado, m.motivo, m.id_pedido,
               TRIM(u.nombre || ' ' || COALESCE(u.apellido, '')) AS usuario,
               (SELECT COUNT(*) FROM detalle_movimiento d WHERE d.id_movimiento = m.id_movimiento) AS lineas,
               (SELECT STRING_AGG(p.nombre || ': ' || x.cantidad || ' u.', ', ' ORDER BY p.nombre)
                  FROM (SELECT id_producto, cantidad FROM detalle_movimiento WHERE id_movimiento = m.id_movimiento LIMIT 2) x
                  JOIN producto p ON p.id_producto = x.id_producto) AS detalle
        FROM movimiento_inventario m
        JOIN usuario u ON u.id_usuario = m.id_usuario
        WHERE $filtroMov
        ORDER BY m.fecha_movimiento DESC
        LIMIT 8
    ");
    $st->execute($parMov->valores);
    $ultimos = array_map(function (array $m): array {
        $detalle = $m['detalle'] ?? '';
        if ((int)$m['lineas'] > 2) {
            $detalle .= ' y ' . ((int)$m['lineas'] - 2) . ' más';
        }

        return [
            'fecha'       => $m['fecha'],
            'tipo'        => $m['tipo'],
            'estado'      => $m['estado'],
            'descripcion' => $detalle !== '' ? $detalle : ($m['motivo'] ?? '-'),
            'referencia'  => $m['id_pedido'] ? 'PED-' . str_pad((string)$m['id_pedido'], 6, '0', STR_PAD_LEFT) : ($m['motivo'] ?? '-'),
            'usuario'     => $m['usuario'],
        ];
    }, $st->fetchAll());

    respuesta(true, $base + [
        'ventas'      => $ventas,
        'utilidad'    => $utilidad,
        'por_tienda'  => $porTienda,
        'tendencia'   => $tendencia,
        'inventario'  => $inv,
        'alertas'     => ['pendientes' => $alertasPendientes],
        'movimientos' => ['total' => $totalMov, 'ultimos' => $ultimos],
    ]);
}

$accion = (string)($_GET['accion'] ?? 'resumen');

try {
    if ($accion !== 'resumen') {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    accionResumen($pdo);
} catch (Throwable $e) {
    error_log('[dashboard] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
