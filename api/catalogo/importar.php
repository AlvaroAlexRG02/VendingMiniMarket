<?php
declare(strict_types=1);

/**
 * Importación inicial del catálogo (HU-14) — CSV o XLSX.
 *
 * POST  archivo=<csv|xlsx>  [solo_validar=1]   Importa (o solo valida sin guardar).
 * GET   ?accion=plantilla                      Descarga un CSV de ejemplo.
 * GET   ?accion=historial                      Últimas importaciones.
 *
 * Una fila por registro, con la columna «tipo»: categoria, proveedor, producto, precio o inventario.
 * Las filas se procesan en ese orden (categorías → proveedores → productos → precios → inventario),
 * sin importar su orden en el archivo. Cada fila es independiente (SAVEPOINT): las
 * válidas se guardan y las erróneas se rechazan con su número de fila.
 *
 * Inventario: carga la existencia inicial por producto y ubicación (tabla existencia) y deja
 * un movimiento ENTRADA confirmado como respaldo. Si ya hay existencia se rechaza la fila.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/precios_lib.php';

const IMP_MAX_BYTES   = 10 * 1024 * 1024;
const IMP_MAX_ERRORES = 300;
const IMP_ORDEN       = ['categoria' => 1, 'proveedor' => 2, 'producto' => 3, 'precio' => 4, 'inventario' => 5];
const IMP_ALIAS_TIPO  = [
    'categoria' => 'categoria', 'categorias' => 'categoria',
    'proveedor' => 'proveedor', 'proveedores' => 'proveedor',
    'producto'  => 'producto',  'productos'  => 'producto',
    'precio'    => 'precio',    'precios'    => 'precio',
    'inventario' => 'inventario', 'stock' => 'inventario',
];


/* ==========================================================
   LECTURA DEL ARCHIVO
========================================================== */

function impNormalizarEncabezado(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

    return trim(preg_replace('/[^a-z0-9]+/', '_', $s) ?? '', '_');
}

/** Primer valor no vacío entre los encabezados indicados (alias). */
function impValor(array $fila, array $aliases, string $default = ''): string
{
    foreach ($aliases as $alias) {
        $clave = impNormalizarEncabezado($alias);

        if (isset($fila[$clave]) && $fila[$clave] !== '') {
            return trim((string)$fila[$clave]);
        }
    }

    return $default;
}

function impNulo(string $s): ?string
{
    return $s === '' ? null : $s;
}

/** "B3" => 1 (índice de columna desde 0). */
function impIndiceColumna(string $referencia): int
{
    preg_match('/^[A-Z]+/', strtoupper($referencia), $m);
    $n = 0;

    foreach (str_split($m[0] ?? 'A') as $letra) {
        $n = $n * 26 + (ord($letra) - 64);
    }

    return $n - 1;
}

function impLeerCsv(string $ruta): array
{
    $contenido = file_get_contents($ruta);

    if ($contenido === false) {
        throw new RuntimeException('No se pudo leer el archivo.');
    }

    if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
        $contenido = substr($contenido, 3);
    }

    // Excel en Windows suele guardar CSV en ANSI (Windows-1252), no en UTF-8.
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }

    $primera = strtok($contenido, "\r\n");
    $primera = $primera === false ? '' : $primera;

    $delimitador = ',';
    $mejor = substr_count($primera, ',');

    foreach ([';', "\t"] as $candidato) {
        $n = substr_count($primera, $candidato);

        if ($n > $mejor) {
            $mejor = $n;
            $delimitador = $candidato;
        }
    }

    $flujo = fopen('php://temp', 'r+');
    fwrite($flujo, $contenido);
    rewind($flujo);

    $matriz = [];

    while (($linea = fgetcsv($flujo, 0, $delimitador, '"', '\\')) !== false) {
        $matriz[] = array_map(fn($x) => trim((string)$x), $linea);
    }

    fclose($flujo);

    return $matriz;
}

/** Lee la primera hoja de un .xlsx sin librerías externas (usa ZipArchive). */
function impLeerXlsx(string $ruta): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Falta la extensión zip de PHP: active extension=zip en php.ini y reinicie Apache, o suba el archivo como CSV UTF-8.');
    }

    $zip = new ZipArchive();

    if ($zip->open($ruta) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo XLSX; puede estar dañado.');
    }

    try {
        $compartidas = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml !== false) {
            $sx = simplexml_load_string($xml);

            if ($sx !== false) {
                foreach ($sx->si as $si) {
                    $texto = '';

                    if (isset($si->t)) {
                        $texto = (string)$si->t;
                    } else {
                        foreach ($si->r as $r) {
                            $texto .= (string)$r->t;
                        }
                    }

                    $compartidas[] = $texto;
                }
            }
        }

        // Ruta de la primera hoja según workbook.xml y sus relaciones.
        $rutaHoja = 'xl/worksheets/sheet1.xml';
        $libro = $zip->getFromName('xl/workbook.xml');
        $rels  = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($libro !== false && $rels !== false) {
            $wb = simplexml_load_string($libro);
            $rl = simplexml_load_string($rels);

            if ($wb !== false && $rl !== false && isset($wb->sheets->sheet[0])) {
                $atributos = $wb->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $rid = $atributos !== null ? (string)$atributos['id'] : '';

                foreach ($rl->Relationship as $rel) {
                    if ((string)$rel['Id'] === $rid) {
                        $destino = ltrim((string)$rel['Target'], '/');
                        $rutaHoja = str_starts_with($destino, 'xl/') ? $destino : 'xl/' . $destino;
                        break;
                    }
                }
            }
        }

        $xmlHoja = $zip->getFromName($rutaHoja);

        if ($xmlHoja === false) {
            throw new RuntimeException('No se encontró la primera hoja del archivo XLSX.');
        }

        $hoja = simplexml_load_string($xmlHoja);

        if ($hoja === false || !isset($hoja->sheetData)) {
            throw new RuntimeException('La hoja del archivo XLSX no se puede leer.');
        }

        $filas = [];
        $ultima = -1;

        foreach ($hoja->sheetData->row as $row) {
            $indiceFila = isset($row['r']) ? ((int)$row['r'] - 1) : $ultima + 1;
            $celdas = [];

            foreach ($row->c as $c) {
                $col  = isset($c['r']) ? impIndiceColumna((string)$c['r']) : count($celdas);
                $tipo = (string)$c['t'];

                if ($tipo === 's') {
                    $valor = $compartidas[(int)$c->v] ?? '';
                } elseif ($tipo === 'inlineStr') {
                    $valor = '';

                    if (isset($c->is->t)) {
                        $valor = (string)$c->is->t;
                    } else {
                        foreach ($c->is->r as $r) {
                            $valor .= (string)$r->t;
                        }
                    }
                } elseif ($tipo === 'b') {
                    $valor = ((string)$c->v) === '1' ? 'true' : 'false';
                } else {
                    $valor = (string)$c->v;
                }

                $celdas[$col] = trim($valor);
            }

            $densa = [];

            if ($celdas) {
                for ($i = 0, $max = max(array_keys($celdas)); $i <= $max; $i++) {
                    $densa[$i] = $celdas[$i] ?? '';
                }
            }

            $filas[$indiceFila] = $densa;
            $ultima = $indiceFila;
        }

        $matriz = [];

        if ($filas) {
            for ($i = 0, $max = max(array_keys($filas)); $i <= $max; $i++) {
                $matriz[$i] = $filas[$i] ?? [];
            }
        }

        return $matriz;
    } finally {
        $zip->close();
    }
}

/** Convierte la matriz de celdas en filas asociativas con su número de fila del archivo. */
function impMatrizAFilas(array $matriz): array
{
    $cabeceras = null;
    $filas = [];

    foreach ($matriz as $i => $celdas) {
        $hayDatos = (bool)array_filter($celdas, fn($x) => $x !== '' && $x !== null);

        if ($cabeceras === null) {
            if ($hayDatos) {
                $cabeceras = array_map('impNormalizarEncabezado', $celdas);
            }

            continue;
        }

        if (!$hayDatos) {
            continue;
        }

        $datos = [];

        foreach ($cabeceras as $j => $h) {
            if ($h !== '') {
                $datos[$h] = trim((string)($celdas[$j] ?? ''));
            }
        }

        $filas[] = ['linea' => $i + 1, 'datos' => $datos];
    }

    return ['cabeceras' => $cabeceras ?? [], 'filas' => $filas];
}

function impLeerArchivo(string $ruta, string $extension): array
{
    if ($extension === 'xlsx') {
        $cabecera = file_get_contents($ruta, false, null, 0, 2);

        if ($cabecera !== 'PK') {
            throw new RuntimeException('El archivo no es un XLSX válido. Si es un .xls antiguo, guárdelo como .xlsx o CSV.');
        }

        return impMatrizAFilas(impLeerXlsx($ruta));
    }

    return impMatrizAFilas(impLeerCsv($ruta));
}


/* ==========================================================
   VALORES
========================================================== */

/** Acepta 1500, 1500.50, 1500,50, 1.500, 1.500,50, 1,500.50, ₡1 500. */
function impNum(string $v, string $etiqueta): float
{
    $v = trim(str_replace(['₡', 'CRC', 'crc', ' ', "\xC2\xA0"], '', $v));

    if ($v === '') {
        throw new RuntimeException("$etiqueta es obligatorio.");
    }

    if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
        $v = str_replace('.', '', $v);                   // 1.500 => 1500
    } elseif (preg_match('/^\d{1,3}(,\d{3})+$/', $v)) {
        $v = str_replace(',', '', $v);                   // 1,500 => 1500
    } elseif (str_contains($v, ',') && str_contains($v, '.')) {
        if (strrpos($v, ',') > strrpos($v, '.')) {
            $v = str_replace(',', '.', str_replace('.', '', $v));   // 1.234,56
        } else {
            $v = str_replace(',', '', $v);                          // 1,234.56
        }
    } else {
        $v = str_replace(',', '.', $v);                  // 1500,50 => 1500.50
    }

    if (!is_numeric($v) || (float)$v < 0 || (float)$v > 9999999999) {
        throw new RuntimeException("$etiqueta debe ser un número igual o mayor que cero.");
    }

    return round((float)$v, 2);
}

function impEstado(string $v): bool
{
    $v = mb_strtolower(trim($v), 'UTF-8');

    if (in_array($v, ['', '1', 'true', 'si', 'sí', 'activo', 'activa', 'yes'], true)) {
        return true;
    }

    if (in_array($v, ['0', 'false', 'no', 'inactivo', 'inactiva'], true)) {
        return false;
    }

    throw new RuntimeException('Estado no válido: use activo o inactivo.');
}

/** "7501055300075.0" (Excel) => "7501055300075". */
function impLimpiarCodigo(string $v): string
{
    return preg_match('/^\d+\.0+$/', $v) ? (string)preg_replace('/\.0+$/', '', $v) : $v;
}

function impTexto(string $valor, int $max, string $etiqueta): ?string
{
    if (mb_strlen($valor, 'UTF-8') > $max) {
        throw new RuntimeException("$etiqueta no puede superar $max caracteres.");
    }

    return impNulo($valor);
}


/* ==========================================================
   BÚSQUEDAS Y CREACIÓN DE APOYO
========================================================== */

function impBuscarNombre(PDO $db, string $entidad, string $nombre): ?array
{
    $mapa = [
        'categoria' => ['public.categoria', 'id_categoria'],
        'proveedor' => ['public.proveedor', 'id_proveedor'],
    ];

    [$tabla, $columna] = $mapa[$entidad];

    $st = $db->prepare("SELECT $columna AS id, estado FROM $tabla WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:n)) LIMIT 1");
    $st->execute([':n' => $nombre]);
    $f = $st->fetch(PDO::FETCH_ASSOC);

    return $f ? ['id' => (int)$f['id'], 'activo' => libBool($f['estado'])] : null;
}

/** Devuelve [id, creada, activa]. */
function impAsegurarCategoria(PDO $db, string $nombre, ?string $descripcion, array &$cnt): array
{
    if (mb_strlen($nombre, 'UTF-8') > 255) {
        throw new RuntimeException('El nombre de la categoría supera 255 caracteres.');
    }

    $existente = impBuscarNombre($db, 'categoria', $nombre);

    if ($existente) {
        return [$existente['id'], false, $existente['activo']];
    }

    $st = $db->prepare('INSERT INTO public.categoria (nombre, descripcion, estado) VALUES (:n, :d, TRUE) RETURNING id_categoria');
    $st->execute([':n' => $nombre, ':d' => $descripcion]);
    $id = (int)$st->fetchColumn();

    libAuditar($db, 'CATEGORIA', 'CREAR', null, [
        'id_categoria' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion, 'estado' => true, 'origen' => 'IMPORTACION',
    ]);

    $cnt['categorias_creadas']++;

    return [$id, true, true];
}

/** Devuelve [id, creado, activo]. $datos: contacto, telefono, correo, direccion (ya validados). */
function impAsegurarProveedor(PDO $db, string $nombre, array $datos, array &$cnt): array
{
    if (mb_strlen($nombre, 'UTF-8') > 255) {
        throw new RuntimeException('El nombre del proveedor supera 255 caracteres.');
    }

    $existente = impBuscarNombre($db, 'proveedor', $nombre);

    if ($existente) {
        return [$existente['id'], false, $existente['activo']];
    }

    $st = $db->prepare('
        INSERT INTO public.proveedor (nombre, contacto, telefono, correo, direccion, estado)
        VALUES (:n, :c, :t, :e, :d, TRUE)
        RETURNING id_proveedor
    ');
    $st->execute([
        ':n' => $nombre,
        ':c' => $datos['contacto'] ?? null,
        ':t' => $datos['telefono'] ?? null,
        ':e' => $datos['correo'] ?? null,
        ':d' => $datos['direccion'] ?? null,
    ]);
    $id = (int)$st->fetchColumn();

    libAuditar($db, 'PROVEEDOR', 'CREAR', null, [
        'id_proveedor' => $id, 'nombre' => $nombre, 'estado' => true, 'origen' => 'IMPORTACION',
    ] + $datos);

    $cnt['proveedores_creados']++;

    return [$id, true, true];
}

function impDatosProveedor(array $f): array
{
    $contacto  = impTexto(impValor($f, ['contacto', 'persona_de_contacto']), 255, 'El contacto');
    $telefono  = impTexto(impValor($f, ['telefono']), 50, 'El teléfono');
    $correo    = impTexto(impValor($f, ['correo', 'email', 'correo_electronico']), 255, 'El correo');
    $direccion = impTexto(impValor($f, ['direccion']), 500, 'La dirección');

    if ($correo !== null && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException("El correo «$correo» no es válido.");
    }

    if ($telefono !== null && !preg_match('/^[0-9+\-\s()]{7,50}$/', $telefono)) {
        throw new RuntimeException("El teléfono «$telefono» no es válido (use solo números, espacios, + - ( ) ).");
    }

    return ['contacto' => $contacto, 'telefono' => $telefono, 'correo' => $correo, 'direccion' => $direccion];
}

/** Por nombre, o por porcentaje ("13", "13%"). Vacío => impuesto activo del 13 %. */
function impResolverImpuesto(PDO $db, string $texto): int
{
    if ($texto === '') {
        $id = $db->query('SELECT id_impuesto FROM public.impuesto WHERE estado = TRUE AND porcentaje = 13 ORDER BY id_impuesto LIMIT 1')->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No hay un impuesto activo del 13 % para usar por defecto: indique la columna impuesto.');
        }

        return (int)$id;
    }

    $st = $db->prepare('SELECT id_impuesto, estado FROM public.impuesto WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:n)) LIMIT 1');
    $st->execute([':n' => $texto]);
    $fila = $st->fetch(PDO::FETCH_ASSOC);

    if (!$fila && preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*%?\s*$/', $texto, $m)) {
        $st = $db->prepare('SELECT id_impuesto, estado FROM public.impuesto WHERE porcentaje = :p AND estado = TRUE ORDER BY id_impuesto LIMIT 1');
        $st->execute([':p' => str_replace(',', '.', $m[1])]);
        $fila = $st->fetch(PDO::FETCH_ASSOC);
    }

    if (!$fila) {
        throw new RuntimeException("El impuesto «$texto» no existe.");
    }

    if (!libBool($fila['estado'])) {
        throw new RuntimeException("El impuesto «$texto» está inactivo.");
    }

    return (int)$fila['id_impuesto'];
}

/** Por nombre, o por porcentaje ("40", "40%"). Debe estar activo. */
function impResolverMargen(PDO $db, string $texto): int
{
    $st = $db->prepare('SELECT id_margen FROM public.margen_ganancia WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:n)) AND estado = TRUE LIMIT 1');
    $st->execute([':n' => $texto]);
    $id = $st->fetchColumn();

    if ($id === false && preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*%?\s*$/', $texto, $m)) {
        $st = $db->prepare('SELECT id_margen FROM public.margen_ganancia WHERE porcentaje = :p AND estado = TRUE ORDER BY id_margen LIMIT 1');
        $st->execute([':p' => str_replace(',', '.', $m[1])]);
        $id = $st->fetchColumn();
    }

    if ($id === false) {
        throw new RuntimeException("El margen «$texto» no existe o está inactivo.");
    }

    return (int)$id;
}


/* ==========================================================
   FILAS POR TIPO — devuelven 'creada' u 'omitida'
========================================================== */

function impFilaCategoria(PDO $db, array $f, array &$cnt): string
{
    $nombre = impValor($f, ['nombre', 'categoria', 'nombre_categoria']);

    if ($nombre === '') {
        throw new RuntimeException('Falta el nombre de la categoría.');
    }

    $descripcion = impTexto(impValor($f, ['descripcion']), 500, 'La descripción');
    [, $creada] = impAsegurarCategoria($db, $nombre, $descripcion, $cnt);

    return $creada ? 'creada' : 'omitida';
}

function impFilaProveedor(PDO $db, array $f, array &$cnt): string
{
    $nombre = impValor($f, ['nombre', 'proveedor', 'nombre_proveedor']);

    if ($nombre === '') {
        throw new RuntimeException('Falta el nombre del proveedor.');
    }

    [, $creado] = impAsegurarProveedor($db, $nombre, impDatosProveedor($f), $cnt);

    return $creado ? 'creada' : 'omitida';
}

function impFilaProducto(PDO $db, array $f, array &$cnt): string
{
    $nombre = impValor($f, ['nombre', 'producto', 'nombre_producto']);

    if ($nombre === '') {
        throw new RuntimeException('Falta el nombre del producto.');
    }

    if (mb_strlen($nombre, 'UTF-8') > 255) {
        throw new RuntimeException('El nombre del producto supera 255 caracteres.');
    }

    if (libNormalizar($nombre) === '') {
        throw new RuntimeException('El nombre del producto debe incluir letras o números.');
    }

    $descripcion = impTexto(impValor($f, ['descripcion']), 500, 'La descripción');
    $codigo = impTexto(impLimpiarCodigo(impValor($f, ['codigo_interno', 'codigo_articulo_retail', 'codigo'])), 100, 'El código interno');
    $barras = impTexto(impLimpiarCodigo(impValor($f, ['codigo_barras', 'codigo_de_barras'])), 100, 'El código de barras');
    $activo = impEstado(impValor($f, ['estado']));

    $nombreCategoria = impValor($f, ['categoria', 'nombre_categoria']);

    if ($nombreCategoria === '') {
        throw new RuntimeException('El producto requiere una categoría.');
    }

    [$idCategoria, , $categoriaActiva] = impAsegurarCategoria($db, $nombreCategoria, null, $cnt);

    if (!$categoriaActiva) {
        throw new RuntimeException("La categoría «$nombreCategoria» está inactiva.");
    }

    $idImpuesto = impResolverImpuesto($db, impValor($f, ['impuesto', 'nombre_impuesto', 'iva']));

    // Proveedor opcional (con costo opcional).
    $idProveedor = null;
    $costo = null;
    $nombreProveedor = impValor($f, ['proveedor', 'nombre_proveedor']);

    if ($nombreProveedor !== '') {
        [$idProveedor, , $proveedorActivo] = impAsegurarProveedor($db, $nombreProveedor, [], $cnt);

        if (!$proveedorActivo) {
            throw new RuntimeException("El proveedor «$nombreProveedor» está inactivo.");
        }

        $textoCosto = impValor($f, ['costo', 'costo_proveedor']);
        $costo = $textoCosto !== '' ? impNum($textoCosto, 'El costo') : null;
    }

    // Duplicados (HU-13): mismo código interno, mismo código de barras o nombre equivalente.
    $ci = mb_strtolower((string)$codigo, 'UTF-8');
    $cb = mb_strtolower((string)$barras, 'UTF-8');

    $st = $db->prepare("
        SELECT nombre, codigo_articulo_retail, codigo_barras
        FROM public.producto
        WHERE (CAST(:ci1 AS TEXT) <> '' AND LOWER(TRIM(COALESCE(codigo_articulo_retail, ''))) = :ci2)
           OR (CAST(:cb1 AS TEXT) <> '' AND LOWER(TRIM(COALESCE(codigo_barras, ''))) = :cb2)
           OR " . libSqlNormalizado('nombre') . " = :nom
        LIMIT 1
    ");
    $st->execute([
        ':ci1' => $ci, ':ci2' => $ci,
        ':cb1' => $cb, ':cb2' => $cb,
        ':nom' => libNormalizar($nombre),
    ]);
    $duplicado = $st->fetch(PDO::FETCH_ASSOC);

    if ($duplicado) {
        if ($ci !== '' && mb_strtolower(trim((string)$duplicado['codigo_articulo_retail']), 'UTF-8') === $ci) {
            throw new RuntimeException("Ya existe un producto con el código interno «$codigo» ({$duplicado['nombre']}).");
        }

        if ($cb !== '' && mb_strtolower(trim((string)$duplicado['codigo_barras']), 'UTF-8') === $cb) {
            throw new RuntimeException("Ya existe un producto con el código de barras «$barras» ({$duplicado['nombre']}).");
        }

        throw new RuntimeException("Posible duplicado: ya existe un producto con nombre equivalente («{$duplicado['nombre']}»).");
    }

    if ($codigo === null) {
        $codigo = libGenerarCodigoCR($db);      // CR600, CR601...
    }

    $st = $db->prepare('
        INSERT INTO public.producto
            (id_categoria, id_impuesto, codigo_articulo_retail, codigo_barras, nombre, descripcion, estado)
        VALUES
            (:cat, :imp, :ci, :cb, :n, :d, :e)
        RETURNING id_producto, codigo_articulo_retail
    ');
    $st->bindValue(':cat', $idCategoria, PDO::PARAM_INT);
    $st->bindValue(':imp', $idImpuesto, PDO::PARAM_INT);
    $st->bindValue(':ci', $codigo, PDO::PARAM_STR);       // NULL => lo asigna el trigger (CR600...)
    $st->bindValue(':cb', $barras, PDO::PARAM_STR);
    $st->bindValue(':n', $nombre, PDO::PARAM_STR);
    $st->bindValue(':d', $descripcion, PDO::PARAM_STR);
    $st->bindValue(':e', $activo, PDO::PARAM_BOOL);
    $st->execute();
    $nuevo = $st->fetch(PDO::FETCH_ASSOC);
    $idProducto = (int)$nuevo['id_producto'];

    if ($idProveedor !== null) {
        $st = $db->prepare('
            INSERT INTO public.producto_proveedor (id_producto, id_proveedor, costo, estado, fecha_actualizacion)
            VALUES (:p, :v, :c, TRUE, NOW())
            ON CONFLICT (id_producto, id_proveedor)
            DO UPDATE SET costo = EXCLUDED.costo, estado = TRUE, fecha_actualizacion = NOW()
        ');
        $st->execute([':p' => $idProducto, ':v' => $idProveedor, ':c' => $costo]);
    }

    libAuditar($db, 'PRODUCTO', 'CREAR', null, [
        'id_producto'            => $idProducto,
        'nombre'                 => $nombre,
        'codigo_articulo_retail' => $nuevo['codigo_articulo_retail'],
        'codigo_barras'          => $barras,
        'categoria_nombre'       => $nombreCategoria,
        'proveedores'            => $idProveedor !== null ? [['nombre' => $nombreProveedor, 'costo' => $costo]] : [],
        'estado'                 => $activo,
        'origen'                 => 'IMPORTACION',
    ]);

    $cnt['productos_creados']++;

    return 'creada';
}

function impFilaPrecio(PDO $db, array $f, array &$cnt, ?int $idUsuario): string
{
    $codigo = impValor($f, ['codigo_interno', 'codigo_articulo_retail', 'codigo']);
    $barras = impLimpiarCodigo(impValor($f, ['codigo_barras', 'codigo_de_barras']));

    if ($codigo === '' && $barras === '') {
        throw new RuntimeException('El precio requiere codigo_interno o codigo_barras para identificar el producto.');
    }

    if ($codigo !== '') {
        $st = $db->prepare('SELECT id_producto FROM public.producto WHERE LOWER(TRIM(codigo_articulo_retail)) = LOWER(TRIM(:c)) LIMIT 1');
        $st->execute([':c' => $codigo]);
        $referencia = "código interno «$codigo»";
    } else {
        $st = $db->prepare('SELECT id_producto FROM public.producto WHERE TRIM(codigo_barras) = TRIM(:c) LIMIT 1');
        $st->execute([':c' => $barras]);
        $referencia = "código de barras «$barras»";
    }

    $idProducto = $st->fetchColumn();

    if ($idProducto === false) {
        throw new RuntimeException("No se encontró un producto con $referencia.");
    }

    $textoTienda = impValor($f, ['tienda_id', 'id_tienda', 'tienda']);

    if (!ctype_digit($textoTienda) || (int)$textoTienda <= 0) {
        throw new RuntimeException('tienda_id debe ser el número (id) de la tienda.');
    }

    $textoMargen = impValor($f, ['margen', 'nombre_margen']);

    if ($textoMargen === '') {
        throw new RuntimeException('El precio requiere un margen (nombre o porcentaje).');
    }

    $idTienda = (int)$textoTienda;
    $idMargen = impResolverMargen($db, $textoMargen);
    $costo    = impNum(impValor($f, ['costo']), 'El costo');

    $refs = plReferencias($db, (int)$idProducto, $idTienda, $idMargen);   // producto activo, tienda, margen, IVA del producto
    plAplicar($db, $refs, $idTienda, $costo, $idUsuario);

    $cnt['precios_aplicados']++;

    return 'creada';
}


function impFilaInventario(PDO $db, array $f, array &$cnt, ?int $idUsuario): string
{
    if ($idUsuario === null) {
        throw new RuntimeException('No se pudo identificar al usuario que realiza la carga.');
    }

    $codigo = impValor($f, ['codigo_interno', 'codigo_articulo_retail', 'codigo']);
    $barras = impLimpiarCodigo(impValor($f, ['codigo_barras', 'codigo_de_barras']));

    if ($codigo === '' && $barras === '') {
        throw new RuntimeException('El inventario requiere codigo_interno o codigo_barras para identificar el producto.');
    }

    if ($codigo !== '') {
        $st = $db->prepare('SELECT id_producto, estado FROM public.producto WHERE LOWER(TRIM(codigo_articulo_retail)) = LOWER(TRIM(:c)) LIMIT 1');
        $st->execute([':c' => $codigo]);
        $referencia = "código interno «$codigo»";
    } else {
        $st = $db->prepare('SELECT id_producto, estado FROM public.producto WHERE TRIM(codigo_barras) = TRIM(:c) LIMIT 1');
        $st->execute([':c' => $barras]);
        $referencia = "código de barras «$barras»";
    }

    $producto = $st->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new RuntimeException("No se encontró un producto con $referencia.");
    }

    if (!$producto['estado']) {
        throw new RuntimeException("El producto con $referencia está inactivo.");
    }

    $textoTienda = impValor($f, ['tienda_id', 'id_tienda', 'tienda']);

    if (!ctype_digit($textoTienda) || (int)$textoTienda <= 0) {
        throw new RuntimeException('tienda_id debe ser el número (id) de la tienda.');
    }

    $idTienda  = (int)$textoTienda;
    $ubicacion = impValor($f, ['ubicacion', 'nombre_ubicacion']);

    $sql    = 'SELECT id_ubicacion, nombre FROM public.ubicacion_inventario WHERE id_tienda = :t AND estado = TRUE';
    $params = [':t' => $idTienda];

    if ($ubicacion !== '') {
        $sql .= ' AND LOWER(TRIM(nombre)) = LOWER(TRIM(:n))';
        $params[':n'] = $ubicacion;
    }

    $st = $db->prepare($sql . ' ORDER BY nombre');
    $st->execute($params);
    $ubicaciones = $st->fetchAll(PDO::FETCH_ASSOC);

    if (!$ubicaciones) {
        throw new RuntimeException($ubicacion !== ''
            ? "La tienda $idTienda no tiene una ubicación activa llamada «$ubicacion»."
            : "La tienda $idTienda no tiene ubicaciones de inventario activas.");
    }

    if (count($ubicaciones) > 1) {
        $nombres = implode(', ', array_column($ubicaciones, 'nombre'));
        throw new RuntimeException("La tienda $idTienda tiene varias ubicaciones ($nombres): indique la columna ubicacion.");
    }

    $idUbicacion = (int)$ubicaciones[0]['id_ubicacion'];

    $cantidad = impNum(impValor($f, ['cantidad', 'existencia', 'stock']), 'La cantidad');

    if ($cantidad != floor($cantidad) || $cantidad > 2000000000) {
        throw new RuntimeException('La cantidad debe ser un número entero igual o mayor que cero.');
    }

    $textoMinimo = impValor($f, ['stock_minimo', 'minimo']);
    $minimo      = $textoMinimo === '' ? 0.0 : impNum($textoMinimo, 'El stock mínimo');

    if ($minimo != floor($minimo) || $minimo > 2000000000) {
        throw new RuntimeException('El stock mínimo debe ser un número entero igual o mayor que cero.');
    }

    $st = $db->prepare('SELECT 1 FROM public.existencia WHERE id_producto = :p AND id_ubicacion = :u');
    $st->execute([':p' => (int)$producto['id_producto'], ':u' => $idUbicacion]);

    if ($st->fetchColumn()) {
        throw new RuntimeException('El producto ya tiene existencia registrada en esa ubicación; la carga inicial no la modifica.');
    }

    $st = $db->prepare('
        INSERT INTO public.existencia (id_producto, id_ubicacion, cantidad, stock_minimo)
        VALUES (:p, :u, :c, :m)
    ');
    $st->execute([':p' => (int)$producto['id_producto'], ':u' => $idUbicacion, ':c' => (int)$cantidad, ':m' => (int)$minimo]);

    if ($cantidad > 0) {
        $st = $db->prepare("
            INSERT INTO public.movimiento_inventario
                (tipo_movimiento, id_ubicacion_destino, id_usuario, estado, fecha_confirmacion, motivo, observaciones)
            VALUES
                ('ENTRADA', :u, :usr, 'CONFIRMADO', NOW(), 'Carga inicial de inventario', 'Importación del catálogo')
            RETURNING id_movimiento
        ");
        $st->execute([':u' => $idUbicacion, ':usr' => $idUsuario]);
        $idMovimiento = (int)$st->fetchColumn();

        $st = $db->prepare('
            INSERT INTO public.detalle_movimiento
                (id_movimiento, id_producto, cantidad, saldo_destino_anterior, saldo_destino_resultante)
            VALUES (:mov, :p, :c, 0, :c2)
        ');
        $st->execute([':mov' => $idMovimiento, ':p' => (int)$producto['id_producto'], ':c' => (int)$cantidad, ':c2' => (int)$cantidad]);
    }

    $cnt['inventario_cargado']++;

    return 'creada';
}


/* ==========================================================
   PROCESO PRINCIPAL
========================================================== */

function impMensajeError(Throwable $e): string
{
    if ($e instanceof RuntimeException) {
        return $e->getMessage();      // incluye PrecioException: mensajes propios, seguros
    }

    error_log('[importar] ' . $e->getMessage());

    if ($e instanceof PDOException) {
        switch ((string)$e->getCode()) {
            case '23505': return 'Registro duplicado.';
            case '23503': return 'Una referencia (categoría, proveedor, impuesto o tienda) no existe.';
            case '22001': return 'Un texto supera la longitud permitida.';
            case '22003': return 'Un número está fuera del rango permitido.';
            default:      return 'Error de base de datos al guardar la fila.';
        }
    }

    return 'Error interno al procesar la fila.';
}

function impRegistrar(PDO $db, string $archivo, string $estado, array $res, ?int $idUsuario): void
{
    try {
        $st = $db->prepare("
            INSERT INTO public.importacion_catalogo
                (nombre_archivo, estado, filas_procesadas, filas_importadas, filas_rechazadas, detalle, id_usuario, fecha_fin)
            VALUES
                (:a, :e, :p, :i, :r, CAST(:d AS jsonb), :u, NOW())
        ");
        $st->execute([
            ':a' => $archivo,
            ':e' => $estado,
            ':p' => $res['procesadas'],
            ':i' => $res['importadas'],
            ':r' => $res['rechazadas'],
            ':d' => json_encode(['resumen' => array_diff_key($res, ['errores' => 1]), 'errores' => $res['errores']], JSON_UNESCAPED_UNICODE),
            ':u' => $idUsuario,
        ]);
    } catch (Throwable $e) {
        error_log('[importar] registro: ' . $e->getMessage());
    }
}

function impProcesar(): void
{
    libImportador();
    set_time_limit(300);

    if (empty($_FILES['archivo'])) {
        $tam = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

        libJson(
            false,
            null,
            $tam > 0
                ? 'El archivo supera el límite del servidor. Aumente upload_max_filesize y post_max_size en php.ini.'
                : 'Seleccione un archivo CSV o XLSX.',
            $tam > 0 ? 413 : 400
        );
    }

    $archivo = $_FILES['archivo'];
    $error   = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        libJson(false, null, 'El archivo supera el límite del servidor. Aumente upload_max_filesize y post_max_size en php.ini.', 413);
    }

    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string)$archivo['tmp_name'])) {
        libJson(false, null, 'No se pudo subir el archivo.', 400);
    }

    if ((int)($archivo['size'] ?? 0) > IMP_MAX_BYTES) {
        libJson(false, null, 'El archivo supera el máximo de 10 MB.', 413);
    }

    $extension = strtolower(pathinfo((string)$archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, ['csv', 'xlsx'], true)) {
        libJson(false, null, 'Formato no admitido. Use CSV (UTF-8) o XLSX.', 400);
    }

    $nombreArchivo = mb_substr(basename((string)$archivo['name']), 0, 255, 'UTF-8');
    $soloValidar   = libBool($_POST['solo_validar'] ?? false);
    $idUsuario     = libUsuarioId();
    $db            = libDb();

    try {
        $lectura = impLeerArchivo((string)$archivo['tmp_name'], $extension);
    } catch (RuntimeException $e) {
        libJson(false, null, $e->getMessage(), 400);
    }

    if (!in_array('tipo', $lectura['cabeceras'], true)) {
        libJson(false, null, 'Falta la columna «tipo» (categoria, proveedor, producto o precio). Descargue la plantilla.', 400);
    }

    if (!$lectura['filas']) {
        libJson(false, null, 'El archivo no contiene filas de datos.', 400);
    }

    // Tipo de cada fila y orden de proceso (estable).
    $filas = [];

    foreach ($lectura['filas'] as $indice => $fila) {
        $original = impValor($fila['datos'], ['tipo', 'entidad']);
        $tipo     = IMP_ALIAS_TIPO[impNormalizarEncabezado($original)] ?? null;

        $filas[] = $fila + [
            'indice'   => $indice,
            'original' => $original,
            'tipo'     => $tipo,
            'orden'    => $tipo !== null ? IMP_ORDEN[$tipo] : 9,
        ];
    }

    usort($filas, fn($a, $b) => [$a['orden'], $a['indice']] <=> [$b['orden'], $b['indice']]);

    $res = [
        'modo'                => $soloValidar ? 'validacion' : 'importacion',
        'procesadas'          => count($filas),
        'importadas'          => 0,
        'omitidas'            => 0,
        'rechazadas'          => 0,
        'categorias_creadas'  => 0,
        'proveedores_creados' => 0,
        'productos_creados'   => 0,
        'precios_aplicados'   => 0,
        'inventario_cargado'  => 0,
        'errores_total'       => 0,
        'errores'             => [],
    ];
    $cnt = [
        'categorias_creadas'  => 0,
        'proveedores_creados' => 0,
        'productos_creados'   => 0,
        'precios_aplicados'   => 0,
        'inventario_cargado'  => 0,
    ];

    try {
        $db->beginTransaction();

        foreach ($filas as $fila) {
            $copia = $cnt;
            $db->exec('SAVEPOINT sp_fila');

            try {
                switch ($fila['tipo']) {
                    case 'categoria':
                        $r = impFilaCategoria($db, $fila['datos'], $cnt);
                        break;
                    case 'proveedor':
                        $r = impFilaProveedor($db, $fila['datos'], $cnt);
                        break;
                    case 'producto':
                        $r = impFilaProducto($db, $fila['datos'], $cnt);
                        break;
                    case 'precio':
                        $r = impFilaPrecio($db, $fila['datos'], $cnt, $idUsuario);
                        break;
                    case 'inventario':
                        $r = impFilaInventario($db, $fila['datos'], $cnt, $idUsuario);
                        break;
                    default:
                        throw new RuntimeException('Tipo no reconocido («' . $fila['original'] . '»). Use categoria, proveedor, producto, precio o inventario.');
                }

                $db->exec('RELEASE SAVEPOINT sp_fila');

                if ($r === 'omitida') {
                    $res['omitidas']++;
                } else {
                    $res['importadas']++;
                }
            } catch (Throwable $e) {
                $db->exec('ROLLBACK TO SAVEPOINT sp_fila');
                $db->exec('RELEASE SAVEPOINT sp_fila');

                $cnt = $copia;
                $res['rechazadas']++;
                $res['errores_total']++;

                if (count($res['errores']) < IMP_MAX_ERRORES) {
                    $res['errores'][] = ['fila' => $fila['linea'], 'error' => impMensajeError($e)];
                }
            }
        }

        if ($soloValidar) {
            $db->rollBack();
        } else {
            $db->commit();
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log('[importar] ' . $e->getMessage());

        if (!$soloValidar) {
            impRegistrar($db, $nombreArchivo, 'FALLIDA', $res, $idUsuario);
        }

        libJson(false, null, 'No se pudo procesar la importación. No se guardó ningún dato.', 500);
    }

    $res = array_merge($res, $cnt);

    // Se ordenan las filas con error por número de fila para leerlas mejor.
    usort($res['errores'], fn($a, $b) => $a['fila'] <=> $b['fila']);

    if ($soloValidar) {
        libJson(true, $res, $res['rechazadas']
            ? 'Validación terminada: hay filas con errores. No se guardó nada.'
            : 'Validación terminada sin errores. No se guardó nada.');
    }

    impRegistrar($db, $nombreArchivo, $res['rechazadas'] ? 'CON_ERRORES' : 'COMPLETADA', $res, $idUsuario);

    libJson(true, $res, $res['rechazadas']
        ? 'Importación terminada con filas rechazadas.'
        : 'Importación completada.');
}

function impHistorial(): void
{
    $db = libDb();

    $filas = $db->query("
        SELECT i.id_importacion, i.nombre_archivo, i.estado, i.filas_procesadas, i.filas_importadas,
               i.filas_rechazadas, i.fecha_inicio,
               TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario
        FROM public.importacion_catalogo i
        LEFT JOIN public.usuario u ON u.id_usuario = i.id_usuario
        ORDER BY i.fecha_inicio DESC, i.id_importacion DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    libJson(true, $filas);
}

function impPlantilla(): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_importacion_catalogo.csv"');
    header('Cache-Control: no-store');

    $salida = fopen('php://output', 'w');
    fwrite($salida, "\xEF\xBB\xBF");

    $columnas = [
        'tipo', 'nombre', 'descripcion', 'categoria', 'proveedor', 'impuesto', 'codigo_interno',
        'codigo_barras', 'costo', 'estado', 'contacto', 'telefono', 'correo', 'direccion',
        'tienda_id', 'margen', 'ubicacion', 'cantidad', 'stock_minimo',
    ];

    $fila = function (array $valores) use ($columnas): array {
        return array_map(fn($c) => $valores[$c] ?? '', $columnas);
    };

    fputcsv($salida, $columnas, ';', '"', '\\');
    fputcsv($salida, $fila(['tipo' => 'categoria', 'nombre' => 'Bebidas', 'descripcion' => 'Refrescos y agua']), ';', '"', '\\');
    fputcsv($salida, $fila([
        'tipo' => 'proveedor', 'nombre' => 'Distribuidora Ejemplo', 'contacto' => 'Juan Pérez',
        'telefono' => '2222-3333', 'correo' => 'ventas@ejemplo.com', 'direccion' => 'San José',
    ]), ';', '"', '\\');
    fputcsv($salida, $fila([
        'tipo' => 'producto', 'nombre' => 'Coca-Cola 600 ml', 'descripcion' => 'Botella plástica',
        'categoria' => 'Bebidas', 'proveedor' => 'Distribuidora Ejemplo', 'impuesto' => '13',
        'codigo_interno' => 'COCA-600', 'codigo_barras' => '7501055300075', 'costo' => '650', 'estado' => 'activo',
    ]), ';', '"', '\\');
    fputcsv($salida, $fila([
        'tipo' => 'precio', 'codigo_interno' => 'COCA-600', 'costo' => '650', 'tienda_id' => '1', 'margen' => '40',
    ]), ';', '"', '\\');
    fputcsv($salida, $fila([
        'tipo' => 'inventario', 'codigo_interno' => 'COCA-600', 'tienda_id' => '1', 'ubicacion' => '',
        'cantidad' => '24', 'stock_minimo' => '6',
    ]), ';', '"', '\\');

    fclose($salida);
    exit;
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';
$accion = (string)($_GET['accion'] ?? '');

try {
    if ($metodo === 'GET' && $accion === 'plantilla') {
        libImportador();
        impPlantilla();
    }

    if ($metodo === 'GET' && $accion === 'historial') {
        libImportador();
        impHistorial();
    }

    libPost();
    impProcesar();
} catch (PDOException $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('[importar] ' . $e->getMessage());

    if ((string)$e->getCode() === '42P01') {
        libJson(false, null, 'Faltan tablas en la base de datos. Ejecute basededatos/NuevasHU.sql en Supabase.', 500);
    }

    libJson(false, null, 'Error de base de datos durante la importación.', 500);
} catch (Throwable $e) {
    error_log('[importar] ' . $e->getMessage());
    libJson(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
