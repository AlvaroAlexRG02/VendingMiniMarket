<?php

/**
 * Funciones compartidas por los reportes y el dashboard.
 *
 *  - Alcance por rol: tiendasPermitidas().
 *  - Ventas confirmadas (manuales + Nayax): sqlLineasVenta().
 *  - Utilidades de fechas, parametros SQL y respuestas JSON.
 */

require_once __DIR__ . '/ubicaciones.php';

const ZONA_REPORTES = 'America/Costa_Rica';
const MOTIVOS_PERDIDA = ['VENCIMIENTO', 'DAÑO', 'ROBO', 'PÉRDIDA'];

/**
 * Con $GLOBALS['REPORTES_CAPTURAR'] activo, respuesta() lanza esta excepción en
 * lugar de imprimir JSON, para reutilizar las consultas de cada reporte
 * (por ejemplo, al exportar a PDF).
 */
final class RespuestaCapturada extends Exception
{
    public function __construct(public bool $exito, public $datos, public string $texto, public int $estado)
    {
        parent::__construct($texto);
    }
}

function respuesta(bool $success, $data = null, string $message = '', int $status = 200): void
{
    if (!empty($GLOBALS['REPORTES_CAPTURAR'])) {
        throw new RespuestaCapturada($success, $data, $message, $status);
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(
        ['success' => $success, 'data' => $data, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}


/** Tiendas que el usuario puede consultar: [id_tienda => nombre]. */
function tiendasPermitidas(PDO $pdo): array
{
    if (esAdministradorOGerente()) {
        $filas = $pdo->query('SELECT id_tienda, nombre FROM tienda WHERE estado = TRUE ORDER BY nombre')->fetchAll();
    } else {
        $filas = ubicacionesAsignadas($pdo, (int)$_SESSION['usuario']['id_usuario']);
        $filas = array_values(array_filter($filas, fn ($t) => $t['estado']));
    }

    $tiendas = [];
    foreach ($filas as $fila) {
        $tiendas[(int)$fila['id_tienda']] = $fila['nombre'];
    }

    return $tiendas;
}


/** Acumulador de parámetros con nombres únicos (PDO pgsql no permite repetir nombres). */
final class Parametros
{
    public array $valores = [];
    private int $n = 0;

    public function agregar($valor): string
    {
        $clave = ':p' . (++$this->n);
        $this->valores[$clave] = $valor;
        return $clave;
    }
}


function fechaValida(string $fecha): bool
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
}


function listaEnteros(array $ids): string
{
    return implode(',', array_map('intval', $ids)) ?: '0';
}


function tablaNayaxExiste(PDO $pdo): bool
{
    static $existe = null;

    if ($existe === null) {
        $existe = in_array(
            $pdo->query("SELECT to_regclass('public.venta_nayax') IS NOT NULL")->fetchColumn(),
            [true, 't', 1, '1'],
            true
        );
    }

    return $existe;
}


/** Costo vigente de un producto en una tienda, para ventas sin costo y para bajas. */
function sqlCostoVigente(string $idProducto, string $idTienda): string
{
    return "COALESCE(
        (SELECT pt.costo FROM precio_tienda pt
          WHERE pt.id_producto = $idProducto AND pt.id_tienda = $idTienda AND pt.estado = TRUE
          ORDER BY pt.fecha_inicio DESC LIMIT 1),
        (SELECT MIN(pp.costo) FROM producto_proveedor pp
          WHERE pp.id_producto = $idProducto AND pp.estado = TRUE)
    )";
}


/**
 * Subconsulta "lineas": una fila por línea vendida y confirmada (manual + Nayax).
 * Columnas: fuente, id_venta, fecha (date local), id_tienda, id_producto,
 *           cantidad, subtotal, impuesto, total, costo_total, sin_costo.
 */
function sqlLineasVenta(PDO $pdo, array $f, Parametros $par): string
{
    $inicio = $par->agregar($f['inicio']);
    $fin    = $par->agregar($f['fin']);
    $zona   = ZONA_REPORTES;
    $tiendas = listaEnteros($f['ids_tiendas']);

    $costoManual = sqlCostoVigente('d.id_producto', 'v.id_tienda');

    $sql = "
        SELECT 'MANUAL' AS fuente, v.id_venta::text AS id_venta,
               (v.fecha AT TIME ZONE '$zona')::date AS fecha,
               v.id_tienda, d.id_producto, d.cantidad,
               d.subtotal, d.impuesto, d.total,
               COALESCE(d.costo_unitario, $costoManual, 0) * d.cantidad AS costo_total,
               (d.costo_unitario IS NULL AND $costoManual IS NULL) AS sin_costo
        FROM venta_manual v
        JOIN venta_manual_detalle d ON d.id_venta = v.id_venta
        JOIN movimiento_inventario m ON m.id_movimiento = v.id_movimiento AND m.estado = 'CONFIRMADO'
        WHERE v.id_tienda IN ($tiendas)
          AND (v.fecha AT TIME ZONE '$zona')::date BETWEEN CAST($inicio AS DATE) AND CAST($fin AS DATE)
    ";

    if (tablaNayaxExiste($pdo)) {
        $inicioN = $par->agregar($f['inicio']);
        $finN    = $par->agregar($f['fin']);
        $costoNayax = sqlCostoVigente('n.id_producto', 'n.id_tienda');
        $sql .= "
        UNION ALL
        SELECT 'NAYAX', n.id_venta_nayax::text,
               (n.fecha AT TIME ZONE '$zona')::date,
               n.id_tienda, n.id_producto, n.cantidad,
               n.subtotal, n.impuesto, n.total,
               COALESCE(n.costo_unitario, $costoNayax, 0) * n.cantidad,
               (n.costo_unitario IS NULL AND $costoNayax IS NULL)
        FROM venta_nayax n
        WHERE n.estado = 'CONFIRMADA'
          AND n.id_tienda IN ($tiendas)
          AND (n.fecha AT TIME ZONE '$zona')::date BETWEEN CAST($inicioN AS DATE) AND CAST($finN AS DATE)
        ";
    }

    return $sql;
}


function numerico(array $filas, array $campos): array
{
    foreach ($filas as &$fila) {
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $fila)) {
                $fila[$campo] = (float)$fila[$campo];
            }
        }
    }
    unset($fila);

    return $filas;
}


function hoyCostaRica(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone(ZONA_REPORTES)))->format('Y-m-d');
}

