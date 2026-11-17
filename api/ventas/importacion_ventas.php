<?php
/** Importación de ventas externas: vista previa + confirmación transaccional. */
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once __DIR__.'/../../config/session.php';
require_once __DIR__.'/../../config/permisos.php';
require_once __DIR__.'/../../config/ubicaciones.php';
require_once __DIR__.'/importacion_ventas_lib.php';

function nayaxResponder(bool $ok, $data=null, string $message='', int $status=200): void {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success'=>$ok,'data'=>$data,'message'=>$message], JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit;
}
function nayaxPeriodo(string $texto): string {
    $d=DateTimeImmutable::createFromFormat('!d-m-Y',$texto);
    if (!$d || $d->format('d-m-Y')!==$texto) throw new DomainException('El rango de fechas del informe no es válido.');
    return $d->format('Y-m-d');
}
function nayaxCatalogo(PDO $pdo): array {
    return $pdo->query('SELECT id_producto,nombre,codigo_articulo_retail FROM public.producto WHERE estado=TRUE ORDER BY nombre,id_producto')->fetchAll(PDO::FETCH_ASSOC);
}
function nayaxInterpretarValidado(PDO $pdo,string $ruta,string $nombre): array {
    $datos=v41Interpretar(v41Matriz($ruta,$nombre));
    $catalogo=nayaxCatalogo($pdo); $porCodigo=[];
    foreach ($catalogo as $p) {
        $codigo=v41Codigo((string)$p['codigo_articulo_retail']);
        if ($codigo!=='') $porCodigo[$codigo][]=$p;
    }
    $mapeos=[];
    if (in_array($pdo->query("SELECT to_regclass('public.nayax_codigo_mapeo') IS NOT NULL")->fetchColumn(), [true, 't', 1, '1'], true)) {
        foreach ($pdo->query('SELECT codigo_retail,id_producto FROM public.nayax_codigo_mapeo')->fetchAll(PDO::FETCH_ASSOC) as $x) {
            $mapeos[(string)$x['codigo_retail']]=(int)$x['id_producto'];
        }
    }
    $catalogoId=[]; foreach($catalogo as $p) $catalogoId[(int)$p['id_producto']]=$p;
    $tiendas=$pdo->query('SELECT id_tienda,nombre FROM public.tienda WHERE estado=TRUE')->fetchAll(PDO::FETCH_ASSOC);
    $tiendasId=[]; foreach($tiendas as $t) $tiendasId[(string)$t['id_tienda']]=$t;
    $filas=[];$reconocidos=0;$noReconocidos=0;$sinTienda=0;
    foreach($datos['filas'] as $r) {
        $coincidencias=$porCodigo[$r['codigo']]??[];
        $id=count($coincidencias)===1?(int)$coincidencias[0]['id_producto']:null;
        if ($id===null && count($coincidencias)===0 && isset($mapeos[$r['codigo']],$catalogoId[$mapeos[$r['codigo']]])) {
            $id=$mapeos[$r['codigo']];
        }
        $r['id_producto']=$id;
        $r['producto_catalogo']=$id!==null?$catalogoId[$id]['nombre']:null;
        $r['id_tienda']=($datos['formato']==='RESUMEN_TIENDA' && isset($tiendasId[$r['codigo_tienda']]))?(int)$tiendasId[$r['codigo_tienda']]['id_tienda']:null;
        if ($id!==null) $reconocidos++; else $noReconocidos++;
        if ($r['id_tienda']===null) $sinTienda++;
        $r['estado']=count($coincidencias)>1?'CODIGO_AMBIGUO':($id===null?'NO_ENCONTRADO':($r['id_tienda']===null?'SIN_TIENDA':'IDENTIFICADO'));
        $filas[]=$r;
    }
    $datos['filas']=$filas;
    $datos['resumen']=['filas_validas'=>count($filas),'identificados'=>$reconocidos,'no_identificados'=>$noReconocidos,'sin_tienda'=>$sinTienda,'rechazados'=>count($datos['rechazadas'])];
    $datos['hash_sha256']=hash_file('sha256',$ruta);
    $datos['nombre_archivo']=basename($nombre);
    return $datos;
}
/** Precarga y bloquea en una sola ida a Supabase las existencias del informe. */
function nayaxCargarExistencias(PDO $pdo,array $ubicaciones,array $productos): array {
    $idsUbicacion=array_values(array_unique(array_map(static fn($u)=>(int)$u['id_ubicacion'],$ubicaciones)));
    $idsProducto=array_values(array_unique(array_map('intval',$productos)));
    sort($idsUbicacion,SORT_NUMERIC); sort($idsProducto,SORT_NUMERIC);
    if (!$idsUbicacion || !$idsProducto) throw new DomainException('No hay ubicaciones o productos disponibles.');
    // Mantiene el mismo bloqueo por par ubicación/producto usado anteriormente,
    // pero adquiere todos los bloqueos en una sola consulta en orden estable.
    $ubiSql=implode(',',$idsUbicacion); $prodSql=implode(',',$idsProducto);
    $st=$pdo->query("SELECT pg_advisory_xact_lock(k.ubi,k.prod) FROM (
        SELECT u.ubi,p.prod
        FROM unnest(ARRAY[$ubiSql]::integer[]) AS u(ubi)
        CROSS JOIN unnest(ARRAY[$prodSql]::integer[]) AS p(prod)
        ORDER BY u.ubi,p.prod
    ) AS k");
    $st->closeCursor();
    $existencias=[];
    $leer=$pdo->query("SELECT id_existencia,id_ubicacion,id_producto,cantidad,stock_minimo
        FROM public.existencia
        WHERE id_ubicacion IN ($ubiSql) AND id_producto IN ($prodSql)
        FOR UPDATE");
    foreach ($leer->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $clave=(int)$e['id_ubicacion'].':'.(int)$e['id_producto'];
        if (isset($existencias[$clave])) throw new DomainException('Existen registros duplicados de inventario para '.$clave.'.');
        $existencias[$clave]=[
            'id_existencia'=>(int)$e['id_existencia'],
            'id_ubicacion'=>(int)$e['id_ubicacion'],
            'id_producto'=>(int)$e['id_producto'],
            'cantidad'=>(int)$e['cantidad'],
            'stock_minimo'=>(int)$e['stock_minimo'],
            'modificada'=>false
        ];
    }
    return $existencias;
}
/** Calcula el descuento en memoria; contempla todas las ubicaciones de la tienda. */
function nayaxPlanificarDescuento(array $ubicaciones,int $producto,int $cantidad,array &$existencias): array {
    $primaria=(int)$ubicaciones[0]['id_ubicacion'];
    $faltante=$cantidad; $asignaciones=[];
    foreach ($ubicaciones as $u) {
        $ubi=(int)$u['id_ubicacion']; $clave=$ubi.':'.$producto;
        $disponible=max(0,(int)($existencias[$clave]['cantidad']??0));
        $usar=min($faltante,$disponible);
        if ($usar>0) { $asignaciones[$ubi]=$usar; $faltante-=$usar; }
        if ($faltante===0) break;
    }
    if ($faltante>0) $asignaciones[$primaria]=($asignaciones[$primaria]??0)+$faltante;
    $resultado=[];
    foreach ($asignaciones as $ubi=>$usadas) {
        $clave=$ubi.':'.$producto;
        if (!isset($existencias[$clave])) {
            $existencias[$clave]=[
                'id_existencia'=>0,'id_ubicacion'=>$ubi,'id_producto'=>$producto,
                'cantidad'=>0,'stock_minimo'=>0,'modificada'=>false
            ];
        }
        $antes=$existencias[$clave]['cantidad'];
        $despues=$antes-$usadas;
        $existencias[$clave]['cantidad']=$despues;
        $existencias[$clave]['modificada']=true;
        $resultado[]=[
            'id_ubicacion'=>$ubi,'id_producto'=>$producto,'cantidad'=>$usadas,
            'antes'=>$antes,'despues'=>$despues,'minimo'=>$existencias[$clave]['stock_minimo']
        ];
    }
    return $resultado;
}
/** Aplica todos los balances en dos consultas (UPDATE + INSERT), sin perder saldos negativos. */
function nayaxGuardarExistencias(PDO $pdo,array $existencias): void {
    $cambios=[]; $creaciones=[];
    foreach ($existencias as $e) {
        if (!$e['modificada']) continue;
        if ($e['id_existencia']>0) {
            $cambios[]=['id_existencia'=>$e['id_existencia'],'cantidad'=>$e['cantidad']];
        } else {
            $creaciones[]=['id_ubicacion'=>$e['id_ubicacion'],'id_producto'=>$e['id_producto'],'cantidad'=>$e['cantidad']];
        }
    }
    foreach (array_chunk($cambios,500) as $grupo) {
        $st=$pdo->prepare('UPDATE public.existencia AS e SET cantidad=d.cantidad,fecha_actualizacion=NOW()
            FROM jsonb_to_recordset(CAST(:filas AS jsonb)) AS d(id_existencia bigint,cantidad integer)
            WHERE e.id_existencia=d.id_existencia');
        $st->execute([':filas'=>json_encode($grupo,JSON_THROW_ON_ERROR)]);
        if ($st->rowCount()!==count($grupo)) throw new RuntimeException('No fue posible actualizar todas las existencias; no se guardó la importación.');
    }
    foreach (array_chunk($creaciones,500) as $grupo) {
        $st=$pdo->prepare('INSERT INTO public.existencia
            (id_ubicacion,id_producto,cantidad,stock_minimo,fecha_actualizacion)
            SELECT d.id_ubicacion,d.id_producto,d.cantidad,0,NOW()
            FROM jsonb_to_recordset(CAST(:filas AS jsonb)) AS d(id_ubicacion bigint,id_producto bigint,cantidad integer)');
        $st->execute([':filas'=>json_encode($grupo,JSON_THROW_ON_ERROR)]);
        if ($st->rowCount()!==count($grupo)) throw new RuntimeException('No fue posible crear todas las existencias; no se guardó la importación.');
    }
}
/** Inserta el detalle y su venta enlazada por lote, evitando dos viajes por producto. */
function nayaxGuardarDetalleYVentas(PDO $pdo,array $filas,int $idLote,int $tienda,int $ubicacion,string $referencia,string $fin): void {
    $sql="WITH lineas AS MATERIALIZED (
            SELECT * FROM jsonb_to_recordset(CAST(:filas AS jsonb)) AS x(
                fila_excel integer,id_producto bigint,codigo_retail text,nombre_producto text,
                cantidad integer,tasa_iva numeric,subtotal numeric,impuesto numeric,total numeric,
                precio_unitario numeric,costo_unitario numeric)
        ), detalles AS (
            INSERT INTO public.importacion_nayax_detalle
                (id_importacion,fila_excel,id_producto,codigo_retail,nombre_archivo_producto,
                 cantidad,tasa_iva,subtotal,impuesto,total)
            SELECT :lote,lineas.fila_excel,lineas.id_producto,lineas.codigo_retail,lineas.nombre_producto,
                   lineas.cantidad,lineas.tasa_iva,lineas.subtotal,lineas.impuesto,lineas.total FROM lineas
            RETURNING id_detalle,fila_excel
        )
        INSERT INTO public.venta_nayax
            (id_tienda,id_ubicacion,id_producto,referencia,fecha,cantidad,precio_unitario,
             subtotal,impuesto,total,costo_unitario,estado,id_importacion,id_importacion_detalle)
        SELECT :tienda,:ubicacion,lineas.id_producto,:referencia,
            (LEAST(CAST(:fin AS date),(NOW() AT TIME ZONE 'America/Costa_Rica')::date)::timestamp
                + INTERVAL '12 hours') AT TIME ZONE 'America/Costa_Rica',
            lineas.cantidad,lineas.precio_unitario,lineas.subtotal,lineas.impuesto,lineas.total,
            lineas.costo_unitario,'CONFIRMADA',:lote_venta,detalles.id_detalle
        FROM lineas JOIN detalles ON detalles.fila_excel=lineas.fila_excel";
    $st=$pdo->prepare($sql);
    foreach (array_chunk($filas,250) as $grupo) {
        $st->execute([
            ':filas'=>json_encode($grupo,JSON_THROW_ON_ERROR),':lote'=>$idLote,
            ':tienda'=>$tienda,':ubicacion'=>$ubicacion,':referencia'=>$referencia,
            ':fin'=>$fin,':lote_venta'=>$idLote
        ]);
        if ($st->rowCount()!==count($grupo)) throw new RuntimeException('No fue posible registrar todas las líneas de venta; no se guardó la importación.');
    }
}
/** Actualiza alertas al finalizar los descuentos, con un solo evento por saldo final. */
function nayaxRegistrarAlertas(PDO $pdo,array $agrupados,int $usuario): int {
    $finales=[];
    foreach ($agrupados as $asignaciones) {
        foreach ($asignaciones as $a) $finales[$a['id_ubicacion'].':'.$a['id_producto']]=$a;
    }
    $alertas=[]; $negativos=0;
    foreach ($finales as $a) {
        $saldo=(int)$a['despues'];$min=(int)$a['minimo'];
        if ($saldo<0) $negativos++;
        if ($saldo>0 && ($min<=0 || $saldo>$min)) continue;
        $alertas[]=[
            'id_ubicacion'=>(int)$a['id_ubicacion'],'id_producto'=>(int)$a['id_producto'],
            'tipo_alerta'=>$saldo<0?'negativo':($saldo===0?'cero':'bajo'),
            'stock_generacion'=>$saldo,'stock_minimo_generacion'=>$min
        ];
    }
    if (!$alertas) return $negativos;
    $ubiSql=implode(',',array_unique(array_column($alertas,'id_ubicacion')));
    $prodSql=implode(',',array_unique(array_column($alertas,'id_producto')));
    $antes=[];
    $st=$pdo->query("SELECT id_ubicacion,id_producto,tipo_alerta
        FROM public.alerta_inventario
        WHERE id_ubicacion IN ($ubiSql) AND id_producto IN ($prodSql) AND estado<>'RESUELTA'");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $a) $antes[(int)$a['id_ubicacion'].':'.(int)$a['id_producto']]=$a['tipo_alerta'];
    $st=$pdo->prepare("INSERT INTO public.alerta_inventario AS existente
        (id_ubicacion,id_producto,tipo_alerta,estado,stock_generacion,stock_minimo_generacion)
        SELECT d.id_ubicacion,d.id_producto,d.tipo_alerta,'PENDIENTE',d.stock_generacion,d.stock_minimo_generacion
        FROM jsonb_to_recordset(CAST(:filas AS jsonb)) AS d(
          id_ubicacion bigint,id_producto bigint,tipo_alerta text,stock_generacion integer,stock_minimo_generacion integer)
        ON CONFLICT (id_ubicacion,id_producto) WHERE estado <> 'RESUELTA'
        DO UPDATE SET tipo_alerta=EXCLUDED.tipo_alerta,stock_generacion=EXCLUDED.stock_generacion,
            stock_minimo_generacion=EXCLUDED.stock_minimo_generacion,fecha_actualizacion=NOW(),
            estado=CASE WHEN existente.tipo_alerta IS DISTINCT FROM EXCLUDED.tipo_alerta
                 THEN 'PENDIENTE' ELSE existente.estado END
        RETURNING id_alerta,id_ubicacion,id_producto,tipo_alerta");
    $st->execute([':filas'=>json_encode($alertas,JSON_THROW_ON_ERROR)]);
    $eventos=[];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $clave=(int)$a['id_ubicacion'].':'.(int)$a['id_producto'];
        $previo=$antes[$clave]??null;
        if ($previo===null || $previo!==$a['tipo_alerta']) {
            $saldo=$finales[$clave]['despues'];
            $eventos[]=['id_alerta'=>(int)$a['id_alerta'],'id_usuario'=>$usuario,
                'accion'=>$previo===null?'GENERADA':'ACTUALIZADA',
                'observaciones'=>'Venta externa Nayax confirmada: saldo '.$saldo];
        }
    }
    if ($eventos) {
        $st=$pdo->prepare('INSERT INTO public.alerta_inventario_gestion
            (id_alerta,id_usuario,accion,observaciones)
            SELECT e.id_alerta,e.id_usuario,e.accion,e.observaciones
            FROM jsonb_to_recordset(CAST(:filas AS jsonb)) AS e(
              id_alerta bigint,id_usuario bigint,accion text,observaciones text)');
        $st->execute([':filas'=>json_encode($eventos,JSON_THROW_ON_ERROR)]);
    }
    return $negativos;
}

function nayaxConfirmar(PDO $pdo,array $datos,array $mapeosCliente,array $nuevosCliente,string $fechaCorte,bool $declaracionCorte,int $usuario): array {
    if ($datos['formato']!=='RESUMEN_TIENDA') throw new DomainException('El detalle general no identifica la tienda; importe el reporte Productos por tienda.');
    if ($datos['rechazadas']) throw new DomainException('El reporte tiene filas rechazadas. Corrija el archivo antes de confirmar.');
    if (!$datos['filas']) throw new DomainException('No se encontraron productos para confirmar.');
    if (count($datos['filas'])>3000) throw new DomainException('El informe supera el máximo de 3000 productos por importación.');
    $inicio=nayaxPeriodo((string)($datos['periodo'][0]??''));
    $finReporte=nayaxPeriodo((string)($datos['periodo'][1]??''));
    if ($inicio>$finReporte) throw new DomainException('El rango de fechas es inválido.');
    $hoy=(new DateTimeImmutable('now',new DateTimeZone('America/Costa_Rica')))->format('Y-m-d');
    $fin=$fechaCorte !== '' ? $fechaCorte : $finReporte;
    $fechaValida=DateTimeImmutable::createFromFormat('!Y-m-d',$fin);
    if (!$fechaValida || $fechaValida->format('Y-m-d')!==$fin || $fin<$inicio || $fin>$finReporte || $fin>$hoy) {
        throw new DomainException('La fecha hasta la cual se registraron las ventas debe estar dentro del rango del reporte y no puede ser futura.');
    }
    if ($fin<$finReporte && !$declaracionCorte) {
        throw new DomainException('Confirme expresamente que el archivo contiene ventas solo hasta la fecha de corte indicada.');
    }
    $tiendaId=(int)($datos['filas'][0]['id_tienda']??0);
    if (!$tiendaId) throw new DomainException('La tienda del reporte no se encuentra registrada.');
    $catalogo=nayaxCatalogo($pdo); $activos=[];
    foreach($catalogo as $p) $activos[(int)$p['id_producto']]=$p;
    $productos=[];$codigosNuevos=[];$porCrear=[];
    $categoriasActivas=$pdo->query('SELECT id_categoria FROM public.categoria WHERE estado=TRUE')->fetchAll(PDO::FETCH_COLUMN);
    $impuestosActivos=$pdo->query('SELECT id_impuesto FROM public.impuesto WHERE estado=TRUE')->fetchAll(PDO::FETCH_COLUMN);
    $categoriasActivas=array_fill_keys(array_map('intval',$categoriasActivas),true);
    $impuestosActivos=array_fill_keys(array_map('intval',$impuestosActivos),true);
    foreach($datos['filas'] as $r) {
        if ((int)($r['id_tienda']??0)!==$tiendaId) throw new DomainException('El reporte contiene más de una tienda. Importe una tienda por archivo.');
        $id=$r['id_producto'];
        if ($id===null && isset($mapeosCliente[(string)$r['fila']])) {
            $id=(int)$mapeosCliente[(string)$r['fila']];
            if (!isset($activos[$id])) throw new DomainException('Una asociación de productos no es válida.');
            $codigosNuevos[$r['codigo']]=$id;
        }
        if ($id===null && array_key_exists($r['codigo'],$nuevosCliente)) {
            // Solo el código ausente del catálogo puede convertirse en producto nuevo.
            if ($r['estado']!=='NO_ENCONTRADO') throw new DomainException('Este código no puede registrarse como producto nuevo: revise sus coincidencias.');
            $nuevo=$nuevosCliente[$r['codigo']];
            if (!is_array($nuevo)) throw new DomainException('Los datos de un producto nuevo no son válidos.');
            $cat=(int)($nuevo['id_categoria']??0); $iva=(int)($nuevo['id_impuesto']??0);
            if (!isset($categoriasActivas[$cat],$impuestosActivos[$iva])) {
                throw new DomainException('Seleccione una categoría y un impuesto activos para el producto '.$r['codigo'].'.');
            }
            $porCrear[$r['codigo']]=['nombre'=>$r['nombre'],'id_categoria'=>$cat,'id_impuesto'=>$iva];
        } elseif ($id===null || !isset($activos[$id])) {
            throw new DomainException('Hay productos nuevos sin preparar o productos sin identificar.');
        }
        if ($r['ingresos']===null || $r['ingresos']<0) throw new DomainException('Hay importes de venta inválidos.');
        if (isset($productos[$r['codigo']]) && $productos[$r['codigo']]!==$id) throw new DomainException('Un código tiene asociaciones diferentes.');
        $productos[$r['codigo']]=$id;
    }
    // Revisar TODOS los posibles duplicados antes de escribir una sola fila.
    // Asi un error de nombre o codigo no obliga a repetir varios intentos.
    $revisarDuplicados=$pdo->prepare("SELECT id_producto,nombre,codigo_articulo_retail,estado
        FROM public.producto
        WHERE LOWER(TRIM(nombre))=LOWER(TRIM(:nombre))
           OR UPPER(TRIM(COALESCE(codigo_articulo_retail,'')))=:codigo
        LIMIT 5");
    $encontradosDuplicados=[];
    $nombresDeNuevos=[];
    foreach ($porCrear as $codigoNuevo=>$datosNuevos) {
        $nombreNuevo=trim((string)$datosNuevos['nombre']);
        $nombreNormalizado=mb_strtolower($nombreNuevo,'UTF-8');
        if (isset($nombresDeNuevos[$nombreNormalizado]) && $nombresDeNuevos[$nombreNormalizado]!==$codigoNuevo) {
            $encontradosDuplicados[]=$codigoNuevo.' y '.$nombresDeNuevos[$nombreNormalizado].': nombre repetido dentro del Excel';
        }
        $nombresDeNuevos[$nombreNormalizado]=$codigoNuevo;
        $revisarDuplicados->execute([':nombre'=>$nombreNuevo,':codigo'=>mb_strtoupper(trim((string)$codigoNuevo),'UTF-8')]);
        foreach ($revisarDuplicados->fetchAll(PDO::FETCH_ASSOC) as $existente) {
            $estadoProducto=($existente['estado']===true || $existente['estado']==='t' || $existente['estado']==='1')
                ? 'activo' : 'inactivo';
            $encontradosDuplicados[]=$codigoNuevo.' — ya existe "'.$existente['nombre'].'" (código '.($existente['codigo_articulo_retail']?:'sin código').', '.$estadoProducto.')';
        }
    }
    if ($encontradosDuplicados) {
        $mostrar=array_slice(array_values(array_unique($encontradosDuplicados)),0,12);
        $restantes=count(array_unique($encontradosDuplicados))-count($mostrar);
        throw new DomainException('Revise estos productos antes de confirmar: '.implode('; ',$mostrar)
            .($restantes>0?'; y '.$restantes.' conflicto(s) más.':'')
            .'. No se descontó inventario.');
    }
    // Integridad y reintentos: el archivo se interpreta otra vez en el servidor.
    // Un mismo SHA y un período solapado de la misma tienda no pueden confirmarse dos veces.
    @set_time_limit(600);
    $pdo->beginTransaction();
    try {
        $pdo->prepare('SELECT pg_advisory_xact_lock(53141,CAST(:tienda AS integer))')->execute([':tienda'=>$tiendaId]);
        $misma=$pdo->prepare('SELECT 1 FROM public.importacion_nayax WHERE sha256=:sha LIMIT 1');
        $misma->execute([':sha'=>$datos['hash_sha256']]);
        if ($misma->fetchColumn()) throw new DomainException('Este mismo archivo ya fue confirmado. No se descontó inventario.');
        $solape=$pdo->prepare('SELECT periodo_inicio,periodo_fin FROM public.importacion_nayax WHERE id_tienda=:tienda AND periodo_inicio<=:fin AND periodo_fin>=:inicio LIMIT 1');
        $solape->execute([':tienda'=>$tiendaId,':inicio'=>$inicio,':fin'=>$fin]);
        if ($x=$solape->fetch(PDO::FETCH_ASSOC)) throw new DomainException('Ya se importaron ventas de esta tienda que se solapan con el período '.$x['periodo_inicio'].' a '.$x['periodo_fin'].'.');
        $ubicaciones=$pdo->prepare("SELECT id_ubicacion,nombre,tipo FROM public.ubicacion_inventario WHERE id_tienda=:tienda AND estado=TRUE ORDER BY CASE UPPER(tipo) WHEN 'TIENDA' THEN 0 WHEN 'EXHIBICION' THEN 1 WHEN 'BODEGA' THEN 2 ELSE 3 END,id_ubicacion");
        $ubicaciones->execute([':tienda'=>$tiendaId]); $lista=$ubicaciones->fetchAll(PDO::FETCH_ASSOC);
        if (!$lista) throw new DomainException('La tienda no tiene ubicaciones activas de inventario.');
        $idPrimario=(int)$lista[0]['id_ubicacion'];
        $crearProducto=$pdo->prepare("INSERT INTO public.producto
            (id_categoria,id_impuesto,codigo_articulo_retail,codigo_barras,nombre,descripcion,estado)
            VALUES (:cat,:iva,:codigo,NULL,:nombre,:descripcion,TRUE) RETURNING id_producto");
        $verificarNombre=$pdo->prepare('SELECT id_producto FROM public.producto WHERE LOWER(TRIM(nombre))=LOWER(TRIM(:nombre)) LIMIT 1');
        $creados=[];
        foreach ($porCrear as $codigo=>$nuevo) {
            if (mb_strlen($codigo,'UTF-8')>100 || mb_strlen($nuevo['nombre'],'UTF-8')>255 || trim($nuevo['nombre'])==='') {
                throw new DomainException('El código retail o el nombre del producto nuevo no es válido: '.$codigo);
            }
            $verificarNombre->execute([':nombre'=>$nuevo['nombre']]);
            if ($verificarNombre->fetchColumn()) throw new DomainException('Existe otro producto con el mismo nombre que '.$codigo.'. Revise antes de crearlo.');
            try {
                $crearProducto->execute([':cat'=>$nuevo['id_categoria'],':iva'=>$nuevo['id_impuesto'],':codigo'=>$codigo,
                    ':nombre'=>$nuevo['nombre'],':descripcion'=>'Creado desde reporte de ventas Nayax.']);
            } catch(PDOException $e) {
                if ($e->getCode()==='23505') throw new DomainException('El producto '.$codigo.' ya existe. Actualice la importación y vuelva a revisarlo.');
                throw $e;
            }
            $id=(int)$crearProducto->fetchColumn();
            $productos[$codigo]=$id;
            $creados[]=['id_producto'=>$id,'codigo_retail'=>$codigo,'nombre'=>$nuevo['nombre'],'categoria'=>$nuevo['id_categoria'],'impuesto'=>$nuevo['id_impuesto']];
            registrarOperacion($pdo,'PRODUCTO','CREAR',$tiendaId,null, end($creados));
        }

        $tasas=$pdo->query('SELECT p.id_producto,i.porcentaje FROM public.producto p JOIN public.impuesto i ON i.id_impuesto=p.id_impuesto')->fetchAll(PDO::FETCH_KEY_PAIR);
        // Un solo SELECT para recuperar todos los costos actuales de la tienda.
        $costosPorProducto=[];
        $st=$pdo->prepare('SELECT DISTINCT ON (id_producto) id_producto,costo
            FROM public.precio_tienda WHERE id_tienda=:tienda AND estado=TRUE AND fecha_fin IS NULL
            ORDER BY id_producto,fecha_inicio DESC,id_precio DESC');
        $st->execute([':tienda'=>$tiendaId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) $costosPorProducto[(int)$c['id_producto']]=$c['costo'];
        $insLote=$pdo->prepare("INSERT INTO public.importacion_nayax (id_tienda,id_usuario,nombre_archivo,sha256,periodo_inicio,periodo_fin,periodo_fin_reporte,lineas,unidades,subtotal,impuesto,total) VALUES (:tienda,:usuario,:archivo,:sha,:inicio,:fin,:fin_reporte,:lineas,:unidades,0,0,0) RETURNING id_importacion");
        $insLote->execute([':tienda'=>$tiendaId,':usuario'=>$usuario,':archivo'=>$datos['nombre_archivo'],':sha'=>$datos['hash_sha256'],':inicio'=>$inicio,':fin'=>$fin,':fin_reporte'=>$finReporte,':lineas'=>count($datos['filas']),':unidades'=>$datos['unidades']]);
        $idLote=(int)$insLote->fetchColumn(); $referencia='IMP-'.str_pad((string)$idLote,6,'0',STR_PAD_LEFT);
        error_log('[Nayax] Lote '.$idLote.': iniciando '.count($datos['filas']).' lineas.');
        $existencias=nayaxCargarExistencias($pdo,$lista,array_values($productos));
        $filasVenta=[]; $agrupados=[];$subtotalLote=0.0;$impuestoLote=0.0;$totalLote=0.0;
        foreach($datos['filas'] as $r) {
            $idProducto=(int)$productos[$r['codigo']];$cantidad=(int)$r['cantidad'];
            if ($cantidad<=0) throw new DomainException('El reporte contiene una cantidad vendida inválida.');
            $total=round((float)$r['ingresos'],2);$tasa=(float)($tasas[$idProducto]??0);
            $subtotal=round($total/(1+$tasa/100),2);$impuesto=round($total-$subtotal,2);
            $costo=$costosPorProducto[$idProducto]??null;
            $filasVenta[]=[
                'fila_excel'=>(int)$r['fila'],'id_producto'=>$idProducto,
                'codigo_retail'=>(string)$r['codigo'],'nombre_producto'=>mb_substr((string)$r['nombre'],0,350),
                'cantidad'=>$cantidad,'tasa_iva'=>$tasa,'subtotal'=>$subtotal,'impuesto'=>$impuesto,
                'total'=>$total,'precio_unitario'=>round($total/$cantidad,2),
                'costo_unitario'=>$costo===null?null:(float)$costo
            ];
            $asignaciones=nayaxPlanificarDescuento($lista,$idProducto,$cantidad,$existencias);
            foreach($asignaciones as $a) $agrupados[$a['id_ubicacion']][]=$a;
            $subtotalLote+=$subtotal;$impuestoLote+=$impuesto;$totalLote+=$total;
        }
        // Todas las operaciones siguen dentro de UNA transacción: un fallo revierte todo.
        nayaxGuardarDetalleYVentas($pdo,$filasVenta,$idLote,$tiendaId,$idPrimario,$referencia,$fin);
        error_log('[Nayax] Lote '.$idLote.': detalle y ventas registrados.');
        nayaxGuardarExistencias($pdo,$existencias);
        error_log('[Nayax] Lote '.$idLote.': existencias actualizadas.');
        $negativos=nayaxRegistrarAlertas($pdo,$agrupados,$usuario);
        error_log('[Nayax] Lote '.$idLote.': alertas actualizadas.');
        $insertMov=$pdo->prepare("INSERT INTO public.movimiento_inventario (id_usuario,id_ubicacion_origen,id_usuario_autoriza,tipo_movimiento,motivo,observaciones,estado,fecha_movimiento,fecha_confirmacion,fecha_autorizacion) VALUES (:usuario,:ubicacion,:usuario_autoriza,'SALIDA','Venta externa Nayax',:nota,'CONFIRMADO',NOW(),NOW(),NOW()) RETURNING id_movimiento");
        $relacion=$pdo->prepare('INSERT INTO public.importacion_nayax_movimiento (id_importacion,id_movimiento) VALUES (:lote,:mov)');
        $movimientos=[];
        foreach($agrupados as $ubicacion=>$asignaciones) {
            $insertMov->execute([':usuario'=>$usuario,':ubicacion'=>$ubicacion,':usuario_autoriza'=>$usuario,':nota'=>$referencia.' | '.$datos['nombre_archivo']]);
            $idMov=(int)$insertMov->fetchColumn(); $movimientos[]=$idMov;
            // Agrupar un producto por ubicación para mantener un solo saldo anterior/final por lote.
            $consolidados=[];
            foreach ($asignaciones as $a) {
                $clave=(int)$a['id_producto'];
                if (!isset($consolidados[$clave])) $consolidados[$clave]=$a;
                else {
                    $consolidados[$clave]['cantidad']+=(int)$a['cantidad'];
                    $consolidados[$clave]['despues']=(int)$a['despues'];
                }
            }
            $insertDetalleMov=$pdo->prepare('INSERT INTO public.detalle_movimiento
                (id_movimiento,id_producto,cantidad,saldo_origen_anterior,saldo_origen_resultante)
                SELECT :mov,d.id_producto,d.cantidad,d.antes,d.despues
                FROM jsonb_to_recordset(CAST(:filas AS jsonb)) AS d(
                    id_producto bigint,cantidad integer,antes integer,despues integer)');
            $insertDetalleMov->execute([':mov'=>$idMov,':filas'=>json_encode(array_values($consolidados),JSON_THROW_ON_ERROR)]);
            $relacion->execute([':lote'=>$idLote,':mov'=>$idMov]);
        }
        $guardarMap=$pdo->prepare('INSERT INTO public.nayax_codigo_mapeo (codigo_retail,id_producto,id_usuario) VALUES (:codigo,:producto,:usuario) ON CONFLICT (codigo_retail) DO UPDATE SET id_producto=EXCLUDED.id_producto,id_usuario=EXCLUDED.id_usuario,fecha_actualizacion=NOW()');
        foreach($codigosNuevos as $codigo=>$producto) $guardarMap->execute([':codigo'=>$codigo,':producto'=>$producto,':usuario'=>$usuario]);
        $pdo->prepare('UPDATE public.importacion_nayax SET subtotal=:subtotal,impuesto=:impuesto,total=:total WHERE id_importacion=:lote')
            ->execute([':subtotal'=>round($subtotalLote,2),':impuesto'=>round($impuestoLote,2),':total'=>round($totalLote,2),':lote'=>$idLote]);
        registrarOperacion($pdo,'IMPORTACION_NAYAX','CONFIRMAR',$tiendaId,null,[
            'id_importacion'=>$idLote,'archivo'=>$datos['nombre_archivo'],'periodo_inicio'=>$inicio,'periodo_fin'=>$fin,'periodo_fin_reporte'=>$finReporte,'productos_nuevos'=>$creados,
            'unidades'=>$datos['unidades'],'lineas'=>count($datos['filas']),'movimientos'=>$movimientos,
            'ubicaciones_negativas'=>$negativos,'total'=>round($totalLote,2)
        ]);
        $pdo->commit();
        error_log('[Nayax] Lote '.$idLote.': importacion confirmada y transaccion cerrada.');
        return ['numero'=>$referencia,'id_importacion'=>$idLote,'unidades'=>$datos['unidades'],'lineas'=>count($datos['filas']),'total'=>round($totalLote,2),'alertas_negativas'=>$negativos,'productos_creados'=>count($creados)];
    } catch(Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
if (empty($_SESSION['usuario']['id_usuario'])) nayaxResponder(false,null,'Debe iniciar sesión.',401);
if (!esAdministrador()) nayaxResponder(false,null,'Solo el administrador puede importar ventas.',403);
if (($_SERVER['REQUEST_METHOD']??'')==='GET') {
    if (!isset($_SESSION['csrf_importacion_nayax'])) $_SESSION['csrf_importacion_nayax']=bin2hex(random_bytes(32));
    nayaxResponder(true,['csrf_token'=>$_SESSION['csrf_importacion_nayax'],'tiendas'=>$pdo->query('SELECT id_tienda,nombre FROM public.tienda WHERE estado=TRUE ORDER BY id_tienda')->fetchAll(PDO::FETCH_ASSOC),'catalogo'=>nayaxCatalogo($pdo),
        'categorias'=>$pdo->query('SELECT id_categoria,nombre FROM public.categoria WHERE estado=TRUE ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC),
        'impuestos'=>$pdo->query('SELECT id_impuesto,nombre,porcentaje FROM public.impuesto WHERE estado=TRUE ORDER BY porcentaje,nombre')->fetchAll(PDO::FETCH_ASSOC)]);
}
if (($_SERVER['REQUEST_METHOD']??'')!=='POST') nayaxResponder(false,null,'Método no permitido.',405);
if (!isset($_SESSION['csrf_importacion_nayax']) || !hash_equals($_SESSION['csrf_importacion_nayax'],(string)($_SERVER['HTTP_X_CSRF_TOKEN']??''))) nayaxResponder(false,null,'Token de seguridad inválido.',403);
if (empty($_FILES['archivo']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) nayaxResponder(false,null,'Seleccione un archivo CSV o XLSX.',400);
$f=$_FILES['archivo'];
if (($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) nayaxResponder(false,null,'Error al subir el archivo.',400);
if (($f['size']??0)<1 || $f['size']>10*1024*1024) nayaxResponder(false,null,'El archivo debe pesar entre 1 byte y 10 MB.',413);
if (!in_array(strtolower(pathinfo((string)$f['name'],PATHINFO_EXTENSION)),['csv','xlsx'],true)) nayaxResponder(false,null,'Formato no admitido: utilice CSV o XLSX.',415);
try {
    $datos=nayaxInterpretarValidado($pdo,$f['tmp_name'],(string)$f['name']);
    if(($_POST['accion']??'')==='confirmar') {
        $map=json_decode((string)($_POST['mapeos']??'{}'),true); if(!is_array($map)) throw new DomainException('Asociaciones inválidas.');
        $nuevos=json_decode((string)($_POST['nuevos']??'{}'),true); if(!is_array($nuevos)) throw new DomainException('Productos nuevos inválidos.');
        $usuarioActual=(int)$_SESSION['usuario']['id_usuario'];
        if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
        $resultado=nayaxConfirmar($pdo,$datos,$map,$nuevos,(string)($_POST['fecha_corte']??''),($_POST['declaracion_corte']??'')==='si',$usuarioActual);
        nayaxResponder(true,$resultado,'Importación confirmada; inventario actualizado.');
    }
    $datos['advertencias']=['Vista previa: el inventario se actualizará únicamente cuando confirme la importación.'];
    nayaxResponder(true,$datos,'Archivo validado. Revise los productos antes de confirmar.');
} catch(DomainException $e) {
    nayaxResponder(false,null,$e->getMessage(),409);
} catch(Throwable $e) {
    error_log('[Importación Nayax] '.$e->getMessage());
    nayaxResponder(false,null,$e instanceof RuntimeException?$e->getMessage():'No fue posible procesar la importación. Verifique la estructura de la base de datos.',500);
}
