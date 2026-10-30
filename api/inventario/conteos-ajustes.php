<?php
/**
 * VendingMiniMarket — HU-24 Conteos físicos / HU-25 Ajustes autorizados.
 * Sin migraciones. Reutiliza conteo_inventario, detalle_conteo,
 * movimiento_inventario, detalle_movimiento y existencia.
 * NINGÚN conteo ni solicitud pendiente modifica existencias.
 */
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';

function caResponder(bool $success, $data = null, string $message = '', int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(compact('success', 'data', 'message'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function caPositivo($value): int {
    if (!is_scalar($value) || is_bool($value)) return 0;
    $n = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $n === false ? 0 : (int)$n;
}
function caNoNegativo($value): ?int {
    if (!is_scalar($value) || is_bool($value)) return null;
    $n = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    return $n === false ? null : (int)$n;
}
function caIdent(string $name): string { return '"' . str_replace('"', '""', $name) . '"'; }
// Las columnas del DER pueden ser "Estado" / "Diferencia" o minúsculas.
// Resolvemos el nombre exacto desde PostgreSQL para no alterar las tablas.
function caCol(PDO $pdo, string $table, string $field): string {
    static $cache = [];
    if (!isset($cache[$table])) {
        $st = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = :schema AND table_name = :tabla');
        $st->execute([':schema' => 'public', ':tabla' => $table]);
        $cache[$table] = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $name) $cache[$table][strtolower((string)$name)] = (string)$name;
    }
    $key = strtolower($field);
    if (!isset($cache[$table][$key])) throw new RuntimeException("La tabla $table no contiene la columna requerida $field.");
    return caIdent($cache[$table][$key]);
}
/** Detecta una columna calculada GENERATED ALWAYS sin cambiar el esquema. */
function caColCalculada(PDO $pdo, string $tabla, string $columna): bool {
    $st = $pdo->prepare("SELECT is_generated FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = :tabla
          AND lower(column_name) = lower(:columna) LIMIT 1");
    $st->execute([':tabla' => $tabla, ':columna' => $columna]);
    return strtoupper((string)$st->fetchColumn()) === 'ALWAYS';
}

function caAcceso(PDO $pdo, int $idUbicacion, bool $operacion = true): array {
    $st = $pdo->prepare('SELECT ui.id_ubicacion, ui.id_tienda, ui.nombre, ui.estado, t.estado AS tienda_activa FROM ubicacion_inventario ui JOIN tienda t ON t.id_tienda=ui.id_tienda WHERE ui.id_ubicacion=:id');
    $st->execute([':id' => $idUbicacion]);
    $u = $st->fetch(PDO::FETCH_ASSOC);
    if (!$u || !in_array($u['estado'], ['t', true, 1, '1', 'true'], true) || !in_array($u['tienda_activa'], ['t', true, 1, '1', 'true'], true)) throw new DomainException('La ubicación no existe o está inactiva.');
    $check = evaluarAccesoUbicacion($pdo, (int)$u['id_tienda'], $operacion ? 'operacion' : 'consulta');
    if (!$check['permitido']) throw new DomainException($check['motivo']);
    return $u;
}
function caBloqueo(PDO $pdo, int $ubicacion, int $producto): void {
    $st = $pdo->prepare('SELECT pg_advisory_xact_lock(CAST(:ubi AS integer), CAST(:prod AS integer))');
    $st->execute([':ubi'=>$ubicacion, ':prod'=>$producto]);
}
function caStock(PDO $pdo, int $ubicacion, int $producto): array {
    $st = $pdo->prepare('SELECT id_existencia, cantidad FROM existencia WHERE id_ubicacion=:ubi AND id_producto=:prod FOR UPDATE');
    $st->execute([':ubi'=>$ubicacion, ':prod'=>$producto]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    return $r ? ['id_existencia'=>(int)$r['id_existencia'],'cantidad'=>(int)$r['cantidad']] : ['id_existencia'=>null,'cantidad'=>0];
}
function caProducto(PDO $pdo, int $producto): void {
    $st = $pdo->prepare('SELECT 1 FROM producto WHERE id_producto=:id AND estado=TRUE');
    $st->execute([':id'=>$producto]);
    if (!$st->fetchColumn()) throw new DomainException("Producto #$producto inexistente o inactivo.");
}
function caResumenConteo(PDO $pdo, int $idConteo): array {
    $estado = caCol($pdo, 'conteo_inventario', 'Estado');
    $ob = caCol($pdo, 'conteo_inventario', 'Observaciones');
    $diff = caCol($pdo, 'detalle_conteo', 'Diferencia');
    $estDiff = caCol($pdo, 'detalle_conteo', 'estado_diferencia');
    $st = $pdo->prepare("SELECT c.id_conteo, c.id_ubicacion, c.id_usuario, c.$estado AS estado, c.$ob AS observaciones,
        c.fecha_conteo, c.fecha_finalizacion, ui.nombre AS ubicacion
        FROM conteo_inventario c JOIN ubicacion_inventario ui ON ui.id_ubicacion=c.id_ubicacion WHERE c.id_conteo=:id");
    $st->execute([':id'=>$idConteo]);
    $c = $st->fetch(PDO::FETCH_ASSOC);
    if (!$c) throw new DomainException('El conteo no existe.');
    caAcceso($pdo, (int)$c['id_ubicacion'], false);
    $st = $pdo->prepare("SELECT d.id_detalle_conteo, d.id_producto, p.nombre AS producto, d.stock_sistema, d.cantidad_fisica,
        d.$diff AS diferencia, d.$estDiff AS estado_diferencia, d.id_movimiento_ajuste
        FROM detalle_conteo d JOIN producto p ON p.id_producto=d.id_producto
        WHERE d.id_conteo=:id ORDER BY p.nombre");
    $st->execute([':id'=>$idConteo]);
    $c['detalles'] = $st->fetchAll(PDO::FETCH_ASSOC);
    return $c;
}
function caOpciones(PDO $pdo, int $rol): array {
    if (!in_array($rol,[1,2,3,4],true)) throw new DomainException('Rol sin acceso al módulo.');
    $ubi = $pdo->query('SELECT ui.id_ubicacion,ui.id_tienda,ui.nombre,ui.tipo,t.nombre AS tienda
      FROM ubicacion_inventario ui JOIN tienda t ON t.id_tienda=ui.id_tienda WHERE ui.estado=TRUE AND t.estado=TRUE ORDER BY t.nombre,ui.nombre')->fetchAll(PDO::FETCH_ASSOC);
    $ubi = array_values(array_filter($ubi, static function($u) use ($pdo): bool {
        return evaluarAccesoUbicacion($pdo,(int)$u['id_tienda'],'consulta')['permitido'];
    }));
    $productos = $pdo->query('SELECT id_producto,nombre,codigo_articulo_retail,codigo_barras FROM producto WHERE estado=TRUE ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    return ['ubicaciones'=>$ubi,'productos'=>$productos,'puede_solicitar'=>in_array($rol,[1,2,3],true),'puede_aprobar'=>in_array($rol,[1,2],true)];
}
function caCrearConteo(PDO $pdo, array $input, int $usuario): array {
    $ubicacion = caPositivo($input['id_ubicacion'] ?? null);
    $items = $input['items'] ?? null;
    if (!$ubicacion || !is_array($items) || count($items)<1 || count($items)>100) throw new DomainException('Seleccione ubicación y entre 1 y 100 productos.');
    $obs = trim((string)($input['observaciones'] ?? ''));
    if (mb_strlen($obs)>500) throw new DomainException('Observaciones: máximo 500 caracteres.');
    $cantidades=[];
    foreach ($items as $i) {
        if (!is_array($i)) throw new DomainException('Producto inválido.');
        $p=caPositivo($i['id_producto'] ?? null);
        $q=caNoNegativo($i['cantidad_fisica'] ?? null);
        if (!$p || $q===null || isset($cantidades[$p])) throw new DomainException('Productos distintos y cantidades físicas enteras de cero o más.');
        $cantidades[$p]=$q;
    }
    ksort($cantidades,SORT_NUMERIC);
    $pdo->beginTransaction();
    try {
        $u=caAcceso($pdo,$ubicacion);
        $estado=caCol($pdo,'conteo_inventario','Estado');
        $observ=caCol($pdo,'conteo_inventario','Observaciones');
        $diff=caCol($pdo,'detalle_conteo','Diferencia');
        $estadoDiff=caCol($pdo,'detalle_conteo','estado_diferencia');
        $st=$pdo->prepare("INSERT INTO conteo_inventario(id_ubicacion,id_usuario,$estado,$observ,fecha_conteo,fecha_finalizacion)
            VALUES(:ubi,:usuario,'FINALIZADO',:observ,NOW(),NOW()) RETURNING id_conteo");
        $st->execute([':ubi'=>$ubicacion,':usuario'=>$usuario,':observ'=>$obs?:null]);
        $id=(int)$st->fetchColumn();
        // "Diferencia" puede ser generada automáticamente o una columna normal.
        // No debemos insertar manualmente valores en una columna GENERATED ALWAYS.
        $diferenciaCalculada = caColCalculada($pdo, 'detalle_conteo', 'Diferencia');
        if ($diferenciaCalculada) {
            $ins = $pdo->prepare("INSERT INTO detalle_conteo
                (id_conteo,id_producto,stock_sistema,cantidad_fisica,$estadoDiff)
                VALUES(:conteo,:producto,:sistema,:fisico,:estado)");
        } else {
            $ins = $pdo->prepare("INSERT INTO detalle_conteo
                (id_conteo,id_producto,stock_sistema,cantidad_fisica,$diff,$estadoDiff)
                VALUES(:conteo,:producto,:sistema,:fisico,:diferencia,:estado)");
        }
        $dif=[];
        foreach($cantidades as $p=>$fisico) {
            caProducto($pdo,$p);
            caBloqueo($pdo,$ubicacion,$p);
            $s=caStock($pdo,$ubicacion,$p)['cantidad'];
            $delta=$fisico-$s;
            $parametros = [':conteo'=>$id, ':producto'=>$p, ':sistema'=>$s,
                ':fisico'=>$fisico, ':estado'=>$delta===0?'SIN_DIFERENCIA':'PENDIENTE'];
            if (!$diferenciaCalculada) $parametros[':diferencia'] = $delta;
            $ins->execute($parametros);
            $dif[]=['id_producto'=>$p,'stock_sistema'=>$s,'cantidad_fisica'=>$fisico,'diferencia'=>$delta];
        }
        registrarOperacion($pdo,'CONTEO_INVENTARIO','REGISTRAR_CONTEO',(int)$u['id_tienda'],null,['id_conteo'=>$id,'id_ubicacion'=>$ubicacion,'detalles'=>$dif]);
        $pdo->commit();
        return ['id_conteo'=>$id,'detalles'=>$dif];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function caSolicitarAjuste(PDO $pdo, array $input, int $usuario): array {
    $ubicacion=caPositivo($input['id_ubicacion'] ?? null);
    $producto=caPositivo($input['id_producto'] ?? null);
    $objetivo=caNoNegativo($input['cantidad_objetivo'] ?? null);
    $conteo=caPositivo($input['id_conteo'] ?? null);
    $motivos=['DIFERENCIA_CONTEO','STOCK_NEGATIVO','ROBO','PERDIDA','ERROR_REGISTRO','OTRO'];
    $motivo=strtoupper(trim((string)($input['motivo']??'')));
    $observ=trim((string)($input['observaciones']??''));
    if (!$ubicacion || !$producto || $objetivo===null || !in_array($motivo,$motivos,true)) throw new DomainException('Indique ubicación, producto, cantidad objetivo y motivo válido.');
    if ($motivo==='OTRO' && $observ==='') throw new DomainException('Describa el motivo del ajuste.');
    if (mb_strlen($observ)>450) throw new DomainException('Observaciones: máximo 450 caracteres.');
    $pdo->beginTransaction();
    try {
        $u=caAcceso($pdo,$ubicacion);
        caProducto($pdo,$producto);
        caBloqueo($pdo,$ubicacion,$producto);
        $antes=caStock($pdo,$ubicacion,$producto)['cantidad'];
        if($antes===$objetivo) throw new DomainException('La cantidad objetivo ya coincide con el stock actual.');
        if($conteo) {
            $c=caResumenConteo($pdo,$conteo);
            if((int)$c['id_ubicacion']!==$ubicacion) throw new DomainException('El conteo no corresponde a esta ubicación.');
            $det=null;
            foreach($c['detalles'] as $d)if((int)$d['id_producto']===$producto)$det=$d;
            if(!$det || (int)$det['cantidad_fisica']!==$objetivo || $det['id_movimiento_ajuste']!==null) throw new DomainException('El detalle del conteo no permite solicitar este ajuste.');
            if((int)$det['stock_sistema']!==$antes) throw new DomainException('El inventario cambió después del conteo. Haga un nuevo conteo antes de ajustar.');
        }
        $st=$pdo->prepare("SELECT 1 FROM movimiento_inventario mi JOIN detalle_movimiento dm ON dm.id_movimiento=mi.id_movimiento
          WHERE mi.tipo_movimiento='AJUSTE' AND mi.estado='PENDIENTE' AND mi.id_ubicacion_origen=:ubi AND dm.id_producto=:prod LIMIT 1");
        $st->execute([':ubi'=>$ubicacion,':prod'=>$producto]);
        if($st->fetchColumn())throw new DomainException('Ya existe un ajuste pendiente para este producto en esta ubicación.');
        $nota=$observ ?: 'Ajuste solicitado para revisión';
        $st=$pdo->prepare("INSERT INTO movimiento_inventario
          (id_usuario,id_ubicacion_origen,id_ubicacion_destino,id_usuario_autoriza,tipo_movimiento,motivo,observaciones,estado,fecha_movimiento,id_pedido)
          VALUES(:usuario,:ubi,NULL,NULL,'AJUSTE',:motivo,:observ,'PENDIENTE',NOW(),NULL) RETURNING id_movimiento");
        $st->execute([':usuario'=>$usuario,':ubi'=>$ubicacion,':motivo'=>$motivo,':observ'=>$nota]);
        $id=(int)$st->fetchColumn();
        $st=$pdo->prepare('INSERT INTO detalle_movimiento(id_movimiento,id_producto,cantidad,saldo_origen_anterior,saldo_origen_resultante)
          VALUES(:mov,:prod,:cantidad,:antes,:despues)');
        $st->execute([':mov'=>$id,':prod'=>$producto,':cantidad'=>abs($objetivo-$antes),':antes'=>$antes,':despues'=>$objetivo]);
        if($conteo) {
            $st=$pdo->prepare('UPDATE detalle_conteo SET id_movimiento_ajuste=:mov WHERE id_conteo=:conteo AND id_producto=:prod AND id_movimiento_ajuste IS NULL');
            $st->execute([':mov'=>$id,':conteo'=>$conteo,':prod'=>$producto]);
            if($st->rowCount()!==1)throw new DomainException('La diferencia ya está vinculada con otro movimiento.');
        }
        registrarOperacion($pdo,'MOVIMIENTO_INVENTARIO','SOLICITAR_AJUSTE',(int)$u['id_tienda'],null,['id_movimiento'=>$id,'id_producto'=>$producto,'antes'=>$antes,'objetivo'=>$objetivo,'motivo'=>$motivo,'id_conteo'=>$conteo?:null]);
        $pdo->commit();
        return ['id_movimiento'=>$id,'estado'=>'PENDIENTE'];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function caResolverAjuste(PDO $pdo, array $input, int $usuario): array {
    $id=caPositivo($input['id_movimiento']??null);
    $decision=strtoupper(trim((string)($input['decision']??'')));
    if(!$id || !in_array($decision,['APROBAR','RECHAZAR'],true)) throw new DomainException('Indique el movimiento y la decisión.');
    $pdo->beginTransaction();
    try {
        $st=$pdo->prepare("SELECT mi.*,dm.id_detalle_movimiento,dm.id_producto,dm.saldo_origen_anterior,dm.saldo_origen_resultante
          FROM movimiento_inventario mi JOIN detalle_movimiento dm ON dm.id_movimiento=mi.id_movimiento
          WHERE mi.id_movimiento=:id AND mi.tipo_movimiento='AJUSTE' AND mi.estado='PENDIENTE' FOR UPDATE OF mi,dm");
        $st->execute([':id'=>$id]);
        $r=$st->fetch(PDO::FETCH_ASSOC);
        if(!$r) throw new DomainException('El ajuste no está pendiente o no existe.');
        if($st->fetch(PDO::FETCH_ASSOC)) throw new DomainException('El ajuste tiene más de un detalle y requiere revisión.');
        $ubicacion=(int)$r['id_ubicacion_origen'];
        $producto=(int)$r['id_producto'];
        $u=caAcceso($pdo,$ubicacion);
        $nuevo=$decision==='APROBAR'?'CONFIRMADO':'ANULADO';
        if($decision==='APROBAR') {
            caBloqueo($pdo,$ubicacion,$producto);
            $s=caStock($pdo,$ubicacion,$producto);
            $antes=(int)$r['saldo_origen_anterior'];
            $despues=(int)$r['saldo_origen_resultante'];
            if($antes!==$s['cantidad'])throw new DomainException('Las existencias cambiaron desde la solicitud; no se aplicó el ajuste. Revise nuevamente.');
            if($despues<0)throw new DomainException('No se permiten cantidades negativas en este módulo.');
            if($s['id_existencia']) {
                $q=$pdo->prepare('UPDATE existencia SET cantidad=:cant,fecha_actualizacion=NOW() WHERE id_existencia=:id');
                $q->execute([':cant'=>$despues,':id'=>$s['id_existencia']]);
            }else{
                $q=$pdo->prepare('INSERT INTO existencia(id_ubicacion,id_producto,cantidad,stock_minimo,fecha_actualizacion)
                    VALUES(:ubi,:prod,:cantidad,0,NOW())');
                $q->execute([':ubi'=>$ubicacion,':prod'=>$producto,':cantidad'=>$despues]);
            }
        }
        $q=$pdo->prepare("UPDATE movimiento_inventario SET estado=:estado,id_usuario_autoriza=:usuario,
          fecha_autorizacion=NOW(),fecha_confirmacion=CASE WHEN :estado2='CONFIRMADO' THEN NOW() ELSE NULL END
          WHERE id_movimiento=:id AND estado='PENDIENTE'");
        $q->execute([':estado'=>$nuevo,':estado2'=>$nuevo,':usuario'=>$usuario,':id'=>$id]);
        if($q->rowCount()!==1)throw new DomainException('El estado del ajuste cambió; inténtelo nuevamente.');
        // Respetar los estados del CHECK de Supabase: AJUSTADA, PENDIENTE, SIN_DIFERENCIA.
        // Un rechazo ANULA la solicitud, no modifica existencias y permite una nueva solicitud.
        $estadoDif=caCol($pdo,'detalle_conteo','estado_diferencia');
        if ($decision==='APROBAR') {
            $q=$pdo->prepare("UPDATE detalle_conteo SET $estadoDif='AJUSTADA' WHERE id_movimiento_ajuste=:id");
        } else {
            $q=$pdo->prepare("UPDATE detalle_conteo SET $estadoDif='PENDIENTE', id_movimiento_ajuste=NULL WHERE id_movimiento_ajuste=:id");
        }
        $q->execute([':id'=>$id]);
        registrarOperacion($pdo,'MOVIMIENTO_INVENTARIO',$decision.'_AJUSTE',(int)$u['id_tienda'],['id_movimiento'=>$id,'estado'=>'PENDIENTE'],['id_movimiento'=>$id,'estado'=>$nuevo,'id_producto'=>$producto]);
        $pdo->commit();
        return ['id_movimiento'=>$id,'estado'=>$nuevo];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function caListar(PDO $pdo, int $rol): array {
    $estado=caCol($pdo,'conteo_inventario','Estado');
    $cols=caCol($pdo,'conteo_inventario','Observaciones');
    $conteos=$pdo->query("SELECT c.id_conteo, c.id_ubicacion, c.id_usuario,c.$estado AS estado,c.$cols AS observaciones,
       c.fecha_conteo,ui.nombre AS ubicacion FROM conteo_inventario c JOIN ubicacion_inventario ui ON ui.id_ubicacion=c.id_ubicacion
       ORDER BY c.id_conteo DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);
    $conteos=array_values(array_filter($conteos,static fn($c)=>evaluarAccesoUbicacion($pdo,(int)(caUbicacionTienda($pdo,(int)$c['id_ubicacion'])),'consulta')['permitido']));
    foreach($conteos as &$c)$c=caResumenConteo($pdo,(int)$c['id_conteo']);
    unset($c);
    $st=$pdo->query("SELECT mi.id_movimiento,mi.id_ubicacion_origen AS id_ubicacion,mi.id_usuario,mi.motivo,mi.observaciones,
       mi.fecha_movimiento,mi.estado,dm.id_producto,p.nombre AS producto,dm.saldo_origen_anterior AS stock_anterior,
       dm.saldo_origen_resultante AS stock_objetivo,ui.nombre AS ubicacion
       FROM movimiento_inventario mi JOIN detalle_movimiento dm ON dm.id_movimiento=mi.id_movimiento
       JOIN producto p ON p.id_producto=dm.id_producto
       JOIN ubicacion_inventario ui ON ui.id_ubicacion=mi.id_ubicacion_origen
       WHERE mi.tipo_movimiento='AJUSTE' ORDER BY mi.id_movimiento DESC LIMIT 60");
    $ajustes=$st->fetchAll(PDO::FETCH_ASSOC);
    $ajustes=array_values(array_filter($ajustes,static fn($a)=>evaluarAccesoUbicacion($pdo,(int)(caUbicacionTienda($pdo,(int)$a['id_ubicacion'])),'consulta')['permitido']));
    return ['conteos'=>$conteos,'ajustes'=>$ajustes,'puede_aprobar'=>in_array($rol,[1,2],true)];
}
function caUbicacionTienda(PDO $pdo,int $ubicacion): int {
    static $memo=[];
    if(isset($memo[$ubicacion]))return $memo[$ubicacion];
    $st=$pdo->prepare('SELECT id_tienda FROM ubicacion_inventario WHERE id_ubicacion=:id');
    $st->execute([':id'=>$ubicacion]);
    return $memo[$ubicacion]=(int)$st->fetchColumn();
}
$usuario=$_SESSION['usuario']??null;
if(!is_array($usuario)||!caPositivo($usuario['id_usuario']??null))caResponder(false,null,'Debe iniciar sesión.',401);
$rol=(int)($usuario['id_rol']??0);$idUsuario=(int)$usuario['id_usuario'];
$metodo=$_SERVER['REQUEST_METHOD']??'GET';
if(!in_array($metodo,['GET','POST'],true))caResponder(false,null,'Método no permitido.',405);
try {
    $accion=trim((string)($_GET['accion']??''));
    if($metodo==='GET') {
        if($accion==='opciones')caResponder(true,caOpciones($pdo,$rol));
        if($accion==='listar')caResponder(true,caListar($pdo,$rol));
        throw new DomainException('Acción no reconocida.');
    }
    $datos=json_decode(file_get_contents('php://input')?:'',true,512,JSON_THROW_ON_ERROR);
    if(!is_array($datos))throw new DomainException('Datos de solicitud inválidos.');
    if($accion==='crear_conteo') {
        if(!in_array($rol,[1,2,3,4],true))caResponder(false,null,'No tiene permiso para registrar conteos.',403);
        caResponder(true,caCrearConteo($pdo,$datos,$idUsuario),'Conteo registrado. No se modificó el inventario.',201);
    }
    if($accion==='solicitar_ajuste') {
        if(!in_array($rol,[1,2,3],true))caResponder(false,null,'No tiene permiso para solicitar ajustes.',403);
        caResponder(true,caSolicitarAjuste($pdo,$datos,$idUsuario),'Ajuste pendiente de autorización. El inventario no cambió.',201);
    }
    if($accion==='resolver_ajuste') {
        if(!in_array($rol,[1,2],true))caResponder(false,null,'Solo Administrador o Gerente General pueden autorizar ajustes.',403);
        caResponder(true,caResolverAjuste($pdo,$datos,$idUsuario),'Solicitud revisada correctamente.');
    }
    throw new DomainException('Acción no reconocida.');
}catch(DomainException | JsonException $e){caResponder(false,null,$e instanceof JsonException?'JSON no válido.':$e->getMessage(),400);
}catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    error_log('[HU24/25] '.$e->getMessage());
    // Diagnóstico SOLO para la sesión de Administrador en el servidor local.
    // El resto de los usuarios recibe el mensaje genérico.
    $esLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    $mensaje = ($esLocal && $rol === 1)
        ? 'Error técnico local: '.$e->getMessage()
        : 'No fue posible completar la operación. Consulte el registro PHP.';
    caResponder(false, null, $mensaje, 500);
}
