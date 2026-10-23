<?php
declare(strict_types=1);

/**
 * Librería compartida — VendingMiniMarket (HU-14, HU-37 a HU-40)
 *
 * La usan api/catalogo/precios.php y api/catalogo/importar.php.
 * No imprime nada por sí sola: solo define funciones.
 * Usa PDO ($pdo), igual que api/catalogo/catalogo.php.
 */

require_once __DIR__ . '/../../config/database.php';   // define $pdo
require_once __DIR__ . '/../../config/session.php';    // inicia la sesión

const LIB_ROLES_ADMIN     = ['administrador', 'admin'];
const LIB_ROLES_IMPORTADOR = ['administrador', 'admin', 'soporte ti', 'soporte_ti', 'soporte-ti', 'soporte'];


/* ==========================================================
   ERRORES CONTROLADOS
========================================================== */

final class PrecioException extends RuntimeException
{
    private int $estadoHttp;

    public function __construct(string $mensaje, int $estadoHttp = 400)
    {
        parent::__construct($mensaje);
        $this->estadoHttp = $estadoHttp;
    }

    public function estadoHttp(): int
    {
        return $this->estadoHttp;
    }
}


/* ==========================================================
   UTILIDADES GENERALES
========================================================== */

function libJson(bool $ok, $data = null, string $mensaje = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode(
        ['success' => $ok, 'data' => $data, 'message' => $mensaje],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

function libDb(): PDO
{
    global $pdo;

    if (!$pdo instanceof PDO) {
        libJson(false, null, 'La conexión a la base de datos no está disponible.', 500);
    }

    return $pdo;
}

function libBool($valor): bool
{
    return $valor === true || $valor === 1 || $valor === '1' || $valor === 't'
        || $valor === 'true' || $valor === 'on';
}

function libRol(): string
{
    return mb_strtolower(trim((string)($_SESSION['usuario']['rol'] ?? '')), 'UTF-8');
}

function libUsuarioId(): ?int
{
    return (int)($_SESSION['usuario']['id_usuario'] ?? 0) ?: null;
}

function libUsuario(): array
{
    if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
        libJson(false, null, 'Debe iniciar sesión.', 401);
    }

    return $_SESSION['usuario'];
}

function libAdmin(): array
{
    $usuario = libUsuario();

    if (!in_array(libRol(), LIB_ROLES_ADMIN, true)) {
        libJson(false, null, 'No tiene permisos de administrador.', 403);
    }

    return $usuario;
}

/** HU-14: administrador o Soporte TI autorizado. */
function libImportador(): array
{
    $usuario = libUsuario();

    if (!in_array(libRol(), LIB_ROLES_IMPORTADOR, true)) {
        libJson(false, null, 'Solo un administrador o Soporte TI autorizado puede importar datos.', 403);
    }

    return $usuario;
}

function libPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        libJson(false, null, 'Método no permitido.', 405);
    }
}

function libEntrada(): array
{
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo === false ? '' : $crudo, true);

    return is_array($datos) ? $datos : $_POST;
}

/** Minúsculas, sin tildes ni símbolos: "Coca-Cola 600 ml" => "cocacola600ml". */
function libNormalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);

    return preg_replace('/[^a-z0-9]/', '', $texto) ?? '';
}

/** Misma normalización de arriba, pero en SQL. */
function libSqlNormalizado(string $columna): string
{
    return "regexp_replace(translate(lower($columna), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun'), '[^a-z0-9]', '', 'g')";
}

/** Bitácora (tabla bitacora_auditoria), igual que en catalogo.php. */
function libAuditar(PDO $db, string $entidad, string $accion, ?array $antes, ?array $despues): void
{
    $st = $db->prepare("
        INSERT INTO bitacora_auditoria
            (id_usuario, entidad, accion, valor_anterior, valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:id_usuario, :entidad, :accion, CAST(:anterior AS jsonb), CAST(:nuevo AS jsonb), 'EXITOSO', :ip, NOW())
    ");

    $st->execute([
        ':id_usuario' => libUsuarioId(),
        ':entidad'    => $entidad,
        ':accion'     => $accion,
        ':anterior'   => $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
        ':nuevo'      => $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

/** Convierte a número los campos indicados (PostgreSQL devuelve NUMERIC como texto). */
function libNumericos(array $filas, array $campos): array
{
    foreach ($filas as &$fila) {
        foreach ($campos as $campo) {
            if (array_key_exists($campo, $fila) && $fila[$campo] !== null) {
                $fila[$campo] = (float)$fila[$campo];
            }
        }
    }
    unset($fila);

    return $filas;
}


/* ==========================================================
   PRECIOS — CÁLCULO Y APLICACIÓN (HU-38, HU-39, HU-40)
========================================================== */

/** Ejecuta $accion en una transacción (o dentro de la que ya esté abierta). */
function plTransaccion(PDO $db, callable $accion)
{
    $propia = !$db->inTransaction();

    if ($propia) {
        $db->beginTransaction();
    }

    try {
        $resultado = $accion();

        if ($propia) {
            $db->commit();
        }

        return $resultado;
    } catch (Throwable $e) {
        if ($propia && $db->inTransaction()) {
            $db->rollBack();
        }

        throw $e;
    }
}

function plNumero($valor, string $etiqueta, float $min = 0.0, float $max = 9999999999.0): float
{
    if (is_string($valor)) {
        $valor = trim($valor);
    }

    if ($valor === '' || $valor === null || !is_numeric($valor)) {
        throw new PrecioException("$etiqueta debe ser un número.");
    }

    $n = (float)$valor;

    if (!is_finite($n) || $n < $min || $n > $max) {
        throw new PrecioException(
            "$etiqueta debe estar entre " . number_format($min, 2, '.', '') .
            ' y ' . number_format($max, 2, '.', '') . '.'
        );
    }

    return round($n, 2);
}

/**
 * Misma regla que el trigger fn_calcular_precio_tienda de la base de datos:
 *
 *   costo_con_impuesto = ROUND(costo × (1 + IVA/100), 2)
 *   precio_calculado   = costo_con_impuesto × (1 + margen/100)
 *   precio_venta       = CEIL(precio_calculado / 25) × 25      (siempre hacia arriba, múltiplo de ₡25)
 *
 * El margen es un recargo (40 = factor 1.40). Al guardar, la base vuelve a calcular y manda su resultado.
 */
function plCalcular(float $costo, float $margen, float $iva): array
{
    $conImpuesto = round($costo * (1 + $iva / 100), 2);
    $calculado   = $conImpuesto * (1 + $margen / 100);

    // round(...,6) evita que un múltiplo exacto de 25 suba otro escalón por coma flotante.
    $venta = round(ceil(round($calculado / 25, 6)) * 25, 2);

    if ($venta > 9999999999.99) {
        throw new PrecioException('El precio de venta calculado supera el máximo permitido.');
    }

    return [
        'costo_con_impuesto' => $conImpuesto,
        'precio_calculado'   => round($calculado, 2),
        'precio_venta'       => $venta,
    ];
}

/** Lee y valida los ids de producto, tienda y (opcional) margen de la entrada. */
function plIds(array $in, bool $conMargen = true): array
{
    $producto = (int)($in['id_producto'] ?? 0);
    $tienda   = (int)($in['id_tienda'] ?? 0);
    $margen   = (int)($in['id_margen'] ?? 0);

    if ($producto <= 0 || $tienda <= 0 || ($conMargen && $margen <= 0)) {
        throw new PrecioException(
            $conMargen ? 'Producto, tienda y margen son obligatorios.' : 'Producto y tienda son obligatorios.'
        );
    }

    return [$producto, $tienda, $margen];
}

/** Valida que producto (activo), tienda, margen (activo) e IVA del producto existan. */
function plReferencias(PDO $db, int $idProducto, int $idTienda, int $idMargen): array
{
    $st = $db->prepare('
        SELECT id_producto, nombre, codigo_articulo_retail, id_impuesto
        FROM public.producto
        WHERE id_producto = :id AND estado = TRUE
    ');
    $st->execute([':id' => $idProducto]);
    $producto = $st->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new PrecioException('Producto inexistente o inactivo.', 404);
    }

    $st = $db->prepare('SELECT 1 FROM public.tienda WHERE id_tienda = :id');
    $st->execute([':id' => $idTienda]);

    if (!$st->fetchColumn()) {
        throw new PrecioException('La tienda seleccionada no existe.');
    }

    $st = $db->prepare('SELECT id_margen, nombre, porcentaje FROM public.margen_ganancia WHERE id_margen = :id AND estado = TRUE');
    $st->execute([':id' => $idMargen]);
    $margen = $st->fetch(PDO::FETCH_ASSOC);

    if (!$margen) {
        throw new PrecioException('El margen no existe o está inactivo.');
    }

    $st = $db->prepare('SELECT porcentaje FROM public.impuesto WHERE id_impuesto = :id AND estado = TRUE');
    $st->execute([':id' => (int)$producto['id_impuesto']]);
    $iva = $st->fetchColumn();

    if ($iva === false) {
        throw new PrecioException('El impuesto del producto no existe o está inactivo.');
    }

    return ['producto' => $producto, 'margen' => $margen, 'iva' => (float)$iva];
}

function plPrecioActivo(PDO $db, int $idProducto, int $idTienda, bool $bloquear = false): ?array
{
    // precio_tienda.precio_con_impuesto es el costo con IVA; se expone como costo_con_impuesto.
    $sql = 'SELECT pt.*, pt.precio_con_impuesto AS costo_con_impuesto
            FROM public.precio_tienda pt
            WHERE pt.id_producto = :p AND pt.id_tienda = :t AND pt.estado = TRUE AND pt.fecha_fin IS NULL
            ORDER BY pt.fecha_inicio DESC, pt.id_precio DESC LIMIT 1';

    if ($bloquear) {
        $sql .= ' FOR UPDATE OF pt';
    }

    $st = $db->prepare($sql);
    $st->execute([':p' => $idProducto, ':t' => $idTienda]);
    $fila = $st->fetch(PDO::FETCH_ASSOC);

    return $fila ?: null;
}

/**
 * Aplica un precio: guarda el cambio en historial_precio, cierra la versión
 * activa anterior (estado = FALSE, fecha_fin) y crea la nueva. Nunca borra.
 * Debe llamarse dentro de una transacción (plTransaccion).
 */
function plAplicar(PDO $db, array $refs, int $idTienda, float $costo, ?int $idUsuario): array
{
    $idProducto = (int)$refs['producto']['id_producto'];
    $idMargen   = (int)$refs['margen']['id_margen'];
    $iva        = (float)$refs['iva'];
    $calc       = plCalcular($costo, (float)$refs['margen']['porcentaje'], $iva);
    $actual     = plPrecioActivo($db, $idProducto, $idTienda, true);

    if ($actual) {
        $st = $db->prepare('UPDATE public.precio_tienda SET estado = FALSE, fecha_fin = NOW() WHERE id_precio = :id');
        $st->execute([':id' => $actual['id_precio']]);
    }

    $st = $db->prepare('
        INSERT INTO public.precio_tienda
            (id_producto, id_tienda, id_margen, costo, precio_con_impuesto, precio_venta, fecha_inicio, fecha_fin, estado)
        VALUES
            (:p, :t, :m, :c, :s, :v, NOW(), NULL, TRUE)
        RETURNING id_precio, precio_con_impuesto, precio_venta
    ');
    $st->execute([
        ':p' => $idProducto,
        ':t' => $idTienda,
        ':m' => $idMargen,
        ':c' => $costo,
        ':s' => $calc['costo_con_impuesto'],
        ':v' => $calc['precio_venta'],
    ]);
    $guardado = $st->fetch(PDO::FETCH_ASSOC);
    $idPrecio = (int)$guardado['id_precio'];

    // El trigger de la base recalcula el precio: se usa SIEMPRE lo que quedó guardado.
    $calc['costo_con_impuesto'] = (float)$guardado['precio_con_impuesto'];
    $calc['precio_venta']       = (float)$guardado['precio_venta'];

    $h = $db->prepare('
        INSERT INTO public.historial_precio
            (id_producto, id_tienda, costo_anterior, costo_con_impuesto_anterior, precio_venta_anterior,
             costo_nuevo, costo_con_impuesto_nuevo, precio_venta_nuevo, id_margen, porcentaje_iva, id_usuario)
        VALUES
            (:p, :t, :ca, :sa, :va, :cn, :sn, :vn, :m, :iva, :u)
    ');
    $h->execute([
        ':p'   => $idProducto,
        ':t'   => $idTienda,
        ':ca'  => $actual ? $actual['costo'] : null,
        ':sa'  => $actual ? $actual['costo_con_impuesto'] : null,
        ':va'  => $actual ? $actual['precio_venta'] : null,
        ':cn'  => $costo,
        ':sn'  => $calc['costo_con_impuesto'],
        ':vn'  => $calc['precio_venta'],
        ':m'   => $idMargen,
        ':iva' => $iva,
        ':u'   => $idUsuario,
    ]);

    $resultado = [
        'id_precio'      => $idPrecio,
        'id_producto'    => $idProducto,
        'id_tienda'      => $idTienda,
        'id_margen'      => $idMargen,
        'margen_nombre'  => $refs['margen']['nombre'],
        'margen_porcentaje' => (float)$refs['margen']['porcentaje'],
        'costo'          => $costo,
        'porcentaje_iva' => $iva,
    ] + $calc;

    libAuditar(
        $db,
        'PRECIO',
        'APLICAR',
        $actual ? [
            'id_producto'         => $idProducto,
            'id_tienda'           => $idTienda,
            'costo'               => (float)$actual['costo'],
            'costo_con_impuesto'  => (float)$actual['costo_con_impuesto'],
            'precio_venta'        => (float)$actual['precio_venta'],
        ] : null,
        $resultado
    );

    return $resultado;
}


/* ==========================================================
   CÓDIGO INTERNO CR (CR600, CR601...)
========================================================== */

/** Número del siguiente código CR: el mayor CR existente + 1, nunca menor que 600. */
function libNumeroSiguienteCR(PDO $db): int
{
    $maximo = (int)$db->query(
        "SELECT COALESCE(MAX(SUBSTRING(codigo_articulo_retail FROM 3)::BIGINT), 0)
         FROM public.producto WHERE codigo_articulo_retail ~* '^CR[0-9]{1,15}$'"
    )->fetchColumn();

    return max(600, $maximo + 1);
}

/** Solo para mostrarlo: no reserva ni consume nada. */
function libSiguienteCodigoCR(PDO $db): string
{
    return 'CR' . libNumeroSiguienteCR($db);
}

/**
 * Código para un producto nuevo. Debe llamarse dentro de una transacción:
 * el candado evita que dos altas simultáneas reciban el mismo código y,
 * como ve las filas aún sin confirmar, sirve también para importar muchas a la vez.
 */
function libGenerarCodigoCR(PDO $db): string
{
    $db->query('SELECT pg_advisory_xact_lock(600600)');

    return libSiguienteCodigoCR($db);
}
