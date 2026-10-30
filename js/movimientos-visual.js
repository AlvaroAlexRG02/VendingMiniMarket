/*=========================================================
  HU-22 / HU-23: PRESENTACIÓN VISUAL NO INVASIVA
  - Usa sólo lecturas de la tabla operativa y API GET.
  - No cambia estadoHU21, no sustituye renderizarTablaMovimientoHU21,
    no cambia el submit ni los permisos.
  - Ante cualquier fallo, la tabla original sigue operativa.
=========================================================*/
(() => {
    'use strict';
    const id = name => document.getElementById(name);
    const bloque = id('panelVisualHU22HU23');
    const tablaOriginal = id('tablaProductosMovimiento');
    if (!bloque || !tablaOriginal) return;

    const cache = { clave: '', resultado: null, consulta: 0, pendiente: false, fallo: '' };
    const nombreSeleccionado = sel => sel?.value ? sel.selectedOptions?.[0]?.textContent?.trim() || '' : '';
    const texto = (nombre, valor) => { const e = id(nombre); if (e) e.textContent = String(valor); };
    const entero = x => Number.isSafeInteger(Number(x)) ? Number(x) : 0;
    const modo = () => id('tipoMovimiento')?.value || '';

    function filasProductos() {
        return [...tablaOriginal.querySelectorAll('tr')].map(tr => {
            const control = tr.querySelector('input.cantidad-item-movimiento');
            if (!control || tr.cells.length < 5) return null;
            return {
                id: Number(control.dataset.producto),
                nombre: tr.cells[0]?.textContent?.trim() || 'Producto',
                stockOrigen: Number(tr.cells[1]?.textContent?.trim() || 0),
                cantidad: Number(control.value),
                valido: control.checkValidity() && /^\d+$/.test(control.value),
            };
        }).filter(Boolean);
    }

    function dibujar() {
        try {
            const tipo = modo();
            const activo = tipo === 'salida' || tipo === 'traslado';
            bloque.hidden = !activo;
            if (!activo) return;
            bloque.dataset.tipo = tipo;
            const origen = id('ubicacionOrigen');
            const destino = id('ubicacionDestino');
            const origenId = Number(origen?.value || 0);
            const destinoId = tipo === 'traslado' ? Number(destino?.value || 0) : 0;
            const productos = filasProductos();
            const datos = cache.resultado;
            const stockOrigen = new Map((datos?.stock || []).map(p => [Number(p.id_producto), Number(p.cantidad)]));
            const vistaDestino = datos?.destino;
            const permitida = !!(vistaDestino?.permitido === true &&
                Number(vistaDestino?.id_ubicacion) === destinoId && destinoId > 0);
            const stockDestino = new Map((vistaDestino?.stock || []).map(p => [Number(p.id_producto), Number(p.cantidad)]));
            texto('visTituloHU22HU23', tipo === 'salida' ? 'Resumen visual de la salida' : 'Resumen visual del traslado');
            texto('visOrigenHU22HU23', nombreSeleccionado(origen) || 'Seleccione ubicación de origen');
            texto('visDestinoHU22HU23', nombreSeleccionado(destino) || 'Seleccione ubicación de destino');
            texto('visOrigenDetalleHU22HU23', tipo === 'salida'
                ? 'Estas unidades se descontarán al registrar.' : 'Las unidades saldrán de esta ubicación.');
            texto('visUnidadesHU22HU23', productos.reduce((sum,p)=>sum+(p.valido && p.cantidad>0?p.cantidad:0),0));
            const cab = id('visCabeceraHU22HU23');
            const cuerpo = id('visFilasHU22HU23');
            if (!cab || !cuerpo) return;
            cab.replaceChildren();
            cuerpo.replaceChildren();
            const columnas = tipo === 'salida'
                ? ['Producto', 'Stock actual', 'Retirar', 'Stock resultante']
                : ['Producto', 'Origen antes → después', 'Mover', 'Destino antes → después'];
            const trCab = document.createElement('tr');
            columnas.forEach(nombre => { const th = document.createElement('th'); th.textContent = nombre; trCab.appendChild(th); });
            cab.appendChild(trCab);
            let insuficiente = false;
            productos.forEach(p => {
                const tr = document.createElement('tr');
                const td = valor => { const e = document.createElement('td'); e.textContent = String(valor); tr.appendChild(e); };
                const antesOrigen = datos ? Number(stockOrigen.get(p.id) ?? 0) : p.stockOrigen;
                const esValido = p.valido && p.cantidad >= 0 && p.cantidad <= antesOrigen;
                if (!esValido) insuficiente = true;
                const antesDestino = permitida ? Number(stockDestino.get(p.id) ?? 0) : null;
                td(p.nombre);
                td(tipo === 'salida' ? antesOrigen : `${antesOrigen} → ${esValido ? antesOrigen-p.cantidad : '—'}`);
                td(p.cantidad);
                td(tipo === 'salida' ? (esValido ? antesOrigen-p.cantidad : '—') :
                    (antesDestino === null ? '—' : `${antesDestino} → ${antesDestino+p.cantidad}`));
                cuerpo.appendChild(tr);
            });
            if (!productos.length) {
                const tr=document.createElement('tr'); const td=document.createElement('td');
                td.colSpan=columnas.length;
                td.textContent='Agregue productos a la tabla para visualizar sus saldos.';
                tr.appendChild(td); cuerpo.appendChild(tr);
            }
            let estado='cargando',titulo='Seleccione origen',detalle='La API volverá a validar los saldos al guardar.';
            if (!origenId) detalle='Seleccione una ubicación de origen.';
            else if (cache.pendiente) { titulo='Consultando existencias'; detalle='Verificando origen y destino...'; }
            else if (cache.fallo) { estado='error'; titulo='No se pudo consultar'; detalle=cache.fallo; }
            else if (!datos) { titulo='Consultando existencias'; detalle='Esperando datos del servidor.'; }
            else if (insuficiente) { estado='error'; titulo='Stock insuficiente'; detalle='Revise la cantidad a mover.'; }
            else if (tipo==='salida') { estado='permitida'; titulo='Stock consultado'; detalle='La salida descuenta el origen y no tiene destino.'; }
            else if (!destinoId) { titulo='Seleccione destino'; detalle='Elija la ubicación que recibirá la mercadería.'; }
            else if (destinoId===origenId) { estado='error'; titulo='Destino inválido'; detalle='Origen y destino deben ser diferentes.'; }
            else if (!vistaDestino) { titulo='Verificando relación'; detalle='No se ha podido comprobar todavía el destino.'; }
            else if (!permitida) { estado='error'; titulo='Relación no permitida'; detalle=vistaDestino.mensaje || 'No existe relación autorizada.'; }
            else { estado='permitida'; titulo=vistaDestino.tipo === 'INTERNA' ? 'Transferencia interna permitida' : 'Abastecimiento permitido'; detalle=vistaDestino.mensaje || 'Relación autorizada.'; }
            const e=id('visEstadoHU22HU23');
            if(e){e.dataset.estado=estado;e.textContent=titulo;}
            texto('visDetalleHU22HU23',detalle);
        } catch (error) {
            // El resumen es secundario: nunca bloquear el registro de movimientos.
            console.warn('[HU22-23 visual] No fue posible actualizar el resumen:',error);
            bloque.hidden=true;
        }
    }

    function consultarUbicaciones() {
        const tipo=modo();
        const origen=Number(id('ubicacionOrigen')?.value || 0);
        const destino=tipo==='traslado' ? Number(id('ubicacionDestino')?.value || 0) : 0;
        const clave=tipo+'|'+origen+'|'+destino;
        if(clave===cache.clave){dibujar();return;}
        cache.clave=clave;
        cache.resultado=null;
        cache.pendiente=false;
        cache.fallo='';
        const solicitud=++cache.consulta;
        if(!origen || !['salida','traslado'].includes(tipo)){dibujar();return;}
        cache.pendiente=true;
        dibujar();
        const parametros=new URLSearchParams({id_ubicacion_origen:String(origen)});
        if(destino) parametros.set('id_ubicacion_destino',String(destino));
        fetch('../api/inventario/salidas-traslados.php?'+parametros.toString(),{
            method:'GET',credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}
        }).then(async resp => {
            const result=await resp.json();
            if(!resp.ok || !result.success) throw Error(result.message || 'No se pudo consultar existencias.');
            if(solicitud!==cache.consulta)return;
            cache.resultado=result.data || {};
        }).catch(error=>{
            if(solicitud===cache.consulta)cache.fallo=error.message || 'Error consultando existencias.';
        }).finally(()=>{
            if(solicitud===cache.consulta){cache.pendiente=false;dibujar();}
        });
    }

    // Se observa sólo la tabla original; nunca se modifica desde este módulo.
    const observer=new MutationObserver(() => dibujar());
    observer.observe(tablaOriginal,{childList:true,subtree:true,characterData:true});
    id('formularioMovimientoInventario')?.addEventListener('input', event=>{
        if(event.target.closest('#tablaProductosMovimiento'))dibujar();
    });
    ['tipoMovimiento','ubicacionOrigen','ubicacionDestino'].forEach(name=>{
        id(name)?.addEventListener('change',()=>consultarUbicaciones());
    });
    // La API de entrada carga las ubicaciones de forma asíncrona. Este módulo
    // sólo reconsulta cuando el usuario cambia origen o destino.
    consultarUbicaciones();
})();
