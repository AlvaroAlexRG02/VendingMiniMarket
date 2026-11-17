<?php
declare(strict_types=1);
/** Lector de archivos externos, sin escrituras en la base de datos. */
function v41IndiceColumna(string $referencia): int
{
    preg_match('/^[A-Z]+/', strtoupper($referencia), $m);
    $n = 0;

    foreach (str_split($m[0] ?? 'A') as $letra) {
        $n = $n * 26 + (ord($letra) - 64);
    }

    return $n - 1;
}

function v41LeerCsv(string $ruta): array
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
function v41LeerXlsx(string $ruta): array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Falta la extensión zip de PHP: active extension=zip en php.ini y reinicie el servidor PHP, o suba CSV UTF-8.');
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
                $col  = isset($c['r']) ? v41IndiceColumna((string)$c['r']) : count($celdas);
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


function v41Normalizar(string $dato): string
{
    $dato = mb_strtolower(trim($dato), 'UTF-8');
    $dato = strtr($dato, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u']);
    return trim(preg_replace('/[^a-z0-9]+/u', '_', $dato) ?? '', '_');
}

function v41Codigo(string $dato): string
{
    return strtoupper(trim($dato));
}

/** Soporta Excel, CSV UTF-8 o CSV de Windows. Los .xls binarios no son XLSX. */
function v41Matriz(string $ruta, string $nombre): array
{
    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    if ($ext === 'csv') return v41LeerCsv($ruta);
    if ($ext === 'xlsx') {
        if (file_get_contents($ruta, false, null, 0, 2) !== 'PK') {
            throw new RuntimeException('El archivo no es un XLSX válido.');
        }
        return v41LeerXlsx($ruta);
    }
    throw new RuntimeException('Utilice CSV o XLSX (no XLS antiguo).');
}

function v41Numero(string $valor): ?float
{
    $v = trim(str_replace(['₡',' '], '', $valor));
    if ($v === '') return null;
    if (preg_match('/^-?\d{1,3}(\.\d{3})+,\d{1,2}$/', $v)) $v = str_replace(',', '.', str_replace('.', '', $v));
    elseif (preg_match('/^-?\d{1,3}(,\d{3})+\.\d{1,2}$/', $v)) $v = str_replace(',', '', $v);
    elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $v)) $v = str_replace('.', '', $v);
    elseif (preg_match('/^-?\d{1,3}(,\d{3})+$/', $v)) $v = str_replace(',', '', $v);
    else $v = str_replace(',', '.', $v);
    return is_numeric($v) && is_finite((float)$v) ? (float)$v : null;
}

function v41FechaExcel(string $fecha): ?string
{
    if ($fecha === '') return null;
    if (is_numeric($fecha)) {
        $dias = (float)$fecha;
        if ($dias < 20000 || $dias > 80000) return null;
        $segundos = (int)round(($dias - 25569) * 86400);
        return gmdate('Y-m-d H:i:s', $segundos); // Hora local informada por el Excel, no convertir a UTC.
    }
    foreach (['d-m-Y H:i:s','d-m-Y H:i','d-m-Y','Y-m-d H:i:s','Y-m-d'] as $formato) {
        $d = DateTimeImmutable::createFromFormat('!'.$formato, $fecha);
        if ($d && $d->format($formato) === $fecha) return $d->format('Y-m-d H:i:s');
    }
    return null;
}

/** Busca la fila real de encabezados, no presupone que sea la primera. */
function v41Interpretar(array $matriz): array
{
    if (count($matriz) > 12000) throw new RuntimeException('El informe supera 12.000 filas; divídalo por período.');
    $cabecera = null;
    $indiceCabecera = null;
    $tipo = null;
    foreach ($matriz as $i => $fila) {
        $valores = array_map('v41Normalizar', array_map('strval', $fila));
        $m = array_flip(array_filter($valores));
        if (isset($m['codigo_del_articulo'], $m['nombre_del_articulo'], $m['unidades_vendidas'])) {
            $cabecera = $valores; $indiceCabecera = $i; $tipo = 'RESUMEN_TIENDA'; break;
        }
        if (isset($m['codigo_del_articulo'], $m['nombre_del_articulo'], $m['numero_de_transaccion'], $m['cantidad'])) {
            $cabecera = $valores; $indiceCabecera = $i; $tipo = 'DETALLE_GENERAL'; break;
        }
    }
    if ($cabecera === null) throw new RuntimeException('Encabezados no reconocidos: requiere código, producto y cantidades.');

    $columnas = [];
    foreach ($cabecera as $col => $valor) if ($valor !== '' && !array_key_exists($valor, $columnas)) $columnas[$valor] = $col;
    $celda = static function (array $fila, string $nombre) use ($columnas): string {
        return array_key_exists($nombre, $columnas) ? trim((string)($fila[$columnas[$nombre]] ?? '')) : '';
    };
    $filas = [];
    $rechazos = [];
    $vistos = [];
    $suma = 0;
    $periodo = null;
    if ($tipo === 'RESUMEN_TIENDA') {
        foreach (array_slice($matriz, 0, $indiceCabecera) as $r) {
            foreach ($r as $v) {
                if (preg_match('/\b(\d{2}-\d{2}-\d{4})\s*-\s*(\d{2}-\d{2}-\d{4})\b/', (string)$v, $m)) {
                    $periodo = [$m[1], $m[2]];
                }
            }
        }
    }
    for ($i = $indiceCabecera + 1; $i < count($matriz); $i++) {
        $r = $matriz[$i];
        $codigo = v41Codigo($celda($r, 'codigo_del_articulo'));
        $nombre = $celda($r, 'nombre_del_articulo');
        if ($codigo === '' && $nombre === '') continue; // pie de informe y totales
        $cantidadTexto = $celda($r, $tipo === 'RESUMEN_TIENDA' ? 'unidades_vendidas' : 'cantidad');
        $cantidad = v41Numero($cantidadTexto);
        $tienda = $celda($r, 'nombre_de_la_tienda');
        $idTiendaExterno = $celda($r, 'codigo_de_tienda');
        $transaccion = $celda($r, 'numero_de_transaccion');
        $fecha = v41FechaExcel($celda($r, 'fecha'));
        $ingresos = v41Numero($celda($r, $tipo === 'RESUMEN_TIENDA' ? 'ingresos_totales' : 'precio_de_venta'));
        $motivo = '';
        if ($codigo === '') $motivo = 'Falta código retail.';
        elseif ($cantidad === null || $cantidad <= 0 || floor($cantidad) !== $cantidad) $motivo = 'Cantidad inválida o no positiva.';
        elseif ($tipo === 'DETALLE_GENERAL' && ($transaccion === '' || $fecha === null)) $motivo = 'Falta número de transacción o fecha.';
        elseif ($tipo === 'RESUMEN_TIENDA' && ($tienda === '' || $idTiendaExterno === '')) $motivo = 'No indica tienda.';
        elseif ($ingresos === null || $ingresos < 0) $motivo = 'Importe de venta inválido.';
        $clave = $tipo === 'RESUMEN_TIENDA' ? $idTiendaExterno.'|'.$codigo : $transaccion.'|'.$codigo.'|'.$fecha.'|'.$cantidad;
        if (!$motivo && isset($vistos[$clave])) $motivo = 'Registro potencialmente duplicado dentro del archivo (revisar).';
        $vistos[$clave] = true;
        $registro = [
            'fila' => $i + 1, 'codigo' => $codigo, 'nombre' => $nombre,
            'cantidad' => $cantidad !== null ? (int)$cantidad : null, 'ingresos' => $ingresos,
            'tienda' => $tienda, 'codigo_tienda' => $idTiendaExterno,
            'transaccion' => $transaccion, 'fecha' => $fecha,
        ];
        if ($motivo) { $registro['motivo'] = $motivo; $rechazos[] = $registro; continue; }
        $filas[] = $registro;
        $suma += $registro['cantidad'];
    }
    if (!$filas && !$rechazos) throw new RuntimeException('No se encontraron productos vendidos en el archivo.');
    if ($tipo === 'RESUMEN_TIENDA' && !$periodo) throw new RuntimeException('No se pudo identificar el rango de fechas del reporte.');
    return ['formato' => $tipo,'periodo' => $periodo, 'filas' => $filas, 'rechazadas' => $rechazos, 'unidades' => $suma];
}
