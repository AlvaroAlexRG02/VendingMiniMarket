/*=========================================================
  VENDING MINI MARKET — Pedidos a proveedor (compras/pedido.php)
  HU-30 · crear pedidos a proveedor
  HU-31 · consultar y actualizar estado
  HU-32 · recibir pedidos (parcial o total)
  Requiere: pedido-api.js y recepcion-api.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const { api } = window.PedidosApi;
    const { api: apiRecepciones } = window.RecepcionesApi;
    const $ = (id) => document.getElementById(id);

    const ETIQUETAS_ESTADO = {
        PENDIENTE: 'Pendiente',
        EN_CAMINO: 'En camino',
        PARCIAL: 'Recibido parcial',
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
    let paginadorPedidos = null;
    let paginadorPorAprobar = null;
    let paginadorRecepciones = null;
    let porAprobarActuales = [];
    let recepcionesActuales = [];
    let proveedores = [];
    let ubicaciones = [];
    let productosProveedorActual = [];
    let puedeActualizarEstado = false;
    let puedeEditarPedido = false;
    let puedeAprobarPedido = false;
    let idUsuarioActual = 0;
    let pedidoEnEdicion = null;

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaPedidos')) {
            return;
        }

        puedeActualizarEstado = document.body.dataset.puedeActualizarEstado === '1';
        puedeEditarPedido = document.body.dataset.puedeEditarPedido === '1';
        puedeAprobarPedido = document.body.dataset.puedeAprobarPedido === '1';
        idUsuarioActual = Number(document.body.dataset.idUsuario) || 0;

        paginadorPedidos = Paginador.crear({
            despuesDe: '#contenedorTablaPedidos',
            filtros: ['#buscarPedido', '#filtroProveedorPedido', '#filtroTiendaPedido', '#filtroEstadoPedido', '#filtroFechaPedido'],
            unidad: 'pedidos',
            alCambiar: renderPedidos
        });
        paginadorPorAprobar = Paginador.crear({
            despuesDe: '#contenedorTablaPedidosPorAprobar',
            unidad: 'pedidos',
            alCambiar: renderPedidosPorAprobar
        });
        paginadorRecepciones = Paginador.crear({
            despuesDe: '#contenedorRecepcionesPendientes',
            unidad: 'entregas',
            alCambiar: renderRecepcionesPendientes
        });

        registrarEventos();

        try {
            await Promise.all([cargarProveedores(), cargarUbicaciones()]);
            await cargarPedidos();

            if (puedeActualizarEstado) {
                await cargarRecepcionesPendientes();
            }
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

        $('botonCerrarModalRecepcion')?.addEventListener('click', cerrarModalRecepcion);
        $('botonCancelarRecepcion')?.addEventListener('click', cerrarModalRecepcion);
        document.querySelector('[data-cerrar-modal-recepcion]')?.addEventListener('click', cerrarModalRecepcion);
        $('formularioRecepcion')?.addEventListener('submit', guardarRecepcion);
        $('cuerpoTablaRecepcionesPendientes')?.addEventListener('click', manejarClicRecepcionesPendientes);
        $('cuerpoTablaPedidosPorAprobar')?.addEventListener('click', manejarClicTabla);

        $('botonCerrarModalVerRecepcion')?.addEventListener('click', cerrarModalVerRecepcion);
        $('botonCerrarVerRecepcion')?.addEventListener('click', cerrarModalVerRecepcion);
        document.querySelector('[data-cerrar-modal-ver-recepcion]')?.addEventListener('click', cerrarModalVerRecepcion);
        $('botonConfirmarDesdeVerRecepcion')?.addEventListener('click', confirmarDesdeModalVerRecepcion);

        $('botonCerrarModalVerPedido')?.addEventListener('click', cerrarModalVerPedido);
        $('botonCerrarVerPedido')?.addEventListener('click', cerrarModalVerPedido);
        document.querySelector('[data-cerrar-modal-ver-pedido]')?.addEventListener('click', cerrarModalVerPedido);

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

    async function cargarProductosDelProveedor(itemsPreset = null) {
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
            } else if (Array.isArray(itemsPreset) && itemsPreset.length) {
                itemsPreset.forEach((item) => agregarFilaProducto(item.id_producto, item.cantidad));
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

    function agregarFilaProducto(idProductoPreset = '', cantidadPreset = 1) {
        const lista = $('listaProductosPedido');

        const fila = document.createElement('div');
        fila.className = 'fila-producto-compra';
        fila.innerHTML = `
            <div class="grupo-formulario">
                <label>Producto</label>
                <select class="item-producto" required>
                    <option value="">Seleccione...</option>
                    ${opcionesProducto(idProductoPreset)}
                </select>
            </div>
            <div class="grupo-formulario">
                <label>Cantidad</label>
                <input class="item-cantidad" type="number" min="1" value="${cantidadPreset}" autocomplete="off" required>
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
            actualizarDisponibilidadProductos();
            calcularTotales();
        });

        lista.appendChild(fila);
        fila.querySelector('.item-producto').value = idProductoPreset || '';
        fila.querySelector('.item-cantidad').value = cantidadPreset;
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

        actualizarDisponibilidadProductos();
        calcularTotales();
    }

    function actualizarDisponibilidadProductos() {
        const selects = [...document.querySelectorAll('#listaProductosPedido .item-producto')];
        const seleccionados = selects.map((s) => s.value).filter((v) => v !== '');

        selects.forEach((select) => {
            [...select.options].forEach((opcion) => {
                if (opcion.value === '') return;
                opcion.disabled = opcion.value !== select.value && seleccionados.includes(opcion.value);
            });
        });
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
        pedidoEnEdicion = null;
        $('tituloModalPedido').textContent = 'Nuevo pedido';
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

    async function abrirModalEditarPedido(idPedido) {
        try {
            const resultado = await api('obtener_pedido', null, { id_pedido: idPedido });
            const pedido = resultado.data;

            if (pedido.estado === 'EN_CAMINO'
                && !await confirmarVending({
                    titulo: 'Pedido aprobado',
                    mensaje: 'Este pedido ya está aprobado. Si lo edita volverá a Pendiente y deberá aprobarse de nuevo. ¿Continuar?',
                    icono: 'fa-solid fa-triangle-exclamation',
                    textoConfirmar: 'Continuar'
                })) {
                return;
            }

            pedidoEnEdicion = pedido.id_pedido;

            $('tituloModalPedido').textContent = 'Editar pedido';
            $('mensajeFormularioPedido').textContent = '';
            $('fechaPedido').value = new Date(pedido.fecha_pedido).toLocaleDateString('es-CR');
            $('observacionesPedido').value = pedido.observaciones || '';
            $('proveedorPedido').value = pedido.id_proveedor;
            $('ubicacionPedido').value = pedido.id_ubicacion;

            const itemsPreset = pedido.detalle.map((d) => ({
                id_producto: d.id_producto,
                cantidad: d.cantidad_solicitada
            }));

            await cargarProductosDelProveedor(itemsPreset);

            $('modalPedido').hidden = false;
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function cerrarModal() {
        $('modalPedido').hidden = true;
        pedidoEnEdicion = null;
    }

    async function guardarPedido(evento) {
        evento.preventDefault();

        const items = leerItemsFormulario();

        if (!items.length || items.some((i) => !i.id_producto || i.cantidad <= 0)) {
            $('mensajeFormularioPedido').textContent = 'Agrega al menos un producto válido.';
            return;
        }

        const idsUnicos = new Set(items.map((i) => i.id_producto));
        if (idsUnicos.size !== items.length) {
            $('mensajeFormularioPedido').textContent = 'No puedes agregar el mismo producto dos veces en el pedido.';
            return;
        }

        const datos = {
            id_proveedor: Number($('proveedorPedido').value),
            id_ubicacion: Number($('ubicacionPedido').value),
            observaciones: $('observacionesPedido').value.trim(),
            items
        };

        try {
            if (pedidoEnEdicion) {
                const resultado = await api('editar_pedido', { ...datos, id_pedido: pedidoEnEdicion });
                cerrarModal();
                ocultarMensaje();
                mostrarMensaje(mensajeTrasGuardar(resultado.data, 'Pedido actualizado correctamente.'), 'exito');
            } else {
                const resultado = await api('crear_pedido', datos);
                cerrarModal();
                ocultarMensaje();
                mostrarMensaje(mensajeTrasGuardar(resultado.data, 'Pedido registrado correctamente.'), 'exito');
            }

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
            await cargarPedidosPorAprobar();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function mensajeTrasGuardar(pedido, base) {
        if (pedido && pedido.estado_aprobacion === 'BORRADOR') {
            return `${base} Quedó como borrador: envíalo a aprobación cuando esté listo.`;
        }
        return base;
    }

    // Mientras no esté aprobado, el pedido se muestra según su etapa de aprobación.
    function etiquetaEstado(pedido) {
        if (pedido.estado === 'PENDIENTE') {
            return pedido.estado_aprobacion === 'BORRADOR' ? 'Borrador' : 'Pendiente de aprobación';
        }
        return ETIQUETAS_ESTADO[pedido.estado] || pedido.estado;
    }

    function colorEstado(pedido) {
        if (pedido.estado === 'PENDIENTE' && pedido.estado_aprobacion === 'BORRADOR') return 'gris';
        return COLOR_ESTADO[pedido.estado] || 'naranja';
    }

    // Panel independiente de los filtros del historial.
    async function cargarPedidosPorAprobar() {
        if (!puedeAprobarPedido || !$('cuerpoTablaPedidosPorAprobar')) return;

        const resultado = await api('listar_pedidos');
        const porAprobar = (resultado.data || [])
            .filter((p) => p.estado === 'PENDIENTE' && p.estado_aprobacion === 'POR_APROBAR');

        porAprobarActuales = porAprobar;
        mostrarElemento('panelPedidosPorAprobar', porAprobar.length > 0);
        renderPedidosPorAprobar();
    }

    function renderPedidosPorAprobar() {
        const visibles = paginadorPorAprobar.paginar(porAprobarActuales);

        $('cuerpoTablaPedidosPorAprobar').innerHTML = visibles.map((p) => `
            <tr>
                <td>${p.numero_pedido}</td>
                <td>${new Date(p.fecha_pedido).toLocaleDateString('es-CR')}</td>
                <td>${escaparHTML(p.proveedor_nombre)}</td>
                <td>${escaparHTML(p.ubicacion_nombre)}</td>
                <td>${p.total_unidades ?? 0} unidad(es)</td>
                <td>${moneda(p.monto_total)}</td>
                <td>${escaparHTML(`${p.solicita_nombre} ${p.solicita_apellido || ''}`.trim())}</td>
                <td>
                    <div class="acciones-tabla">
                        <button type="button" class="boton-accion accion-ver boton-ver-pedido" data-pedido="${p.id_pedido}" title="Ver pedido" aria-label="Ver pedido ${p.numero_pedido}">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button type="button" class="boton-accion accion-exito boton-aprobar-pedido" data-pedido="${p.id_pedido}" title="Aprobar pedido" aria-label="Aprobar pedido ${p.numero_pedido}">
                            <i class="fa-solid fa-circle-check"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPedidos() {
        const tabla = $('cuerpoTablaPedidos');
        const visibles = paginadorPedidos.paginar(pedidos);

        tabla.innerHTML = visibles.map((p) => `
            <tr>
                <td>${p.numero_pedido}</td>
                <td>${new Date(p.fecha_pedido).toLocaleDateString('es-CR')}</td>
                <td>${escaparHTML(p.proveedor_nombre)}</td>
                <td>${escaparHTML(p.ubicacion_nombre)}</td>
                <td>${escaparHTML(`${p.solicita_nombre} ${p.solicita_apellido || ''}`.trim())}</td>
                <td>${p.total_unidades ?? 0}</td>
                <td>${moneda(p.monto_total)}</td>
                <td>
                    <span class="badge badge-${colorEstado(p)}">
                        ${etiquetaEstado(p)}
                    </span>
                    ${p.numero_factura ? '<span class="badge badge-verde">Facturado</span>' : ''}
                </td>
                <td>${celdaAcciones(p)}</td>
            </tr>
        `).join('');

        mostrarElemento('contenedorTablaPedidos', pedidos.length > 0);
        mostrarElemento('mensajeSinPedidos', pedidos.length === 0);
        mostrarElemento('mensajeSinResultadosPedidos', false);

        $('totalPedidos').textContent = pedidos.length;
        $('pedidosPendientes').textContent = pedidos.filter((p) => p.estado === 'PENDIENTE').length;
        $('pedidosRequierenAprobacion').textContent = pedidos.filter((p) => p.estado_aprobacion === 'POR_APROBAR' && !['RECIBIDO', 'CANCELADO'].includes(p.estado)).length;
        $('totalProductosPedidos').textContent = pedidos.reduce((s, p) => s + Number(p.total_unidades || 0), 0);
        $('resultadoBusquedaPedidos').textContent = `${pedidos.length} resultado(s)`;
    }

    function celdaAcciones(pedido) {
        const cerrado = ['RECIBIDO', 'CANCELADO'].includes(pedido.estado);
        const esPropio = Number(pedido.id_usuario_solicita) === idUsuarioActual;
        const enAprobacion = pedido.estado === 'PENDIENTE';
        const aprobado = pedido.estado_aprobacion === 'APROBADO';

        const boton = (clase, titulo, icono, color) => `<button type="button" class="boton-accion ${color} ${clase}" data-pedido="${pedido.id_pedido}" title="${titulo}" aria-label="${titulo} ${pedido.numero_pedido}">
                   <i class="fa-solid ${icono}"></i>
               </button>`;

        const botonVer = boton('boton-ver-pedido', 'Ver pedido', 'fa-eye', 'accion-ver');

        // Se edita mientras no haya mercadería recibida. El Dependiente solo edita
        // sus pedidos y no mientras esperan aprobación.
        const editable = ['PENDIENTE', 'EN_CAMINO'].includes(pedido.estado);
        const botonEditar = (puedeEditarPedido && editable && (
            puedeAprobarPedido || (esPropio && pedido.estado_aprobacion !== 'POR_APROBAR')
        )) ? boton('boton-editar-pedido', 'Editar pedido', 'fa-pen', 'accion-editar') : '';

        const botonEnviar = (enAprobacion && pedido.estado_aprobacion === 'BORRADOR' && (puedeAprobarPedido || esPropio))
            ? boton('boton-enviar-aprobacion-pedido', 'Enviar a aprobación', 'fa-paper-plane', 'accion-info')
            : '';

        const botonAprobar = (puedeAprobarPedido && enAprobacion && pedido.estado_aprobacion === 'POR_APROBAR')
            ? boton('boton-aprobar-pedido', 'Aprobar pedido', 'fa-circle-check', 'accion-exito')
            : '';

        // Solo se recibe mercadería de pedidos ya aprobados (En camino / Recibido parcial).
        const botonRecibir = (!cerrado && aprobado)
            ? boton('boton-recibir-pedido', 'Recibir pedido', 'fa-truck-ramp-box', 'accion-aviso')
            : '';

        // Pedido recibido por completo y sin factura: listo para facturar.
        const botonFacturar = (pedido.estado === 'RECIBIDO' && !pedido.numero_factura)
            ? boton('boton-facturar-pedido', 'Listo para facturar', 'fa-file-invoice-dollar', 'accion-teal')
            : '';

        const botonCancelar = (puedeActualizarEstado && !cerrado)
            ? boton('boton-cancelar-pedido', 'Cancelar pedido', 'fa-ban', 'accion-peligro')
            : '';

        return `<div class="acciones-tabla">${botonVer}${botonEditar}${botonEnviar}${botonAprobar}${botonRecibir}${botonFacturar}${botonCancelar}</div>`;
    }

    async function manejarClicTabla(evento) {
        const botonVer = evento.target.closest('button.boton-ver-pedido');
        if (botonVer) {
            await abrirModalVerPedido(Number(botonVer.dataset.pedido));
            return;
        }

        const botonEditar = evento.target.closest('button.boton-editar-pedido');
        if (botonEditar) {
            await abrirModalEditarPedido(Number(botonEditar.dataset.pedido));
            return;
        }

        const botonEnviar = evento.target.closest('button.boton-enviar-aprobacion-pedido');
        if (botonEnviar) {
            await cambiarAprobacion('enviar_aprobacion_pedido', Number(botonEnviar.dataset.pedido));
            return;
        }

        const botonFacturar = evento.target.closest('button.boton-facturar-pedido');
        if (botonFacturar) {
            window.location.href = `facturas.php?id_pedido=${encodeURIComponent(botonFacturar.dataset.pedido)}`;
            return;
        }

        const botonCancelar = evento.target.closest('button.boton-cancelar-pedido');
        if (botonCancelar) {
            if (window.confirm('¿Cancelar este pedido? Esta acción no se puede deshacer.')) {
                await cancelarPedido(Number(botonCancelar.dataset.pedido));
            }
            return;
        }

        const botonAprobar = evento.target.closest('button.boton-aprobar-pedido');
        if (botonAprobar) {
            await cambiarAprobacion('aprobar_pedido', Number(botonAprobar.dataset.pedido));
            return;
        }

        const boton = evento.target.closest('button.boton-recibir-pedido');
        if (!boton) return;

        await abrirModalRecepcion(Number(boton.dataset.pedido));
    }

    async function cambiarAprobacion(accion, idPedido) {
        try {
            const resultado = await api(accion, { id_pedido: idPedido });
            mostrarMensaje(resultado.message, 'exito');
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }

        await cargarPedidos();
    }

    async function cancelarPedido(idPedido) {
        try {
            const resultado = await api('actualizar_estado_pedido', { id_pedido: idPedido, estado: 'CANCELADO' });
            mostrarMensaje(resultado.message, 'exito');
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }

        await cargarPedidos();
    }


    /*-----------------------------------------------------
      HU-32 — RECIBIR PEDIDO
    -----------------------------------------------------*/

    async function abrirModalRecepcion(idPedido) {
        try {
            const resultado = await api('obtener_pedido', null, { id_pedido: idPedido });
            const pedido = resultado.data;

            $('idPedidoRecepcion').value = pedido.id_pedido;
            $('observacionesRecepcion').value = '';
            $('mensajeFormularioRecepcion').textContent = '';

            const lista = $('listaProductosRecepcion');
            const pendientes = pedido.detalle
                .map((d) => ({
                    ...d,
                    pendiente_real: d.cantidad_solicitada - d.cantidad_recibida - (d.cantidad_reservada_pendiente || 0)
                }))
                .filter((d) => d.pendiente_real > 0);

            if (!pendientes.length) {
                lista.innerHTML = '<p>Este pedido ya no tiene cantidades pendientes de recibir. Si registraste una recepción, espera a que un Encargado o Administrador la confirme.</p>';
            } else {
                lista.innerHTML = pendientes.map((d) => {
                    const pendiente = d.pendiente_real;
                    const nota = d.cantidad_reservada_pendiente > 0
                        ? `<small>${d.cantidad_reservada_pendiente} unidad(es) ya registradas, en espera de confirmación.</small>`
                        : '';
                    return `
                        <div class="fila-producto-compra" data-producto="${d.id_producto}">
                            <div class="grupo-formulario">
                                <label>Producto</label>
                                <input type="text" value="${escaparHTML(d.nombre)}" readonly>
                            </div>
                            <div class="grupo-formulario">
                                <label>Solicitado</label>
                                <input type="text" value="${d.cantidad_solicitada}" readonly>
                            </div>
                            <div class="grupo-formulario">
                                <label>Ya recibido</label>
                                <input type="text" value="${d.cantidad_recibida}" readonly>
                            </div>
                            <div class="grupo-formulario">
                                <label>Recibido ahora (máx. ${pendiente})</label>
                                <input
                                    class="item-cantidad-recepcion"
                                    type="number"
                                    min="0"
                                    max="${pendiente}"
                                    value="0"
                                >
                                ${nota}
                            </div>
                        </div>
                    `;
                }).join('');

                lista.querySelectorAll('.item-cantidad-recepcion').forEach((campo) => {
                    const maximo = Number(campo.max);

                    const validar = () => {
                        const valor = Number(campo.value);

                        if (campo.value !== '' && valor > maximo) {
                            campo.setCustomValidity(`El valor debe ser menor o igual a ${maximo}.`);
                        } else if (campo.value !== '' && valor < 0) {
                            campo.setCustomValidity('El valor debe ser mayor o igual a 0.');
                        } else if (campo.validity.stepMismatch) {
                            campo.setCustomValidity('Ingresa un número entero válido.');
                        } else {
                            campo.setCustomValidity('');
                        }
                    };

                    campo.addEventListener('input', validar);
                    campo.addEventListener('invalid', validar);
                });
            }

            $('modalRecepcion').hidden = false;
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function cerrarModalRecepcion() {
        $('modalRecepcion').hidden = true;
    }

    async function abrirModalVerPedido(idPedido) {
        try {
            const resultado = await api('obtener_pedido', null, { id_pedido: idPedido });
            const pedido = resultado.data;

            $('numeroVerPedido').value = pedido.numero_pedido;
            $('proveedorVerPedido').value = pedido.proveedor_nombre;
            $('ubicacionVerPedido').value = pedido.ubicacion_nombre;
            $('solicitaVerPedido').value = `${pedido.solicita_nombre} ${pedido.solicita_apellido || ''}`.trim();
            $('fechaVerPedido').value = new Date(pedido.fecha_pedido).toLocaleDateString('es-CR');
            $('estadoVerPedido').value = etiquetaEstado(pedido);
            $('observacionesVerPedido').value = pedido.observaciones || '';

            const lista = $('listaProductosVerPedido');
            lista.innerHTML = pedido.detalle.map((d) => `
                <div class="fila-producto-compra" data-producto="${d.id_producto}">
                    <div class="grupo-formulario">
                        <label>Producto</label>
                        <input type="text" value="${escaparHTML(d.nombre)}" readonly>
                    </div>
                    <div class="grupo-formulario">
                        <label>Solicitado</label>
                        <input type="text" value="${d.cantidad_solicitada}" readonly>
                    </div>
                    <div class="grupo-formulario">
                        <label>Recibido</label>
                        <input type="text" value="${d.cantidad_recibida}" readonly>
                    </div>
                    <div class="grupo-formulario">
                        <label>Costo unitario</label>
                        <input type="text" value="${moneda(d.costo_unitario)}" readonly>
                    </div>
                </div>
            `).join('');

            $('totalVerPedido').textContent = `Total estimado: ${moneda(pedido.monto_total)}`;

            $('modalVerPedido').hidden = false;
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function cerrarModalVerPedido() {
        $('modalVerPedido').hidden = true;
    }

    async function guardarRecepcion(evento) {
        evento.preventDefault();

        const items = [...document.querySelectorAll('#listaProductosRecepcion .fila-producto-compra')]
            .map((fila) => ({
                id_producto: Number(fila.dataset.producto),
                cantidad: Number(fila.querySelector('.item-cantidad-recepcion').value) || 0
            }))
            .filter((item) => item.cantidad > 0);

        if (!items.length) {
            $('mensajeFormularioRecepcion').textContent = 'Indica al menos una cantidad recibida mayor a cero.';
            return;
        }

        const botonGuardar = $('botonGuardarRecepcion');
        if (botonGuardar) botonGuardar.disabled = true;

        try {
            const resultado = await apiRecepciones('registrar_recepcion', {
                id_pedido: Number($('idPedidoRecepcion').value),
                items,
                observaciones: $('observacionesRecepcion').value.trim()
            });

            cerrarModalRecepcion();
            mostrarMensaje(resultado.message, 'exito');
            await cargarPedidos();

            if (puedeActualizarEstado) {
                await cargarRecepcionesPendientes();
            }
        } catch (error) {
            $('mensajeFormularioRecepcion').textContent = error.message;
        } finally {
            if (botonGuardar) botonGuardar.disabled = false;
        }
    }


    /*-----------------------------------------------------
      HU-32 — CONFIRMAR RECEPCIÓN (Encargado/Administrador)
    -----------------------------------------------------*/

    async function cargarRecepcionesPendientes() {
        try {
            const resultado = await apiRecepciones('listar_recepciones_pendientes');
            recepcionesActuales = resultado.data || [];

            mostrarElemento('panelRecepcionesPendientes', recepcionesActuales.length > 0);
            renderRecepcionesPendientes();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function renderRecepcionesPendientes() {
        const visibles = paginadorRecepciones.paginar(recepcionesActuales);

        $('cuerpoTablaRecepcionesPendientes').innerHTML = visibles.map((r) => `
                <tr>
                    <td>${r.numero_pedido}</td>
                    <td>${new Date(r.fecha_movimiento).toLocaleString('es-CR')}</td>
                    <td>${escaparHTML(r.proveedor_nombre)}</td>
                    <td>${escaparHTML(r.ubicacion_nombre)}</td>
                    <td>${r.total_productos} producto(s), ${r.total_unidades} unidad(es)</td>
                    <td>${escaparHTML(`${r.registrado_por_nombre} ${r.registrado_por_apellido || ''}`.trim())}</td>
                    <td>
                        <div class="acciones-tabla">
                            <button type="button" class="boton-accion accion-ver boton-ver-recepcion" data-movimiento="${r.id_movimiento}" title="Ver recepción" aria-label="Ver recepción del pedido ${r.numero_pedido}">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button type="button" class="boton-accion accion-exito boton-confirmar-recepcion" data-movimiento="${r.id_movimiento}" title="Confirmar recepción" aria-label="Confirmar recepción del pedido ${r.numero_pedido}">
                                <i class="fa-solid fa-check"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
    }

    async function manejarClicRecepcionesPendientes(evento) {
        const botonVer = evento.target.closest('button.boton-ver-recepcion');
        if (botonVer) {
            await abrirModalVerRecepcion(Number(botonVer.dataset.movimiento));
            return;
        }

        const botonConfirmar = evento.target.closest('button.boton-confirmar-recepcion');
        if (botonConfirmar) {
            await confirmarRecepcion(Number(botonConfirmar.dataset.movimiento));
        }
    }

    async function confirmarRecepcion(idMovimiento) {
        try {
            await apiRecepciones('confirmar_recepcion', { id_movimiento: idMovimiento });

            mostrarMensaje('Recepción confirmada correctamente.', 'exito');
            await cargarPedidos();
            await cargarRecepcionesPendientes();
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      HU-32 — VER RECEPCIÓN PENDIENTE (antes de confirmar)
    -----------------------------------------------------*/

    async function abrirModalVerRecepcion(idMovimiento) {
        try {
            const resultado = await apiRecepciones('obtener_recepcion', null, { id_movimiento: idMovimiento });
            const recepcion = resultado.data;

            $('idMovimientoVerRecepcion').value = recepcion.id_movimiento;
            $('pedidoVerRecepcion').value = recepcion.numero_pedido;
            $('proveedorVerRecepcion').value = recepcion.proveedor_nombre;
            $('ubicacionVerRecepcion').value = recepcion.ubicacion_nombre;
            $('registradoPorVerRecepcion').value = `${recepcion.registrado_por_nombre} ${recepcion.registrado_por_apellido || ''}`.trim();
            $('fechaVerRecepcion').value = new Date(recepcion.fecha_movimiento).toLocaleString('es-CR');
            $('observacionesVerRecepcion').value = recepcion.observaciones || 'Sin observaciones.';

            $('listaProductosVerRecepcion').innerHTML = (recepcion.detalle || []).map((item) => `
                <div class="fila-producto-compra">
                    <div class="grupo-formulario">
                        <label>Producto</label>
                        <input type="text" value="${escaparHTML(item.nombre)}" disabled>
                    </div>
                    <div class="grupo-formulario">
                        <label>Cantidad</label>
                        <input type="text" value="${item.cantidad}" disabled>
                    </div>
                </div>
            `).join('');

            $('modalVerRecepcion').hidden = false;
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function cerrarModalVerRecepcion() {
        $('modalVerRecepcion').hidden = true;
    }

    async function confirmarDesdeModalVerRecepcion() {
        const idMovimiento = Number($('idMovimientoVerRecepcion').value);
        if (!idMovimiento) return;

        await confirmarRecepcion(idMovimiento);
        cerrarModalVerRecepcion();
    }


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
