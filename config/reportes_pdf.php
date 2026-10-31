<?php

/**
 * Generador de PDF para los reportes — mismo diseño que el historial de
 * compras (encabezado azul, tabla con cabecera oscura, filas alternas,
 * pastillas de estado y pie con numeración).
 *
 * pdfReporte([
 *   'titulo'    => 'Vending Mini Market',
 *   'subtitulo' => 'Reporte de ventas',
 *   'archivo'   => 'reporte-ventas_2026-10',
 *   'meta'      => [['Periodo', 'Octubre 2026'], ...],
 *   'resumen'   => [['Ventas sin IVA', '₡1,250.00'], ...],      // tarjetas opcionales
 *   'secciones' => [[
 *       'titulo' => '...', 'descripcion' => '...', 'vacio' => '...', 'nota' => '...',
 *       'columnas' => [['titulo' => 'Tienda', 'campo' => 'tienda', 'tipo' => 'texto'], ...],
 *       'filas' => [...],
 *   ]],
 * ]);
 *
 * Tipos de columna: texto, entero, moneda, porcentaje, fecha, badge
 * ('badge' usa 'etiquetas' => [valor => [texto, color]]).
 * 'sumar' => true agrega esa columna a la fila de totales.
 */

require_once __DIR__ . '/../vendor/autoload.php';

const PDF_COLORES_BADGE = [
    'verde'   => '#16a34a',
    'rojo'    => '#dc2626',
    'naranja' => '#d97706',
    'azul'    => '#2563eb',
    'gris'    => '#6b7280',
];

function pdfEscapar($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function pdfMoneda($valor): string
{
    return '₡' . number_format((float)$valor, 2);
}

function pdfCelda(array $columna, $valor): string
{
    $tipo = $columna['tipo'] ?? 'texto';

    switch ($tipo) {
        case 'moneda':
            return '<td class="num">' . pdfMoneda($valor) . '</td>';
        case 'entero':
            return '<td class="num">' . number_format((float)$valor, 0) . '</td>';
        case 'porcentaje':
            return '<td class="num">' . number_format((float)$valor, 1) . '%</td>';
        case 'fecha':
            return '<td>' . ($valor ? pdfEscapar(date('d/m/Y', strtotime((string)$valor))) : '-') . '</td>';
        case 'badge':
            [$texto, $color] = $columna['etiquetas'][$valor] ?? [(string)$valor, 'gris'];
            return '<td><span class="badge" style="background:' . (PDF_COLORES_BADGE[$color] ?? '#6b7280') . '">'
                . pdfEscapar($texto) . '</span></td>';
        default:
            return '<td>' . pdfEscapar($valor === null || $valor === '' ? '-' : $valor) . '</td>';
    }
}

function pdfSeccion(array $seccion): string
{
    $html = '<div class="seccion">';
    $html .= '<h2>' . pdfEscapar($seccion['titulo']) . '</h2>';

    if (!empty($seccion['descripcion'])) {
        $html .= '<p class="descripcion">' . pdfEscapar($seccion['descripcion']) . '</p>';
    }

    $columnas = $seccion['columnas'];
    $filas = $seccion['filas'];

    if (!$filas) {
        return $html . '<p class="vacio">' . pdfEscapar($seccion['vacio'] ?? 'No hay datos para el periodo seleccionado.') . '</p></div>';
    }

    $html .= '<table class="datos"><thead><tr>';
    foreach ($columnas as $c) {
        $num = in_array($c['tipo'] ?? 'texto', ['moneda', 'entero', 'porcentaje'], true) ? ' class="num"' : '';
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
            if (isset($totales[$c['campo']])) {
                $html .= pdfCelda($c, $totales[$c['campo']]);
            } else {
                $html .= '<td>' . ($primera ? 'Totales' : '') . '</td>';
            }
            $primera = false;
        }
        $html .= '</tr></tfoot>';
    }

    $html .= '</table>';

    if (!empty($seccion['nota'])) {
        $html .= '<p class="nota">' . pdfEscapar($seccion['nota']) . '</p>';
    }

    return $html . '</div>';
}

function pdfReporte(array $doc): void
{
    @ini_set('memory_limit', '512M');
    @set_time_limit(120);

    $meta = '';
    foreach ($doc['meta'] as [$etiqueta, $valor]) {
        $meta .= '<tr><td class="etiqueta">' . pdfEscapar($etiqueta) . ':</td><td>' . pdfEscapar($valor) . '</td></tr>';
    }

    $resumen = '';
    if (!empty($doc['resumen'])) {
        $resumen = '<table class="resumen"><tr>';
        foreach ($doc['resumen'] as [$etiqueta, $valor]) {
            $resumen .= '<td><div class="resumen-etiqueta">' . pdfEscapar($etiqueta) . '</div><div class="resumen-valor">'
                . pdfEscapar($valor) . '</div></td>';
        }
        $resumen .= '</tr></table>';
    }

    $secciones = '';
    foreach ($doc['secciones'] as $seccion) {
        $secciones .= pdfSeccion($seccion);
    }

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>
        @page { margin: 28px 32px 40px; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { color: #1f2937; font-size: 9px; }
        .encabezado { border-bottom: 3px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
        .encabezado h1 { margin: 0; font-size: 19px; color: #1e3a8a; }
        .encabezado p { margin: 2px 0 0; color: #6b7280; font-size: 10.5px; }
        table.meta { width: 100%; margin-bottom: 12px; font-size: 9.5px; color: #374151; }
        table.meta td { padding: 2px 0; }
        table.meta td.etiqueta { font-weight: bold; width: 110px; }
        table.resumen { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 14px; }
        table.resumen td { background: #eff6ff; border-left: 3px solid #2563eb; padding: 7px 9px; }
        .resumen-etiqueta { color: #6b7280; font-size: 8px; text-transform: uppercase; }
        .resumen-valor { color: #1e3a8a; font-size: 13px; font-weight: bold; margin-top: 2px; }
        .seccion { margin-bottom: 16px; }
        .seccion h2 { margin: 0 0 2px; font-size: 12px; color: #1e3a8a; }
        .descripcion { margin: 0 0 6px; color: #6b7280; font-size: 8.5px; }
        table.datos { width: 100%; border-collapse: collapse; }
        table.datos thead th { background: #1e3a8a; color: #ffffff; text-align: left; padding: 6px 5px; font-size: 8px; text-transform: uppercase; }
        table.datos tbody td { padding: 5px; border-bottom: 1px solid #e5e7eb; font-size: 8.5px; }
        table.datos tbody tr:nth-child(even) { background: #f3f4f6; }
        table.datos .num, table.datos th.num { text-align: right; }
        table.datos tfoot td { padding: 7px 5px; font-weight: bold; border-top: 2px solid #1e3a8a; font-size: 8.5px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 7.5px; color: #ffffff; }
        .vacio { text-align: center; padding: 14px; color: #6b7280; border: 1px dashed #d1d5db; }
        .nota { margin: 5px 0 0; color: #6b7280; font-size: 8px; }
        .pie { margin-top: 14px; font-size: 8px; color: #9ca3af; text-align: center; }
    </style></head><body>
        <div class="encabezado">
            <h1>' . pdfEscapar($doc['titulo']) . '</h1>
            <p>' . pdfEscapar($doc['subtitulo']) . '</p>
        </div>
        <table class="meta">' . $meta . '</table>
        ' . $resumen . $secciones . '
        <div class="pie">Vending Mini Market — Reporte generado automáticamente por el sistema.</div>
    </body></html>';

    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled' => false,
        'defaultFont' => 'DejaVu Sans',
    ]);
    $dompdf->setPaper('letter', 'landscape');
    $dompdf->loadHtml($html);
    $dompdf->render();

    // Numeración de páginas.
    $canvas = $dompdf->getCanvas();
    $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
    $canvas->page_text($canvas->get_width() - 110, $canvas->get_height() - 26, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 7.5, [0.61, 0.64, 0.69]);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $doc['archivo'] . '.pdf"');
    header('Cache-Control: no-store');

    echo $dompdf->output();
    exit;
}
