<?php

/**
 * API del catálogo — VendingMiniMarket
 *
 * Tablas reales: producto, categoria, impuesto, proveedor,
 * producto_proveeador y bitacora_auditoria (historial).
 *
 * HU-08  Registrar productos           crear_producto
 * HU-09  Editar / inactivar            editar_producto, inactivar_producto, activar_producto, historial_producto
 * HU-10  Buscar                        listar_productos?busqueda=
 * HU-11  Categorías                    listar/crear/editar/inactivar/activar_categoria
 * HU-12  Proveedores                   listar/crear/editar/inactivar/activar_proveedor
 * HU-13  Duplicados                    verificarDuplicados()
 * HU-15  Consultar y exportar          listar_productos, exportar_productos
 *
 * Usa PDO ($pdo), igual que api/auth/login.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';   // define $pdo
require_once __DIR__ . '/../../config/session.php';    // inicia la sesión

const POR_PAGINA_DEFECTO = 25;
const POR_PAGINA_MAXIMO  = 100;


/* ==========================================================
   UTILIDADES GENERALES
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

function esAdministrador(): bool
{
    $rol = mb_strtolower(trim((string)($_SESSION['usuario']['rol'] ?? '')), 'UTF-8');

    return in_array($rol, ['administrador', 'admin'], true);
}

function exigirAdministrador(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador()) {
        respuesta(false, null, 'No tiene permisos de administrador.', 403);
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

function aBool($valor): bool
{
    return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
}

function manejarErrorBD(PDOException $e, string $mensajeGeneral): void
{
    $codigo = (string)$e->getCode();

    if ($codigo === '23505') {
        respuesta(false, null, 'Ya existe un registro con el mismo código o nombre.', 409);
    }

    if ($codigo === '23503') {
        respuesta(false, null, 'La operación se relaciona con un registro que no existe.', 409);
    }

    error_log('[catalogo] ' . $e->getMessage());
    respuesta(false, null, $mensajeGeneral, 500);
}

/** Ejecuta $accion dentro de una transacción; revierte todo si algo falla. */
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


/* ==========================================================
   BITÁCORA (historial) — tabla bitacora_auditoria
========================================================== */

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
   VALIDACIÓN DE CAMPOS
========================================================== */

function campoTexto(array $in, string $clave, int $max, bool $obligatorio, string $etiqueta): ?string
{
    $valor = trim((string)($in[$clave] ?? ''));

    if ($valor === '') {
        if ($obligatorio) {
            respuesta(false, null, "$etiqueta es obligatorio.", 400);
        }
        return null;
    }

    if (mb_strlen($valor, 'UTF-8') > $max) {
        respuesta(false, null, "$etiqueta no puede superar $max caracteres.", 400);
    }

    return $valor;
}

/** Minúsculas, sin tildes ni símbolos: "Coca-Cola 600 ml" => "cocacola600ml". */
function normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);

    return preg_replace('/[^a-z0-9]/', '', $texto) ?? '';
}

/** Misma normalización de arriba, pero en SQL. */
function sqlNormalizado(string $columna): string
{
    return "regexp_replace(translate(lower($columna), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun'), '[^a-z0-9]', '', 'g')";
}


/* ==========================================================
   PRODUCTOS — LECTURA
========================================================== */

const SQL_PRODUCTO_BASE = "
    SELECT
        p.id_producto,
        p.id_categoria,
        p.id_impuesto,
        p.codigo_articulo_retail,
        p.codigo_barras,
        p.nombre,
        p.descripcion,
        p.estado,
        c.nombre     AS categoria_nombre,
        i.nombre     AS impuesto_nombre,
        i.porcentaje AS impuesto_porcentaje,
        (
            SELECT string_agg(pr.nombre, ', ' ORDER BY pr.nombre)
            FROM producto_proveedor pp
            JOIN proveedor pr ON pr.id_proveedor = pp.id_proveedor
            WHERE pp.id_producto = p.id_producto AND pp.estado = TRUE
        ) AS proveedores_texto
    FROM producto p
    JOIN categoria c ON c.id_categoria = p.id_categoria
    JOIN impuesto  i ON i.id_impuesto  = p.id_impuesto
";

function normalizarFilaProducto(array $f): array
{
    $f['id_producto']         = (int)$f['id_producto'];
    $f['id_categoria']        = (int)$f['id_categoria'];
    $f['id_impuesto']         = (int)$f['id_impuesto'];
    $f['impuesto_porcentaje'] = (float)$f['impuesto_porcentaje'];
    $f['estado']              = aBool($f['estado']);

    return $f;
}

function obtenerProducto(int $id): ?array
{
    $st = db()->prepare(SQL_PRODUCTO_BASE . ' WHERE p.id_producto = :id');
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ? normalizarFilaProducto($fila) : null;
}

function proveedoresDeProducto(int $idProducto): array
{
    $st = db()->prepare("
        SELECT pp.id_proveedor, pr.nombre, pp.costo, pr.estado AS proveedor_activo
        FROM producto_proveedor pp
        JOIN proveedor pr ON pr.id_proveedor = pp.id_proveedor
        WHERE pp.id_producto = :id AND pp.estado = TRUE
        ORDER BY pr.nombre
    ");
    $st->execute([':id' => $idProducto]);

    return array_map(function (array $f): array {
        return [
            'id_proveedor'     => (int)$f['id_proveedor'],
            'nombre'           => $f['nombre'],
            'costo'            => $f['costo'] !== null ? (float)$f['costo'] : null,
            'proveedor_activo' => aBool($f['proveedor_activo']),
        ];
    }, $st->fetchAll());
}

/** WHERE compartido por la consulta y la exportación (HU-10, HU-15). */
function filtrosProductos(array &$params): string
{
    $condiciones = [];

    $busqueda = trim((string)($_GET['busqueda'] ?? ''));
    if ($busqueda !== '') {
        $partes = [];

        $normalizada = normalizar($busqueda);
        if ($normalizada !== '') {
            // Nombre: sin distinguir tildes, mayúsculas ni símbolos.
            $partes[] = sqlNormalizado('p.nombre') . ' LIKE :b_nombre';
            $params[':b_nombre'] = '%' . $normalizada . '%';
        }

        // Códigos: coincidencia parcial literal. Se usa '!' como carácter de
        // escape (no '\') porque el analizador de PDO trata '\' como escape
        // dentro de las comillas y rompería los parámetros.
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda) . '%';
        $partes[] = "COALESCE(p.codigo_articulo_retail, '') ILIKE :b_interno ESCAPE '!'";
        $partes[] = "COALESCE(p.codigo_barras, '') ILIKE :b_barras ESCAPE '!'";
        $params[':b_interno'] = $like;
        $params[':b_barras']  = $like;

        $condiciones[] = '(' . implode(' OR ', $partes) . ')';
    }

    $categoria = (int)($_GET['categoria'] ?? 0);
    if ($categoria > 0) {
        $condiciones[] = 'p.id_categoria = :f_categoria';
        $params[':f_categoria'] = $categoria;
    }

    $estado = mb_strtolower(trim((string)($_GET['estado'] ?? '')), 'UTF-8');
    if ($estado === 'activos' || $estado === 'activo') {
        $condiciones[] = 'p.estado = TRUE';
    } elseif ($estado === 'inactivos' || $estado === 'inactivo') {
        $condiciones[] = 'p.estado = FALSE';
    }

    return $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
}

function accionListarProductos(array $in): void
{
    exigirSesion();

    $params = [];
    $where  = filtrosProductos($params);

    $porPagina = (int)($_GET['por_pagina'] ?? POR_PAGINA_DEFECTO);
    $porPagina = max(1, min(POR_PAGINA_MAXIMO, $porPagina));

    $conteo = db()->prepare('SELECT COUNT(*) FROM producto p' . $where);
    $conteo->execute($params);
    $total = (int)$conteo->fetchColumn();

    $totalPaginas = max(1, (int)ceil($total / $porPagina));
    $pagina       = max(1, min($totalPaginas, (int)($_GET['pagina'] ?? 1)));
    $offset       = ($pagina - 1) * $porPagina;

    $st = db()->prepare(
        SQL_PRODUCTO_BASE . $where .
        ' ORDER BY LOWER(p.nombre), p.id_producto LIMIT ' . $porPagina . ' OFFSET ' . $offset
    );
    $st->execute($params);

    respuesta(true, [
        'items'         => array_map('normalizarFilaProducto', $st->fetchAll()),
        'total'         => $total,
        'pagina'        => $pagina,
        'por_pagina'    => $porPagina,
        'total_paginas' => $totalPaginas,
    ], 'Productos consultados correctamente.');
}

function accionObtenerProducto(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
    $producto = $id > 0 ? obtenerProducto($id) : null;

    if (!$producto) {
        respuesta(false, null, 'Producto no encontrado.', 404);
    }

    $producto['proveedores'] = proveedoresDeProducto($id);

    respuesta(true, $producto);
}

function accionResumenProductos(array $in): void
{
    exigirSesion();

    $r = db()->query("
        SELECT
            COUNT(*)                           AS total,
            COUNT(*) FILTER (WHERE estado)     AS activos,
            COUNT(*) FILTER (WHERE NOT estado) AS inactivos
        FROM producto
    ")->fetch();

    $categorias = (int)db()->query('SELECT COUNT(*) FROM categoria WHERE estado')->fetchColumn();

    respuesta(true, [
        'total'              => (int)$r['total'],
        'activos'            => (int)$r['activos'],
        'inactivos'          => (int)$r['inactivos'],
        'categorias_activas' => $categorias,
    ]);
}

/** Lista mínima de productos activos para los módulos que aún usan localStorage. */
function accionProductosParaModulos(array $in): void
{
    exigirSesion();

    $st = db()->query("
        SELECT p.id_producto, p.codigo_articulo_retail, p.nombre, p.estado,
               c.nombre AS categoria_nombre, i.porcentaje AS impuesto_porcentaje
        FROM producto p
        JOIN categoria c ON c.id_categoria = p.id_categoria
        JOIN impuesto  i ON i.id_impuesto  = p.id_impuesto
        ORDER BY LOWER(p.nombre), p.id_producto
    ");

    $filas = array_map(function (array $f): array {
        return [
            'id_producto'            => (int)$f['id_producto'],
            'codigo_articulo_retail' => $f['codigo_articulo_retail'],
            'nombre'                 => $f['nombre'],
            'estado'                 => aBool($f['estado']),
            'categoria_nombre'       => $f['categoria_nombre'],
            'impuesto_porcentaje'    => (float)$f['impuesto_porcentaje'],
        ];
    }, $st->fetchAll());

    respuesta(true, $filas);
}

function accionListarImpuestos(array $in): void
{
    exigirSesion();

    $st = db()->query('SELECT id_impuesto, nombre, porcentaje, estado FROM impuesto ORDER BY porcentaje DESC, nombre');

    respuesta(true, array_map(function (array $f): array {
        return [
            'id_impuesto' => (int)$f['id_impuesto'],
            'nombre'      => $f['nombre'],
            'porcentaje'  => (float)$f['porcentaje'],
            'estado'      => aBool($f['estado']),
        ];
    }, $st->fetchAll()));
}


/* ==========================================================
   PRODUCTOS — EXPORTAR (HU-15)
========================================================== */

function celdaCsv($valor): string
{
    $texto = (string)($valor ?? '');

    // Evita que Excel interprete texto como fórmula.
    if ($texto !== '' && preg_match('/^[=+\-@\t\r]/', $texto)) {
        $texto = "'" . $texto;
    }

    return $texto;
}

function accionExportarProductos(array $in): void
{
    exigirSesion();

    $params = [];
    $where  = filtrosProductos($params);

    $st = db()->prepare(SQL_PRODUCTO_BASE . $where . ' ORDER BY LOWER(p.nombre), p.id_producto');
    $st->execute($params);

    $archivo = 'catalogo_productos_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Cache-Control: no-store');

    $salida = fopen('php://output', 'w');

    // BOM para que Excel reconozca UTF-8 (tildes y ñ).
    fwrite($salida, "\xEF\xBB\xBF");

    fputcsv($salida, [
        'Código interno', 'Código de barras', 'Nombre', 'Descripción', 'Categoría',
        'Impuesto', 'Proveedores', 'Estado',
    ], ';', '"', '\\');

    // Se recorre fila por fila para no cargar todo el catálogo en memoria.
    while ($f = $st->fetch()) {
        fputcsv($salida, [
            celdaCsv($f['codigo_articulo_retail']),
            celdaCsv($f['codigo_barras']),
            celdaCsv($f['nombre']),
            celdaCsv($f['descripcion']),
            celdaCsv($f['categoria_nombre']),
            celdaCsv($f['impuesto_nombre']),
            celdaCsv($f['proveedores_texto']),
            aBool($f['estado']) ? 'Activo' : 'Inactivo',
        ], ';', '"', '\\');
    }

    fclose($salida);
    exit;
}


/* ==========================================================
   PRODUCTOS — ESCRITURA (HU-08, HU-09, HU-13)
========================================================== */

function leerProveedoresProducto(array $in): array
{
    $lista = $in['proveedores'] ?? [];
    if (!is_array($lista)) {
        respuesta(false, null, 'La lista de proveedores no es válida.', 400);
    }

    $resultado = [];

    foreach ($lista as $item) {
        $id = (int)($item['id_proveedor'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        if (isset($resultado[$id])) {
            respuesta(false, null, 'Un proveedor está repetido en la lista.', 400);
        }

        $costo = $item['costo'] ?? null;
        if ($costo === '' || $costo === null) {
            $costo = null;
        } elseif (!is_numeric($costo) || (float)$costo < 0 || (float)$costo > 9999999999) {
            respuesta(false, null, 'El costo del proveedor debe ser un número igual o mayor que cero.', 400);
        } else {
            $costo = round((float)$costo, 2);
        }

        $resultado[$id] = ['id_proveedor' => $id, 'costo' => $costo];
    }

    return array_values($resultado);
}

function leerProducto(array $in, ?array $actual): array
{
    $producto = [
        'codigo_articulo_retail' => campoTexto($in, 'codigo_articulo_retail', 100, false, 'El código interno'),
        'codigo_barras'          => campoTexto($in, 'codigo_barras', 100, false, 'El código de barras'),
        'nombre'                 => campoTexto($in, 'nombre', 255, true, 'El nombre del producto'),
        'descripcion'            => campoTexto($in, 'descripcion', 500, false, 'La descripción'),
    ];

    if (normalizar($producto['nombre']) === '') {
        respuesta(false, null, 'El nombre del producto debe incluir letras o números.', 400);
    }

    // Categoría: obligatoria; activa (o la misma que ya tenía el producto).
    $idCategoria = (int)($in['id_categoria'] ?? 0);
    if ($idCategoria <= 0) {
        respuesta(false, null, 'Seleccione una categoría.', 400);
    }

    $st = db()->prepare('SELECT estado FROM categoria WHERE id_categoria = :id');
    $st->execute([':id' => $idCategoria]);
    $categoria = $st->fetch();

    if (!$categoria) {
        respuesta(false, null, 'La categoría seleccionada no existe.', 400);
    }

    if (!aBool($categoria['estado']) && (!$actual || $actual['id_categoria'] !== $idCategoria)) {
        respuesta(false, null, 'La categoría seleccionada está inactiva.', 400);
    }

    $producto['id_categoria'] = $idCategoria;

    // Impuesto: obligatorio; activo (o el mismo que ya tenía el producto).
    $idImpuesto = (int)($in['id_impuesto'] ?? 0);
    if ($idImpuesto <= 0) {
        respuesta(false, null, 'Seleccione un impuesto.', 400);
    }

    $st = db()->prepare('SELECT estado FROM impuesto WHERE id_impuesto = :id');
    $st->execute([':id' => $idImpuesto]);
    $impuesto = $st->fetch();

    if (!$impuesto) {
        respuesta(false, null, 'El impuesto seleccionado no existe.', 400);
    }

    if (!aBool($impuesto['estado']) && (!$actual || $actual['id_impuesto'] !== $idImpuesto)) {
        respuesta(false, null, 'El impuesto seleccionado está inactivo.', 400);
    }

    $producto['id_impuesto'] = $idImpuesto;

    return $producto;
}

/** Cada proveedor elegido debe existir y estar activo (o ya estar asociado al producto). */
function validarProveedoresProducto(array $proveedores, ?array $actual): void
{
    if (!$proveedores) {
        return;
    }

    $yaAsociados = [];
    if ($actual) {
        foreach (proveedoresDeProducto($actual['id_producto']) as $p) {
            $yaAsociados[$p['id_proveedor']] = true;
        }
    }

    $st = db()->prepare('SELECT estado FROM proveedor WHERE id_proveedor = :id');

    foreach ($proveedores as $p) {
        $st->execute([':id' => $p['id_proveedor']]);
        $fila = $st->fetch();

        if (!$fila) {
            respuesta(false, null, 'Uno de los proveedores seleccionados no existe.', 400);
        }

        if (!aBool($fila['estado']) && empty($yaAsociados[$p['id_proveedor']])) {
            respuesta(false, null, 'Uno de los proveedores seleccionados está inactivo.', 400);
        }
    }
}

/**
 * HU-13. Mismo código interno o de barras => se bloquea.
 * Nombre equivalente (sin tildes/símbolos/mayúsculas) => se advierte y el
 * administrador puede continuar enviando confirmar_duplicado = true.
 */
function verificarDuplicados(array $p, int $excluirId, bool $confirmado): void
{
    $codigoInterno = mb_strtolower((string)($p['codigo_articulo_retail'] ?? ''), 'UTF-8');
    $codigoBarras  = mb_strtolower((string)($p['codigo_barras'] ?? ''), 'UTF-8');
    $nombreNorm    = normalizar($p['nombre']);

    $sql = "
        SELECT id_producto, codigo_articulo_retail, codigo_barras, nombre, estado
        FROM producto
        WHERE id_producto <> :excluir
          AND (
                (CAST(:ci1 AS TEXT) <> '' AND LOWER(TRIM(COALESCE(codigo_articulo_retail, ''))) = :ci2)
             OR (CAST(:cb1 AS TEXT) <> '' AND LOWER(TRIM(COALESCE(codigo_barras, ''))) = :cb2)
             OR " . sqlNormalizado('nombre') . " = :nom
          )
        ORDER BY nombre
        LIMIT 10
    ";

    $st = db()->prepare($sql);
    $st->execute([
        ':excluir' => $excluirId,
        ':ci1'     => $codigoInterno,
        ':ci2'     => $codigoInterno,
        ':cb1'     => $codigoBarras,
        ':cb2'     => $codigoBarras,
        ':nom'     => $nombreNorm,
    ]);

    $filas = $st->fetchAll();

    if (!$filas) {
        return;
    }

    $bloqueante = false;
    $duplicados = [];

    foreach ($filas as $f) {
        $motivos = [];

        if ($codigoInterno !== '' &&
            mb_strtolower(trim((string)$f['codigo_articulo_retail']), 'UTF-8') === $codigoInterno) {
            $motivos[] = 'mismo código interno';
            $bloqueante = true;
        }

        if ($codigoBarras !== '' &&
            mb_strtolower(trim((string)$f['codigo_barras']), 'UTF-8') === $codigoBarras) {
            $motivos[] = 'mismo código de barras';
            $bloqueante = true;
        }

        if (normalizar($f['nombre']) === $nombreNorm) {
            $motivos[] = 'nombre equivalente';
        }

        $duplicados[] = [
            'id_producto'            => (int)$f['id_producto'],
            'codigo_articulo_retail' => $f['codigo_articulo_retail'],
            'codigo_barras'          => $f['codigo_barras'],
            'nombre'                 => $f['nombre'],
            'estado'                 => aBool($f['estado']),
            'motivo'                 => implode(', ', $motivos),
        ];
    }

    if (!$bloqueante && $confirmado) {
        return;
    }

    $mensaje = $bloqueante
        ? 'Ya existe un producto con el mismo código. Revise el producto existente (puede estar inactivo).'
        : 'Se encontró un posible producto duplicado por nombre. Revíselo antes de continuar.';

    respuesta(false, [
        'duplicados'      => $duplicados,
        'puede_continuar' => !$bloqueante,
    ], $mensaje, 409);
}

/** Sincroniza producto_proveedor: inserta o actualiza los elegidos y desactiva (no borra) el resto. */
function guardarProveedoresProducto(int $idProducto, array $proveedores): void
{
    $pdo = db();

    $upsert = $pdo->prepare("
        INSERT INTO producto_proveedor (id_producto, id_proveedor, costo, estado, fecha_actualizacion)
        VALUES (:producto, :proveedor, :costo, TRUE, NOW())
        ON CONFLICT (id_producto, id_proveedor)
        DO UPDATE SET costo = EXCLUDED.costo, estado = TRUE, fecha_actualizacion = NOW()
    ");

    foreach ($proveedores as $p) {
        $upsert->execute([
            ':producto'  => $idProducto,
            ':proveedor' => $p['id_proveedor'],
            ':costo'     => $p['costo'],
        ]);
    }

    $ids = array_map(fn($p) => (int)$p['id_proveedor'], $proveedores);

    if ($ids) {
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $baja = $pdo->prepare("
            UPDATE producto_proveedor
            SET estado = FALSE, fecha_actualizacion = NOW()
            WHERE id_producto = ? AND estado = TRUE AND id_proveedor NOT IN ($marcas)
        ");
        $baja->execute(array_merge([$idProducto], $ids));
    } else {
        $baja = $pdo->prepare("
            UPDATE producto_proveedor
            SET estado = FALSE, fecha_actualizacion = NOW()
            WHERE id_producto = :id AND estado = TRUE
        ");
        $baja->execute([':id' => $idProducto]);
    }
}

/** Foto del producto (con proveedores) para guardar en la bitácora. */
function fotoProducto(int $id): array
{
    $producto = obtenerProducto($id) ?? [];
    $producto['proveedores'] = proveedoresDeProducto($id);

    return $producto;
}

function accionCrearProducto(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $p           = leerProducto($in, null);
    $proveedores = leerProveedoresProducto($in);

    validarProveedoresProducto($proveedores, null);
    verificarDuplicados($p, 0, !empty($in['confirmar_duplicado']));

    $nuevo = enTransaccion(function () use ($p, $proveedores) {
        $st = db()->prepare("
            INSERT INTO producto
                (id_categoria, id_impuesto, codigo_articulo_retail, codigo_barras, nombre, descripcion, estado)
            VALUES
                (:id_categoria, :id_impuesto, :codigo_interno, :codigo_barras, :nombre, :descripcion, TRUE)
            RETURNING id_producto
        ");
        $st->execute([
            ':id_categoria'   => $p['id_categoria'],
            ':id_impuesto'    => $p['id_impuesto'],
            ':codigo_interno' => $p['codigo_articulo_retail'],
            ':codigo_barras'  => $p['codigo_barras'],
            ':nombre'         => $p['nombre'],
            ':descripcion'    => $p['descripcion'],
        ]);

        $id = (int)$st->fetchColumn();

        guardarProveedoresProducto($id, $proveedores);

        $foto = fotoProducto($id);
        auditar('PRODUCTO', 'CREAR', null, $foto);

        return $foto;
    }, 'No se pudo registrar el producto.');

    respuesta(true, $nuevo, 'Producto registrado correctamente.', 201);
}

function accionEditarProducto(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_producto'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Producto inválido.', 400);
    }

    $actual = obtenerProducto($id);
    if (!$actual) {
        respuesta(false, null, 'Producto no encontrado.', 404);
    }

    $p           = leerProducto($in, $actual);
    $proveedores = leerProveedoresProducto($in);

    validarProveedoresProducto($proveedores, $actual);
    verificarDuplicados($p, $id, !empty($in['confirmar_duplicado']));

    $nuevo = enTransaccion(function () use ($id, $p, $proveedores) {
        $antes = fotoProducto($id);

        $st = db()->prepare("
            UPDATE producto SET
                id_categoria           = :id_categoria,
                id_impuesto            = :id_impuesto,
                codigo_articulo_retail = :codigo_interno,
                codigo_barras          = :codigo_barras,
                nombre                 = :nombre,
                descripcion            = :descripcion
            WHERE id_producto = :id
        ");
        $st->execute([
            ':id_categoria'   => $p['id_categoria'],
            ':id_impuesto'    => $p['id_impuesto'],
            ':codigo_interno' => $p['codigo_articulo_retail'],
            ':codigo_barras'  => $p['codigo_barras'],
            ':nombre'         => $p['nombre'],
            ':descripcion'    => $p['descripcion'],
            ':id'             => $id,
        ]);

        guardarProveedoresProducto($id, $proveedores);

        $despues = fotoProducto($id);
        auditar('PRODUCTO', 'EDITAR', $antes, $despues);

        return $despues;
    }, 'No se pudo actualizar el producto.');

    respuesta(true, $nuevo, 'Producto actualizado correctamente.');
}

function cambiarEstadoProducto(array $in, bool $activar): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_producto'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Producto inválido.', 400);
    }

    $actual = obtenerProducto($id);
    if (!$actual) {
        respuesta(false, null, 'Producto no encontrado.', 404);
    }

    if ($actual['estado'] === $activar) {
        respuesta(false, null, $activar ? 'El producto ya está activo.' : 'El producto ya está inactivo.', 409);
    }

    $nuevo = enTransaccion(function () use ($id, $activar) {
        $antes = fotoProducto($id);

        $st = db()->prepare('UPDATE producto SET estado = :estado WHERE id_producto = :id');
        $st->bindValue(':estado', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = fotoProducto($id);
        auditar('PRODUCTO', $activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    }, 'No se pudo cambiar el estado del producto.');

    respuesta(true, $nuevo, $activar ? 'Producto activado correctamente.' : 'Producto inactivado correctamente.');
}

function accionInactivarProducto(array $in): void
{
    cambiarEstadoProducto($in, false);
}

function accionActivarProducto(array $in): void
{
    cambiarEstadoProducto($in, true);
}

function accionHistorialProducto(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Producto inválido.', 400);
    }

    $st = db()->prepare("
        SELECT b.id_evento, b.accion, b.valor_anterior, b.valor_nuevo, b.fecha,
               TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario
        FROM bitacora_auditoria b
        LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
        WHERE b.entidad = 'PRODUCTO'
          AND COALESCE(b.valor_nuevo->>'id_producto', b.valor_anterior->>'id_producto') = :id
        ORDER BY b.fecha DESC, b.id_evento DESC
        LIMIT 100
    ");
    $st->execute([':id' => (string)$id]);

    $filas = array_map(function (array $f): array {
        return [
            'id_evento' => (int)$f['id_evento'],
            'accion'    => $f['accion'],
            'antes'     => $f['valor_anterior'] !== null ? json_decode((string)$f['valor_anterior'], true) : null,
            'despues'   => $f['valor_nuevo'] !== null ? json_decode((string)$f['valor_nuevo'], true) : null,
            'fecha'     => $f['fecha'],
            'usuario'   => $f['usuario'],
        ];
    }, $st->fetchAll());

    respuesta(true, $filas);
}


/* ==========================================================
   CATEGORÍAS (HU-11)
========================================================== */

function normalizarFilaCategoria(array $f): array
{
    $f['id_categoria'] = (int)$f['id_categoria'];
    $f['estado']       = aBool($f['estado']);

    if (isset($f['total_productos'])) {
        $f['total_productos'] = (int)$f['total_productos'];
    }

    return $f;
}

function obtenerCategoria(int $id): ?array
{
    $st = db()->prepare('SELECT id_categoria, nombre, descripcion, estado FROM categoria WHERE id_categoria = :id');
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ? normalizarFilaCategoria($fila) : null;
}

function accionListarCategorias(array $in): void
{
    exigirSesion();

    $st = db()->query("
        SELECT c.id_categoria, c.nombre, c.descripcion, c.estado,
               (SELECT COUNT(*) FROM producto p WHERE p.id_categoria = c.id_categoria) AS total_productos
        FROM categoria c
        ORDER BY LOWER(c.nombre)
    ");

    respuesta(true, array_map('normalizarFilaCategoria', $st->fetchAll()));
}

function nombreCategoriaExiste(string $nombre, int $excluirId): bool
{
    $st = db()->prepare('SELECT 1 FROM categoria WHERE LOWER(TRIM(nombre)) = LOWER(:n) AND id_categoria <> :id LIMIT 1');
    $st->execute([':n' => $nombre, ':id' => $excluirId]);

    return (bool)$st->fetchColumn();
}

function accionCrearCategoria(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $nombre      = campoTexto($in, 'nombre', 255, true, 'El nombre de la categoría');
    $descripcion = campoTexto($in, 'descripcion', 500, false, 'La descripción');

    if (nombreCategoriaExiste($nombre, 0)) {
        respuesta(false, null, 'Ya existe una categoría con ese nombre.', 409);
    }

    $fila = enTransaccion(function () use ($nombre, $descripcion) {
        $st = db()->prepare("
            INSERT INTO categoria (nombre, descripcion, estado)
            VALUES (:n, :d, TRUE)
            RETURNING id_categoria
        ");
        $st->execute([':n' => $nombre, ':d' => $descripcion]);

        $nueva = obtenerCategoria((int)$st->fetchColumn());
        auditar('CATEGORIA', 'CREAR', null, $nueva);

        return $nueva;
    }, 'No se pudo crear la categoría.');

    respuesta(true, $fila, 'Categoría creada correctamente.', 201);
}

function accionEditarCategoria(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_categoria'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Categoría inválida.', 400);
    }

    $antes = obtenerCategoria($id);
    if (!$antes) {
        respuesta(false, null, 'Categoría no encontrada.', 404);
    }

    $nombre      = campoTexto($in, 'nombre', 255, true, 'El nombre de la categoría');
    $descripcion = campoTexto($in, 'descripcion', 500, false, 'La descripción');

    if (nombreCategoriaExiste($nombre, $id)) {
        respuesta(false, null, 'Ya existe otra categoría con ese nombre.', 409);
    }

    $fila = enTransaccion(function () use ($id, $nombre, $descripcion, $antes) {
        $st = db()->prepare('UPDATE categoria SET nombre = :n, descripcion = :d WHERE id_categoria = :id');
        $st->execute([':n' => $nombre, ':d' => $descripcion, ':id' => $id]);

        $despues = obtenerCategoria($id);
        auditar('CATEGORIA', 'EDITAR', $antes, $despues);

        return $despues;
    }, 'No se pudo actualizar la categoría.');

    respuesta(true, $fila, 'Categoría actualizada correctamente.');
}

function cambiarEstadoCategoria(array $in, bool $activar): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_categoria'] ?? 0);
    $antes = $id > 0 ? obtenerCategoria($id) : null;

    if (!$antes) {
        respuesta(false, null, 'Categoría no encontrada.', 404);
    }

    $fila = enTransaccion(function () use ($id, $activar, $antes) {
        $st = db()->prepare('UPDATE categoria SET estado = :e WHERE id_categoria = :id');
        $st->bindValue(':e', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = obtenerCategoria($id);
        auditar('CATEGORIA', $activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    }, 'No se pudo cambiar el estado de la categoría.');

    respuesta(true, $fila, $activar ? 'Categoría activada correctamente.' : 'Categoría desactivada correctamente.');
}

function accionInactivarCategoria(array $in): void
{
    cambiarEstadoCategoria($in, false);
}

function accionActivarCategoria(array $in): void
{
    cambiarEstadoCategoria($in, true);
}


/* ==========================================================
   PROVEEDORES (HU-12)
========================================================== */

function normalizarFilaProveedor(array $f): array
{
    $f['id_proveedor'] = (int)$f['id_proveedor'];
    $f['estado']       = aBool($f['estado']);

    if (isset($f['total_productos'])) {
        $f['total_productos'] = (int)$f['total_productos'];
    }

    return $f;
}

function obtenerProveedor(int $id): ?array
{
    $st = db()->prepare("
        SELECT id_proveedor, nombre, contacto, telefono, correo, direccion, estado
        FROM proveedor WHERE id_proveedor = :id
    ");
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ? normalizarFilaProveedor($fila) : null;
}

function accionListarProveedores(array $in): void
{
    exigirSesion();

    $st = db()->query("
        SELECT pr.id_proveedor, pr.nombre, pr.contacto, pr.telefono, pr.correo, pr.direccion, pr.estado,
               (SELECT COUNT(*) FROM producto_proveedor pp
                 WHERE pp.id_proveedor = pr.id_proveedor AND pp.estado = TRUE) AS total_productos
        FROM proveedor pr
        ORDER BY LOWER(pr.nombre)
    ");

    respuesta(true, array_map('normalizarFilaProveedor', $st->fetchAll()));
}

function leerProveedor(array $in): array
{
    $proveedor = [
        'nombre'    => campoTexto($in, 'nombre', 255, true, 'El nombre del proveedor'),
        'contacto'  => campoTexto($in, 'contacto', 255, false, 'La persona de contacto'),
        'telefono'  => campoTexto($in, 'telefono', 50, false, 'El teléfono'),
        'correo'    => campoTexto($in, 'correo', 255, false, 'El correo'),
        'direccion' => campoTexto($in, 'direccion', 500, false, 'La dirección'),
    ];

    if ($proveedor['correo'] !== null && !filter_var($proveedor['correo'], FILTER_VALIDATE_EMAIL)) {
        respuesta(false, null, 'El correo electrónico no es válido.', 400);
    }

    if ($proveedor['telefono'] !== null && !preg_match('/^[0-9+\-\s()]{7,50}$/', $proveedor['telefono'])) {
        respuesta(false, null, 'El teléfono no es válido. Use solo números, espacios, + - ( ).', 400);
    }

    return $proveedor;
}

function nombreProveedorExiste(string $nombre, int $excluirId): bool
{
    $st = db()->prepare('SELECT 1 FROM proveedor WHERE LOWER(TRIM(nombre)) = LOWER(:n) AND id_proveedor <> :id LIMIT 1');
    $st->execute([':n' => $nombre, ':id' => $excluirId]);

    return (bool)$st->fetchColumn();
}

function accionCrearProveedor(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $p = leerProveedor($in);

    if (nombreProveedorExiste($p['nombre'], 0)) {
        respuesta(false, null, 'Ya existe un proveedor con ese nombre.', 409);
    }

    $fila = enTransaccion(function () use ($p) {
        $st = db()->prepare("
            INSERT INTO proveedor (nombre, contacto, telefono, correo, direccion, estado)
            VALUES (:nombre, :contacto, :telefono, :correo, :direccion, TRUE)
            RETURNING id_proveedor
        ");
        $st->execute([
            ':nombre'    => $p['nombre'],
            ':contacto'  => $p['contacto'],
            ':telefono'  => $p['telefono'],
            ':correo'    => $p['correo'],
            ':direccion' => $p['direccion'],
        ]);

        $nuevo = obtenerProveedor((int)$st->fetchColumn());
        auditar('PROVEEDOR', 'CREAR', null, $nuevo);

        return $nuevo;
    }, 'No se pudo crear el proveedor.');

    respuesta(true, $fila, 'Proveedor creado correctamente.', 201);
}

function accionEditarProveedor(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_proveedor'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Proveedor inválido.', 400);
    }

    $antes = obtenerProveedor($id);
    if (!$antes) {
        respuesta(false, null, 'Proveedor no encontrado.', 404);
    }

    $p = leerProveedor($in);

    if (nombreProveedorExiste($p['nombre'], $id)) {
        respuesta(false, null, 'Ya existe otro proveedor con ese nombre.', 409);
    }

    $fila = enTransaccion(function () use ($id, $p, $antes) {
        $st = db()->prepare("
            UPDATE proveedor SET
                nombre = :nombre, contacto = :contacto, telefono = :telefono,
                correo = :correo, direccion = :direccion
            WHERE id_proveedor = :id
        ");
        $st->execute([
            ':nombre'    => $p['nombre'],
            ':contacto'  => $p['contacto'],
            ':telefono'  => $p['telefono'],
            ':correo'    => $p['correo'],
            ':direccion' => $p['direccion'],
            ':id'        => $id,
        ]);

        $despues = obtenerProveedor($id);
        auditar('PROVEEDOR', 'EDITAR', $antes, $despues);

        return $despues;
    }, 'No se pudo actualizar el proveedor.');

    respuesta(true, $fila, 'Proveedor actualizado correctamente.');
}

function cambiarEstadoProveedor(array $in, bool $activar): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_proveedor'] ?? 0);
    $antes = $id > 0 ? obtenerProveedor($id) : null;

    if (!$antes) {
        respuesta(false, null, 'Proveedor no encontrado.', 404);
    }

    $fila = enTransaccion(function () use ($id, $activar, $antes) {
        $st = db()->prepare('UPDATE proveedor SET estado = :e WHERE id_proveedor = :id');
        $st->bindValue(':e', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = obtenerProveedor($id);
        auditar('PROVEEDOR', $activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    }, 'No se pudo cambiar el estado del proveedor.');

    respuesta(true, $fila, $activar ? 'Proveedor activado correctamente.' : 'Proveedor desactivado correctamente.');
}

function accionInactivarProveedor(array $in): void
{
    cambiarEstadoProveedor($in, false);
}

function accionActivarProveedor(array $in): void
{
    cambiarEstadoProveedor($in, true);
}


/* ==========================================================
   SESIÓN ACTUAL (mostrar usuario y ocultar acciones)
========================================================== */

function accionSesion(array $in): void
{
    $u = exigirSesion();

    respuesta(true, [
        'nombre'   => trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')),
        'correo'   => $u['correo'] ?? '',
        'rol'      => $u['rol'] ?? '',
        'es_admin' => esAdministrador(),
    ]);
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? '');

$acciones = [
    'sesion'                => 'accionSesion',

    'listar_productos'      => 'accionListarProductos',
    'obtener_producto'      => 'accionObtenerProducto',
    'resumen_productos'     => 'accionResumenProductos',
    'productos_para_modulos' => 'accionProductosParaModulos',
    'exportar_productos'    => 'accionExportarProductos',
    'crear_producto'        => 'accionCrearProducto',
    'editar_producto'       => 'accionEditarProducto',
    'inactivar_producto'    => 'accionInactivarProducto',
    'activar_producto'      => 'accionActivarProducto',
    'historial_producto'    => 'accionHistorialProducto',

    'listar_impuestos'      => 'accionListarImpuestos',

    'listar_categorias'     => 'accionListarCategorias',
    'crear_categoria'       => 'accionCrearCategoria',
    'editar_categoria'      => 'accionEditarCategoria',
    'inactivar_categoria'   => 'accionInactivarCategoria',
    'activar_categoria'     => 'accionActivarCategoria',

    'listar_proveedores'    => 'accionListarProveedores',
    'crear_proveedor'       => 'accionCrearProveedor',
    'editar_proveedor'      => 'accionEditarProveedor',
    'inactivar_proveedor'   => 'accionInactivarProveedor',
    'activar_proveedor'     => 'accionActivarProveedor',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[catalogo] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
