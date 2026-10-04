/*=========================================================
  VENDING MINI MARKET — Catálogo de productos (productos.html)
  HU-09 inactivar/activar · HU-10 búsqueda · HU-15 consultar y exportar
  Requiere: app.js y catalogo-comun.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const $ = (id) => document.getElementById(id);

    const POR_PAGINA = 25;

    /*
     * Inventario, ventas y movimientos todavía leen los productos de
     * localStorage ("productosVending"). Con esta bandera en true, el
     * catálogo real (base de datos) se copia ahí para que sigan
     * funcionando. Cuando esos módulos lean de la API, ponerla en false.
     */
    const SINCRONIZAR_MODULOS_LOCALES = true;

    const ETIQUETAS_CAMBIO = {
        nombre: 'Nombre',
        codigo_articulo_retail: 'Código interno',
        codigo_barras: 'Código de barras',
        descripcion: 'Descripción',
        categoria_nombre: 'Categoría',
        impuesto_nombre: 'Impuesto',
        estado: 'Estado'
    };

    const ACCIONES_HISTORIAL = {
        CREAR: 'Producto creado',
        EDITAR: 'Producto editado',
        INACTIVAR: 'Producto inactivado',
        ACTIVAR: 'Producto activado'
    };

    let productos = [];
    let paginaActual = 1;
    let totalPaginas = 1;
    let totalProductos = 0;
    let numeroPeticion = 0;


    /*-----------------------------------------------------
      INICIO
    -----------------------------------------------------*/

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaProductos')) {
            return;
        }

        try {
            await C.cargarSesion();
            await cargarCategorias();
            registrarEventos();
            await recargar();

            if (SINCRONIZAR_MODULOS_LOCALES) {
                sincronizarModulosLocales();
            }
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }

    async function recargar() {
        await Promise.all([cargarProductos(), cargarResumen()]);
    }


    /*-----------------------------------------------------
      FILTROS
    -----------------------------------------------------*/

    function leerFiltros() {
        return {
            busqueda: $('buscarProducto')?.value.trim() || '',
            categoria: $('filtroCategoriaProducto')?.value || '',
            estado: $('filtroEstadoProducto')?.value || ''
        };
    }

    function hayFiltros(filtros = leerFiltros()) {
        return Boolean(filtros.busqueda || filtros.categoria || filtros.estado);
    }

    async function cargarCategorias() {
        const resultado = await C.api('listar_categorias');
        const filtro = $('filtroCategoriaProducto');

        if (!filtro) {
            return;
        }

        filtro.innerHTML = '<option value="">Todas las categorías</option>';

        (resultado.data || []).forEach((categoria) => {
            const opcion = document.createElement('option');
            opcion.value = categoria.id_categoria;
            opcion.textContent = categoria.estado
                ? categoria.nombre
                : `${categoria.nombre} (inactiva)`;
            filtro.appendChild(opcion);
        });
    }


    /*-----------------------------------------------------
      CONSULTA (HU-10, HU-15)
    -----------------------------------------------------*/

    async function cargarProductos(pagina = 1) {
        const peticion = ++numeroPeticion;
        const filtros = leerFiltros();

        try {
            const resultado = await C.api('listar_productos', null, {
                ...filtros,
                pagina,
                por_pagina: POR_PAGINA
            });

            // Si el usuario siguió escribiendo, se descarta esta respuesta.
            if (peticion !== numeroPeticion) {
                return;
            }

            const datos = resultado.data;

            productos = datos.items || [];
            paginaActual = datos.pagina;
            totalPaginas = datos.total_paginas;
            totalProductos = datos.total;

            renderProductos(filtros);
            renderPaginacion();
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }

    async function cargarResumen() {
        try {
            const { data } = await C.api('resumen_productos');

            $('totalProductos').textContent = data.total;
            $('productosActivos').textContent = data.activos;
            $('productosInactivos').textContent = data.inactivos;
            $('categoriasActivas').textContent = data.categorias_activas;
        } catch (error) {
            console.error(error);
        }
    }

    function renderProductos(filtros) {
        const cuerpo = $('cuerpoTablaProductos');
        const contenedorTabla = $('contenedorTablaProductos');
        const sinProductos = $('mensajeSinProductos');
        const sinResultados = $('mensajeSinResultadosProductos');
        const admin = C.esAdmin();

        cuerpo.innerHTML = '';

        const vacio = productos.length === 0;
        const conFiltros = hayFiltros(filtros);

        contenedorTabla.style.display = vacio ? 'none' : '';
        sinProductos.hidden = !(vacio && !conFiltros);
        sinResultados.hidden = !(vacio && conFiltros);

        const desde = (paginaActual - 1) * POR_PAGINA + 1;
        const hasta = desde + productos.length - 1;

        $('resultadoBusquedaProductos').textContent = vacio
            ? ''
            : `Mostrando ${desde}–${hasta} de ${totalProductos} producto(s).`;

        productos.forEach((producto) => {
            const fila = document.createElement('tr');
            const nombre = C.escapeHtml(producto.nombre);

            const botonesAdmin = admin
                ? `
                    <a href="producto-nuevo.html?id=${producto.id_producto}"
                       class="boton-accion-producto boton-editar-producto"
                       title="Editar producto" aria-label="Editar ${nombre}">
                        <i class="fa-solid fa-pen"></i>
                    </a>
                    ${producto.estado
                        ? `<button type="button"
                                   class="boton-accion-producto boton-inactivar-producto"
                                   data-accion="inactivar" data-id="${producto.id_producto}"
                                   title="Inactivar producto" aria-label="Inactivar ${nombre}">
                                <i class="fa-solid fa-ban"></i>
                           </button>`
                        : `<button type="button"
                                   class="boton-accion-producto boton-activar-producto"
                                   data-accion="activar" data-id="${producto.id_producto}"
                                   title="Activar producto" aria-label="Activar ${nombre}">
                                <i class="fa-solid fa-circle-check"></i>
                           </button>`}
                  `
                : '';

            fila.innerHTML = `
                <td>
                    <strong>${nombre}</strong>
                    ${producto.descripcion
                        ? `<br><small>${C.escapeHtml(producto.descripcion)}</small>`
                        : ''}
                </td>
                <td>
                    ${C.escapeHtml(producto.codigo_articulo_retail || '—')}
                    <br><small>${C.escapeHtml(producto.codigo_barras || '—')}</small>
                </td>
                <td>${C.escapeHtml(producto.categoria_nombre)}</td>
                <td>${C.escapeHtml(producto.impuesto_nombre)}</td>
                <td>${C.escapeHtml(producto.proveedores_texto || 'Sin proveedor')}</td>
                <td>
                    <span class="insignia ${producto.estado ? 'insignia-verde' : 'insignia-rojo'}">
                        ${producto.estado ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <div class="acciones-producto-tabla">
                        <button type="button"
                                class="boton-accion-producto boton-historial-producto"
                                data-accion="historial" data-id="${producto.id_producto}"
                                title="Ver historial" aria-label="Ver historial de ${nombre}">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </button>
                        ${botonesAdmin}
                    </div>
                </td>
            `;

            cuerpo.appendChild(fila);
        });
    }

    function renderPaginacion() {
        const caja = $('paginacionProductos');

        if (!caja) {
            return;
        }

        caja.hidden = totalPaginas <= 1;

        $('textoPaginacionProductos').textContent = `Página ${paginaActual} de ${totalPaginas}`;
        $('botonPaginaAnterior').disabled = paginaActual <= 1;
        $('botonPaginaSiguiente').disabled = paginaActual >= totalPaginas;
    }


    /*-----------------------------------------------------
      EVENTOS
    -----------------------------------------------------*/

    function registrarEventos() {
        $('buscarProducto')?.addEventListener('input', C.debounce(() => cargarProductos(1), 300));
        $('filtroCategoriaProducto')?.addEventListener('change', () => cargarProductos(1));
        $('filtroEstadoProducto')?.addEventListener('change', () => cargarProductos(1));

        $('botonLimpiarFiltrosProductos')?.addEventListener('click', () => {
            $('buscarProducto').value = '';
            $('filtroCategoriaProducto').value = '';
            $('filtroEstadoProducto').value = '';
            cargarProductos(1);
            $('buscarProducto').focus();
        });

        $('botonPaginaAnterior')?.addEventListener('click', () => cargarProductos(paginaActual - 1));
        $('botonPaginaSiguiente')?.addEventListener('click', () => cargarProductos(paginaActual + 1));

        $('botonExportarCatalogo')?.addEventListener('click', exportarCatalogo);

        $('cuerpoTablaProductos').addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion]');

            if (!boton) {
                return;
            }

            const id = Number(boton.dataset.id);

            if (boton.dataset.accion === 'inactivar') {
                cambiarEstado(id, false);
            } else if (boton.dataset.accion === 'activar') {
                cambiarEstado(id, true);
            } else if (boton.dataset.accion === 'historial') {
                verHistorial(id);
            }
        });
    }


    /*-----------------------------------------------------
      EXPORTAR (HU-15) — todos los productos que coinciden con
      los filtros activos, no solo la página visible
    -----------------------------------------------------*/

    function exportarCatalogo() {
        window.location.href = C.urlApi('exportar_productos', leerFiltros());
    }


    /*-----------------------------------------------------
      ACTIVAR / INACTIVAR (HU-09) — nunca se borra
    -----------------------------------------------------*/

    async function cambiarEstado(id, activar) {
        const producto = productos.find((p) => p.id_producto === id);

        if (!producto) {
            return;
        }

        const confirmado = await C.confirmar({
            titulo: activar ? 'Activar producto' : 'Inactivar producto',
            texto: activar
                ? `"${producto.nombre}" volverá a estar disponible en el catálogo.`
                : `"${producto.nombre}" dejará de estar disponible, pero su historial se conserva.`,
            textoConfirmar: activar ? 'Activar' : 'Inactivar',
            peligro: !activar
        });

        if (!confirmado) {
            return;
        }

        try {
            const resultado = await C.api(
                activar ? 'activar_producto' : 'inactivar_producto',
                { id_producto: id }
            );

            C.mostrarMensaje(resultado.message, 'exito');
            await Promise.all([cargarProductos(paginaActual), cargarResumen()]);
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      HISTORIAL (HU-09) — viene de bitacora_auditoria
    -----------------------------------------------------*/

    function formatearValor(clave, valor) {
        if (valor === null || valor === undefined || valor === '') {
            return '—';
        }

        if (clave === 'estado') {
            return valor ? 'Activo' : 'Inactivo';
        }

        return String(valor);
    }

    function textoProveedores(foto) {
        return (foto?.proveedores || [])
            .map((p) => (p.costo !== null && p.costo !== undefined
                ? `${p.nombre} (${C.moneda(p.costo)})`
                : p.nombre))
            .sort()
            .join(', ');
    }

    function cambiosEntre(antes, despues) {
        if (!antes || !despues) {
            return [];
        }

        const cambios = Object.keys(ETIQUETAS_CAMBIO)
            .filter((clave) => String(antes[clave] ?? '') !== String(despues[clave] ?? ''))
            .map((clave) => `${ETIQUETAS_CAMBIO[clave]}: ${formatearValor(clave, antes[clave])} → ${formatearValor(clave, despues[clave])}`);

        const provAntes = textoProveedores(antes);
        const provDespues = textoProveedores(despues);

        if (provAntes !== provDespues) {
            cambios.push(`Proveedores: ${provAntes || '—'} → ${provDespues || '—'}`);
        }

        return cambios;
    }

    async function verHistorial(id) {
        const producto = productos.find((p) => p.id_producto === id);

        try {
            const { data } = await C.api('historial_producto', null, { id });
            const contenedor = document.createElement('div');

            if (!data.length) {
                contenedor.innerHTML = '<p>Este producto todavía no tiene movimientos registrados.</p>';
            } else {
                const lista = document.createElement('ul');
                lista.className = 'lista-historial';

                data.forEach((registro) => {
                    const elemento = document.createElement('li');
                    const cambios = registro.accion === 'EDITAR'
                        ? cambiosEntre(registro.antes, registro.despues)
                        : [];

                    elemento.innerHTML = `
                        <strong>${C.escapeHtml(ACCIONES_HISTORIAL[registro.accion] || registro.accion)}</strong>
                        <small>
                            ${C.escapeHtml(C.fechaHora(registro.fecha))}
                            ${registro.usuario ? ' · ' + C.escapeHtml(registro.usuario) : ''}
                        </small>
                        ${cambios.length
                            ? `<ul>${cambios.map((c) => `<li>${C.escapeHtml(c)}</li>`).join('')}</ul>`
                            : ''}
                    `;

                    lista.appendChild(elemento);
                });

                contenedor.appendChild(lista);
            }

            C.mostrarDialogo(`Historial: ${producto ? producto.nombre : 'producto'}`, contenedor);
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      COMPATIBILIDAD CON MÓDULOS QUE USAN localStorage
    -----------------------------------------------------*/

    async function sincronizarModulosLocales() {
        try {
            const { data } = await C.api('productos_para_modulos');

            const previos = JSON.parse(localStorage.getItem('productosVending') || '[]');
            const porId = new Map(
                (Array.isArray(previos) ? previos : []).map((p) => [String(p.id), p])
            );

            const nuevos = data.map((p) => {
                const previo = porId.get(String(p.id_producto)) || {};

                return {
                    ...previo,
                    id: String(p.id_producto),
                    codigo: p.codigo_articulo_retail || '',
                    nombre: p.nombre,
                    categoria: p.categoria_nombre || '',
                    impuesto: Number(p.impuesto_porcentaje),
                    estado: p.estado ? 'Activo' : 'Inactivo',
                    stock: Number(previo.stock) || 0,
                    stockMinimo: Number(previo.stockMinimo) || 0,
                    precioVenta: Number(previo.precioVenta) || 0,
                    ubicacion: previo.ubicacion || ''
                };
            });

            localStorage.setItem('productosVending', JSON.stringify(nuevos));
        } catch (error) {
            console.warn('No se pudo sincronizar con los módulos locales:', error);
        }
    }
})();
