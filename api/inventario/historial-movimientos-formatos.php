<?php
/**
 * HU-29 — Exportaciones de historial de movimientos.
 * SOLO LECTURA. Se incluye desde historial-movimientos.php después de validar sesión.
 * PDF autosuficiente (sin Composer); XLSX mediante ZipArchive, ya habilitada para Nayax.
 */
declare(strict_types=1);

function hxTexto($v): string { return trim((string)($v ?? '')); }
function hxEsc($v): string { return htmlspecialchars(hxTexto($v), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function hxRef(array $r): string {
    return !empty($r['id_pedido']) ? 'PED-' . str_pad((string)$r['id_pedido'], 6, '0', STR_PAD_LEFT)
      : 'MOV-' . str_pad((string)$r['id_movimiento'], 6, '0', STR_PAD_LEFT);
}
function hxTipo(array $r): string {
    return ['ENTRADA'=>'Entrada','SALIDA'=>'Salida','TRASLADO'=>'Traslado','AJUSTE'=>'Ajuste','BAJA'=>'Merma / baja']
      [strtoupper(hxTexto($r['tipo_movimiento']))] ?? hxTexto($r['tipo_movimiento']);
}
function hxLugar($tienda, $ubicacion): string {
    $t = hxTexto($tienda); $u = hxTexto($ubicacion);
    return $u === '' ? '' : (($t !== '' && strcasecmp($t, $u) !== 0) ? "$t / $u" : $u);
}
function hxSitios(array $r): array {
    $tipo = strtoupper(hxTexto($r['tipo_movimiento']));
    $o = hxLugar($r['tienda_origen'] ?? '', $r['origen'] ?? '');
    $d = hxLugar($r['tienda_destino'] ?? '', $r['destino'] ?? '');
    $descr = hxTexto($r['motivo'] ?? '') . ' ' . hxTexto($r['observaciones'] ?? '');
    if ($tipo === 'ENTRADA') {
        $o = $o ?: (!empty($r['id_pedido']) ? 'Ingreso por pedido' : (stripos($descr,'proveedor') !== false ? 'Proveedor externo' : 'Ingreso externo'));
        $d = $d ?: 'No registrado';
    } elseif ($tipo === 'SALIDA') {
        $o = $o ?: 'No registrado';
        $d = $d ?: (stripos($descr,'nayax') !== false ? 'Venta Nayax' : (stripos($descr,'venta') !== false ? 'Venta / cliente' : 'Salida externa'));
    } elseif ($tipo === 'BAJA') {
        $o = $o ?: 'No registrado'; $d = $d ?: 'Baja de inventario';
    } elseif ($tipo === 'AJUSTE') {
        $o = $o ?: ($d ?: 'No registrado'); $d = $d ?: 'Ajuste de inventario';
    } else {
        $o = $o ?: 'No registrado'; $d = $d ?: 'No registrado';
    }
    return [$o, $d];
}
function hxFecha($raw): string {
    $v=hxTexto($raw);
    if ($v === '') return '—';
    try { return (new DateTimeImmutable($v, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Costa_Rica'))->format('d/m/Y H:i'); }
    catch (Throwable $e) { return $v; }
}
function hxSaldoFinal(array $r): ?int {
    $tipo = strtoupper(hxTexto($r['tipo_movimiento']));
    if ($tipo === 'ENTRADA') $v = $r['saldo_destino_resultante'] ?? $r['saldo_origen_resultante'];
    elseif (in_array($tipo,['SALIDA','BAJA'],true)) $v = $r['saldo_origen_resultante'] ?? $r['saldo_destino_resultante'];
    else $v = $r['saldo_origen_resultante'] ?? $r['saldo_destino_resultante'];
    return $v === null || $v === '' ? null : (int)$v;
}
function hxNum($v): string { return $v === null || $v === '' ? '—' : (string)(int)$v; }
function hxEtiqueta(PDO $pdo, string $sql, int $id, string $defecto): string {
    if ($id <= 0) return $defecto;
    $st=$pdo->prepare($sql); $st->execute([':id'=>$id]);
    return (string)($st->fetchColumn() ?: "#$id");
}
function hxFiltrosMeta(PDO $pdo, int $n, int $unidades): array {
    $desde = hxTexto($_GET['desde'] ?? ''); $hasta = hxTexto($_GET['hasta'] ?? '');
    $tipo = strtoupper(hxTexto($_GET['tipo'] ?? ''));
    $tipo = $tipo === 'MERMA' ? 'BAJA' : $tipo;
    $tipos = ['ENTRADA'=>'Entrada','SALIDA'=>'Salida','BAJA'=>'Merma / baja','AJUSTE'=>'Ajuste','TRASLADO'=>'Traslado'];
    $tienda=hxEtiqueta($pdo,'SELECT nombre FROM public.tienda WHERE id_tienda=:id', (int)($_GET['id_tienda'] ?? 0),'Todas las tiendas');
    $ubic=hxEtiqueta($pdo,'SELECT nombre FROM public.ubicacion_inventario WHERE id_ubicacion=:id',(int)($_GET['id_ubicacion'] ?? 0),'Todas las ubicaciones');
    $usuario=hxEtiqueta($pdo,"SELECT TRIM(CONCAT_WS(' ',nombre,apellido)) FROM public.usuario WHERE id_usuario=:id",(int)($_GET['id_usuario'] ?? 0),'Todos los responsables');
    return [
      'periodo'=>($desde ?: 'Inicio') . ' al ' . ($hasta ?: 'Actualidad'),
      'tienda'=>$tienda,'ubicacion'=>$ubic,'tipo'=>$tipos[$tipo] ?? ($tipo ?: 'Todos'),
      'responsable'=>$usuario,'busqueda'=>hxTexto($_GET['busqueda'] ?? '') ?: 'Sin búsqueda',
      'registros'=>$n,'unidades'=>$unidades,
      'generado'=>(new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica')))->format('d/m/Y H:i'),
    ];
}
function hxDatos(PDO $pdo): array {
    if (!in_array((int)($_SESSION['usuario']['id_rol'] ?? 0),[1,2],true)) {
        hmResponder(false, null, 'No tiene permiso para exportar movimientos.', 403);
    }
    $f=hmFiltros($pdo); $base=hmBase();
    $conteo=$pdo->prepare('SELECT COUNT(*) '.$base.' WHERE '.$f['where']);
    hmBind($conteo,$f['params']); $conteo->execute();
    $n=(int)$conteo->fetchColumn();
    if ($n>50000) hmResponder(false,null,'La consulta supera 50 000 registros. Ajuste los filtros antes de exportar.',413);
    $st=$pdo->prepare(hmCampos().$base.' WHERE '.$f['where']. ' ORDER BY COALESCE(m.fecha_confirmacion,m.fecha_movimiento) DESC,m.id_movimiento DESC,p.nombre ASC LIMIT 50000');
    hmBind($st,$f['params']); $st->execute();
    $filas=$st->fetchAll(PDO::FETCH_ASSOC);
    $unidades=array_sum(array_map(static fn($x)=>(int)$x['cantidad'],$filas));
    return [$filas,hxFiltrosMeta($pdo,count($filas),$unidades)];
}
function hxLinea(array $r): array {
    [$origen,$destino]=hxSitios($r);
    return [hxFecha($r['fecha']),hxRef($r),hxTexto($r['codigo_retail'] ?? '') ?: '—',
      hxTexto($r['producto']),hxTipo($r),$origen,$destino,(int)$r['cantidad'],
      $r['saldo_origen_anterior'] ?? null,$r['saldo_origen_resultante'] ?? null,
      $r['saldo_destino_anterior'] ?? null,$r['saldo_destino_resultante'] ?? null,
      hxSaldoFinal($r),hxTexto($r['responsable'] ?? '') ?: '—',
      hxTexto($r['estado'] ?? ''),hxTexto($r['motivo'] ?? ''),hxTexto($r['observaciones'] ?? '')];
}
function hxXxml($s): string { return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function hxCol(int $n): string { $a=''; while($n>0){$n--; $a=chr(65+($n%26)).$a; $n=intdiv($n,26);} return $a; }
function hxXCell(int $col,int $row,$v,int $style): string {
    $ref=hxCol($col).$row;
    if (is_int($v) || is_float($v)) return '<c r="'.$ref.'" s="'.$style.'"><v>'.$v.'</v></c>';
    return '<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.hxXxml($v ?? '—').'</t></is></c>';
}
function hxXlsx(array $filas,array $meta): void {
    if (!class_exists('ZipArchive')) hmResponder(false,null,'La extensión PHP ZIP es necesaria para descargar Excel. Active extension=zip en php.ini.',500);
    $tmp=tempnam(sys_get_temp_dir(),'hist29_');
    if ($tmp===false) throw new RuntimeException('No se pudo preparar Excel.');
    $xml=tempnam(sys_get_temp_dir(),'hist29_xml_');
    if ($xml===false) {unlink($tmp);throw new RuntimeException('No se pudo preparar el historial.');}
    $h=fopen($xml,'wb');
    try {
        fwrite($h,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($h,'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="8" topLeftCell="A9" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>');
        $widths=[20,16,16,37,16,30,30,12,17,17,17,17,17,24,17,28,42];
        fwrite($h,'<cols>');foreach($widths as $i=>$w) fwrite($h,'<col min="'.($i+1).'" max="'.($i+1).'" width="'.$w.'" customWidth="1"/>');fwrite($h,'</cols><sheetData>');
        fwrite($h,'<row r="1" ht="31" customHeight="1">'.hxXCell(1,1,'VENDING MINI MARKET · HISTORIAL DE MOVIMIENTOS',1).'</row>');
        fwrite($h,'<row r="2" ht="25" customHeight="1">'.hxXCell(1,2,'Reporte de inventario | Datos reales del sistema',2).'</row>');
        fwrite($h,'<row r="3" ht="24" customHeight="1">'.hxXCell(1,3,'Período: '.$meta['periodo'].'   |   Tienda: '.$meta['tienda'].'   |   Tipo: '.$meta['tipo'],2).'</row>');
        fwrite($h,'<row r="4" ht="23" customHeight="1">'.hxXCell(1,4,'Ubicación: '.$meta['ubicacion'].'   |   Responsable: '.$meta['responsable'].'   |   Búsqueda: '.$meta['busqueda'],2).'</row>');
        fwrite($h,'<row r="5" ht="24" customHeight="1">'.hxXCell(1,5,'Registros: '.number_format($meta['registros'],0,',','.').'    ·    Unidades movidas: '.number_format($meta['unidades'],0,',','.').'    ·    Generado: '.$meta['generado'],8).'</row>');
        fwrite($h,'<row r="6" ht="8" customHeight="1"/>');
        fwrite($h,'<row r="7" ht="22" customHeight="1">'.hxXCell(1,7,'DETALLE DE MOVIMIENTOS',8).'</row>');
        $headers=['Fecha y hora','Referencia','Código retail','Producto','Tipo','Origen','Destino','Cantidad','Saldo anterior origen','Saldo final origen','Saldo anterior destino','Saldo final destino','Saldo final','Responsable','Estado','Motivo','Observaciones'];
        fwrite($h,'<row r="8" ht="34" customHeight="1">');foreach($headers as $i=>$v) fwrite($h,hxXCell($i+1,8,$v,3));fwrite($h,'</row>');
        $rnum=9;
        foreach($filas as $r){
            $line=hxLinea($r);fwrite($h,'<row r="'.$rnum.'" ht="21" customHeight="1">');
            foreach($line as $i=>$v){
                $isNum=in_array($i,[7,8,9,10,11,12],true);
                if($isNum && $v!==null) $v=(int)$v;
                $style=$isNum ? (($v!==null && $v<0)?7:6) : ($rnum%2===0?5:4);
                if($v===null) {$v='—';$style=9;}
                fwrite($h,hxXCell($i+1,$rnum,$v,$style));
            }
            fwrite($h,'</row>');$rnum++;
        }
        // Orden obligatorio de SpreadsheetML: autoFilter ANTES de mergeCells.
        fwrite($h,'</sheetData><autoFilter ref="A8:Q'.max(8,$rnum-1).'"/>');
        fwrite($h,'<mergeCells count="6"><mergeCell ref="A1:Q1"/><mergeCell ref="A2:Q2"/><mergeCell ref="A3:Q3"/><mergeCell ref="A4:Q4"/><mergeCell ref="A5:Q5"/><mergeCell ref="A7:Q7"/></mergeCells>');
        fwrite($h,'<pageMargins left="0.25" right="0.25" top="0.55" bottom="0.5" header="0.25" footer="0.25"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1"/></worksheet>');
        fclose($h);$h=null;
        $z=new ZipArchive();
        if($z->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE)!==true) throw new RuntimeException('No fue posible generar el archivo Excel.');
        $z->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $z->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Movimientos" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $styles='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        .'<fonts count="4"><font><sz val="10"/><name val="Calibri"/><color rgb="FF233554"/></font><font><b/><sz val="17"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font><font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font><font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFB42318"/></font></fonts>'
        .'<fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF11244C"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF1FA"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF5F8FD"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFFFFF"/><bgColor indexed="64"/></patternFill></fill></fills>'
        .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="hair"><color rgb="FFE2E9F3"/></bottom><diagonal/></border></borders>'
        .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        .'<cellXfs count="10">'
        .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' // 0
        .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>' // 1 title
        .'<xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1"/>' // 2 meta
        .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' // 3 header
        .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0"/>' // 4 data even
        .'<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0"/>' // 5 alternate
        .'<xf numFmtId="1" fontId="0" fillId="5" borderId="1" xfId="0" applyAlignment="1" applyNumberFormat="1"><alignment horizontal="center"/></xf>' // 6 number
        .'<xf numFmtId="1" fontId="3" fillId="4" borderId="1" xfId="0" applyAlignment="1" applyFont="1" applyNumberFormat="1"><alignment horizontal="center"/></xf>' // 7 negative
        .'<xf numFmtId="0" fontId="0" fillId="3" borderId="0" xfId="0" applyFill="1"/>' // 8 detail title
        .'<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center"/></xf>' // 9 missing
        .'</cellXfs></styleSheet>';
        $z->addFromString('xl/styles.xml',$styles);
        $z->addFile($xml,'xl/worksheets/sheet1.xml');
        if(!$z->close()) throw new RuntimeException('No se pudo cerrar Excel.');
        $nombre='VMM_Historial_Movimientos_'.(new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica')))->format('Y-m-d').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$nombre.'"');
        clearstatcache(true,$tmp);header('Content-Length: '.filesize($tmp));header('Cache-Control: private, no-store');
        readfile($tmp);
    } finally {
        if(is_resource($h)) fclose($h);
        if(is_file($tmp)) unlink($tmp);
        if(is_file($xml)) unlink($xml);
    }
    exit;
}
function hxTablaHtml(array $filas): string {
    $h='<table><thead><tr><th>Fecha y hora</th><th>Referencia</th><th>Producto</th><th>Tipo</th><th>Origen</th><th>Destino</th><th>Cantidad</th><th>Saldo final</th><th>Responsable</th></tr></thead><tbody>';
    foreach($filas as $r){$v=hxLinea($r);$saldo=$v[12];$h.='<tr>';
       foreach([0,1,3,4,5,6,7,12,13] as $i){$num=$i===7||$i===12;$val=$v[$i]===null?'—':$v[$i];
         $h.='<td class="'.($num?'num ':'').(($i===12&&$saldo!==null&&$saldo<0)?'neg':'').'">'.hxEsc($val).'</td>';}
       $h.='</tr>';}
    return $h.'</tbody></table>';
}
function hxImprimir(array $filas,array $meta): void {
    header('Content-Type: text/html; charset=utf-8');header('Cache-Control: private,no-store');
    $m=static fn($k)=>hxEsc($meta[$k]);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Historial de movimientos | Vending Mini Market</title><style>
    @page{size:A4 landscape;margin:14mm 10mm}*{box-sizing:border-box}body{font:11px Arial,sans-serif;color:#21314c;margin:0}
    header{padding:18px 20px;background:#11244c;color:white;border-radius:6px}h1{margin:4px 0 0;font-size:19px}p{margin:5px 0}.sup{letter-spacing:.09em;font-size:11px}
    .meta{margin:14px 0;padding:12px;background:#eaf1fa;line-height:1.8;border-radius:6px}.total{font-weight:700;color:#11244c}
    table{width:100%;border-collapse:collapse;table-layout:fixed}thead{display:table-header-group}th{background:#142e5a;color:white;text-align:left;font-size:10px;padding:9px 6px}
    td{border-bottom:1px solid #e2e9f3;padding:7px 6px;word-break:break-word;vertical-align:top}tbody tr:nth-child(even){background:#f5f8fd}tr{break-inside:avoid}
    th:nth-child(1){width:12%}th:nth-child(2){width:9%}th:nth-child(3){width:18%}th:nth-child(4){width:9%}th:nth-child(5){width:14%}th:nth-child(6){width:13%}
    th:nth-child(7),th:nth-child(8){width:7%}th:nth-child(9){width:11%}th:nth-child(7),th:nth-child(8){text-align:center}.num{text-align:center;white-space:nowrap}.neg{color:#b42318;font-weight:bold}
    footer{margin-top:16px;color:#66758c;font-size:10px;border-top:1px solid #d8e0ec;padding-top:9px}
    .controls{display:flex;justify-content:flex-end;padding:12px 0}.controls button{background:#11244c;color:white;border:0;padding:10px 16px;border-radius:6px;cursor:pointer}
    @media print{header{-webkit-print-color-adjust:exact;print-color-adjust:exact}.meta,th,tbody tr{-webkit-print-color-adjust:exact;print-color-adjust:exact}.controls{display:none}}
    </style></head><body>';
    echo '<header><div class="sup">VENDING MINI MARKET</div><h1>Historial de movimientos de inventario</h1></header>';
    echo '<div class="meta"><strong>Período:</strong> '.$m('periodo').' &nbsp; <strong>Tienda:</strong> '.$m('tienda').' &nbsp; <strong>Tipo:</strong> '.$m('tipo')
      .' &nbsp; <strong>Ubicación:</strong> '.$m('ubicacion').'<br><strong>Responsable:</strong> '.$m('responsable').' &nbsp; <strong>Búsqueda:</strong> '.$m('busqueda')
      .' &nbsp; <span class="total">'.$m('registros').' registros · '.$m('unidades').' unidades</span> &nbsp; <strong>Generado:</strong> '.$m('generado').'</div>';
    echo '<div class="controls"><button onclick="window.print()">Imprimir reporte</button></div>';
    echo hxTablaHtml($filas);
    echo '<footer>Vending Mini Market · Reporte de consulta. No modifica las existencias.</footer>';
    echo '<script>window.addEventListener("load",()=>setTimeout(()=>window.print(),350));</script></body></html>';
    exit;
}
/** Conversión a WinAnsi para los caracteres usados en el PDF. */
function hxPdfStr($text): string {
    $v=(string)$text;
    $v=@iconv('UTF-8','Windows-1252//TRANSLIT',$v) ?: preg_replace('/[^\x20-\x7E]/','?', $v);
    return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)', ' ', ' '],$v);
}
function hxPdfTrunc($s,int $n): string {
    $s=preg_replace('/\s+/u',' ',hxTexto($s));
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) return substr($s, 0, $n);
    return count($chars) > $n ? implode('', array_slice($chars,0,max(0,$n-2))).'…' : $s;
}
function hxPdfText(float $x,float $y,string $text,float $size=8,bool $bold=false,string $color='0.13 0.21 0.34'): string {
    return "$color rg BT /F".($bold?'2':'1')." $size Tf 1 0 0 1 $x $y Tm (".hxPdfStr($text).") Tj ET\n";
}
function hxPdfRect(float $x,float $y,float $w,float $h,string $color): string { return "$color rg $x $y $w $h re f\n"; }
function hxPdf(array $filas,array $meta): void {
    // PDF horizontal A4 autónomo: evita depender de vendor/autoload.php (Reportes).
    if(count($filas)>5000) hmResponder(false,null,'Para PDF, filtre el historial a un máximo de 5 000 registros. Excel permite hasta 50 000.',413);
    $perPage=17;$pages=max(1,(int)ceil(count($filas)/$perPage));$streams=[];
    $x=[28,115,190,330,392,496,598,645,704];
    $w=[87,75,140,62,104,102,47,59,110];
    $labels=['Fecha','Referencia','Producto','Tipo','Origen','Destino','Cant.','Saldo final','Responsable'];
    for($pg=0;$pg<$pages;$pg++){
        $s='';$s.=hxPdfRect(0,538,842,57,'0.067 0.141 0.298');
        $s.=hxPdfText(28,566,'VENDING MINI MARKET',18,true,'1 1 1');
        $s.=hxPdfText(28,550,'REPORTE DE MOVIMIENTOS DE INVENTARIO',9,false,'0.82 0.90 1');
        $s.=hxPdfRect(28,488,786,42,'0.93 0.96 0.99');
        $s.=hxPdfText(38,514,hxPdfTrunc('Período: '.$meta['periodo'].'   |   Tienda: '.$meta['tienda'].'   |   Tipo: '.$meta['tipo'],114),8.3,true);
        $s.=hxPdfText(38,497,hxPdfTrunc('Responsable: '.$meta['responsable'].'   |   Registros: '.$meta['registros'].'   |   Unidades: '.$meta['unidades'].'   |   Generado: '.$meta['generado'],125),8.0,false);
        $s.=hxPdfRect(28,458,786,24,'0.08 0.18 0.35');
        foreach($labels as $i=>$l) {
            $lx=($i===6 || $i===7) ? $x[$i]+($w[$i]-strlen($l)*4.0)/2 : $x[$i]+4;
            $s.=hxPdfText($lx,466,$l,8,true,'1 1 1');
        }
        $subset=array_slice($filas,$pg*$perPage,$perPage);
        foreach($subset as $i=>$r){
            $y=433-$i*22;
            if($i%2===1) $s.=hxPdfRect(28,$y-5,786,22,'0.96 0.98 1');
            $v=hxLinea($r);
            $values=[$v[0],$v[1],$v[3],$v[4],$v[5],$v[6],$v[7],$v[12]??'—',$v[13]];
            $max=[16,15,26,14,21,21,8,10,22];
            foreach($values as $j=>$val){
                $red=($j===7 && $val!==null && is_numeric($val) && (int)$val<0);
                $str=hxPdfTrunc((string)$val,$max[$j]);
                $tx=($j===6||$j===7)?$x[$j]+($w[$j]-strlen($str)*3.6)/2:$x[$j]+4;
                $s.=hxPdfText($tx,$y+2,$str,7.4,$red,$red?'0.71 0.14 0.1':'0.13 0.21 0.34');
            }
        }
        $s.=hxPdfRect(28,32,786,0.6,'0.83 0.87 0.93');
        $s.=hxPdfText(29,18,'Historial de movimientos · Vending Mini Market · Solo lectura',7.5,false,'0.40 0.46 0.55');
        $s.=hxPdfText(740,18,"Página ".($pg+1)." de $pages",7.5,true,'0.40 0.46 0.55');
        $streams[]=$s;
    }
    $objs=[1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    $kids=[];$num=5;
    foreach($streams as $stream){
        $p=$num++;$c=$num++;
        $kids[]="$p 0 R";
        $objs[$p]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$c.' 0 R >>';
        $objs[$c]='<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream";
    }
    $objs[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($kids).' >>';
    ksort($objs);$pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offsets=[0];
    foreach($objs as $i=>$body){$offsets[$i]=strlen($pdf);$pdf.="$i 0 obj\n$body\nendobj\n";}
    $start=strlen($pdf);$pdf.="xref\n0 ".(count($objs)+1)."\n0000000000 65535 f \n";
    foreach($objs as $i=>$body) $pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";
    $pdf.="trailer\n<< /Size ".(count($objs)+1)." /Root 1 0 R >>\nstartxref\n$start\n%%EOF\n";
    $nombre='VMM_Historial_Movimientos_'.(new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica')))->format('Y-m-d').'.pdf';
    header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$nombre.'"');
    header('Content-Length: '.strlen($pdf));header('Cache-Control: private,no-store');echo $pdf;exit;
}
function hmExportar29(PDO $pdo,string $modo): void {
    [$rows,$meta]=hxDatos($pdo);
    if($modo==='xlsx') hxXlsx($rows,$meta);
    if($modo==='pdf') hxPdf($rows,$meta);
    if($modo==='imprimir') hxImprimir($rows,$meta);
    hmResponder(false,null,'Formato no reconocido.',400);
}
