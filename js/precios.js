/*=========================================================
  VENDING MINI MARKET — Precios por tienda (precios.php)
  HU-37 IVA y márgenes · HU-38 cálculo · HU-39 precio por tienda
  HU-40 simulación e historial
  Requiere: app.js y catalogo-comun.js cargados antes.
=========================================================*/


(() => {
    'use strict';

    const C = window.CatalogoComun;
    const $ = (id) => document.getElementById(id);

    const API = '../api/catalogo/precios.php';
    const PAGINA_LOGIN = '../index/index.html';

    let catalogos = { productos: [], tiendas: [], margenes: [], impuestos: [] };
    let categorias = [];
    let margenesTodos = [];
    let costoManual = false;
    let numeroPeticionPrecios = 0;




    /*-----------------------------------------------------
      API
    -----------------------------------------------------*/


    async function llamar(accion, datos = null, parametros = {}) {
        const url = new URL(API, window.location.href);
        url.searchParams.set('accion', accion);

        Object.entries(parametros).forEach(([clave, valor]) => {
            if (valor !== undefined && valor !== null && valor !== '') {
                url.searchParams.set(clave, valor);
            }
        });

        const opciones = {
            method: datos ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        };

        if (datos) {
            opciones.headers['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(datos);
        }

        let respuesta;

        try {
            respuesta = await fetch(url.toString(), opciones);
        } catch (error) {
            throw new Error('No fue posible comunicarse con el servidor.');
        }

        let resultado;

        try {
            resultado = await respuesta.json();
        } catch (error) {
            throw new Error('El servidor devolvió una respuesta no válida. Revise XAMPP y los registros de error de PHP.');
        }

        if (respuesta.status === 401) {
            window.location.href = PAGINA_LOGIN;
            throw new Error(resultado.message || 'Debe iniciar sesión.');
        }

        if (!respuesta.ok || !resultado.success) {
            const error = new Error(resultado.message || 'Ocurrió un error.');
            error.data = resultado.data;
            throw error;
        }

        return resultado;
    }




    /*-----------------------------------------------------
      UTILIDADES DE INTERFAZ
    -----------------------------------------------------*/


    const porcentaje = (valor) => `${Number(valor ?? 0)}%`;

    function nombreTienda(tienda) {
        return tienda.nombre || tienda.nombre_tienda || tienda.descripcion || `Tienda ${tienda.id_tienda}`;
    }

    function etiquetaTienda(id) {
        const tienda = catalogos.tiendas.find((t) => Number(t.id_tienda) === Number(id));
        return tienda ? nombreTienda(tienda) : `Tienda ${id}`;
    }

    /** Llena un <select> conservando la selección anterior si sigue existiendo. */
    function llenar(select, items, valor, texto, placeholder) {
        const previo = select.value;

        select.replaceChildren();

        if (placeholder !== null) {
            select.add(new Option(placeholder, ''));
        }

        items.forEach((item) => select.add(new Option(texto(item), String(valor(item)))));

        if (previo && Array.from(select.options).some((o) => o.value === previo)) {
            select.value = previo;
        }
    }

    function celda(fila, valor, clase) {
        const td = document.createElement('td');
        td.textContent = valor ?? '';

        if (clase) {
            td.className = clase;
        }

        fila.appendChild(td);
        return td;
    }

    function boton(contenidoHtml, titulo, clase, alHacerClic) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = clase;
        b.title = titulo;
        b.setAttribute('aria-label', titulo);
        b.innerHTML = contenidoHtml;
        b.addEventListener('click', alHacerClic);
        return b;
    }

    function celdaAcciones(fila, botones) {
        const td = document.createElement('td');
        const caja = document.createElement('div');
        caja.className = 'acciones-producto-tabla';
        botones.filter(Boolean).forEach((b) => caja.appendChild(b));
        td.appendChild(caja);
        fila.appendChild(td);
    }

    function fechaCorta(iso) {
        return iso ? C.fechaHora(iso) : '—';
    }

    function dinero(valor) {
        return valor === null || valor === undefined ? '—' : C.moneda(valor);
    }

    async function ejecutar(accion) {
        try {
            await accion();
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }




    /*-----------------------------------------------------
      INICIO
    -----------------------------------------------------*/


    document.addEventListener('DOMContentLoaded', iniciar);


    async function iniciar() {
        if (!$('selProducto')) {
            return;
        }

        try {
            await C.cargarSesion();
            await Promise.all([cargarCatalogos(), cargarMargenes()]);
            registrarEventos();
            await cargarPrecios();
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }


    async function cargarCatalogos() {
        const [{ data }, resultadoCategorias] = await Promise.all([
            llamar('catalogos'),
            C.api('listar_categorias')
        ]);

        catalogos = data;
        categorias = (resultadoCategorias.data || []).filter((c) => c.estado);

        const textoProducto = (p) => `${p.nombre} (${p.codigo_articulo_retail || 'sin código'})`;
        const textoMargen = (m) => `${m.nombre} (${porcentaje(m.porcentaje)})`;

        llenar($('selProducto'), catalogos.productos, (p) => p.id_producto, textoProducto, 'Seleccione un producto');
        llenar($('selProductoIva'), catalogos.productos, (p) => p.id_producto, textoProducto, 'Seleccione un producto');
        llenar($('selImpuestoIva'), catalogos.impuestos, (i) => i.id_impuesto,
            (i) => `${i.nombre} (${porcentaje(i.porcentaje)})`, 'Seleccione un impuesto');

        llenar($('selTienda'), catalogos.tiendas, (t) => t.id_tienda, nombreTienda, 'Seleccione una tienda');
        llenar($('masTienda'), catalogos.tiendas, (t) => t.id_tienda, nombreTienda, 'Seleccione una tienda');
        llenar($('filtroTienda'), catalogos.tiendas, (t) => t.id_tienda, nombreTienda, 'Todas las tiendas');

        llenar($('selMargen'), catalogos.margenes, (m) => m.id_margen, textoMargen, 'Seleccione un margen');
        llenar($('masMargen'), catalogos.margenes, (m) => m.id_margen, textoMargen, 'Seleccione un margen');
        llenar($('masCategoria'), categorias, (c) => c.id_categoria, (c) => c.nombre, 'Todas las categorías');

        renderChecksMargenes();
    }

    function renderChecksMargenes() {
        const caja = $('listaMargenesSimulacion');
        const primera = caja.children.length === 0;
        const marcados = new Set(Array.from(caja.querySelectorAll('input:checked')).map((i) => i.value));

        caja.replaceChildren();

        catalogos.margenes.forEach((margen) => {
            const etiqueta = document.createElement('label');
            const casilla = document.createElement('input');

            casilla.type = 'checkbox';
            casilla.value = String(margen.id_margen);
            casilla.checked = primera || marcados.has(casilla.value);

            etiqueta.append(casilla, ` ${margen.nombre} (${porcentaje(margen.porcentaje)})`);
            caja.appendChild(etiqueta);
        });
    }




    /*-----------------------------------------------------
      EVENTOS
    -----------------------------------------------------*/


    function registrarEventos() {
        $('selProducto').addEventListener('change', () => { costoManual = false; ejecutar(cargarContexto); });
        $('selTienda').addEventListener('change', () => { costoManual = false; ejecutar(cargarContexto); });
        $('inputCosto').addEventListener('input', () => { costoManual = true; });

        $('botonCalcular').addEventListener('click', () => ejecutar(calcular));
        $('botonAplicar').addEventListener('click', () => ejecutar(aplicar));
        $('botonSimular').addEventListener('click', () => ejecutar(simular));
        $('botonHistorialSeleccion').addEventListener('click', () => ejecutar(historialDeSeleccion));

        $('formularioMargen').addEventListener('submit', (e) => { e.preventDefault(); ejecutar(guardarMargen); });
        $('botonCancelarMargen').addEventListener('click', salirDeEdicionMargen);

        $('formularioIva').addEventListener('submit', (e) => { e.preventDefault(); ejecutar(guardarIva); });
        $('selProductoIva').addEventListener('change', preseleccionarIva);

        $('botonVistaPrevia').addEventListener('click', () => ejecutar(() => margenMasivo(true)));
        $('botonAplicarMasivo').addEventListener('click', () => ejecutar(() => margenMasivo(false)));

        $('filtroTienda').addEventListener('change', () => ejecutar(cargarPrecios));
        $('filtroBusqueda').addEventListener('input', C.debounce(() => ejecutar(cargarPrecios), 300));
    }




    /*-----------------------------------------------------
      CALCULAR, APLICAR Y SIMULAR (HU-38, HU-39, HU-40)
    -----------------------------------------------------*/


    function leerSeleccion() {
        return {
            id_producto: Number($('selProducto').value),
            id_tienda: Number($('selTienda').value),
            id_margen: Number($('selMargen').value),
            costo: $('inputCosto').value
        };
    }

    function validarSeleccion(s, { margen = true, costo = true } = {}) {
        if (!s.id_producto || !s.id_tienda) {
            throw new Error('Seleccione un producto y una tienda.');
        }

        if (margen && !s.id_margen) {
            throw new Error('Seleccione un margen.');
        }

        if (costo && (s.costo === '' || Number(s.costo) < 0)) {
            throw new Error('Ingrese un costo válido (0 o más).');
        }
    }

    async function cargarContexto() {
        const idProducto = Number($('selProducto').value);
        const idTienda = Number($('selTienda').value);
        const info = $('infoContexto');

        if (!idProducto) {
            info.textContent = '';
            return;
        }

        const { data } = await llamar('contexto', null, { id_producto: idProducto, id_tienda: idTienda || '' });
        const partes = [`IVA del producto: ${data.impuesto_nombre} (${porcentaje(data.impuesto_porcentaje)}).`];

        if (data.precio_activo) {
            partes.push(`Precio activo en esta tienda: ${C.moneda(data.precio_activo.precio_venta)} (costo ${C.moneda(data.precio_activo.costo)}).`);
        } else if (idTienda) {
            partes.push('Todavía no tiene precio activo en esta tienda.');
        }

        if (data.costos_proveedor.length) {
            partes.push('Costo por proveedor: ' + data.costos_proveedor
                .map((p) => `${p.nombre} ${dinero(p.costo)}`)
                .join(', ') + '.');
        }

        info.textContent = partes.join(' ');

        if (!costoManual) {
            const conCosto = data.costos_proveedor.find((p) => p.costo !== null);
            const sugerido = data.precio_activo ? data.precio_activo.costo : (conCosto ? conCosto.costo : '');
            $('inputCosto').value = sugerido === '' ? '' : String(sugerido);
        }
    }

    function mostrarCalculo(datos, titulo) {
        const caja = $('resultadoCalculo');
        const lineas = [
            titulo,
            `Costo: ${C.moneda(datos.costo)}`,
            `Margen: ${datos.margen_nombre || ''} (${porcentaje(datos.margen_porcentaje)})`,
            `IVA: ${porcentaje(datos.porcentaje_iva)}`,
            `Costo con IVA: ${C.moneda(datos.costo_con_impuesto)}`,
            `Precio con margen: ${C.moneda(datos.precio_calculado)}`,
            `Precio de venta: ${C.moneda(datos.precio_venta)}`
        ];

        if (datos.precio_actual !== undefined && datos.precio_actual !== null) {
            lineas.push(`Precio activo actual: ${C.moneda(datos.precio_actual)}`);
        }

        caja.textContent = lineas.join('\n');
        caja.hidden = false;
    }

    async function calcular() {
        const seleccion = leerSeleccion();
        validarSeleccion(seleccion);

        const { data } = await llamar('calcular', seleccion);

        mostrarCalculo(data, 'Cálculo (todavía no se aplicó):');
        C.mostrarMensaje('Precio calculado. El precio activo no cambió.');
    }

    async function aplicar() {
        const seleccion = leerSeleccion();
        validarSeleccion(seleccion);

        const confirmado = await C.confirmar({
            titulo: 'Aplicar precio',
            texto: 'Esto reemplaza el precio activo de este producto en la tienda seleccionada. El precio anterior queda en el historial.',
            textoConfirmar: 'Aplicar'
        });

        if (!confirmado) {
            return;
        }

        const { data, message } = await llamar('aplicar', seleccion);

        mostrarCalculo(data, 'Precio aplicado:');
        C.mostrarMensaje(message);

        await Promise.all([cargarPrecios(), cargarContexto()]);
        await cargarHistorial(seleccion.id_producto, seleccion.id_tienda);
    }

    async function simular() {
        const seleccion = leerSeleccion();
        validarSeleccion(seleccion, { margen: false, costo: false });

        const ids = Array.from($('listaMargenesSimulacion').querySelectorAll('input:checked'))
            .map((i) => Number(i.value));

        if (!ids.length) {
            throw new Error('Marque al menos un margen para simular.');
        }

        const { data, message } = await llamar('simular', {
            id_producto: seleccion.id_producto,
            id_tienda: seleccion.id_tienda,
            costo: seleccion.costo,
            id_margenes: ids,
            guardar: true
        });

        renderEscenarios(data);
        C.mostrarMensaje(message);

        await cargarSimulaciones(seleccion.id_producto, seleccion.id_tienda);
    }

    function renderEscenarios(data) {
        const cuerpo = $('cuerpoEscenarios');
        const admin = C.esAdmin();

        cuerpo.replaceChildren();

        data.escenarios.forEach((e) => {
            const fila = document.createElement('tr');
            const diferencia = e.diferencia_actual;

            celda(fila, `${e.margen_nombre} (${porcentaje(e.margen_porcentaje)})`);
            celda(fila, porcentaje(e.porcentaje_iva), 'celda-numero');
            celda(fila, C.moneda(e.costo_con_impuesto), 'celda-numero');
            celda(fila, C.moneda(e.precio_venta), 'celda-numero');
            celda(fila, C.moneda(e.ganancia_unitaria), 'celda-numero');
            celda(
                fila,
                diferencia === null
                    ? 'Sin precio activo'
                    : `${diferencia > 0 ? '+' : ''}${C.moneda(diferencia)}`,
                'celda-numero'
            );

            celdaAcciones(fila, [
                admin && e.id_simulacion
                    ? boton('<i class="fa-solid fa-check"></i>', 'Aplicar este escenario', 'boton-accion-producto boton-activar-producto',
                        () => ejecutar(() => aplicarSimulacion(e.id_simulacion, e.margen_nombre)))
                    : null
            ]);

            cuerpo.appendChild(fila);
        });

        $('contenedorEscenarios').hidden = false;
    }

    async function aplicarSimulacion(idSimulacion, nombreMargen) {
        const confirmado = await C.confirmar({
            titulo: 'Aplicar escenario',
            texto: `Se aplicará el escenario «${nombreMargen}» como precio activo. El precio anterior queda en el historial.`,
            textoConfirmar: 'Aplicar'
        });

        if (!confirmado) {
            return;
        }

        const { data, message } = await llamar('aplicar_simulacion', { id_simulacion: idSimulacion });

        C.mostrarMensaje(message);
        $('contenedorEscenarios').hidden = true;

        await Promise.all([cargarPrecios(), cargarContexto()]);
        await cargarHistorial(data.id_producto, data.id_tienda);
    }




    /*-----------------------------------------------------
      MÁRGENES (HU-37)
    -----------------------------------------------------*/


    async function cargarMargenes() {
        const { data } = await llamar('listar_margenes');
        margenesTodos = data || [];
        renderMargenes();
    }

    function renderMargenes() {
        const cuerpo = $('cuerpoMargenes');
        cuerpo.replaceChildren();

        margenesTodos.forEach((margen) => {
            const fila = document.createElement('tr');
            const etiqueta = document.createElement('td');
            const insignia = document.createElement('span');

            celda(fila, margen.nombre);
            celda(fila, porcentaje(margen.porcentaje), 'celda-numero');
            celda(fila, margen.descripcion || '—');
            celda(fila, margen.precios_activos, 'celda-numero');

            insignia.className = `insignia ${margen.estado ? 'insignia-verde' : 'insignia-rojo'}`;
            insignia.textContent = margen.estado ? 'Activo' : 'Inactivo';
            etiqueta.appendChild(insignia);
            fila.appendChild(etiqueta);

            celdaAcciones(fila, [
                boton('<i class="fa-solid fa-pen"></i>', `Editar ${margen.nombre}`, 'boton-accion-producto boton-editar-producto',
                    () => entrarEnEdicionMargen(margen)),
                margen.estado
                    ? boton('<i class="fa-solid fa-ban"></i>', `Desactivar ${margen.nombre}`, 'boton-accion-producto boton-inactivar-producto',
                        () => ejecutar(() => cambiarEstadoMargen(margen, false)))
                    : boton('<i class="fa-solid fa-circle-check"></i>', `Activar ${margen.nombre}`, 'boton-accion-producto boton-activar-producto',
                        () => ejecutar(() => cambiarEstadoMargen(margen, true)))
            ]);

            cuerpo.appendChild(fila);
        });
    }

    function entrarEnEdicionMargen(margen) {
        $('margenId').value = margen.id_margen;
        $('margenNombre').value = margen.nombre;
        $('margenPorcentaje').value = margen.porcentaje;
        $('margenDescripcion').value = margen.descripcion || '';
        $('tituloFormularioMargen').textContent = 'Editar margen';
        $('botonGuardarMargen').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        $('botonCancelarMargen').hidden = false;

        C.limpiarMensaje();
        $('margenNombre').focus();
        $('formularioMargen').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function salirDeEdicionMargen() {
        $('formularioMargen').reset();
        $('margenId').value = '';
        $('tituloFormularioMargen').textContent = 'Márgenes de ganancia';
        $('botonGuardarMargen').innerHTML = '<i class="fa-solid fa-plus"></i> Crear margen';
        $('botonCancelarMargen').hidden = true;
    }

    async function guardarMargen() {
        const boton = $('botonGuardarMargen');
        boton.disabled = true;

        try {
            const { message } = await llamar('guardar_margen', {
                id_margen: Number($('margenId').value) || 0,
                nombre: $('margenNombre').value.trim(),
                porcentaje: $('margenPorcentaje').value,
                descripcion: $('margenDescripcion').value.trim()
            });

            C.mostrarMensaje(message);
            salirDeEdicionMargen();
            await Promise.all([cargarMargenes(), cargarCatalogos()]);
        } finally {
            boton.disabled = false;
        }
    }

    async function cambiarEstadoMargen(margen, activar) {
        const confirmado = await C.confirmar({
            titulo: activar ? 'Activar margen' : 'Desactivar margen',
            texto: activar
                ? `«${margen.nombre}» volverá a poder elegirse en cálculos nuevos.`
                : `«${margen.nombre}» no se podrá elegir en cálculos nuevos. Los ${margen.precios_activos} precio(s) activo(s) que ya lo usan se conservan.`,
            textoConfirmar: activar ? 'Activar' : 'Desactivar',
            peligro: !activar
        });

        if (!confirmado) {
            return;
        }

        const { message } = await llamar('estado_margen', { id_margen: margen.id_margen, activar });

        C.mostrarMensaje(message);
        await Promise.all([cargarMargenes(), cargarCatalogos()]);
    }




    /*-----------------------------------------------------
      IVA POR PRODUCTO (HU-37)
    -----------------------------------------------------*/


    function preseleccionarIva() {
        const producto = catalogos.productos.find((p) => String(p.id_producto) === $('selProductoIva').value);

        if (producto) {
            $('selImpuestoIva').value = String(producto.id_impuesto);
        }
    }

    async function guardarIva() {
        const idProducto = Number($('selProductoIva').value);
        const idImpuesto = Number($('selImpuestoIva').value);

        if (!idProducto || !idImpuesto) {
            throw new Error('Seleccione un producto y un impuesto.');
        }

        const { message } = await llamar('configurar_iva', { id_producto: idProducto, id_impuesto: idImpuesto });

        C.mostrarMensaje(message);
        await cargarCatalogos();
        await cargarPrecios();

        if (Number($('selProducto').value) === idProducto) {
            await cargarContexto();
        }
    }




    /*-----------------------------------------------------
      MARGEN POR TIENDA (HU-37)
    -----------------------------------------------------*/


    async function margenMasivo(vistaPrevia) {
        const datos = {
            id_tienda: Number($('masTienda').value),
            id_margen: Number($('masMargen').value),
            id_categoria: Number($('masCategoria').value) || 0,
            vista_previa: vistaPrevia
        };

        if (!datos.id_tienda || !datos.id_margen) {
            throw new Error('Seleccione la tienda y el margen.');
        }

        if (!vistaPrevia) {
            const confirmado = await C.confirmar({
                titulo: 'Aplicar margen a la tienda',
                texto: 'Se recalcularán todos los precios activos que coincidan, conservando su costo. Los precios anteriores quedan en el historial. Revise antes la vista previa.',
                textoConfirmar: 'Aplicar a la tienda'
            });

            if (!confirmado) {
                return;
            }
        }

        const { data, message } = await llamar('margen_masivo', datos);
        const cuerpo = $('cuerpoMasivo');

        cuerpo.replaceChildren();

        data.items.forEach((item) => {
            const fila = document.createElement('tr');

            celda(fila, item.nombre);
            celda(fila, C.moneda(item.costo), 'celda-numero');
            celda(fila, C.moneda(item.precio_actual), 'celda-numero');
            celda(fila, C.moneda(item.precio_nuevo), 'celda-numero');
            cuerpo.appendChild(fila);
        });

        $('contenedorMasivo').hidden = false;
        $('resumenMasivo').textContent = `${message} (${data.total} producto(s))`;

        if (!vistaPrevia) {
            C.mostrarMensaje(message);
            await cargarPrecios();
        }
    }




    /*-----------------------------------------------------
      PRECIOS ACTIVOS (HU-39)
    -----------------------------------------------------*/


    async function cargarPrecios() {
        const peticion = ++numeroPeticionPrecios;

        const { data } = await llamar('listar', null, {
            id_tienda: $('filtroTienda').value,
            busqueda: $('filtroBusqueda').value.trim()
        });

        // Si el usuario siguió escribiendo, se descarta esta respuesta.
        if (peticion !== numeroPeticionPrecios) {
            return;
        }

        const cuerpo = $('cuerpoPrecios');
        const admin = C.esAdmin();

        cuerpo.replaceChildren();

        $('resumenPrecios').textContent = data.length
            ? `${data.length} precio(s) activo(s)${data.length === 500 ? ' (se muestran los primeros 500; use los filtros)' : ''}.`
            : 'No hay precios activos con estos filtros.';

        data.forEach((p) => {
            const fila = document.createElement('tr');
            const primera = document.createElement('td');
            const nombre = document.createElement('strong');

            nombre.textContent = p.nombre;
            primera.appendChild(nombre);

            if (p.codigo_articulo_retail) {
                const codigo = document.createElement('small');
                codigo.textContent = p.codigo_articulo_retail;
                primera.append(document.createElement('br'), codigo);
            }

            fila.appendChild(primera);

            celda(fila, etiquetaTienda(p.id_tienda));
            celda(fila, C.moneda(p.costo), 'celda-numero');
            celda(fila, C.moneda(p.costo_con_impuesto), 'celda-numero');
            celda(fila, C.moneda(p.precio_venta), 'celda-numero');
            celda(fila, porcentaje(p.iva_actual), 'celda-numero');
            celda(fila, `${p.margen_nombre} (${porcentaje(p.margen_porcentaje)})`);
            celda(fila, fechaCorta(p.fecha_inicio));

            celdaAcciones(fila, [
                boton('<i class="fa-solid fa-clock-rotate-left"></i>', `Ver historial de ${p.nombre}`,
                    'boton-accion-producto boton-historial-producto',
                    () => ejecutar(() => cargarHistorial(p.id_producto, p.id_tienda, true))),
                admin
                    ? boton('<i class="fa-solid fa-rotate"></i>', `Recalcular ${p.nombre} con el IVA actual`,
                        'boton-accion-producto boton-editar-producto',
                        () => ejecutar(() => recalcular(p)))
                    : null
            ]);

            cuerpo.appendChild(fila);
        });
    }

    async function recalcular(p) {
        const confirmado = await C.confirmar({
            titulo: 'Recalcular precio',
            texto: `Se recalculará «${p.nombre}» en ${etiquetaTienda(p.id_tienda)} con el mismo costo y margen y el IVA actual del producto (${porcentaje(p.iva_actual)}). El precio anterior queda en el historial.`,
            textoConfirmar: 'Recalcular'
        });

        if (!confirmado) {
            return;
        }

        const { message } = await llamar('aplicar', {
            id_producto: p.id_producto,
            id_tienda: p.id_tienda,
            id_margen: p.id_margen,
            costo: p.costo
        });

        C.mostrarMensaje(message);
        await cargarPrecios();
        await cargarHistorial(p.id_producto, p.id_tienda);
    }




    /*-----------------------------------------------------
      HISTORIAL Y SIMULACIONES (HU-40)
    -----------------------------------------------------*/


    async function historialDeSeleccion() {
        const idProducto = Number($('selProducto').value);
        const idTienda = Number($('selTienda').value);

        if (!idProducto) {
            throw new Error('Seleccione un producto.');
        }

        await cargarHistorial(idProducto, idTienda, true);
    }

    async function cargarHistorial(idProducto, idTienda, desplazar = false) {
        const producto = catalogos.productos.find((p) => Number(p.id_producto) === Number(idProducto));
        const [{ data }] = await Promise.all([
            llamar('historial', null, { id_producto: idProducto, id_tienda: idTienda || '' }),
            cargarSimulaciones(idProducto, idTienda)
        ]);

        $('tituloHistorial').textContent =
            `Historial: ${producto ? producto.nombre : 'producto'}${idTienda ? ' · ' + etiquetaTienda(idTienda) : ' · todas las tiendas'}`;

        const cuerpo = $('cuerpoHistorial');
        cuerpo.replaceChildren();

        if (!data.length) {
            const fila = document.createElement('tr');
            const td = celda(fila, 'Este producto todavía no tiene cambios de precio registrados.');
            td.colSpan = 7;
            cuerpo.appendChild(fila);
        }

        data.forEach((h) => {
            const fila = document.createElement('tr');

            celda(fila, fechaCorta(h.fecha_cambio));
            celda(fila, etiquetaTienda(h.id_tienda));
            celda(fila, `${dinero(h.costo_anterior)} → ${C.moneda(h.costo_nuevo)}`, 'celda-numero');
            celda(fila, `${dinero(h.precio_venta_anterior)} → ${C.moneda(h.precio_venta_nuevo)}`, 'celda-numero');
            celda(fila, `${h.margen_nombre} (${porcentaje(h.margen_porcentaje)})`);
            celda(fila, porcentaje(h.porcentaje_iva), 'celda-numero');
            celda(fila, h.usuario || '—');
            cuerpo.appendChild(fila);
        });

        if (desplazar) {
            $('panelHistorial').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    async function cargarSimulaciones(idProducto, idTienda) {
        const { data } = await llamar('simulaciones', null, { id_producto: idProducto, id_tienda: idTienda || '' });
        const cuerpo = $('cuerpoSimulaciones');

        cuerpo.replaceChildren();

        if (!data.length) {
            const fila = document.createElement('tr');
            const td = celda(fila, 'No hay simulaciones guardadas para esta selección.');
            td.colSpan = 6;
            cuerpo.appendChild(fila);
            return;
        }

        data.forEach((s) => {
            const fila = document.createElement('tr');

            celda(fila, fechaCorta(s.fecha_creacion));
            celda(fila, etiquetaTienda(s.id_tienda));
            celda(fila, `${s.margen_nombre} (${porcentaje(s.margen_porcentaje)})`);
            celda(fila, C.moneda(s.costo), 'celda-numero');
            celda(fila, C.moneda(s.precio_venta), 'celda-numero');
            celda(fila, s.aplicado ? 'Aplicada' : 'Solo simulación');
            cuerpo.appendChild(fila);
        });
    }
})();
