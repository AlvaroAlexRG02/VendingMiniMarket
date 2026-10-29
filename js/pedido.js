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
    let proveedores = [];
    let ubicaciones = [];
    let productosProveedorActual = [];
    let puedeActualizarEstado = false;
    let puedeEditarPedido = false;
    let pedidoEnEdicion = null;

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaPedidos')) {
            return;
        }

        puedeActualizarEstado = document.body.dataset.puedeActualizarEstado === '1';
        puedeEditarPedido = document.body.dataset.puedeEditarPedido === '1';

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
                await api('editar_pedido', { ...datos, id_pedido: pedidoEnEdicion });
                cerrarModal();
                ocultarMensaje();
                mostrarMensaje('Pedido actualizado correctamente.', 'exito');
            } else {
                await api('crear_pedido', datos);
                cerrarModal();
                ocultarMensaje();
                mostrarMensaje('Pedido registrado correctamente.', 'exito');
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
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    }

    function renderPedidos() {
        const tabla = $('cuerpoTablaPedidos');

        tabla.innerHTML = pedidos.map((p) => `
            <tr>
                <td>${p.numero_pedido}</td>
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
                    ${p.requiere_aprobacion ? '<span class="badge badge-morado">Por aprobar</span>' : ''}
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
        const cerrado = ['RECIBIDO', 'CANCELADO'].includes(pedido.estado);

        const botonVer = `<button type="button" class="boton-tabla boton-ver-pedido" data-pedido="${pedido.id_pedido}" title="Ver pedido">
                   <i class="fa-solid fa-eye"></i>
               </button>`;

        const botonEditar = (puedeEditarPedido && pedido.estado === 'PENDIENTE')
            ? `<button type="button" class="boton-tabla boton-editar-pedido" data-pedido="${pedido.id_pedido}" title="Editar pedido">
                   <i class="fa-solid fa-pen"></i>
               </button>`
            : '';

        const botonRecibir = cerrado
            ? ''
            : `<button type="button" class="boton-tabla boton-recibir-pedido" data-pedido="${pedido.id_pedido}" title="Recibir pedido">
                   <i class="fa-solid fa-truck-ramp-box"></i>
               </button>`;

        if (!puedeActualizarEstado) {
            return `<div class="acciones-tabla">${botonVer}${botonEditar}${botonRecibir}</div>`;
        }

        if (cerrado) {
            return `<div class="acciones-tabla">${botonVer}</div>`;
        }

        const opciones = Object.entries(ETIQUETAS_ESTADO)
            .map(([valor, etiqueta]) => `<option value="${valor}" ${valor === pedido.estado ? 'selected' : ''}>${etiqueta}</option>`)
            .join('');

        return `
            <div class="acciones-tabla">
                ${botonVer}
                ${botonEditar}
                ${botonRecibir}
                <select class="selector-estado-pedido" data-pedido="${pedido.id_pedido}">
                    ${opciones}
                </select>
            </div>
        `;
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

        const boton = evento.target.closest('button.boton-recibir-pedido');
        if (!boton) return;

        await abrirModalRecepcion(Number(boton.dataset.pedido));
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
            $('estadoVerPedido').value = ETIQUETAS_ESTADO[pedido.estado] || pedido.estado;
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
        }
    }


    /*-----------------------------------------------------
      HU-32 — CONFIRMAR RECEPCIÓN (Encargado/Administrador)
    -----------------------------------------------------*/

    async function cargarRecepcionesPendientes() {
        try {
            const resultado = await apiRecepciones('listar_recepciones_pendientes');
            const pendientes = resultado.data || [];

            mostrarElemento('panelRecepcionesPendientes', pendientes.length > 0);

            $('cuerpoTablaRecepcionesPendientes').innerHTML = pendientes.map((r) => `
                <tr>
                    <td>${r.numero_pedido}</td>
                    <td>${new Date(r.fecha_movimiento).toLocaleString('es-CR')}</td>
                    <td>${escaparHTML(r.proveedor_nombre)}</td>
                    <td>${escaparHTML(r.ubicacion_nombre)}</td>
                    <td>${escaparHTML(`${r.registrado_por_nombre} ${r.registrado_por_apellido || ''}`.trim())}</td>
                    <td>
                        <div class="acciones-tabla">
                            <button type="button" class="boton boton-borde boton-tabla boton-ver-recepcion" data-movimiento="${r.id_movimiento}">
                                Ver
                            </button>
                            <button type="button" class="boton boton-azul boton-tabla boton-confirmar-recepcion" data-movimiento="${r.id_movimiento}">
                                Confirmar
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
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
