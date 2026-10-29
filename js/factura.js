/*=========================================================
  VENDING MINI MARKET — Facturas de proveedor (compras/facturas.php)
  HU-33 · registrar facturas de proveedor
  Requiere: pedido-api.js y factura-api.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const { api: apiPedidos } = window.PedidosApi;
    const { api: apiFacturas } = window.FacturasApi;
    const $ = (id) => document.getElementById(id);

    const ETIQUETAS_ESTADO = {
        PENDIENTE: 'Pendiente',
        EN_REVISION: 'En revisión',
        APROBADA: 'Aprobada',
        RECHAZADA: 'Rechazada',
        ANULADA: 'Anulada'
    };

    const COLOR_ESTADO = {
        PENDIENTE: 'naranja',
        EN_REVISION: 'morado',
        APROBADA: 'verde',
        RECHAZADA: 'rojo',
        ANULADA: 'gris'
    };

    let facturas = [];
    let pedidos = [];
    let productosProveedorActual = [];
    let productosProveedorDetalle = [];
    let facturaDetalleActual = null;
    let puedeRevisarFactura = false;
    let puedeValidarFactura = false;
    let puedeAnularFactura = false;
    let puedeExportarHistorial = false;
    let idUsuarioActual = 0;

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaFacturas')) {
            return;
        }

        puedeRevisarFactura = document.body.dataset.puedeRevisarFactura === '1';
        puedeValidarFactura = document.body.dataset.puedeValidarFactura === '1';
        puedeAnularFactura = document.body.dataset.puedeAnularFactura === '1';
        puedeExportarHistorial = document.body.dataset.puedeExportarHistorial === '1';
        idUsuarioActual = Number(document.body.dataset.idUsuarioActual || 0);

        registrarEventos();

        try {
            await Promise.all([cargarProveedores(), cargarUbicaciones(), cargarImpuestos(), cargarPedidos()]);
            await cargarFacturas();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function registrarEventos() {
        $('botonNuevaFactura')?.addEventListener('click', abrirModalNuevaFactura);
        $('botonPrimeraFactura')?.addEventListener('click', abrirModalNuevaFactura);
        $('botonCerrarModalFactura')?.addEventListener('click', cerrarModal);
        $('botonCancelarFactura')?.addEventListener('click', cerrarModal);
        document.querySelector('[data-cerrar-modal-factura]')?.addEventListener('click', cerrarModal);

        $('formularioFactura')?.addEventListener('submit', guardarFactura);
        $('proveedorFactura')?.addEventListener('change', cargarProductosDelProveedor);
        $('pedidoRelacionadoFactura')?.addEventListener('change', sincronizarConPedido);
        $('botonAgregarProductoFactura')?.addEventListener('click', () => agregarFilaProducto());

        $('botonCerrarModalDetalleFactura')?.addEventListener('click', cerrarModalDetalle);
        $('botonCerrarDetalleFactura')?.addEventListener('click', cerrarModalDetalle);
        document.querySelector('[data-cerrar-modal-detalle-factura]')?.addEventListener('click', cerrarModalDetalle);
        $('botonAgregarProductoDetalle')?.addEventListener('click', () => agregarFilaProductoDetalle());
        $('botonGuardarDetalleFactura')?.addEventListener('click', guardarCambiosDetalle);
        $('botonEnviarRevisionDetalle')?.addEventListener('click', enviarRevisionDesdeDetalle);
        $('botonAprobarFacturaDetalle')?.addEventListener('click', aprobarFacturaDetalle);
        $('botonRechazarFacturaDetalle')?.addEventListener('click', rechazarFacturaDetalle);
        $('botonAnularFacturaDetalle')?.addEventListener('click', anularFacturaDetalle);
        $('botonExportarHistorial')?.addEventListener('click', exportarHistorial);
        $('impuestoFacturaDetalle')?.addEventListener('change', calcularTotalesDetalle);

        ['buscarFactura', 'filtroProveedorFactura', 'filtroTiendaFactura', 'filtroEstadoFactura', 'filtroFechaFactura']
            .forEach((id) => {
                $(id)?.addEventListener(id === 'buscarFactura' ? 'input' : 'change', () => cargarFacturas());
            });

        $('botonLimpiarFiltrosFacturas')?.addEventListener('click', () => {
            ['buscarFactura', 'filtroProveedorFactura', 'filtroTiendaFactura', 'filtroEstadoFactura', 'filtroFechaFactura']
                .forEach((id) => { const e = $(id); if (e) e.value = ''; });
            cargarFacturas();
        });

        $('cuerpoTablaFacturas')?.addEventListener('click', manejarClicTabla);
    }

    function mostrarMensaje(texto, tipo = 'info') {
        const el = $('mensajeFacturas');
        if (!el) return;
        el.textContent = texto;
        el.className = `mensaje-producto mensaje-${tipo}`;
        el.hidden = false;
    }

    function moneda(valor) {
        return '₡' + Number(valor || 0).toLocaleString('es-CR', { maximumFractionDigits: 2 });
    }

    function escaparHTML(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function mostrarElemento(id, visible) {
        const el = $(id);
        if (el) el.hidden = !visible;
    }


    /*-----------------------------------------------------
      CATÁLOGOS DE APOYO
    -----------------------------------------------------*/

    async function cargarProveedores() {
        const resultado = await apiPedidos('listar_proveedores');
        const datos = resultado.data || [];

        [$('proveedorFactura'), $('filtroProveedorFactura')].forEach((select, indice) => {
            if (!select) return;
            const vacio = indice === 0 ? 'Seleccione un proveedor' : 'Todos los proveedores';
            select.innerHTML = `<option value="">${vacio}</option>` +
                datos.map((p) => `<option value="${p.id_proveedor}">${escaparHTML(p.nombre)}</option>`).join('');
        });
    }

    async function cargarUbicaciones() {
        const resultado = await apiPedidos('listar_ubicaciones');
        const datos = resultado.data || [];

        [$('ubicacionFactura'), $('filtroTiendaFactura')].forEach((select, indice) => {
            if (!select) return;
            const vacio = indice === 0 ? 'Seleccione una tienda' : 'Todas las tiendas';
            select.innerHTML = `<option value="">${vacio}</option>` +
                datos.map((u) => `<option value="${u.id_ubicacion}">${escaparHTML(u.nombre)}</option>`).join('');
        });
    }

    async function cargarImpuestos() {
        const respuesta = await fetch('../api/catalogo/catalogo.php?accion=listar_impuestos', { credentials: 'same-origin' });
        const resultado = await respuesta.json();
        const datos = (resultado.data || []).filter((i) => i.estado);

        [$('impuestoFactura'), $('impuestoFacturaDetalle')].forEach((select) => {
            if (!select) return;
            select.innerHTML = '<option value="">Seleccione un impuesto</option>' +
                datos.map((i) => `<option value="${i.id_impuesto}" data-porcentaje="${i.porcentaje}">${escaparHTML(i.nombre)} (${i.porcentaje}%)</option>`).join('');
        });
    }

    async function cargarPedidos() {
        const resultado = await apiFacturas('listar_pedidos_disponibles');
        pedidos = resultado.data || [];

        const select = $('pedidoRelacionadoFactura');
        if (select) {
            select.innerHTML = '<option value="">Sin pedido relacionado</option>' +
                pedidos.map((p) => `<option value="${p.id_pedido}">${p.numero_pedido} - ${escaparHTML(p.proveedor_nombre)} (${new Date(p.fecha_pedido).toLocaleDateString('es-CR')})</option>`).join('');
        }
    }

    async function sincronizarConPedido() {
        const idPedido = $('pedidoRelacionadoFactura').value;

        if (!idPedido) {
            $('proveedorFactura').disabled = false;
            $('ubicacionFactura').disabled = false;
            return;
        }

        const pedido = pedidos.find((p) => String(p.id_pedido) === idPedido);
        if (!pedido) return;

        $('proveedorFactura').value = pedido.id_proveedor;
        $('ubicacionFactura').value = pedido.id_ubicacion;
        $('proveedorFactura').disabled = true;
        $('ubicacionFactura').disabled = true;

        await cargarProductosDelProveedor(false);

        try {
            const resultado = await apiPedidos('obtener_pedido', null, { id_pedido: idPedido });
            const detallePedido = resultado.data?.detalle || [];

            $('listaProductosFactura').innerHTML = '';

            if (detallePedido.length) {
                detallePedido.forEach((item) => agregarFilaProducto({
                    id_producto: item.id_producto,
                    cantidad: item.cantidad_recibida > 0 ? item.cantidad_recibida : item.cantidad_solicitada,
                    costo_unitario: item.costo_unitario
                }));
                mostrarElemento('mensajeSinProductosFactura', false);
            } else {
                agregarFilaProducto();
            }

            calcularTotales();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    async function cargarProductosDelProveedor(agregarFilaVacia = true) {
        const idProveedor = $('proveedorFactura').value;
        $('listaProductosFactura').innerHTML = '';
        productosProveedorActual = [];

        const boton = $('botonAgregarProductoFactura');

        if (!idProveedor) {
            boton.disabled = true;
            mostrarElemento('mensajeSinProductosFactura', true);
            return;
        }

        try {
            const resultado = await apiPedidos('listar_productos_proveedor', null, { id_proveedor: idProveedor });
            productosProveedorActual = resultado.data || [];
            boton.disabled = productosProveedorActual.length === 0;

            if (productosProveedorActual.length === 0) {
                mostrarMensaje('Este proveedor no tiene productos asociados.', 'error');
            } else if (agregarFilaVacia) {
                agregarFilaProducto();
            }
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      FILAS DE PRODUCTO DEL FORMULARIO
    -----------------------------------------------------*/

    function opcionesProducto(idSeleccionado) {
        return productosProveedorActual.map((p) => `
            <option value="${p.id_producto}" data-costo="${p.costo || 0}" ${String(p.id_producto) === String(idSeleccionado) ? 'selected' : ''}>
                ${escaparHTML(p.nombre)}
            </option>
        `).join('');
    }

    function agregarFilaProducto(item = null) {
        const lista = $('listaProductosFactura');

        const fila = document.createElement('div');
        fila.className = 'fila-producto-compra';
        fila.innerHTML = `
            <div class="grupo-formulario">
                <label>Producto</label>
                <select class="item-producto" required>
                    <option value="">Seleccione...</option>
                    ${opcionesProducto(item ? item.id_producto : '')}
                </select>
            </div>
            <div class="grupo-formulario">
                <label>Cantidad</label>
                <input class="item-cantidad" type="number" min="1" value="1" autocomplete="off" required>
            </div>
            <div class="grupo-formulario">
                <label>Costo unitario</label>
                <input class="item-costo" type="number" min="0" step="0.01" value="0" autocomplete="off" required>
            </div>
            <div class="grupo-formulario">
                <label>Subtotal</label>
                <input class="item-subtotal" type="text" readonly value="₡0">
            </div>
            <button type="button" class="boton-eliminar-producto-compra" aria-label="Quitar producto">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        fila.querySelector('.item-producto').addEventListener('change', (e) => {
            const opcion = e.target.selectedOptions[0];
            fila.querySelector('.item-costo').value = opcion ? Number(opcion.dataset.costo || 0) : 0;
            actualizarFila(fila);
        });
        fila.querySelector('.item-cantidad').addEventListener('input', () => actualizarFila(fila));
        fila.querySelector('.item-costo').addEventListener('input', () => actualizarFila(fila));
        fila.querySelector('button').addEventListener('click', () => {
            fila.remove();
            mostrarElemento('mensajeSinProductosFactura', !lista.children.length);
            calcularTotales();
        });

        lista.appendChild(fila);

        if (item) {
            fila.querySelector('.item-producto').value = item.id_producto;
            fila.querySelector('.item-cantidad').value = item.cantidad;
            fila.querySelector('.item-costo').value = item.costo_unitario;
        } else {
            fila.querySelector('.item-producto').value = '';
            fila.querySelector('.item-cantidad').value = 1;
            fila.querySelector('.item-costo').value = 0;
        }

        mostrarElemento('mensajeSinProductosFactura', false);
        actualizarFila(fila);
    }

    function actualizarFila(fila) {
        const cantidad = Number(fila.querySelector('.item-cantidad').value) || 0;
        const costo = Number(fila.querySelector('.item-costo').value) || 0;
        fila.querySelector('.item-subtotal').value = moneda(cantidad * costo);
        calcularTotales();
    }

    function leerItemsFormulario() {
        return [...document.querySelectorAll('#listaProductosFactura .fila-producto-compra')].map((fila) => ({
            id_producto: Number(fila.querySelector('.item-producto').value) || 0,
            cantidad: Number(fila.querySelector('.item-cantidad').value) || 0,
            costo_unitario: Number(fila.querySelector('.item-costo').value) || 0
        }));
    }

    function calcularTotales() {
        const items = leerItemsFormulario();
        const subtotal = items.reduce((suma, item) => suma + item.cantidad * item.costo_unitario, 0);

        const selectImpuesto = $('impuestoFactura');
        const porcentaje = Number(selectImpuesto?.selectedOptions[0]?.dataset.porcentaje || 0);
        const impuesto = subtotal * porcentaje / 100;
        const total = subtotal + impuesto;

        $('subtotalFacturaResumen').textContent = moneda(subtotal);
        $('impuestoFacturaResumen').textContent = moneda(impuesto);
        $('totalFacturaResumen').textContent = moneda(total);
    }


    /*-----------------------------------------------------
      MODAL
    -----------------------------------------------------*/

    function abrirModalNuevaFactura() {
        $('formularioFactura').reset();
        $('listaProductosFactura').innerHTML = '';
        $('botonAgregarProductoFactura').disabled = true;
        $('proveedorFactura').disabled = false;
        $('ubicacionFactura').disabled = false;
        mostrarElemento('mensajeSinProductosFactura', true);
        $('mensajeFormularioFactura').textContent = '';
        calcularTotales();
        $('modalFactura').hidden = false;
    }

    function cerrarModal() {
        $('modalFactura').hidden = true;
    }

    async function guardarFactura(evento) {
        evento.preventDefault();

        const items = leerItemsFormulario();

        if (!items.length || items.some((i) => !i.id_producto || i.cantidad <= 0)) {
            $('mensajeFormularioFactura').textContent = 'Agrega al menos un producto válido.';
            return;
        }

        const datos = new FormData();
        datos.append('accion', 'crear_factura');
        datos.append('id_proveedor', $('proveedorFactura').value);
        datos.append('id_ubicacion', $('ubicacionFactura').value);
        datos.append('id_pedido', $('pedidoRelacionadoFactura').value);
        datos.append('numero_factura', $('numeroFactura').value.trim());
        datos.append('fecha_emision', $('fechaFactura').value);
        datos.append('fecha_vencimiento', $('fechaVencimientoFactura').value);
        datos.append('id_impuesto', $('impuestoFactura').value);
        datos.append('observaciones', $('observacionesFactura').value.trim());
        datos.append('items', JSON.stringify(items));

        const archivo = $('archivoFactura').files[0];
        if (archivo) {
            datos.append('evidencia', archivo);
        }

        try {
            const resultado = await apiFacturas('crear_factura', datos);

            cerrarModal();
            mostrarMensaje(resultado.message, 'exito');
            await Promise.all([cargarFacturas(), cargarPedidos()]);
        } catch (error) {
            $('mensajeFormularioFactura').textContent = error.message;
        }
    }


    /*-----------------------------------------------------
      LISTADO
    -----------------------------------------------------*/

    function leerFiltros() {
        return {
            busqueda: $('buscarFactura')?.value.trim() || '',
            id_proveedor: $('filtroProveedorFactura')?.value || '',
            id_ubicacion: $('filtroTiendaFactura')?.value || '',
            estado: $('filtroEstadoFactura')?.value || '',
            fecha: $('filtroFechaFactura')?.value || ''
        };
    }

    async function cargarFacturas() {
        try {
            const resultado = await apiFacturas('listar_facturas', null, leerFiltros());
            facturas = resultado.data || [];
            renderFacturas();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function renderFacturas() {
        const tabla = $('cuerpoTablaFacturas');

        tabla.innerHTML = facturas.map((f) => `
            <tr>
                <td>
                    ${escaparHTML(f.numero_factura)}
                    ${f.evidencia_url ? `<a href="${escaparHTML(f.evidencia_url)}" target="_blank" rel="noopener" title="Ver evidencia"><i class="fa-solid fa-paperclip"></i></a>` : ''}
                </td>
                <td>${new Date(f.fecha_emision).toLocaleDateString('es-CR')}</td>
                <td>${f.fecha_vencimiento ? new Date(f.fecha_vencimiento).toLocaleDateString('es-CR') : '-'}</td>
                <td>${escaparHTML(f.proveedor_nombre)}</td>
                <td>${escaparHTML(f.ubicacion_nombre)}</td>
                <td>${moneda(f.subtotal)}</td>
                <td>${moneda(f.monto_impuesto)}</td>
                <td>${moneda(f.monto_total)}</td>
                <td>${Number(f.movimientos_asociados || 0)}</td>
                <td>
                    <span class="badge badge-${COLOR_ESTADO[f.estado] || 'naranja'}">
                        ${ETIQUETAS_ESTADO[f.estado] || f.estado}
                    </span>
                </td>
                <td>${celdaAcciones(f)}</td>
            </tr>
        `).join('');

        mostrarElemento('contenedorTablaFacturas', facturas.length > 0);
        mostrarElemento('mensajeSinFacturas', facturas.length === 0);
        mostrarElemento('mensajeSinResultadosFacturas', false);

        $('totalFacturas').textContent = facturas.length;
        $('facturasPendientes').textContent = facturas.filter((f) => f.estado === 'PENDIENTE').length;
        $('facturasEnRevision').textContent = facturas.filter((f) => f.estado === 'EN_REVISION').length;
        $('montoTotalFacturas').textContent = moneda(facturas.reduce((s, f) => s + Number(f.monto_total || 0), 0));
        $('resultadoBusquedaFacturas').textContent = `${facturas.length} resultado(s)`;
    }

    function celdaAcciones(factura) {
        return `
            <button type="button" class="boton boton-borde boton-tabla boton-ver-factura" data-factura="${factura.id_factura}">
                Ver
            </button>
        `;
    }

    async function manejarClicTabla(evento) {
        const boton = evento.target.closest('button.boton-ver-factura');
        if (!boton) return;

        await abrirModalDetalleFactura(Number(boton.dataset.factura));
    }


    /*-----------------------------------------------------
      MODAL: VER / EDITAR FACTURA (Encargado/Administrador)
    -----------------------------------------------------*/

    function opcionesProductoDetalle(idSeleccionado) {
        return productosProveedorDetalle.map((p) => `
            <option value="${p.id_producto}" data-costo="${p.costo || 0}" ${String(p.id_producto) === String(idSeleccionado) ? 'selected' : ''}>
                ${escaparHTML(p.nombre)}
            </option>
        `).join('');
    }

    function agregarFilaProductoDetalle(item = null) {
        const lista = $('listaProductosDetalle');

        const fila = document.createElement('div');
        fila.className = 'fila-producto-compra';
        fila.innerHTML = `
            <div class="grupo-formulario">
                <label>Producto</label>
                <select class="item-producto" required>
                    <option value="">Seleccione...</option>
                    ${opcionesProductoDetalle(item ? item.id_producto : '')}
                </select>
            </div>
            <div class="grupo-formulario">
                <label>Cantidad</label>
                <input class="item-cantidad" type="number" min="1" value="1" autocomplete="off" required>
            </div>
            <div class="grupo-formulario">
                <label>Costo unitario</label>
                <input class="item-costo" type="number" min="0" step="0.01" value="0" autocomplete="off" required>
            </div>
            <div class="grupo-formulario">
                <label>Subtotal</label>
                <input class="item-subtotal" type="text" readonly value="₡0">
            </div>
            <button type="button" class="boton-eliminar-producto-compra" aria-label="Quitar producto">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        fila.querySelector('.item-producto').addEventListener('change', (e) => {
            const opcion = e.target.selectedOptions[0];
            fila.querySelector('.item-costo').value = opcion ? Number(opcion.dataset.costo || 0) : 0;
            actualizarFilaDetalle(fila);
        });
        fila.querySelector('.item-cantidad').addEventListener('input', () => actualizarFilaDetalle(fila));
        fila.querySelector('.item-costo').addEventListener('input', () => actualizarFilaDetalle(fila));
        fila.querySelector('button').addEventListener('click', () => {
            fila.remove();
            calcularTotalesDetalle();
        });

        lista.appendChild(fila);

        if (item) {
            fila.querySelector('.item-producto').value = item.id_producto;
            fila.querySelector('.item-cantidad').value = item.cantidad;
            fila.querySelector('.item-costo').value = item.costo_unitario;
        } else {
            fila.querySelector('.item-producto').value = '';
            fila.querySelector('.item-cantidad').value = 1;
            fila.querySelector('.item-costo').value = 0;
        }

        actualizarFilaDetalle(fila);
    }

    function actualizarFilaDetalle(fila) {
        const cantidad = Number(fila.querySelector('.item-cantidad').value) || 0;
        const costo = Number(fila.querySelector('.item-costo').value) || 0;
        fila.querySelector('.item-subtotal').value = moneda(cantidad * costo);
        calcularTotalesDetalle();
    }

    function leerItemsDetalle() {
        return [...document.querySelectorAll('#listaProductosDetalle .fila-producto-compra')].map((fila) => ({
            id_producto: Number(fila.querySelector('.item-producto').value) || 0,
            cantidad: Number(fila.querySelector('.item-cantidad').value) || 0,
            costo_unitario: Number(fila.querySelector('.item-costo').value) || 0
        }));
    }

    function calcularTotalesDetalle() {
        const items = leerItemsDetalle();
        const subtotal = items.reduce((suma, item) => suma + item.cantidad * item.costo_unitario, 0);

        const selectImpuesto = $('impuestoFacturaDetalle');
        const porcentaje = Number(selectImpuesto?.selectedOptions[0]?.dataset.porcentaje || 0);
        const impuesto = subtotal * porcentaje / 100;
        const total = subtotal + impuesto;

        $('subtotalDetalleResumen').textContent = moneda(subtotal);
        $('impuestoDetalleResumen').textContent = moneda(impuesto);
        $('totalDetalleResumen').textContent = moneda(total);
    }

    function bloquearCamposDetalle(bloqueado) {
        ['numeroFacturaDetalle', 'fechaFacturaDetalle', 'fechaVencimientoFacturaDetalle', 'impuestoFacturaDetalle', 'archivoFacturaDetalle', 'observacionesFacturaDetalle']
            .forEach((id) => { const el = $(id); if (el) el.disabled = bloqueado; });

        $('botonAgregarProductoDetalle').disabled = bloqueado;

        document.querySelectorAll('#listaProductosDetalle .item-producto, #listaProductosDetalle .item-cantidad, #listaProductosDetalle .item-costo')
            .forEach((el) => { el.disabled = bloqueado; });

        document.querySelectorAll('#listaProductosDetalle .boton-eliminar-producto-compra')
            .forEach((el) => { el.hidden = bloqueado; });
    }

    async function abrirModalDetalleFactura(idFactura) {
        try {
            const resultado = await apiFacturas('obtener_factura', null, { id_factura: idFactura });
            const factura = resultado.data;
            facturaDetalleActual = factura;

            $('idFacturaDetalle').value = factura.id_factura;
            $('tituloModalDetalleFactura').textContent = `Factura ${factura.numero_factura}`;
            $('badgeEstadoDetalleFactura').className = `badge badge-${COLOR_ESTADO[factura.estado] || 'naranja'}`;
            $('badgeEstadoDetalleFactura').textContent = ETIQUETAS_ESTADO[factura.estado] || factura.estado;
            $('pedidoRelacionadoDetalle').value = factura.numero_pedido || 'Sin pedido relacionado';
            $('proveedorDetalle').value = factura.proveedor_nombre;
            $('ubicacionDetalle').value = factura.ubicacion_nombre;
            $('numeroFacturaDetalle').value = factura.numero_factura;
            $('fechaFacturaDetalle').value = (factura.fecha_emision || '').slice(0, 10);
            $('fechaVencimientoFacturaDetalle').value = factura.fecha_vencimiento ? factura.fecha_vencimiento.slice(0, 10) : '';
            $('impuestoFacturaDetalle').value = factura.id_impuesto;
            $('observacionesFacturaDetalle').value = factura.observaciones || '';
            $('motivoRechazoFactura').value = '';
            $('motivoAnulacionFactura').value = '';
            $('archivoFacturaDetalle').value = '';
            $('evidenciaActualDetalle').innerHTML = factura.evidencia_url
                ? `Archivo actual: <a href="${escaparHTML(factura.evidencia_url)}" target="_blank" rel="noopener">ver PDF</a>`
                : 'No se ha adjuntado ningún archivo.';
            $('mensajeDetalleFactura').textContent = '';

            const resultadoProductos = await apiPedidos('listar_productos_proveedor', null, { id_proveedor: factura.id_proveedor });
            productosProveedorDetalle = resultadoProductos.data || [];

            $('listaProductosDetalle').innerHTML = '';
            (factura.detalle || []).forEach((item) => agregarFilaProductoDetalle(item));
            calcularTotalesDetalle();

            const puedeEditar = (puedeRevisarFactura && ['PENDIENTE', 'EN_REVISION', 'RECHAZADA'].includes(factura.estado))
                || (Number(factura.id_usuario_registra) === idUsuarioActual && ['PENDIENTE', 'RECHAZADA'].includes(factura.estado));
            bloquearCamposDetalle(!puedeEditar);
            mostrarElemento('botonGuardarDetalleFactura', puedeEditar);
            mostrarElemento('botonEnviarRevisionDetalle', puedeRevisarFactura && factura.estado === 'PENDIENTE');

            const puedeAprobarEstaFactura = puedeValidarFactura && factura.estado === 'EN_REVISION';
            const puedeRechazarEstaFactura = puedeValidarFactura && ['PENDIENTE', 'EN_REVISION'].includes(factura.estado);
            const puedeAnularEstaFactura = puedeAnularFactura && factura.estado !== 'ANULADA';
            mostrarElemento('grupoMotivoRechazoFactura', puedeRechazarEstaFactura);
            mostrarElemento('botonAprobarFacturaDetalle', puedeAprobarEstaFactura);
            mostrarElemento('botonRechazarFacturaDetalle', puedeRechazarEstaFactura);
            mostrarElemento('grupoMotivoAnulacionFactura', puedeAnularEstaFactura);
            mostrarElemento('botonAnularFacturaDetalle', puedeAnularEstaFactura);

            $('modalDetalleFactura').hidden = false;
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function cerrarModalDetalle() {
        $('modalDetalleFactura').hidden = true;
    }

    async function guardarCambiosDetalle() {
        const items = leerItemsDetalle();

        if (!items.length || items.some((i) => !i.id_producto || i.cantidad <= 0)) {
            $('mensajeDetalleFactura').textContent = 'Agrega al menos un producto válido.';
            return;
        }

        if (!$('numeroFacturaDetalle').value.trim() || !$('fechaFacturaDetalle').value || !$('impuestoFacturaDetalle').value) {
            $('mensajeDetalleFactura').textContent = 'Completa el número de factura, la fecha de emisión y el impuesto.';
            return;
        }

        const datos = new FormData();
        datos.append('id_factura', $('idFacturaDetalle').value);
        datos.append('numero_factura', $('numeroFacturaDetalle').value.trim());
        datos.append('fecha_emision', $('fechaFacturaDetalle').value);
        datos.append('fecha_vencimiento', $('fechaVencimientoFacturaDetalle').value);
        datos.append('id_impuesto', $('impuestoFacturaDetalle').value);
        datos.append('observaciones', $('observacionesFacturaDetalle').value.trim());
        datos.append('items', JSON.stringify(items));

        const archivo = $('archivoFacturaDetalle').files[0];
        if (archivo) {
            datos.append('evidencia', archivo);
        }

        try {
            await apiFacturas('actualizar_factura', datos);
            mostrarMensaje('Factura actualizada correctamente.', 'exito');
            cerrarModalDetalle();
            await cargarFacturas();
        } catch (error) {
            $('mensajeDetalleFactura').textContent = error.message;
        }
    }

    async function enviarRevisionDesdeDetalle() {
        try {
            await apiFacturas('marcar_en_revision', { id_factura: Number($('idFacturaDetalle').value) });
            mostrarMensaje('Factura validada correctamente.', 'exito');
            cerrarModalDetalle();
            await cargarFacturas();
        } catch (error) {
            $('mensajeDetalleFactura').textContent = error.message;
        }
    }

    async function aprobarFacturaDetalle() {
        try {
            await apiFacturas('aprobar_factura', { id_factura: Number($('idFacturaDetalle').value) });
            mostrarMensaje('Factura aprobada correctamente.', 'exito');
            cerrarModalDetalle();
            await cargarFacturas();
        } catch (error) {
            $('mensajeDetalleFactura').textContent = error.message;
        }
    }

    async function rechazarFacturaDetalle() {
        const motivo = $('motivoRechazoFactura').value.trim();

        if (!motivo) {
            $('mensajeDetalleFactura').textContent = 'Indica el motivo del rechazo.';
            return;
        }

        try {
            await apiFacturas('rechazar_factura', { id_factura: Number($('idFacturaDetalle').value), motivo });
            mostrarMensaje('Factura rechazada.', 'exito');
            cerrarModalDetalle();
            await cargarFacturas();
        } catch (error) {
            $('mensajeDetalleFactura').textContent = error.message;
        }
    }

    async function anularFacturaDetalle() {
        const motivo = $('motivoAnulacionFactura').value.trim();

        if (!motivo) {
            $('mensajeDetalleFactura').textContent = 'Indica el motivo de la anulación.';
            return;
        }

        try {
            await apiFacturas('anular_factura', { id_factura: Number($('idFacturaDetalle').value), motivo });
            mostrarMensaje('Factura anulada.', 'exito');
            cerrarModalDetalle();
            await cargarFacturas();
        } catch (error) {
            $('mensajeDetalleFactura').textContent = error.message;
        }
    }

    function exportarHistorial() {
        const parametros = new URLSearchParams(leerFiltros());
        window.open(`../api/compras/facturas.php?accion=exportar_historial&${parametros.toString()}`, '_blank');
    }
})();
