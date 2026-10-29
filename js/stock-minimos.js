/* HU-27 - Mínimos por ubicación. Independiente de inventario.js y movimientos.js. */
(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const state = { productos: [], ubicaciones: [], registros: [], seleccion: '', cargando: false };
    const endpoint = '../api/inventario/stock-minimos.php';
    const normalizar = s => String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const numero = x => Number(x || 0).toLocaleString('es-CR');
    const mostrar = (msg, tipo = '') => { const e = $('sm27Mensaje'); e.textContent = msg; e.className = 'sm27-msg show ' + tipo; };
    const limpiarMensaje = () => { $('sm27Mensaje').className = 'sm27-msg'; $('sm27Mensaje').textContent = ''; };

    async function api(accion, datos = null, parametros = {}) {
        const url = new URL(endpoint, location.href); url.searchParams.set('accion', accion);
        Object.entries(parametros).forEach(([k, v]) => { if (v !== undefined && v !== null) url.searchParams.set(k, String(v)); });
        const r = await fetch(url, { method: datos ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json', ...(datos ? { 'Content-Type': 'application/json' } : {}) }, ...(datos ? { body: JSON.stringify(datos) } : {}) });
        let res; try { res = await r.json(); } catch { throw Error('La API devolvió una respuesta inválida. Revisa la terminal PHP.'); }
        if (r.status === 401) { location.href = '../index/index.html'; throw Error('Inicia sesión.'); }
        if (!r.ok || !res.success) throw Error(res.message || 'No se pudo realizar la operación.');
        return res.data;
    }
    function pintarProductos(usarSeleccion = true) {
        const buscar = normalizar($('sm27Buscar').value);
        const encontrados = state.productos.filter(p => [p.nombre, p.codigo_articulo_retail, p.codigo_barras].some(v => normalizar(v).includes(buscar)));
        const select = $('sm27Producto');
        const sel = usarSeleccion ? state.seleccion : '';
        const f = document.createDocumentFragment();
        const op = document.createElement('option'); op.value = ''; op.textContent = 'Seleccione un producto'; f.appendChild(op);
        for (const p of encontrados) { const o = document.createElement('option'); o.value = String(p.id_producto); o.textContent = p.nombre + ' — ' + (p.codigo_articulo_retail || p.codigo_barras || 'Sin código'); f.appendChild(o); }
        select.replaceChildren(f);
        select.value = encontrados.some(p => String(p.id_producto) === sel) ? sel : '';
        state.seleccion = select.value;
        $('sm27Coincidencias').textContent = numero(encontrados.length) + ' producto(s) encontrado(s)';
        vistaPrevia();
    }
    function pintarUbicaciones() {
        const sel = $('sm27Ubicacion'); const frag = document.createDocumentFragment();
        const vacia = document.createElement('option'); vacia.value = ''; vacia.textContent = 'Seleccione una ubicación'; frag.appendChild(vacia);
        for (const u of state.ubicaciones) { const o = document.createElement('option'); o.value = String(u.id_ubicacion); o.textContent = u.tienda + ' — ' + u.nombre + ' (' + u.tipo + ')'; frag.appendChild(o); }
        sel.replaceChildren(frag);
        if (state.ubicaciones.length) sel.value = String(state.ubicaciones[0].id_ubicacion);
    }
    function vistaPrevia() {
        const id = Number(state.seleccion); const f = state.registros.find(r => r.id_producto === id);
        const out = $('sm27VistaPrevia');
        if (!id) { out.textContent = 'El mínimo cero desactiva las alertas para ese producto y ubicación.'; return; }
        const stock = f ? f.cantidad : 0;
        out.textContent = `Stock actual en la ubicación: ${numero(stock)} unidad(es). ${f ? 'Mínimo actual: ' + numero(f.stock_minimo) + '.' : 'Este producto aún no tiene existencia registrada aquí.'}`;
    }
    function editar(r) {
        const buscar = $('sm27Buscar'); buscar.value = '';
        state.seleccion = String(r.id_producto); pintarProductos();
        $('sm27Minimo').value = String(r.stock_minimo);
        window.scrollTo({ top: 0, behavior: 'smooth' });
        $('sm27Minimo').focus();
    }
    function dibujar() {
        const solo = $('sm27SoloAlertas').checked;
        const tbody = $('sm27Filas'); tbody.replaceChildren();
        const registros = state.registros.filter(r => !solo || r.estado !== 'NORMAL');
        $('sm27Total').textContent = numero(state.registros.length);
        $('sm27Alertas').textContent = numero(state.registros.filter(r => r.estado !== 'NORMAL').length);
        $('sm27Sugeridas').textContent = numero(state.registros.reduce((t, r) => t + r.sugerencia, 0));
        if (!registros.length) { const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 7; td.textContent = solo ? 'No hay alertas de stock mínimo para esta ubicación.' : 'No hay existencias registradas en esta ubicación.'; tr.appendChild(td); tbody.appendChild(tr); return; }
        for (const r of registros) {
            const tr = document.createElement('tr');
            const celda = valor => { const td = document.createElement('td'); td.textContent = String(valor); tr.appendChild(td); return td; };
            celda(r.producto); celda(r.codigo_retail || '—'); celda(numero(r.cantidad)); celda(numero(r.stock_minimo));
            const td = document.createElement('td'); const badge = document.createElement('span'); badge.className = 'sm27-badge' + (r.estado === 'AGOTADO' ? ' zero' : r.estado === 'STOCK_BAJO' ? ' low' : ''); badge.textContent = r.estado === 'AGOTADO' ? 'Agotado' : r.estado === 'STOCK_BAJO' ? 'Stock bajo' : 'Normal'; td.appendChild(badge); tr.appendChild(td);
            celda(numero(r.sugerencia));
            const acc = document.createElement('td');
            if (window.SM27_PUEDE_GUARDAR) { const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sm27-btn'; btn.textContent = 'Editar'; btn.addEventListener('click', () => editar(r)); acc.appendChild(btn); } else acc.textContent = '—';
            tr.appendChild(acc); tbody.appendChild(tr);
        }
    }
    async function registros() {
        const ubicacion = $('sm27Ubicacion').value;
        state.registros = []; dibujar(); vistaPrevia();
        if (!ubicacion) return;
        const data = await api('registros', null, { id_ubicacion: ubicacion });
        // No confiamos en una consulta anterior si el usuario cambió ubicación.
        if ($('sm27Ubicacion').value !== ubicacion) return;
        state.registros = data.registros || [];
        dibujar(); vistaPrevia();
    }
    async function guardar() {
        if (state.cargando) return;
        const ubicacion = $('sm27Ubicacion').value, producto = $('sm27Producto').value;
        const raw = $('sm27Minimo').value.trim();
        if (!ubicacion || !producto || !/^\d+$/.test(raw) || Number(raw) > 1000000) { mostrar('Seleccione ubicación, producto y un mínimo entero entre 0 y 1000000.', 'error'); return; }
        state.cargando = true; $('sm27Guardar').disabled = true;
        try {
            await api('guardar', { id_ubicacion: Number(ubicacion), id_producto: Number(producto), stock_minimo: Number(raw) });
            await registros(); mostrar('Stock mínimo guardado. Las alertas y sugerencias están actualizadas.', 'ok');
        } catch (e) { mostrar(e.message, 'error'); }
        finally { state.cargando = false; $('sm27Guardar').disabled = !window.SM27_PUEDE_GUARDAR; }
    }
    async function inicializar() {
        try {
            const data = await api('opciones'); state.productos = data.productos || []; state.ubicaciones = data.ubicaciones || [];
            pintarUbicaciones(); pintarProductos(); await registros();
        } catch (e) { mostrar('No se pudieron cargar los datos: ' + e.message, 'error'); }
    }
    $('sm27Buscar').addEventListener('input', () => { state.seleccion = ''; pintarProductos(false); });
    $('sm27Producto').addEventListener('change', e => { state.seleccion = e.target.value; const r = state.registros.find(x => String(x.id_producto) === state.seleccion); $('sm27Minimo').value = r ? String(r.stock_minimo) : ''; vistaPrevia(); });
    $('sm27Ubicacion').addEventListener('change', async () => { limpiarMensaje(); try { await registros(); } catch (e) { mostrar(e.message, 'error'); } });
    $('sm27SoloAlertas').addEventListener('change', dibujar);
    $('sm27Actualizar').addEventListener('click', async () => { try { await registros(); mostrar('Existencias y alertas actualizadas.', 'ok'); } catch (e) { mostrar(e.message, 'error'); } });
    $('sm27Guardar').addEventListener('click', guardar);
    inicializar();
})();
