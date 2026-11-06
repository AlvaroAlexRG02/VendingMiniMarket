<?php

/**
 * Generador de PDF para los reportes — Vending Mini Market.
 *
 * Cada tipo de reporte tiene su propio tema (color, etiqueta y portada) pero
 * comparten estructura: encabezado, ficha de datos, tarjetas de resumen,
 * secciones numeradas (tablas y gráficos de barras) y pie con paginación.
 *
 * pdfReporte([
 *   'tipo'      => 'ventas',                 // ventas|utilidad|inventario|rotacion|cierre
 *   'subtitulo' => 'Ventas por tienda y periodo',
 *   'archivo'   => 'reporte-ventas_2026-10',
 *   'meta'      => [['Periodo', 'Octubre 2026'], ...],
 *   'resumen'   => [['Ventas', '₡1,250.00', 'nota opcional'], ...],
 *   'secciones' => [ ... ver abajo ... ],
 * ]);
 *
 * Secciones:
 *   ['tipo' => 'tabla',    'titulo', 'descripcion', 'columnas', 'filas', 'vacio', 'nota']
 *   ['tipo' => 'barras',   'titulo', 'descripcion', 'items' => [['etiqueta', 'valor', 'texto', 'color']]]
 *   ['tipo' => 'columnas', 'titulo', 'descripcion', 'items' => [['etiqueta', 'valor']]]
 *   ['tipo' => 'aviso',    'texto', 'nivel' => 'info'|'alerta']
 *   ['tipo' => 'par',      'izquierda' => seccion, 'derecha' => seccion]   // dos gráficos lado a lado
 *
 * Columnas: titulo, campo, tipo (texto|entero|moneda|porcentaje|fecha|badge), sumar, etiquetas.
 */

require_once __DIR__ . '/../vendor/autoload.php';

const PDF_TEMAS = [
    'ventas'     => ['nombre' => 'VENTAS',               'principal' => '#2563eb', 'oscuro' => '#1e3a8a', 'claro' => '#eff6ff'],
    'utilidad'   => ['nombre' => 'UTILIDAD',             'principal' => '#16a34a', 'oscuro' => '#14532d', 'claro' => '#f0fdf4'],
    'inventario' => ['nombre' => 'INVENTARIO',           'principal' => '#d97706', 'oscuro' => '#78350f', 'claro' => '#fffbeb'],
    'rotacion'   => ['nombre' => 'ROTACIÓN Y PÉRDIDAS',  'principal' => '#7c3aed', 'oscuro' => '#4c1d95', 'claro' => '#f5f3ff'],
    'cierre'     => ['nombre' => 'CIERRE DIARIO',        'principal' => '#0f766e', 'oscuro' => '#134e4a', 'claro' => '#f0fdfa'],
];

const PDF_COLORES = [
    'verde'   => '#16a34a',
    'rojo'    => '#dc2626',
    'naranja' => '#d97706',
    'azul'    => '#2563eb',
    'morado'  => '#7c3aed',
    'gris'    => '#6b7280',
];

/**
 * Dompdf arma las fuentes del PDF en un archivo temporal. El usuario del servidor
 * web no siempre tiene escritura en sys_get_temp_dir(), y entonces falla con
 * "Path cannot be empty": se usa el primer directorio realmente escribible.
 */
function pdfDirectorioTemporal(): string
{
    $candidatos = [
        sys_get_temp_dir(),
        '/tmp',
        dirname(__DIR__, 4) . '/temp',
        __DIR__ . '/../tmp',
    ];

    foreach ($candidatos as $directorio) {
        if ($directorio && is_dir($directorio) && is_writable($directorio)) {
            return rtrim($directorio, '/');
        }
    }

    return sys_get_temp_dir();
}

function pdfEscapar($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function pdfMoneda($valor): string
{
    return '₡' . number_format((float)$valor, 2);
}

function pdfMonedaCorta($valor): string
{
    $n = (float)$valor;
    $abs = abs($n);

    if ($abs >= 1e6) {
        return '₡' . number_format($n / 1e6, 1) . ' M';
    }

    if ($abs >= 1e3) {
        return '₡' . number_format($n / 1e3, 1) . ' mil';
    }

    return '₡' . number_format($n, 0);
}

function pdfCelda(array $columna, $valor): string
{
    switch ($columna['tipo'] ?? 'texto') {
        case 'moneda':
            return '<td class="num">' . pdfMoneda($valor) . '</td>';
        case 'entero':
            return '<td class="num">' . number_format((float)$valor, 0) . '</td>';
        case 'porcentaje':
            return '<td class="num">' . number_format((float)$valor, 1) . '%</td>';
        case 'fecha':
            return '<td>' . ($valor ? pdfEscapar(date('d/m/Y', strtotime((string)$valor))) : '-') . '</td>';
        case 'positivo_negativo':
            $clase = (float)$valor < 0 ? 'neg' : 'pos';
            return '<td class="num ' . $clase . '">' . pdfMoneda($valor) . '</td>';
        case 'badge':
            [$texto, $color] = $columna['etiquetas'][$valor] ?? [(string)$valor, 'gris'];
            return '<td><span class="badge" style="background:' . (PDF_COLORES[$color] ?? '#6b7280') . '">' . pdfEscapar($texto) . '</span></td>';
        default:
            return '<td>' . pdfEscapar($valor === null || $valor === '' ? '-' : $valor) . '</td>';
    }
}

function pdfTitulo(array $s, int &$n): string
{
    $n++;
    $html = '<div class="seccion-titulo"><span class="numero">' . $n . '</span>' . pdfEscapar($s['titulo']) . '</div>';

    if (!empty($s['descripcion'])) {
        $html .= '<p class="descripcion">' . pdfEscapar($s['descripcion']) . '</p>';
    }

    return $html;
}

function pdfTabla(array $s): string
{
    $columnas = $s['columnas'];
    $filas = $s['filas'];

    if (!$filas) {
        return '<p class="vacio">' . pdfEscapar($s['vacio'] ?? 'No hay datos para mostrar.') . '</p>';
    }

    $html = '<table class="datos"><thead><tr>';
    foreach ($columnas as $c) {
        $num = in_array($c['tipo'] ?? 'texto', ['moneda', 'entero', 'porcentaje', 'positivo_negativo'], true) ? ' class="num"' : '';
        $html .= '<th' . $num . '>' . pdfEscapar($c['titulo']) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    $totales = [];
    foreach ($filas as $fila) {
        $html .= '<tr>';
        foreach ($columnas as $c) {
            $valor = $fila[$c['campo']] ?? null;
            $html .= pdfCelda($c, $valor);
            if (!empty($c['sumar'])) {
                $totales[$c['campo']] = ($totales[$c['campo']] ?? 0) + (float)$valor;
            }
        }
        $html .= '</tr>';
    }

    $html .= '</tbody>';

    if ($totales) {
        $html .= '<tfoot><tr>';
        $primera = true;
        foreach ($columnas as $c) {
            $html .= isset($totales[$c['campo']])
                ? pdfCelda($c, $totales[$c['campo']])
                : '<td>' . ($primera ? 'Totales' : '') . '</td>';
            $primera = false;
        }
        $html .= '</tr></tfoot>';
    }

    $html .= '</table>';

    if (!empty($s['nota'])) {
        $html .= '<p class="nota">' . pdfEscapar($s['nota']) . '</p>';
    }

    return $html;
}

function pdfBarras(array $s, string $colorTema): string
{
    $items = $s['items'];
    $maximo = 0;
    foreach ($items as $i) {
        $maximo = max($maximo, abs((float)$i['valor']));
    }

    if (!$items || $maximo <= 0) {
        return '<p class="vacio">Sin datos para graficar en este periodo.</p>';
    }

    $html = '<table class="barras">';
    foreach ($items as $i) {
        $ancho = $i['valor'] == 0 ? 0 : max(1.5, abs((float)$i['valor']) / $maximo * 100);
        $color = $i['valor'] < 0 ? PDF_COLORES['rojo'] : (PDF_COLORES[$i['color'] ?? ''] ?? $colorTema);
        $html .= '<tr>'
            . '<td class="b-etiqueta">' . pdfEscapar($i['etiqueta']) . '</td>'
            . '<td class="b-pista"><div class="b-relleno" style="width:' . round($ancho, 1) . '%;background:' . $color . '"></div></td>'
            . '<td class="b-valor">' . pdfEscapar($i['texto'] ?? number_format((float)$i['valor'], 0)) . '</td>'
            . '</tr>';
    }

    return $html . '</table>';
}

function pdfColumnas(array $s, string $colorTema): string
{
    $items = $s['items'];
    $maximo = 0;
    foreach ($items as $i) {
        $maximo = max($maximo, (float)$i['valor']);
    }

    if (!$items || $maximo <= 0) {
        return '<p class="vacio">Sin datos para graficar en este periodo.</p>';
    }

    $alto = 110;
    $ancho = round(100 / count($items), 2);
    $cadaN = (int)ceil(count($items) / 16);
    $celdas = '';
    $etiquetas = '';

    foreach ($items as $k => $i) {
        $h = $i['valor'] > 0 ? max(2, (int)round($i['valor'] / $maximo * $alto)) : 0;
        $celdas .= '<td class="c-celda" style="width:' . $ancho . '%"><div class="c-barra" style="height:' . $h . 'px;background:' . $colorTema . '"></div></td>';
        $etiquetas .= '<td class="c-etiqueta">' . ($k % $cadaN === 0 ? pdfEscapar($i['etiqueta']) : '') . '</td>';
    }

    return '<table class="columnas"><tr>' . $celdas . '</tr><tr>' . $etiquetas . '</tr></table>'
        . '<p class="nota">Máximo del periodo: ' . pdfEscapar(pdfMonedaCorta($maximo)) . '</p>';
}

function pdfContenidoSeccion(array $s, string $colorTema): string
{
    switch ($s['tipo'] ?? 'tabla') {
        case 'barras':
            return pdfBarras($s, $colorTema);
        case 'columnas':
            return pdfColumnas($s, $colorTema);
        default:
            return pdfTabla($s);
    }
}

function pdfSeccion(array $s, int &$n, string $colorTema): string
{
    if (($s['tipo'] ?? '') === 'aviso') {
        return '<div class="aviso ' . (($s['nivel'] ?? 'info') === 'alerta' ? 'aviso-alerta' : '') . '">' . pdfEscapar($s['texto']) . '</div>';
    }

    if (($s['tipo'] ?? '') === 'par') {
        $lado = function (array $x) use (&$n, $colorTema): string {
            return pdfTitulo($x, $n) . pdfContenidoSeccion($x, $colorTema);
        };

        return '<table class="par"><tr><td class="par-izq">' . $lado($s['izquierda']) . '</td><td class="par-der">' . $lado($s['derecha']) . '</td></tr></table>';
    }

    return '<div class="seccion">' . pdfTitulo($s, $n) . pdfContenidoSeccion($s, $colorTema) . '</div>';
}

function pdfReporte(array $doc): void
{
    @ini_set('memory_limit', '512M');
    @set_time_limit(120);

    $tema = PDF_TEMAS[$doc['tipo']] ?? PDF_TEMAS['ventas'];
    $p = $tema['principal'];
    $o = $tema['oscuro'];
    $c = $tema['claro'];

    $meta = '';
    foreach (array_chunk($doc['meta'], 2) as $par) {
        $meta .= '<tr>';
        foreach ($par as [$etiqueta, $valor]) {
            $meta .= '<td class="m-etiqueta">' . pdfEscapar($etiqueta) . '</td><td class="m-valor">' . pdfEscapar($valor) . '</td>';
        }
        if (count($par) === 1) {
            $meta .= '<td class="m-etiqueta"></td><td class="m-valor"></td>';
        }
        $meta .= '</tr>';
    }

    $resumen = '';
    if (!empty($doc['resumen'])) {
        $resumen = '<table class="resumen"><tr>';
        foreach ($doc['resumen'] as $r) {
            $resumen .= '<td><div class="r-etiqueta">' . pdfEscapar($r[0]) . '</div><div class="r-valor">' . pdfEscapar($r[1]) . '</div>'
                . (!empty($r[2]) ? '<div class="r-nota">' . pdfEscapar($r[2]) . '</div>' : '') . '</td>';
        }
        $resumen .= '</tr></table>';
    }

    $n = 0;
    $secciones = '';
    foreach ($doc['secciones'] as $s) {
        $secciones .= pdfSeccion($s, $n, $p);
    }

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
        @page { margin: 26px 32px 46px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #1f2937; font-size: 9px; }

        .portada { border-bottom: 3px solid ' . $p . '; padding-bottom: 10px; margin-bottom: 12px; }
        .portada table { width: 100%; }
        .marca { color: #6b7280; font-size: 8px; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; }
        .portada h1 { margin: 3px 0 0; font-size: 21px; color: ' . $o . '; }
        .portada .sub { margin: 3px 0 0; color: #6b7280; font-size: 10.5px; }
        .etiqueta-tipo { background: ' . $p . '; color: #ffffff; font-size: 8.5px; font-weight: bold; letter-spacing: 1px; padding: 6px 12px; text-align: center; }

        table.meta { width: 100%; margin-bottom: 12px; background: ' . $c . '; border-left: 3px solid ' . $p . '; padding: 5px 8px; }
        table.meta td { padding: 3px 6px; font-size: 9px; }
        td.m-etiqueta { color: #6b7280; font-weight: bold; width: 70px; }
        td.m-valor { color: #111827; width: 36%; }

        table.resumen { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 14px; }
        table.resumen td { background: ' . $c . '; border-top: 3px solid ' . $p . '; padding: 8px 10px; }
        .r-etiqueta { color: #6b7280; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .r-valor { color: ' . $o . '; font-size: 14px; font-weight: bold; margin-top: 3px; }
        .r-nota { color: #6b7280; font-size: 7.5px; margin-top: 2px; }

        .seccion { margin-bottom: 14px; }
        .seccion-titulo { color: ' . $o . '; font-size: 11.5px; font-weight: bold; border-bottom: 1px solid ' . $p . '; padding-bottom: 3px; margin-bottom: 4px; page-break-after: avoid; }
        .numero { background: ' . $p . '; color: #ffffff; padding: 1px 6px; margin-right: 6px; font-size: 9px; }
        .descripcion { margin: 0 0 6px; color: #6b7280; font-size: 8.5px; }

        table.datos { width: 100%; border-collapse: collapse; }
        table.datos thead th { background: ' . $o . '; color: #ffffff; text-align: left; padding: 6px 5px; font-size: 7.8px; text-transform: uppercase; }
        table.datos tbody td { padding: 5px; border-bottom: 1px solid #e5e7eb; font-size: 8.5px; }
        table.datos tbody tr:nth-child(even) { background: #f3f4f6; }
        table.datos .num, table.datos th.num { text-align: right; }
        table.datos tfoot td { padding: 7px 5px; font-weight: bold; border-top: 2px solid ' . $o . '; font-size: 8.5px; background: ' . $c . '; }
        .pos { color: #15803d; font-weight: bold; }
        .neg { color: #dc2626; font-weight: bold; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 7.5px; color: #ffffff; }

        table.barras { width: 100%; border-collapse: collapse; }
        table.barras td { padding: 3px 0; vertical-align: middle; font-size: 8.5px; }
        td.b-etiqueta { width: 30%; color: #111827; font-weight: bold; padding-right: 6px; }
        td.b-pista { width: 48%; background: #eef2f7; }
        .b-relleno { height: 10px; }
        td.b-valor { width: 22%; text-align: right; color: #4b5563; padding-left: 6px; }

        table.columnas { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td.c-celda { height: 110px; vertical-align: bottom; text-align: center; border-bottom: 1px solid #9ca3af; }
        .c-barra { width: 70%; margin: 0 auto; }
        td.c-etiqueta { font-size: 7px; color: #6b7280; text-align: center; padding-top: 3px; }

        table.par { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        td.par-izq { width: 50%; vertical-align: top; padding-right: 12px; }
        td.par-der { width: 50%; vertical-align: top; padding-left: 12px; }

        .aviso { background: #eff6ff; border-left: 3px solid #2563eb; color: #1e3a8a; padding: 6px 9px; margin-bottom: 10px; font-size: 8.5px; }
        .aviso-alerta { background: #fffbeb; border-left-color: #d97706; color: #78350f; }
        .vacio { text-align: center; padding: 12px; color: #6b7280; border: 1px dashed #d1d5db; margin: 0; }
        .nota { margin: 5px 0 0; color: #6b7280; font-size: 7.8px; }
    </style></head><body>
        <div class="portada"><table><tr>
            <td>
                <div class="marca">Vending Mini Market</div>
                <h1>' . pdfEscapar($doc['subtitulo']) . '</h1>
                <p class="sub">' . pdfEscapar($doc['descripcion'] ?? '') . '</p>
            </td>
            <td style="width:150px;vertical-align:top"><div class="etiqueta-tipo">' . pdfEscapar($tema['nombre']) . '</div></td>
        </tr></table></div>
        <table class="meta">' . $meta . '</table>
        ' . $resumen . $secciones . '
    </body></html>';

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => false,
        'defaultFont'     => 'DejaVu Sans',
        'tempDir'         => pdfDirectorioTemporal(),
    ]);
    $dompdf->setPaper('letter', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();

    // Pie en todas las páginas: marca, tipo y paginación.
    $canvas = $dompdf->getCanvas();
    $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
    $w = $canvas->get_width();
    $h = $canvas->get_height();
    [$r, $g, $b] = sscanf($p, '#%02x%02x%02x');
    $canvas->page_script(function ($pagina, $total, $lienzo, $metricas) use ($fuente, $w, $h, $r, $g, $b, $tema) {
        $lienzo->line(32, $h - 34, $w - 32, $h - 34, [$r / 255, $g / 255, $b / 255], 0.8);
        $lienzo->text(32, $h - 27, 'Vending Mini Market — Reporte generado automáticamente por el sistema', $fuente, 7, [0.61, 0.64, 0.69]);
        $lienzo->text($w - 150, $h - 27, $tema['nombre'] . '  ·  Página ' . $pagina . ' de ' . $total, $fuente, 7, [0.35, 0.39, 0.46]);
    });

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $doc['archivo'] . '.pdf"');
    header('Cache-Control: no-store');

    echo $dompdf->output();
    exit;
}
