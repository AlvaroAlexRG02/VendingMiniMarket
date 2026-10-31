/*
 * Dashboard — HU-48.
 *
 * Consume api/dashboard/dashboard.php. La API entrega la vista consolidada
 * para Administrador y Gerente, y solo la ubicación autorizada para el
 * personal operativo; aquí solo se dibuja lo que la API devuelve.
 */
(function () {
    'use strict';

    const API = '../api/dashboard/dashboard.php';
    const $ = (id) => document.getElementById(id);

    let secuencia = 0;

    document.addEventListener('DOMContentLoaded', iniciar);

    const { escapar, moneda, monedaCorta, entero, fechaLocal, fechaCorta, barras, columnas } = window.VMComun;

    function texto(id, valor) {
        const elemento = $(id);
        if (elemento) elemento.textContent = valor;
    }

    async function consultar() {
        const consulta = new URLSearchParams({ accion: 'resumen', fecha: $('fechaDashboard').value || fechaLocal() });
        const idTienda = $('filtroTiendaDashboard').value;
        if (idTienda) consulta.set('id_tienda', idTienda);

        const respuesta = await fetch(`${API}?${consulta}`, { credentials: 'same-origin' });
        const json = await respuesta.json().catch(() => null);

        if (!respuesta.ok || !json || !json.success) {
            throw new Error((json && json.message) || 'No se pudo cargar el dashboard.');
        }

        return json.data;
    }

    function llenarTiendas(datos) {
        const select = $('filtroTiendaDashboard');
        if (select.dataset.listo) return;

        select.innerHTML = (datos.puede_elegir_tienda ? '<option value="">Todas las tiendas</option>' : '') +
            datos.tiendas.map((t) => `<option value="${t.id_tienda}">${escapar(t.nombre)}</option>`).join('');
        select.disabled = !datos.puede_elegir_tienda;
        select.dataset.listo = '1';
    }

    function dibujarTendencia(datos) {
        const contenedor = $('tendenciaVentasDashboard');
        if (!contenedor) return;

        contenedor.innerHTML = columnas(
            datos.tendencia.map((d) => ({ etiqueta: fechaCorta(d.fecha), valor: d.ingresos, texto: monedaCorta(d.ingresos) })),
            { color: 'color-verde' }
        );
    }

    function dibujarVentasPorTienda(datos) {
        const panel = $('panelVentasTiendaDashboard');
        if (!panel) return;

        const mostrar = datos.consolidado && datos.por_tienda.length > 1 && !$('filtroTiendaDashboard').value;
        panel.hidden = !mostrar;
        if (!mostrar) return;

        $('barrasVentasTiendaDashboard').innerHTML = barras(
            datos.por_tienda.map((t) => ({ etiqueta: t.tienda, valor: t.total, texto: `${moneda(t.total)} · ${entero(t.ventas)} venta(s)` })),
            'verde'
        );
    }

    function dibujarMovimientos(movimientos) {
        const cuerpo = $('tablaMovimientosDashboard');
        if (!cuerpo) return;

        if (!movimientos.ultimos.length) {
            cuerpo.innerHTML = `
                <tr><td colspan="5">
                    <div class="estado-vacio-tabla">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        <strong>No hay movimientos en este día</strong>
                        <span>Los movimientos del día seleccionado aparecerán aquí.</span>
                    </div>
                </td></tr>`;
            return;
        }

        cuerpo.innerHTML = movimientos.ultimos.map((m) => `
            <tr>
                <td>${escapar(new Date(m.fecha).toLocaleString('es-CR'))}</td>
                <td>${escapar(m.tipo)}${m.estado === 'PENDIENTE' ? ' <span class="badge badge-naranja">Pendiente</span>' : ''}</td>
                <td>${escapar(m.descripcion)}</td>
                <td>${escapar(m.referencia)}</td>
                <td>${escapar(m.usuario)}</td>
            </tr>`).join('');
    }

    function dibujar(datos) {
        const v = datos.ventas;

        texto('totalVentasDashboard', moneda(v.total));
        texto('detalleVentasDashboard', `${entero(v.unidades)} unidad(es) · ${entero(v.ventas)} venta(s) confirmada(s)`);

        if (datos.utilidad) {
            texto('utilidadDashboard', moneda(datos.utilidad.valor));
            texto('detalleUtilidadDashboard', `Margen ${datos.utilidad.margen.toLocaleString('es-CR')}% sobre ventas sin IVA`);
        }

        const inv = datos.inventario;
        texto('stockCriticoDashboard', entero(inv.criticos));
        texto('detalleStockDashboard', `${entero(inv.agotados)} agotado(s) · ${entero(inv.bajo_minimo)} bajo mínimo${inv.negativos ? ` · ${entero(inv.negativos)} negativo(s)` : ''}`);

        texto('totalMovimientosDashboard', entero(datos.movimientos.total));

        dibujarTendencia(datos);
        dibujarVentasPorTienda(datos);
        dibujarMovimientos(datos.movimientos);
    }

    async function cargar() {
        const numero = ++secuencia;

        try {
            const datos = await consultar();
            if (numero !== secuencia) return;
            llenarTiendas(datos);
            dibujar(datos);
        } catch (error) {
            if (numero !== secuencia) return;
            texto('mensajeBienvenidaDashboard', error.message);
        }
    }

    async function iniciar() {
        if (!$('totalVentasDashboard')) return;

        const fecha = $('fechaDashboard');
        if (!fecha.value) fecha.value = fechaLocal();
        fecha.max = fechaLocal();

        // La primera carga también llena el selector de tiendas.
        await cargar();

        fecha.addEventListener('change', cargar);
        $('filtroTiendaDashboard').addEventListener('change', cargar);
    }
})();
