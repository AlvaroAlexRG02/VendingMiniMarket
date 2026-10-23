/*=========================================================
  VENDING MINI MARKET — Pedidos a proveedor (compras/pedido.php)
  HU-30 · crear pedidos a proveedor
  HU-31 · consultar y actualizar estado
  Requiere: pedido-api.js cargado antes.
=========================================================*/

(() => {
    'use strict';

    const { api } = window.PedidosApi;
    const $ = (id) => document.getElementById(id);

    const ETIQUETAS_ESTADO = {
        PENDIENTE: 'Pendiente',
        EN_CAMINO: 'En camino',
        PARCIAL: 'Parcial',
        RECIBIDO: 'Recibido',
        CANCELADO: 'Cancelado'
    };

    const COLOR_ESTADO = {
        PENDIENTE: 'naranja',
        EN_CAMINO: 'azul',
        PARCIAL: 'morado',
        RECIBIDO: 'verde',
        CANCELADO: 'rojo'
    };

    let pedidos = [];
    let proveedores = [];
    let ubicaciones = [];
    let productosProveedorActual = [];
    let puedeActualizarEstado = false;

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaPedidos')) {
            return;
        }

        puedeActualizarEstado = document.body.dataset.puedeActualizarEstado === '1';

        registrarEventos();

        try {
            await Promise.all([cargarProveedores(), cargarUbicaciones()]);
            await cargarPedidos();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function registrarEventos() {
        $('botonNuevoPedido')?.addEventListener('click', abrirModalNuevoPedido);
        $('botonPrimerPedido')?.addEventListener('click', abrirModalNuevoPedido);
        $('botonCerrarModalPedido')?.addEventListener('click', cerrarModal);
        $('botonCancelarPedido')?.addEventListener('click', cerrarModal);
        document.querySelector('[data-cerrar-modal-pedido]')?.addEventListener('click', cerrarModal);

        $('formularioPedido')?.addEventListener('submit', guardarPedido);
        $('proveedorPedido')?.addEventListener('change', cargarProductosDelProveedor);
        $('botonAgregarProductoPedido')?.addEventListener('click', () => agregarFilaProducto());

        ['buscarPedido', 'filtroProveedorPedido', 'filtroTiendaPedido', 'filtroEstadoPedido', 'filtroFechaPedido']
            .forEach((id) => {
                $(id)?.addEventListener(id === 'buscarPedido' ? 'input' : 'change', () => cargarPedidos());
            });

        $('botonLimpiarFiltrosPedidos')?.addEventListener('click', () => {
            ['buscarPedido', 'filtroProveedorPedido', 'filtroTiendaPedido', 'filtroEstadoPedido', 'filtroFechaPedido']
                .forEach((id) => { const e = $(id); if (e) e.value = ''; });
            cargarPedidos();
        });

        $('cuerpoTablaPedidos')?.addEventListener('click', manejarClicTabla);
    }

    function mostrarMensaje(texto, tipo = 'info') {
        const el = $('mensajePedidos');
        if (!el) return;

        el.textContent = texto;
        el.className = `mensaje-producto mensaje-${tipo}`;
        el.hidden = false;
    }

    function ocultarMensaje() {
        const el = $('mensajePedidos');
        if (el) el.hidden = true;
    }

    function moneda(valor) {
        return '₡' + Number(valor || 0).toLocaleString('es-CR', { maximumFractionDigits: 2 });
    }


    /*-----------------------------------------------------
      CATÁLOGOS DE APOYO
    -----------------------------------------------------*/

    async function cargarProveedores() {
        const resultado = await api('listar_proveedores');
        proveedores = resultado.data || [];

        const selects = [$('proveedorPedido'), $('filtroProveedorPedido')];
        selects.forEach((select, indice) => {
            if (!select) return;
            const vacio = indice === 0 ? 'Seleccione un proveedor' : 'Todos los proveedores';
            select.innerHTML = `<option value="">${vacio}</option>` +
                proveedores.map((p) => `<option value="${p.id_proveedor}">${escaparHTML(p.nombre)}</option>`).join('');
        });
    }

    async function cargarUbicaciones() {
        const resultado = await api('listar_ubicaciones');
        ubicaciones = resultado.data || [];

        const selects = [$('ubicacionPedido'), $('filtroTiendaPedido')];
        selects.forEach((select, indice) => {
            if (!select) return;
            const vacio = indice === 0 ? 'Seleccione una tienda' : 'Todas las tiendas';
            select.innerHTML = `<option value="">${vacio}</option>` +
                ubicaciones.map((u) => `<option value="${u.id_ubicacion}">${escaparHTML(u.nombre)}</option>`).join('');
        });
    }

    async function cargarProductosDelProveedor() {
        const idProveedor = $('proveedorPedido').value;
        $('listaProductosPedido').innerHTML = '';
        productosProveedorActual = [];

        const boton = $('botonAgregarProductoPedido');

        if (!idProveedor) {
            boton.disabled = true;
            mostrarElemento('mensajeSinProductosPedido', true);
            return;
        }

        try {
            const resultado = await api('listar_productos_proveedor', null, { id_proveedor: idProveedor });
            productosProveedorActual = resultado.data || [];
            boton.disabled = productosProveedorActual.length === 0;

            if (productosProveedorActual.length === 0) {
                mostrarMensaje('Este proveedor no tiene productos asociados.', 'error');
            } else {
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

    function agregarFilaProducto() {
        const lista = $('listaProductosPedido');

        const fila = document.createElement('div');
        fila.className = 'fila-producto-compra';
        fila.innerHTML = `
            <div class="grupo-formulario">
                <label>Producto</label>
                <select class="item-producto" required>
                    <option value="">Seleccione...</option>
                    ${opcionesProducto('')}
                </select>
            </div>
            <div class="grupo-formulario">
                <label>Cantidad</label>
                <input class="item-cantidad" type="number" min="1" value="1" required>
            </div>
            <div class="grupo-formulario">
                <label>Costo unitario</label>
                <input class="item-costo" type="text" readonly value="₡0">
            </div>
            <div class="grupo-formulario">
                <label>Subtotal</label>
                <input class="item-subtotal" type="text" readonly value="₡0">
            </div>
            <button type="button" class="boton-eliminar-producto-compra" aria-label="Quitar producto">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;

        fila.querySelector('.item-producto').addEventListener('change', () => actualizarFila(fila));
        fila.querySelector('.item-cantidad').addEventListener('input', () => actualizarFila(fila));
        fila.querySelector('button').addEventListener('click', () => {
            fila.remove();
            mostrarElemento('mensajeSinProductosPedido', !lista.children.length);
            calcularTotales();
        });

        lista.appendChild(fila);
        mostrarElemento('mensajeSinProductosPedido', false);
        actualizarFila(fila);
    }

    function actualizarFila(fila) {
        const select = fila.querySelector('.item-producto');
        const opcion = select.selectedOptions[0];
        const costo = opcion ? Number(opcion.dataset.costo || 0) : 0;
        const cantidad = Number(fila.querySelector('.item-cantidad').value) || 0;

        fila.querySelector('.item-costo').value = moneda(costo);
        fila.querySelector('.item-subtotal').value = moneda(costo * cantidad);

        calcularTotales();
    }

    function leerItemsFormulario() {
        return [...document.querySelectorAll('#listaProductosPedido .fila-producto-compra')].map((fila) => {
            const select = fila.querySelector('.item-producto');
            return {
                id_producto: Number(select.value) || 0,
                cantidad: Number(fila.querySelector('.item-cantidad').value) || 0
            };
        });
    }

    function calcularTotales() {
        const items = leerItemsFormulario();
        const total = items.reduce((suma, item) => {
            const producto = productosProveedorActual.find((p) => p.id_producto === item.id_producto);
            return suma + item.cantidad * Number(producto?.costo || 0);
        }, 0);

        $('cantidadTotalPedido').textContent = items.reduce((suma, i) => suma + i.cantidad, 0);
        $('totalPedido').textContent = moneda(total);
    }


    /*-----------------------------------------------------
      MODAL
    -----------------------------------------------------*/

    function abrirModalNuevoPedido() {
        $('formularioPedido').reset();
        $('fechaPedido').value = new Date().toLocaleDateString('es-CR');
        $('listaProductosPedido').innerHTML = '';
        $('botonAgregarProductoPedido').disabled = true;
        mostrarElemento('mensajeSinProductosPedido', true);
        $('cantidadTotalPedido').textContent = '0';
        $('totalPedido').textContent = '₡0';
        $('mensajeFormularioPedido').textContent = '';
        $('modalPedido').hidden = false;
    }

    function cerrarModal() {
        $('modalPedido').hidden = true;
    }

    async function guardarPedido(evento) {
        evento.preventDefault();

        const items = leerItemsFormulario();

        if (!items.length || items.some((i) => !i.id_producto || i.cantidad <= 0)) {
            $('mensajeFormularioPedido').textContent = 'Agrega al menos un producto válido.';
            return;
        }

        try {
            await api('crear_pedido', {
                id_proveedor: Number($('proveedorPedido').value),
                id_ubicacion: Number($('ubicacionPedido').value),
                observaciones: $('observacionesPedido').value.trim(),
                items
            });

            cerrarModal();
            ocultarMensaje();
            mostrarMensaje('Pedido registrado correctamente.', 'exito');
            await cargarPedidos();
        } catch (error) {
            $('mensajeFormularioPedido').textContent = error.message;
        }
    }


    /*-----------------------------------------------------
      LISTADO
    -----------------------------------------------------*/

    function leerFiltros() {
        return {
            busqueda: $('buscarPedido')?.value.trim() || '',
            id_proveedor: $('filtroProveedorPedido')?.value || '',
            id_ubicacion: $('filtroTiendaPedido')?.value || '',
            estado: $('filtroEstadoPedido')?.value || '',
            fecha: $('filtroFechaPedido')?.value || ''
        };
    }

    async function cargarPedidos() {
        try {
            const resultado = await api('listar_pedidos', null, leerFiltros());
            pedidos = resultado.data || [];
            renderPedidos();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function renderPedidos() {
        const tabla = $('cuerpoTablaPedidos');

        tabla.innerHTML = pedidos.map((p) => `
            <tr>
                <td>${new Date(p.fecha_pedido).toLocaleDateString('es-CR')}</td>
                <td>${escaparHTML(p.proveedor_nombre)}</td>
                <td>${escaparHTML(p.ubicacion_nombre)}</td>
                <td>${escaparHTML(`${p.solicita_nombre} ${p.solicita_apellido || ''}`.trim())}</td>
                <td>${p.total_unidades ?? 0}</td>
                <td>${moneda(p.monto_total)}</td>
                <td>
                    <span class="badge badge-${COLOR_ESTADO[p.estado] || 'naranja'}">
                        ${ETIQUETAS_ESTADO[p.estado] || p.estado}
                    </span>
                    ${p.requiere_aprobacion ? '<span class="badge badge-morado">Requiere aprobación</span>' : ''}
                </td>
                <td>${celdaAcciones(p)}</td>
            </tr>
        `).join('');

        mostrarElemento('contenedorTablaPedidos', pedidos.length > 0);
        mostrarElemento('mensajeSinPedidos', pedidos.length === 0);
        mostrarElemento('mensajeSinResultadosPedidos', false);

        $('totalPedidos').textContent = pedidos.length;
        $('pedidosPendientes').textContent = pedidos.filter((p) => p.estado === 'PENDIENTE').length;
        $('pedidosRequierenAprobacion').textContent = pedidos.filter((p) => p.requiere_aprobacion).length;
        $('totalProductosPedidos').textContent = pedidos.reduce((s, p) => s + Number(p.total_unidades || 0), 0);
        $('resultadoBusquedaPedidos').textContent = `${pedidos.length} resultado(s)`;
    }

    function celdaAcciones(pedido) {
        if (!puedeActualizarEstado || ['RECIBIDO', 'CANCELADO'].includes(pedido.estado)) {
            return '-';
        }

        const opciones = Object.entries(ETIQUETAS_ESTADO)
            .map(([valor, etiqueta]) => `<option value="${valor}" ${valor === pedido.estado ? 'selected' : ''}>${etiqueta}</option>`)
            .join('');

        return `
            <select class="selector-estado-pedido" data-pedido="${pedido.id_pedido}">
                ${opciones}
            </select>
        `;
    }

    async function manejarClicTabla(evento) {
        const select = evento.target.closest('select.selector-estado-pedido');
        if (!select) return;
    }

    document.addEventListener('change', async (evento) => {
        const select = evento.target.closest('select.selector-estado-pedido');
        if (!select) return;

        try {
            await api('actualizar_estado_pedido', {
                id_pedido: Number(select.dataset.pedido),
                estado: select.value
            });

            mostrarMensaje('Estado del pedido actualizado.', 'exito');
            await cargarPedidos();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
            await cargarPedidos();
        }
    });


    /*-----------------------------------------------------
      UTILIDADES
    -----------------------------------------------------*/

    function mostrarElemento(id, visible) {
        const el = $(id);
        if (el) el.hidden = !visible;
    }

    function escaparHTML(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }
})();
