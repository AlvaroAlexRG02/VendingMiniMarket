<?php
/** Lectura de importaciones Nayax confirmadas para Registro de ventas. */
declare(strict_types=1);
ini_set('display_errors','0'); error_reporting(E_ALL);
require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../../config/session.php';
if (empty($_SESSION['usuario']['id_usuario'])) {
    http_response_code(401); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Debe iniciar sesión.']); exit;
}
function nayaxListaResponder(bool $ok,$data=null,string $msg='',int $code=200): void {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
    echo json_encode(['success'=>$ok,'data'=>$data,'message'=>$msg],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
}
try {
    $usuario=$_SESSION['usuario'];$id=(int)$usuario['id_usuario'];$rol=(int)($usuario['id_rol']??0);
    if (!in_array($rol,[1,2,3,4],true)) nayaxListaResponder(false,null,'Acceso no permitido.',403);
    if (!in_array($pdo->query("SELECT to_regclass('public.importacion_nayax') IS NOT NULL")->fetchColumn(), [true, 't', 1, '1'], true)) nayaxListaResponder(true,[]);
    $extra=$rol<=2?'':' AND EXISTS (SELECT 1 FROM public.usuario_tienda ut WHERE ut.id_tienda=i.id_tienda AND ut.id_usuario=:usuario AND ut.estado=TRUE)';
    $param=$rol<=2?[]:[':usuario'=>$id];
    if (isset($_GET['id'])) {
        $idLote=(int)$_GET['id'];if($idLote<=0) nayaxListaResponder(false,null,'Importación inválida.',400);
        $st=$pdo->prepare("SELECT i.id_importacion,i.nombre_archivo,i.periodo_inicio,i.periodo_fin,t.nombre AS tienda,i.unidades,i.total
            FROM public.importacion_nayax i JOIN public.tienda t ON t.id_tienda=i.id_tienda
            WHERE i.id_importacion=:id AND i.estado='CONFIRMADA' $extra");
        $st->execute([':id'=>$idLote]+$param);$lote=$st->fetch(PDO::FETCH_ASSOC);
        if (!$lote) nayaxListaResponder(false,null,'No se encontró la importación.',404);
        $st=$pdo->prepare("SELECT d.fila_excel,d.codigo_retail,d.nombre_archivo_producto,d.cantidad,d.tasa_iva,d.subtotal,d.impuesto,d.total,p.nombre AS producto_catalogo
            FROM public.importacion_nayax_detalle d
            JOIN public.producto p ON p.id_producto=d.id_producto
            WHERE d.id_importacion=:id ORDER BY d.fila_excel");
        $st->execute([':id'=>$idLote]);
        $lote['productos']=array_map(static function(array $x):array {
            foreach(['subtotal','impuesto','total','tasa_iva'] as $c) $x[$c]=(float)$x[$c];
            $x['cantidad']=(int)$x['cantidad']; return $x;
        },$st->fetchAll(PDO::FETCH_ASSOC));
        nayaxListaResponder(true,$lote);
    }
    $st=$pdo->prepare("SELECT i.id_importacion,i.id_tienda,t.nombre AS tienda,i.nombre_archivo,i.periodo_inicio,i.periodo_fin,
        to_char(i.fecha_confirmacion AT TIME ZONE 'America/Costa_Rica','YYYY-MM-DD\"T\"HH24:MI:SS')||'-06:00' AS fecha,
        TRIM(CONCAT_WS(' ',u.nombre,u.apellido)) AS usuario,i.lineas,i.unidades,i.subtotal,i.impuesto,i.total
        FROM public.importacion_nayax i
        JOIN public.tienda t ON t.id_tienda=i.id_tienda
        JOIN public.usuario u ON u.id_usuario=i.id_usuario
        WHERE i.estado='CONFIRMADA' $extra
        ORDER BY i.fecha_confirmacion DESC,i.id_importacion DESC LIMIT 500");
    $st->execute($param);
    $registros=[];
    foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $idLote=(int)$r['id_importacion'];
        $registros[]=[
            'id_importacion'=>$idLote,'numero'=>'IMP-'.str_pad((string)$idLote,6,'0',STR_PAD_LEFT),
            'id_tienda'=>(int)$r['id_tienda'],'tienda'=>$r['tienda'],'fecha'=>$r['fecha'],
            'origen'=>'Nayax','metodoPago'=>'No especificado','subtotal'=>(float)$r['subtotal'],
            'impuesto'=>(float)$r['impuesto'],'total'=>(float)$r['total'],'estado'=>'Confirmada',
            'usuario'=>$r['usuario'],'productos'=>[],'productoNombre'=>'Importación: '.$r['lineas'].' productos',
            'cantidad'=>(int)$r['unidades'],'periodo_inicio'=>$r['periodo_inicio'],'periodo_fin'=>$r['periodo_fin'],
            'nombre_archivo'=>$r['nombre_archivo']
        ];
    }
    nayaxListaResponder(true,$registros);
} catch(Throwable $e) {
    error_log('[Lista importaciones Nayax] '.$e->getMessage());
    nayaxListaResponder(false,null,'No fue posible cargar las importaciones.',500);
}
