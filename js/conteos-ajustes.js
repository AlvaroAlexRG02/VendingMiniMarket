/* VendingMiniMarket — HU-24 y HU-25. No altera movimientos.js ni inventario.js. */
(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const estado = {productos:[],ubicaciones:[],items:[],conteo:0,puedeSolicitar:false,puedeAprobar:false,cuentaAviso:0};
    const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    const labelProducto = p => `${p.nombre} — ${p.codigo_articulo_retail || p.codigo_barras || `#${p.id_producto}`}`;
    function aviso(texto, tipo='ok') {
        const el=$('ca24Aviso');
        el.textContent=texto;el.className=`ca24-alert visible ${tipo}`;
        const n=++estado.cuentaAviso;
        setTimeout(()=>{if(n===estado.cuentaAviso)el.classList.remove('visible');},5000);
    }
    async function api(accion, datos=null) {
        const url=`../api/inventario/conteos-ajustes.php?accion=${encodeURIComponent(accion)}`;
        let respuesta;
        try { respuesta=await fetch(url,{method:datos?'POST':'GET',credentials:'same-origin',headers:{'Accept':'application/json',...(datos?{'Content-Type':'application/json'}:{})},...(datos?{body:JSON.stringify(datos)}:{})}); }
        catch (_) {throw new Error('No se pudo conectar con el servidor.');}
        let r;
        try {r=await respuesta.json();} catch (_) {throw new Error('La API devolvió un formato no válido.');}
        if(respuesta.status===401){window.location.href='../index/index.html';return;}
        if(!respuesta.ok||!r.success)throw new Error(r.message||'Ocurrió un error.');
        return r.data;
    }
    function enteros(value, permitirCero=false) {
        const raw=String(value).trim();
        if(!/^\d+$/.test(raw))return null;
        const n=Number(raw);
        return Number.isSafeInteger(n)&&(permitirCero?n>=0:n>0)?n:null;
    }
    function llenarSelect(el,items,valor='') {
        const anterior=el.value;
        el.innerHTML=`<option value="">${esc(valor)}</option>`+items.map(x=>`<option value="${esc(x.id)}">${esc(x.nombre)}</option>`).join('');
        if(items.some(x=>String(x.id)===anterior))el.value=anterior;
    }
    function llenarUbicaciones() {
        const lugares=estado.ubicaciones.map(u=>({id:u.id_ubicacion,nombre:`${u.tienda} — ${u.nombre} (${u.tipo})`}));
        llenarSelect($('ca24Ubicacion'),lugares,'Seleccione ubicación');
        llenarSelect($('ca24AjusteUbicacion'),lugares,'Seleccione ubicación');
    }
    function filtrarProductos() {
        const busqueda=$('ca24Buscar').value.trim().toLocaleLowerCase();
        const lista=estado.productos.filter(p=>[p.nombre,p.codigo_articulo_retail,p.codigo_barras].some(x=>String(x||'').toLocaleLowerCase().includes(busqueda)));
        const opciones=lista.slice(0,220).map(p=>({id:p.id_producto,nombre:labelProducto(p)}));
        llenarSelect($('ca24Producto'),opciones,'Seleccione un producto');
    }
    function renderItems() {
        const tbody=$('ca24Items');
        tbody.innerHTML=estado.items.length?estado.items.map(i=>`<tr><td>${esc(i.nombre)}</td><td><input data-cantidad="${i.id_producto}" type="number" min="0" step="1" value="${i.cantidad_fisica}" aria-label="Cantidad física de ${esc(i.nombre)}"></td><td><button class="ca24-btn" data-quitar="${i.id_producto}" type="button">Quitar</button></td></tr>`).join(''):'<tr><td colspan="3">Aún no hay productos agregados.</td></tr>';
        $('ca24TotalItems').textContent=`${estado.items.length} producto(s) incluido(s)`;
    }
    function agregar() {
        const id=enteros($('ca24Producto').value),cantidad=enteros($('ca24Cantidad').value,true);
        if(!id||cantidad===null)throw new Error('Seleccione producto e indique una cantidad física entera (puede ser cero).');
        const p=estado.productos.find(x=>Number(x.id_producto)===id);
        if(!p)throw new Error('Producto inválido.');
        if(estado.items.some(x=>x.id_producto===id))throw new Error('Este producto ya está en la tabla; edite su cantidad allí.');
        estado.items.push({id_producto:id,nombre:p.nombre,cantidad_fisica:cantidad});
        $('ca24Cantidad').value='';$('ca24Producto').value='';renderItems();
    }
    async function guardarConteo() {
        const ubicacion=enteros($('ca24Ubicacion').value);
        if(!ubicacion||!estado.items.length)throw new Error('Seleccione ubicación y agregue productos a la tabla.');
        const guardar=$('ca24Guardar');guardar.disabled=true;
        try {
            const res=await api('crear_conteo',{id_ubicacion:ubicacion,observaciones:$('ca24Observaciones').value.trim(),items:estado.items.map(i=>({id_producto:i.id_producto,cantidad_fisica:i.cantidad_fisica}))});
            estado.items=[];renderItems();$('ca24Observaciones').value='';
            aviso(`Conteo #${res.id_conteo} registrado. Las existencias no cambiaron.`);
            await actualizar();
        }finally {guardar.disabled=false;}
    }
    function vincularConteo(conteo,producto,objetivo,ubicacion) {
        estado.conteo=conteo;
        $('ca24AjusteUbicacion').value=String(ubicacion);
        $('ca24AjusteProducto').value=String(producto);
        $('ca24Objetivo').value=String(objetivo);
        $('ca24Motivo').value='DIFERENCIA_CONTEO';
        $('ca24ConteoVinculado').textContent=`Vinculado al conteo #${conteo}. Cantidad objetivo: ${objetivo}.`;
        $('ca24PanelAjuste').scrollIntoView({behavior:'smooth',block:'start'});
    }
    function desvincular() {estado.conteo=0;$('ca24ConteoVinculado').textContent='Ajuste manual (sin conteo vinculado).';}
    async function solicitarAjuste() {
        if(!estado.puedeSolicitar)throw new Error('Tu rol no puede solicitar ajustes.');
        const ub=enteros($('ca24AjusteUbicacion').value),prod=enteros($('ca24AjusteProducto').value),obj=enteros($('ca24Objetivo').value,true);
        const motivo=$('ca24Motivo').value;
        if(!ub||!prod||obj===null||!motivo)throw new Error('Complete ubicación, producto, cantidad objetivo y motivo.');
        const boton=$('ca24Solicitar');boton.disabled=true;
        try {
            const r=await api('solicitar_ajuste',{id_ubicacion:ub,id_producto:prod,cantidad_objetivo:obj,motivo,observaciones:$('ca24Justificacion').value.trim(),id_conteo:estado.conteo||null});
            aviso(`Ajuste #${r.id_movimiento} solicitado; pendiente de autorización.`);
            desvincular();$('ca24Objetivo').value='';$('ca24Justificacion').value='';$('ca24Motivo').value='';
            await actualizar();
        }finally {boton.disabled=false;}
    }
    async function resolver(id,decision) {
        if(!estado.puedeAprobar)throw new Error('Tu rol no puede autorizar ajustes.');
        const texto=decision==='APROBAR'?'APROBAR y actualizar existencias':'RECHAZAR sin modificar existencias';
        if(!window.confirm(`¿Desea ${texto} para el ajuste #${id}?`))return;
        const res=await api('resolver_ajuste',{id_movimiento:id,decision});
        aviso(`Ajuste #${id}: ${res.estado}.`);await actualizar();
    }
    function renderConteos(conteos) {
        const el=$('ca24HistorialConteos');
        if(!conteos.length){el.innerHTML='<p class="ca24-muted">No hay conteos registrados.</p>';return;}
        el.innerHTML=conteos.map(c=>{
            const filas=(c.detalles||[]).map(d=>{
                const delta=Number(d.diferencia);
                const accion=delta!==0&&!d.id_movimiento_ajuste&&estado.puedeSolicitar?`<button type="button" class="ca24-btn" data-conteo="${c.id_conteo}" data-ubicacion="${c.id_ubicacion}" data-producto="${d.id_producto}" data-objetivo="${d.cantidad_fisica}">Solicitar ajuste</button>`:(d.id_movimiento_ajuste?`Vinculado a ajuste #${esc(d.id_movimiento_ajuste)}`:'—');
                return `<tr><td>${esc(d.producto)}</td><td>${esc(d.stock_sistema)}</td><td>${esc(d.cantidad_fisica)}</td><td class="${delta<0?'ca24-negative':delta>0?'ca24-positive':''}">${delta>0?'+':''}${esc(delta)}</td><td>${accion}</td></tr>`;
            }).join('');
            return `<details class="ca24-detail"><summary>Conteo #${esc(c.id_conteo)} — ${esc(c.ubicacion)} · ${esc(c.estado)} <span class="ca24-muted">(${new Date(c.fecha_conteo).toLocaleString('es-CR')})</span></summary><p class="ca24-muted">${esc(c.observaciones||'Sin observaciones')}</p><div class="ca24-scroll"><table class="ca24-table"><thead><tr><th>Producto</th><th>Stock sistema</th><th>Conteo físico</th><th>Diferencia</th><th>Acción</th></tr></thead><tbody>${filas}</tbody></table></div></details>`;
        }).join('');
    }
    function renderAjustes(ajustes) {
        const el=$('ca24HistorialAjustes');
        if(!ajustes.length){el.innerHTML='<tr><td colspan="6">No hay ajustes registrados.</td></tr>';return;}
        el.innerHTML=ajustes.map(a=>{
            const pendiente=a.estado==='PENDIENTE';
            const botones=pendiente&&estado.puedeAprobar?`<button type="button" class="ca24-btn blue" data-aprobar="${a.id_movimiento}">Aprobar</button> <button type="button" class="ca24-btn" data-rechazar="${a.id_movimiento}">Rechazar</button>`:'—';
            return `<tr><td>#${esc(a.id_movimiento)}</td><td>${esc(a.producto)}<br><span class="ca24-muted">${esc(a.ubicacion)}</span></td><td>${esc(a.stock_anterior)} → ${esc(a.stock_objetivo)}</td><td>${esc(a.motivo)}<br><span class="ca24-muted">${esc(a.observaciones||'')}</span></td><td><span class="ca24-badge ${pendiente?'pending':'done'}">${esc(a.estado)}</span></td><td>${botones}</td></tr>`;
        }).join('');
    }
    async function actualizar() {
        const data=await api('listar');
        renderConteos(data.conteos||[]);renderAjustes(data.ajustes||[]);
    }
    async function iniciar() {
        try {
            const data=await api('opciones');
            estado.productos=data.productos||[];estado.ubicaciones=data.ubicaciones||[];
            estado.puedeSolicitar=Boolean(data.puede_solicitar);estado.puedeAprobar=Boolean(data.puede_aprobar);
            llenarUbicaciones();filtrarProductos();
            llenarSelect($('ca24AjusteProducto'),estado.productos.map(p=>({id:p.id_producto,nombre:labelProducto(p)})),'Seleccione producto');
            $('ca24Solicitar').disabled=!estado.puedeSolicitar;
            await actualizar();
        }catch(e){aviso(e.message,'error');}
    }
    $('ca24Buscar').addEventListener('input',()=>{filtrarProductos();$('ca24Producto').value='';});
    $('ca24Agregar').addEventListener('click',()=>{try{agregar();}catch(e){aviso(e.message,'error');}});
    $('ca24Items').addEventListener('input',e=>{const id=Number(e.target.dataset.cantidad);if(!id)return;const item=estado.items.find(x=>x.id_producto===id);if(item){const n=enteros(e.target.value,true);item.cantidad_fisica=n===null?-1:n;}});
    $('ca24Items').addEventListener('click',e=>{const btn=e.target.closest('[data-quitar]');if(!btn)return;estado.items=estado.items.filter(x=>x.id_producto!==Number(btn.dataset.quitar));renderItems();});
    $('ca24Limpiar').addEventListener('click',()=>{estado.items=[];renderItems();});
    $('ca24Guardar').addEventListener('click',async()=>{try{if(estado.items.some(x=>x.cantidad_fisica<0))throw new Error('Hay cantidades físicas inválidas.');await guardarConteo();}catch(e){aviso(e.message,'error');}});
    $('ca24HistorialConteos').addEventListener('click',e=>{const b=e.target.closest('[data-conteo]');if(b)vincularConteo(Number(b.dataset.conteo),Number(b.dataset.producto),Number(b.dataset.objetivo),Number(b.dataset.ubicacion));});
    $('ca24Solicitar').addEventListener('click',async()=>{try{await solicitarAjuste();}catch(e){aviso(e.message,'error');}});
    $('ca24Desvincular').addEventListener('click',desvincular);
    $('ca24Actualizar').addEventListener('click',async()=>{try{await actualizar();}catch(e){aviso(e.message,'error');}});
    $('ca24HistorialAjustes').addEventListener('click',async e=>{const a=e.target.closest('[data-aprobar]');const r=e.target.closest('[data-rechazar]');try{if(a)await resolver(Number(a.dataset.aprobar),'APROBAR');if(r)await resolver(Number(r.dataset.rechazar),'RECHAZAR');}catch(err){aviso(err.message,'error');}});
    $('ca24Ubicacion').addEventListener('change',()=>{estado.items=[];renderItems();});
    $('ca24AjusteUbicacion').addEventListener('change',desvincular);
    $('ca24AjusteProducto').addEventListener('change',desvincular);
    iniciar();
})();
