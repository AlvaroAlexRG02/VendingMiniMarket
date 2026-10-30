/* VendingMiniMarket — HU-26, mermas y bajas. Independiente de movimientos.js. */
(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const estado = { productos: [], ubicaciones: [], stock: new Map(), seleccionado: '', items: new Map(), guardando: false };
    const API = '../api/inventario/mermas.php';
    const normalizar = s => String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const entero = v => /^\d+$/.test(String(v).trim()) ? Number(v) : NaN;
    const escape = v => String(v ?? '');
    let avisoTimeout;

    function aviso(mensaje, tipo = 'info') {
        const el = $('bajaAviso');
        clearTimeout(avisoTimeout);
        el.textContent = mensaje;
        el.className = 'baja-alert visible' + (tipo === 'error' ? ' error' : tipo === 'ok' ? ' ok' : '');
        avisoTimeout = setTimeout(() => el.classList.remove('visible'), tipo === 'error' ? 5500 : 3200);
    }
    async function api(accion, payload) {
        const url = API + '?accion=' + encodeURIComponent(accion);
        const esFormulario = typeof FormData !== 'undefined' && payload instanceof FormData;
        let resp;
        try {
            resp = await fetch(url, {
                method: payload ? 'POST' : 'GET',
                credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json', ...(payload && !esFormulario ? { 'Content-Type': 'application/json' } : {}) },
                ...(payload ? { body: esFormulario ? payload : JSON.stringify(payload) } : {})
            });
        } catch (_) { throw new Error('No fue posible conectar con el servidor.'); }
        let data;
        try { data = await resp.json(); } catch (_) { throw new Error('El servidor devolvió una respuesta no válida.'); }
        if (resp.status === 401) { window.location.href = '../index/index.html'; throw new Error('Inicia sesión nuevamente.'); }
        if (!resp.ok || !data.success) throw new Error(data.message || 'No fue posible completar la operación.');
        return data.data;
    }
    function stock(idProducto) {
        return estado.stock.get($('bajaUbicacion').value + ':' + String(idProducto)) ?? 0;
    }
    function textoProducto(p) {
        return `${p.nombre} — ${p.codigo_articulo_retail || p.codigo_barras || 'Sin código'}`;
    }
    function cerrarOpciones() {
        $('bajaOpciones').hidden = true;
        $('bajaBuscar').setAttribute('aria-expanded', 'false');
    }
    function opciones() {
        const q = normalizar($('bajaBuscar').value);
        const todos = estado.productos.filter(p => {
            return !q || [p.nombre, p.codigo_articulo_retail, p.codigo_barras].some(c => normalizar(c).includes(q));
        });
        $('bajaCoincidencias').textContent = `${todos.length} producto(s) encontrado(s).`;
        const cont = $('bajaOpciones');
        cont.replaceChildren();
        if (!todos.length) {
            const p = document.createElement('div');
            p.textContent = 'No se encontraron productos.';
            p.style.padding = '9px';
            cont.appendChild(p);
        } else {
            for (const p of todos.slice(0, 100)) {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = textoProducto(p);
                b.dataset.id = String(p.id_producto);
                cont.appendChild(b);
            }
            if (todos.length > 100) {
                const hint = document.createElement('div');
                hint.textContent = 'Escribe más letras para reducir la búsqueda.';
                hint.style.padding = '9px';
                cont.appendChild(hint);
            }
        }
        cont.hidden = false;
        $('bajaBuscar').setAttribute('aria-expanded', 'true');
    }
    function elegido() {
        const p = estado.productos.find(p => String(p.id_producto) === estado.seleccionado);
        $('bajaElegido').textContent = p ? `Producto: ${textoProducto(p)}` : 'Producto: sin seleccionar';
        $('bajaDisponible').textContent = p && $('bajaUbicacion').value ? stock(p.id_producto) + ' unidad(es)' : 'Selecciona ubicación y producto';
    }
    function renderTabla() {
        const body = $('bajaItems');
        body.replaceChildren();
        let cantidadTotal = 0;
        let valido = true;
        if (!estado.items.size) {
            const tr = document.createElement('tr'), td = document.createElement('td');
            td.colSpan = 5; td.textContent = 'No hay productos agregados.'; tr.appendChild(td); body.appendChild(tr);
        }
        for (const [id, item] of estado.items) {
            const actual = stock(id), cantidad = entero(item.cantidad);
            const ok = Number.isSafeInteger(cantidad) && cantidad > 0 && cantidad <= actual;
            if (!ok) valido = false;
            else cantidadTotal += cantidad;
            const tr = document.createElement('tr');
            const celda = valor => { const td = document.createElement('td'); td.textContent = String(valor); tr.appendChild(td); return td; };
            celda(item.nombre); celda(actual);
            const td = document.createElement('td');
            const input = document.createElement('input');
            input.type = 'number'; input.min = '1'; input.step = '1'; input.value = String(item.cantidad);
            input.dataset.id = id; input.className = 'baja-cantidad-item'; input.setAttribute('aria-label', `Cantidad a retirar de ${item.nombre}`);
            if (!ok) input.style.borderColor = '#dd4444';
            td.appendChild(input); tr.appendChild(td);
            celda(ok ? actual - cantidad : '—');
            const accion = document.createElement('td');
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'baja-btn baja-quitar'; button.textContent = 'Quitar'; button.dataset.id = id;
            accion.appendChild(button); tr.appendChild(accion); body.appendChild(tr);
        }
        $('bajaResumen').textContent = !estado.items.size ? 'Agrega un producto para visualizar la baja.'
            : `${estado.items.size} producto(s) | ${cantidadTotal} unidad(es) a retirar | ${valido ? 'Stock suficiente' : 'Revisa las cantidades: stock insuficiente o valor inválido.'}`;
        $('bajaResumen').style.borderColor = valido ? '#cce0ff' : '#d54040';
    }
    function limpiar() {
        estado.items.clear(); estado.seleccionado = '';
        ['bajaBuscar', 'bajaCantidad', 'bajaObservaciones', 'bajaEvidencia'].forEach(id => $(id).value = '');
        ['bajaUbicacion', 'bajaMotivo'].forEach(id => $(id).value = '');
        cerrarOpciones(); elegido(); renderTabla();
    }
    async function cargar() {
        const data = await api('datos');
        estado.productos = Array.isArray(data.productos) ? data.productos : [];
        estado.ubicaciones = Array.isArray(data.ubicaciones) ? data.ubicaciones : [];
        estado.stock.clear();
        for (const r of data.existencias || []) estado.stock.set(r.id_ubicacion + ':' + r.id_producto, Number(r.cantidad));
        const anterior = $('bajaUbicacion').value;
        const sel = $('bajaUbicacion');
        sel.replaceChildren();
        const placeholder = document.createElement('option'); placeholder.value = ''; placeholder.textContent = 'Seleccione una ubicación'; sel.appendChild(placeholder);
        for (const u of estado.ubicaciones) {
            const op = document.createElement('option');
            op.value = String(u.id_ubicacion); op.textContent = `${u.tienda} — ${u.nombre} (${u.tipo})`; sel.appendChild(op);
        }
        if (Array.from(sel.options).some(o => o.value === anterior)) sel.value = anterior;
        elegido(); renderTabla();
        $('bajaCoincidencias').textContent = `${estado.productos.length} producto(s) disponibles.`;
    }
    async function actualizarHistorial() {
        const rows = await api('historial');
        const body = $('bajaHistorial'); body.replaceChildren();
        if (!rows.length) {
            const tr = document.createElement('tr'), td = document.createElement('td');
            td.colSpan = 8; td.textContent = 'No hay bajas registradas.'; tr.appendChild(td); body.appendChild(tr); return;
        }
        for (const r of rows) {
            const tr = document.createElement('tr');
            const values = [
                r.fecha_movimiento ? new Date(r.fecha_movimiento).toLocaleString('es-CR') : '—',
                r.producto, r.ubicacion, r.motivo, r.cantidad,
                `${r.saldo_origen_anterior ?? '—'} → ${r.saldo_origen_resultante ?? '—'}`, r.responsable || '—'
            ];
            for (const val of values) { const td = document.createElement('td'); td.textContent = escape(val); tr.appendChild(td); }
            const evidencia = document.createElement('td');
            const ref = String(r.evidencia_url || '');
            let enlace = '';
            if (/^merma:[a-f0-9]{32}\.(jpg|png|pdf)$/.test(ref)) {
                enlace = `${API}?accion=evidencia&id_movimiento=${encodeURIComponent(r.id_movimiento)}`;
            } else if (/^https?:\/\//i.test(ref)) {
                // Compatibilidad con las bajas antiguas que guardaban una URL.
                enlace = ref;
            }
            if (enlace) {
                const link = document.createElement('a');
                link.href = enlace;
                link.textContent = 'Ver archivo';
                if (/^https?:\/\//i.test(ref)) { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
                evidencia.appendChild(link);
            } else {
                evidencia.textContent = '—';
            }
            tr.appendChild(evidencia);
            body.appendChild(tr);
        }
    }
    function datosARegistrar() {
        const ubi = entero($('bajaUbicacion').value), motivo = $('bajaMotivo').value;
        if (!Number.isSafeInteger(ubi) || ubi < 1) throw new Error('Selecciona la ubicación de origen.');
        if (!motivo) throw new Error('Selecciona un motivo de baja.');
        if (!estado.items.size) throw new Error('Agrega al menos un producto.');
        const items = [];
        for (const [id, item] of estado.items) {
            const cantidad = entero(item.cantidad);
            if (!Number.isSafeInteger(cantidad) || cantidad < 1 || cantidad > stock(id)) throw new Error(`Revisa la cantidad de ${item.nombre}: stock disponible ${stock(id)}.`);
            items.push({ id_producto: Number(id), cantidad });
        }
        comprobarEvidencia();
        return { id_ubicacion: ubi, motivo, observaciones: $('bajaObservaciones').value.trim(), items };
    }
    function comprobarEvidencia() {
        const archivo = $('bajaEvidencia').files?.[0];
        if (!archivo) return null;
        if (!/\.(jpe?g|png|pdf)$/i.test(archivo.name)) throw new Error('Evidencia: selecciona JPG, PNG o PDF.');
        if (archivo.size < 1 || archivo.size > 2 * 1024 * 1024) throw new Error('La evidencia debe pesar 2 MB o menos.');
        return archivo;
    }
    function confirmar() {
        try {
            const payload = datosARegistrar();
            const cantidad = payload.items.reduce((total, item) => total + item.cantidad, 0);
            const texto = Array.from($('bajaUbicacion').selectedOptions)[0]?.textContent || 'ubicación seleccionada';
            $('bajaConfirmarTexto').textContent = `Se retirarán ${cantidad} unidad(es) de ${payload.items.length} producto(s) en ${texto}. Motivo: ${payload.motivo}. ¿Confirmar?`;
            const modal = $('bajaConfirmar');
            modal.showModal();
        } catch (err) { aviso(err.message, 'error'); }
    }
    async function registrar() {
        if (estado.guardando) return;
        let payload;
        try { payload = datosARegistrar(); } catch (err) { $('bajaConfirmar').close(); aviso(err.message, 'error'); return; }
        estado.guardando = true;
        $('bajaAceptar').disabled = true;
        $('bajaGuardar').disabled = true;
        try {
            const form = new FormData();
            form.append('datos', JSON.stringify(payload));
            const evidencia = comprobarEvidencia();
            if (evidencia) form.append('evidencia', evidencia, evidencia.name);
            const res = await api('registrar', form);
            $('bajaConfirmar').close();
            limpiar();
            await cargar();
            await actualizarHistorial();
            aviso(`Movimiento #${res.id_movimiento} registrado: ${res.unidades} unidad(es) descontadas.`, 'ok');
        } catch (err) { $('bajaConfirmar').close(); aviso(err.message, 'error'); }
        finally { estado.guardando = false; $('bajaAceptar').disabled = false; $('bajaGuardar').disabled = false; }
    }
    function iniciarEventos() {
        $('bajaBuscar').addEventListener('input', () => { estado.seleccionado = ''; elegido(); opciones(); });
        $('bajaBuscar').addEventListener('focus', opciones);
        $('bajaOpciones').addEventListener('click', event => {
            const btn = event.target.closest('button[data-id]'); if (!btn) return;
            estado.seleccionado = btn.dataset.id;
            const p = estado.productos.find(p => String(p.id_producto) === estado.seleccionado);
            $('bajaBuscar').value = p ? textoProducto(p) : '';
            cerrarOpciones(); elegido();
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('.baja-search-menu')) cerrarOpciones();
        });
        $('bajaUbicacion').addEventListener('change', () => { estado.items.clear(); renderTabla(); elegido(); });
        $('bajaAgregar').addEventListener('click', () => {
            const id = estado.seleccionado, cantidad = entero($('bajaCantidad').value);
            const p = estado.productos.find(p => String(p.id_producto) === id);
            if (!p) { aviso('Selecciona un producto de la lista.', 'error'); return; }
            if (!String($('bajaUbicacion').value)) { aviso('Selecciona una ubicación.', 'error'); return; }
            if (!Number.isSafeInteger(cantidad) || cantidad < 1 || cantidad > stock(id)) { aviso(`Cantidad inválida. Disponible: ${stock(id)} unidad(es).`, 'error'); return; }
            if (estado.items.has(id)) { aviso('El producto ya está agregado. Modifica su cantidad en la tabla.', 'error'); return; }
            estado.items.set(id, { nombre: p.nombre, cantidad });
            $('bajaBuscar').value = ''; $('bajaCantidad').value = ''; estado.seleccionado = '';
            cerrarOpciones(); elegido(); renderTabla();
        });
        $('bajaItems').addEventListener('click', event => {
            const btn = event.target.closest('button.baja-quitar'); if (!btn) return;
            estado.items.delete(btn.dataset.id); renderTabla();
        });
        $('bajaItems').addEventListener('input', event => {
            const input = event.target.closest('input.baja-cantidad-item'); if (!input) return;
            const item = estado.items.get(input.dataset.id); if (!item) return;
            item.cantidad = input.value;
            const actual = stock(input.dataset.id), cantidad = entero(input.value);
            const valido = Number.isSafeInteger(cantidad) && cantidad > 0 && cantidad <= actual;
            input.style.borderColor = valido ? '#cbd7e7' : '#d54040';
            input.closest('tr').cells[3].textContent = valido ? String(actual - cantidad) : '—';
            const total = Array.from(estado.items.values()).reduce((sum, it) => sum + (Number.isSafeInteger(entero(it.cantidad)) ? entero(it.cantidad) : 0), 0);
            $('bajaResumen').textContent = `${estado.items.size} producto(s) | ${total} unidad(es) solicitadas${valido ? '' : ' | Revisa la cantidad.'}`;
        });
        $('bajaLimpiar').addEventListener('click', () => { limpiar(); aviso('Formulario limpio.'); });
        $('bajaGuardar').addEventListener('click', confirmar);
        $('bajaCancelar').addEventListener('click', () => $('bajaConfirmar').close());
        $('bajaAceptar').addEventListener('click', registrar);
        $('bajaActualizar').addEventListener('click', async () => {
            try { await actualizarHistorial(); await cargar(); aviso('Información actualizada.'); }
            catch (err) { aviso(err.message, 'error'); }
        });
    }
    document.addEventListener('DOMContentLoaded', async () => {
        if (!$('bajaGuardar')) return;
        iniciarEventos();
        try { await cargar(); await actualizarHistorial(); }
        catch (err) { aviso(err.message, 'error'); }
    });
})();
