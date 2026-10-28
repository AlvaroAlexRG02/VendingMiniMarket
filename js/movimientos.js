/*=========================================================
  HU-21 — ENTRADA DE MERCADERÍA
  VendingMiniMarket
=========================================================*/

const estadoHU21 = {
    productos: [],
    ubicaciones: [],
    proveedores: [],
    proveedoresProducto: [],
    pedidos: [],
    productoSeleccionadoId: "",
    conservarMensajeTrasReset: false,
    itemsMovimiento: [],
    pedidoTablaActual: "",
    stockOrigenHU22: new Map(),
    stockOrigenUbicacionHU22: 0
};


/*=========================================================
  INICIALIZACIÓN
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {

    const formulario =
        document.getElementById(
            "formularioMovimientoInventario"
        );

    /*
     * Este archivo solamente trabaja en movimientos.php.
     */
    if (!formulario) {
        return;
    }

    establecerFechaHU21();

    registrarEventosHU21();

    actualizarTipoMovimientoHU21();

    actualizarResumenHU21();

    cargarDatosHU21();
});


/*=========================================================
  EVENTOS
=========================================================*/

function registrarEventosHU21() {

    const formulario =
        document.getElementById(
            "formularioMovimientoInventario"
        );

    const tipo =
        document.getElementById(
            "tipoMovimiento"
        );

    const buscador =
        document.getElementById(
            "buscarProductoMovimiento"
        );

    const producto =
        document.getElementById(
            "productoMovimiento"
        );

    const destino =
        document.getElementById(
            "ubicacionDestino"
        );

    const cantidad =
        document.getElementById(
            "cantidadMovimiento"
        );

    const origenMercaderia =
        document.getElementById(
            "origenMercaderia"
        );

    const proveedor =
        document.getElementById(
            "proveedorMovimiento"
        );

    const pedido =
        document.getElementById(
            "pedidoMovimiento"
        );


    tipo?.addEventListener(
        "change",
        () => {

            estadoHU21.itemsMovimiento = [];
            estadoHU21.pedidoTablaActual = "";
            renderizarTablaMovimientoHU21();
            actualizarTipoMovimientoHU21();
            actualizarResumenHU21();

        }
    );


    buscador?.addEventListener(
        "input",
        () => {

            filtrarProductosHU21(
                buscador.value
            );

        }
    );


    /*
     * Compatible con lector de código de barras.
     */
    buscador?.addEventListener(
        "keydown",
        (evento) => {

            if (evento.key !== "Enter") {
                return;
            }

            evento.preventDefault();

            seleccionarPorCodigoHU21(
                buscador.value,
                true
            );

        }
    );


    producto?.addEventListener(
        "change",
        () => {

            estadoHU21.productoSeleccionadoId =
                producto.value || "";

            cargarProveedoresHU21(estadoHU21.proveedores);
            cargarPedidosHU21(estadoHU21.pedidos);
            actualizarResumenHU21();

        }
    );


    destino?.addEventListener("change", () => {
        cargarPedidosHU21(estadoHU21.pedidos);
        renderizarTablaMovimientoHU21();
        actualizarResumenHU21();
    });

    document.getElementById("ubicacionOrigen")?.addEventListener("change", async () => {
        estadoHU21.itemsMovimiento = [];
        renderizarTablaMovimientoHU21();
        await cargarStockOrigenHU22HU23();
    });


    cantidad?.addEventListener(
        "input",
        actualizarResumenHU21
    );


    origenMercaderia?.addEventListener(
        "change",
        () => {
            estadoHU21.itemsMovimiento = [];
            estadoHU21.pedidoTablaActual = "";
            const destinoActual = document.getElementById("ubicacionDestino");
            if (destinoActual) destinoActual.value = "";
            actualizarCamposOrigenEntradaHU21();
            cargarPedidosHU21(estadoHU21.pedidos);
            renderizarTablaMovimientoHU21();
        }
    );


    proveedor?.addEventListener(
        "change",
        actualizarResumenHU21
    );


    pedido?.addEventListener("change", () => {
        cargarTablaPedidoHU21();
    });


    document.getElementById("botonAgregarProductoMovimiento")?.addEventListener(
        "click", agregarProductoTablaHU21
    );

    document.getElementById("tablaProductosMovimiento")?.addEventListener(
        "input", cambiarCantidadTablaHU21
    );
    document.getElementById("tablaProductosMovimiento")?.addEventListener(
        "click", eliminarProductoTablaHU21
    );

    // El aviso de error anterior ya no debe permanecer al corregir campos.
    // No ocultamos mensajes de éxito ni mensajes informativos de carga.
    const limpiarErrorAlEditar = () => {
        const mensaje = document.getElementById('mensajeMovimiento');
        if (mensaje && !mensaje.hidden && mensaje.dataset.tipo === 'error') {
            ocultarMensajeHU21();
        }
    };
    formulario?.addEventListener('input', limpiarErrorAlEditar);
    formulario?.addEventListener('change', limpiarErrorAlEditar);
    formulario?.addEventListener('click', (evento) => {
        if (evento.target.closest('.quitar-item-movimiento')) limpiarErrorAlEditar();
    });

    formulario?.addEventListener(
        "submit",
        registrarEntradaHU21
    );


    formulario?.addEventListener(
        "reset",
        () => {

            const conservarMensaje =
                estadoHU21
                    .conservarMensajeTrasReset;

            setTimeout(
                () => {

                    reiniciarControlesHU21(
                        !conservarMensaje
                    );

                    estadoHU21
                        .conservarMensajeTrasReset =
                        false;

                },
                0
            );

        }
    );
}


/*=========================================================
  CARGAR DATOS DEL FORMULARIO
=========================================================*/

async function cargarDatosHU21(silencioso = false) {
    if (!silencioso) {
        mostrarMensajeHU21("Cargando datos del formulario...", "info", "cargando");
    }

    try {

        const respuesta =
            await fetch(
                "../api/inventario/entrada.php",
                {
                    method: "GET",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers: {

                        "Accept":
                            "application/json"

                    }
                }
            );


        const resultado =
            await leerRespuestaJSONHU21(
                respuesta
            );


        if (
            !respuesta.ok ||
            !resultado.success
        ) {

            if (
                respuesta.status === 401
            ) {

                window.location.href =
                    "../index/index.html";

                return;
            }


            throw new Error(
                resultado.message ||
                "No fue posible cargar la información."
            );
        }


        estadoHU21.productos =
            Array.isArray(
                resultado.data?.productos
            )
                ? resultado.data.productos
                : [];


        estadoHU21.ubicaciones =
            Array.isArray(
                resultado.data?.ubicaciones
            )
                ? resultado.data.ubicaciones
                : [];


        estadoHU21.proveedores =
            Array.isArray(resultado.data?.proveedores)
                ? resultado.data.proveedores
                : [];

        estadoHU21.proveedoresProducto =
            Array.isArray(resultado.data?.proveedores_producto)
                ? resultado.data.proveedores_producto
                : [];


        estadoHU21.pedidos =
            Array.isArray(
                resultado.data?.pedidos
            )
                ? resultado.data.pedidos
                : [];


        estadoHU21.productoSeleccionadoId =
            "";


        cargarProductosHU21(
            estadoHU21.productos
        );


        cargarUbicacionesHU21(
            estadoHU21.ubicaciones
        );


        cargarProveedoresHU21(
            estadoHU21.proveedores
        );


        cargarPedidosHU21(
            estadoHU21.pedidos
        );

        renderizarTablaMovimientoHU21();


        actualizarCamposOrigenEntradaHU21();


        actualizarResultadoBusquedaProductoHU21(
            "",
            estadoHU21.productos.length,
            false
        );


        if (!silencioso) ocultarMensajeHU21();

    } catch (error) {

        console.error(
            "HU-21 - Error cargando datos:",
            error
        );


        if (!silencioso) {
            mostrarMensajeHU21(
                error.message || "No fue posible cargar los datos del formulario.",
                "error"
            );
        }

    }
}


/*=========================================================
  LEER RESPUESTA API
=========================================================*/

async function leerRespuestaJSONHU21(
    respuesta
) {

    const texto =
        await respuesta.text();


    if (!texto.trim()) {

        throw new Error(
            "La API no devolvió información."
        );

    }


    try {

        return JSON.parse(
            texto
        );

    } catch (error) {

        console.error(
            "Respuesta recibida de la API:",
            texto
        );


        throw new Error(
            "La API devolvió una respuesta inválida. Revisa entrada.php."
        );

    }
}


/*=========================================================
  PRODUCTOS
=========================================================*/

function cargarProductosHU21(
    productos,
    mantenerSeleccion = true
) {

    const selector =
        document.getElementById(
            "productoMovimiento"
        );


    if (!selector) {
        return;
    }


    const seleccionado =
        mantenerSeleccion
            ? estadoHU21.productoSeleccionadoId
            : "";


    selector.innerHTML = "";


    const opcionInicial =
        document.createElement(
            "option"
        );


    opcionInicial.value =
        "";


    opcionInicial.textContent =
        productos.length > 0
            ? "Seleccione un producto"
            : "No se encontraron productos";


    selector.appendChild(
        opcionInicial
    );


    productos.forEach(
        (producto) => {

            const opcion =
                document.createElement(
                    "option"
                );


            opcion.value =
                String(
                    producto.id_producto
                );


            const codigo =
                producto.codigo_articulo_retail ||
                producto.codigo_barras ||
                "Sin código";


            opcion.textContent =
                `${producto.nombre} — ${codigo}`;


            opcion.dataset.codigoRetail =
                producto.codigo_articulo_retail ||
                "";


            opcion.dataset.codigoBarras =
                producto.codigo_barras ||
                "";


            selector.appendChild(
                opcion
            );

        }
    );


    if (
        seleccionado &&
        existeProductoEnSelectorHU21(
            selector,
            seleccionado
        )
    ) {

        selector.value =
            seleccionado;

    } else {

        selector.value =
            "";

    }
}


/*=========================================================
  VERIFICAR PRODUCTO EN SELECT
=========================================================*/

function existeProductoEnSelectorHU21(
    selector,
    idProducto
) {

    return Array.from(
        selector.options
    ).some(
        (opcion) =>
            opcion.value ===
            String(
                idProducto
            )
    );
}


/*=========================================================
  SELECCIONAR PRODUCTO
=========================================================*/

function seleccionarProductoHU21(
    idProducto,
    enfocar = false
) {

    const selector =
        document.getElementById(
            "productoMovimiento"
        );


    if (!selector) {
        return false;
    }


    const id =
        String(
            idProducto || ""
        );


    if (
        !id ||
        !existeProductoEnSelectorHU21(
            selector,
            id
        )
    ) {

        return false;
    }


    selector.value =
        id;


    estadoHU21.productoSeleccionadoId = id;
    cargarProveedoresHU21(estadoHU21.proveedores);
    cargarPedidosHU21(estadoHU21.pedidos);
    actualizarResumenHU21();


    if (enfocar) {

        selector.focus();

    }


    return true;
}


/*=========================================================
  BUSCAR PRODUCTOS
=========================================================*/

function filtrarProductosHU21(
    texto
) {

    const busqueda =
        normalizarHU21(
            texto
        );


    /*
     * Si se borra la búsqueda, reiniciamos la selección.
     * No se borra la tabla de productos ya agregados.
     */
    if (!busqueda) {

        estadoHU21.productoSeleccionadoId = "";

        cargarProductosHU21(
            estadoHU21.productos,
            false
        );


        actualizarResultadoBusquedaProductoHU21(
            "",
            estadoHU21.productos.length,
            false
        );


        cargarProveedoresHU21(estadoHU21.proveedores);
        cargarPedidosHU21(estadoHU21.pedidos);
        actualizarResumenHU21();

        return;
    }


    const resultados =
        estadoHU21.productos.filter(
            (producto) => {

                const nombre =
                    normalizarHU21(
                        producto.nombre
                    );


                const retail =
                    normalizarHU21(
                        producto
                            .codigo_articulo_retail
                    );


                const barras =
                    normalizarHU21(
                        producto
                            .codigo_barras
                    );


                return (
                    nombre.includes(
                        busqueda
                    ) ||
                    retail.includes(
                        busqueda
                    ) ||
                    barras.includes(
                        busqueda
                    )
                );

            }
        );


    cargarProductosHU21(
        resultados,
        true
    );

    // Si el nuevo filtro excluye al producto anterior, ya no sigue seleccionado.
    if (!resultados.some(
        (producto) => String(producto.id_producto) === estadoHU21.productoSeleccionadoId
    )) {
        estadoHU21.productoSeleccionadoId = "";
    }


    /*
     * Primero revisamos si se escribió
     * exactamente un código retail
     * o código de barras.
     */
    const productoCodigoExacto =
        buscarProductoPorCodigoExactoHU21(
            texto
        );


    let seleccionadoAutomaticamente =
        false;


    if (productoCodigoExacto) {

        seleccionadoAutomaticamente =
            seleccionarProductoHU21(
                productoCodigoExacto
                    .id_producto,
                false
            );

    } else if (
        resultados.length === 1
    ) {

        /*
         * Si el nombre o búsqueda deja
         * una sola coincidencia,
         * también la seleccionamos.
         */
        seleccionadoAutomaticamente =
            seleccionarProductoHU21(
                resultados[0]
                    .id_producto,
                false
            );

    }


    actualizarResultadoBusquedaProductoHU21(
        texto,
        resultados.length,
        seleccionadoAutomaticamente
    );

    cargarProveedoresHU21(estadoHU21.proveedores);
    cargarPedidosHU21(estadoHU21.pedidos);
    actualizarResumenHU21();
}


/*=========================================================
  MENSAJE DEL BUSCADOR DE PRODUCTOS
=========================================================*/

function actualizarResultadoBusquedaProductoHU21(
    texto,
    cantidad,
    seleccionadoAutomaticamente = false
) {

    const resultado =
        document.getElementById(
            "resultadoBusquedaProductoMovimiento"
        );


    if (!resultado) {
        return;
    }


    const busqueda =
        normalizarHU21(
            texto
        );


    if (!busqueda) {

        resultado.textContent =
            cantidad === 1
                ? "1 producto disponible."
                : `${cantidad} productos disponibles.`;

        return;
    }


    if (cantidad === 0) {

        resultado.textContent =
            "No se encontraron productos con esa búsqueda.";

        return;
    }


    if (
        seleccionadoAutomaticamente
    ) {

        resultado.textContent =
            "Producto encontrado y seleccionado automáticamente.";

        return;
    }


    if (cantidad === 1) {

        resultado.textContent =
            "1 producto encontrado.";

        return;
    }


    resultado.textContent =
        `${cantidad} productos encontrados. ` +
        "Seleccione uno de la lista.";
}


/*=========================================================
  BUSCAR CÓDIGO EXACTO
=========================================================*/

function buscarProductoPorCodigoExactoHU21(
    texto
) {

    const codigoBuscado =
        normalizarHU21(
            texto
        );


    if (!codigoBuscado) {
        return null;
    }


    return estadoHU21.productos.find(
        (producto) => {

            const retail =
                normalizarHU21(
                    producto
                        .codigo_articulo_retail
                );


            const barras =
                normalizarHU21(
                    producto
                        .codigo_barras
                );


            return (
                retail ===
                    codigoBuscado ||
                barras ===
                    codigoBuscado
            );

        }
    ) || null;
}


/*=========================================================
  SELECCIÓN POR CÓDIGO
=========================================================*/

function seleccionarPorCodigoHU21(
    texto,
    enfocar = true
) {

    const producto =
        buscarProductoPorCodigoExactoHU21(
            texto
        );


    if (!producto) {
        return false;
    }


    const selector =
        document.getElementById(
            "productoMovimiento"
        );


    if (!selector) {
        return false;
    }


    const idProducto =
        String(
            producto.id_producto
        );


    /*
     * En caso de que el filtro actual
     * haya eliminado el producto,
     * lo cargamos temporalmente.
     */
    if (
        !existeProductoEnSelectorHU21(
            selector,
            idProducto
        )
    ) {

        cargarProductosHU21(
            [producto],
            false
        );

    }


    const seleccionado =
        seleccionarProductoHU21(
            idProducto,
            enfocar
        );


    if (seleccionado) {

        actualizarResultadoBusquedaProductoHU21(
            texto,
            1,
            true
        );

    }


    return seleccionado;
}


/*=========================================================
  NORMALIZAR TEXTO
=========================================================*/

function normalizarHU21(
    valor
) {

    return String(
        valor || ""
    )
        .trim()
        .toLowerCase()
        .normalize(
            "NFD"
        )
        .replace(
            /[\u0300-\u036f]/g,
            ""
        );
}


/*=========================================================
  UBICACIONES
=========================================================*/

function cargarUbicacionesHU21(
    ubicaciones
) {

    const selectores = [

        document.getElementById(
            "ubicacionDestino"
        ),

        document.getElementById(
            "ubicacionOrigen"
        )

    ];


    selectores.forEach(
        (selector) => {

            if (!selector) {
                return;
            }


            const seleccionado =
                selector.value;


            selector.innerHTML =
                "";


            const opcionInicial =
                document.createElement(
                    "option"
                );


            opcionInicial.value =
                "";


            opcionInicial.textContent =
                ubicaciones.length > 0
                    ? "Seleccione una ubicación"
                    : "No hay ubicaciones disponibles";


            selector.appendChild(
                opcionInicial
            );


            ubicaciones.forEach(
                (ubicacion) => {

                    const opcion =
                        document.createElement(
                            "option"
                        );


                    opcion.value =
                        String(
                            ubicacion.id_ubicacion
                        );


                    opcion.textContent =
                        `${ubicacion.tienda} — ` +
                        `${ubicacion.nombre} ` +
                        `(${ubicacion.tipo})`;


                    selector.appendChild(
                        opcion
                    );

                }
            );


            if (
                seleccionado &&
                Array.from(
                    selector.options
                ).some(
                    (opcion) =>
                        opcion.value ===
                        seleccionado
                )
            ) {

                selector.value =
                    seleccionado;

            }

        }
    );
}


/*=========================================================
  PROVEEDORES
=========================================================*/

/**
 * Devuelve los proveedores activos comunes a los productos indicados.
 * Se usa la relación producto_proveedor enviada por entrada.php (GET).
 * Los productos ya agregados deben pertenecer al mismo proveedor.
 */
function proveedoresComunesHU21(idsProducto) {
    const ids = [...new Set(idsProducto.map(Number).filter(Number.isInteger))];
    if (!ids.length) return [];

    const relaciones = estadoHU21.proveedoresProducto;
    const permitidos = ids.map(idProducto => new Set(
        relaciones
            .filter(rel => Number(rel.id_producto) === idProducto)
            .map(rel => Number(rel.id_proveedor))
    ));

    return estadoHU21.proveedores.filter(proveedor =>
        permitidos.every(grupo => grupo.has(Number(proveedor.id_proveedor)))
    );
}

/*=========================================================
  PROVEEDORES ASOCIADOS AL PRODUCTO / A LA TABLA
=========================================================*/
function cargarProveedoresHU21(proveedores = estadoHU21.proveedores) {
    const selector = document.getElementById('proveedorMovimiento');
    if (!selector) return;

    const origen = document.getElementById('origenMercaderia')?.value;
    const esProveedor = document.getElementById('tipoMovimiento')?.value === 'entrada'
        && origen === 'Proveedor';
    const seleccionadoAnterior = selector.value;
    const idProductoActual = Number(document.getElementById('productoMovimiento')?.value || 0);

    const idsTabla = estadoHU21.itemsMovimiento.map(item => Number(item.id_producto));
    const idsRelevantes = [...idsTabla];
    if (idProductoActual > 0) idsRelevantes.push(idProductoActual);

    const visibles = esProveedor && idsRelevantes.length
        ? proveedoresComunesHU21(idsRelevantes)
        : [];

    selector.replaceChildren();
    const inicial = document.createElement('option');
    inicial.value = '';
    inicial.textContent = !esProveedor
        ? 'Seleccione un proveedor'
        : idsRelevantes.length === 0
            ? 'Seleccione primero un producto'
            : visibles.length === 0
                ? (idsTabla.length > 0
                    ? 'No hay proveedores comunes a los productos'
                    : 'El producto no tiene proveedores asociados activos')
                : 'Seleccione un proveedor del producto';
    selector.appendChild(inicial);

    for (const proveedor of visibles) {
        const opcion = document.createElement('option');
        opcion.value = String(proveedor.id_proveedor);
        opcion.textContent = proveedor.nombre || `Proveedor #${proveedor.id_proveedor}`;
        selector.appendChild(opcion);
    }

    // Si el proveedor seleccionado continúa siendo compatible, se conserva.
    // Si deja de serlo, se limpia, evitando asociaciones inválidas.
    selector.value = visibles.some(p => String(p.id_proveedor) === seleccionadoAnterior)
        ? seleccionadoAnterior : '';

    let ayuda = document.getElementById('ayudaProveedorMovimiento');
    if (!ayuda) {
        ayuda = document.createElement('small');
        ayuda.id = 'ayudaProveedorMovimiento';
        ayuda.className = 'texto-secundario';
        ayuda.setAttribute('aria-live', 'polite');
        selector.insertAdjacentElement('afterend', ayuda);
    }
    ayuda.hidden = !esProveedor;
    if (esProveedor) {
        ayuda.textContent = idsRelevantes.length === 0
            ? 'Seleccione un producto para ver sus proveedores asociados.'
            : visibles.length === 0
                ? 'No existe un proveedor activo que suministre todos los productos seleccionados.'
                : `${visibles.length} proveedor(es) compatible(s) con el producto y la tabla.`;
    }
}


/*=========================================================
  PEDIDOS DE COMPRA
=========================================================*/

function cargarPedidosHU21(pedidos) {
    const selector = document.getElementById("pedidoMovimiento");
    if (!selector) return;
    const seleccionado = selector.value;
    const ubicaciones = new Map(estadoHU21.ubicaciones.map(u => [Number(u.id_ubicacion), u]));
    const unicos = new Map();
    for (const pedido of pedidos) {
        const id = Number(pedido.id_pedido);
        if (!unicos.has(id)) {
            unicos.set(id, { ...pedido, totalPendiente: 0 });
        }
        unicos.get(id).totalPendiente += Number(pedido.cantidad_pendiente || 0);
    }
    selector.replaceChildren();
    const inicial = document.createElement("option");
    inicial.value = "";
    inicial.textContent = unicos.size
        ? "Seleccione un pedido y vea los productos"
        : "No hay pedidos autorizados con mercadería pendiente";
    selector.appendChild(inicial);
    for (const p of unicos.values()) {
        const opcion = document.createElement("option");
        const ubicacion = ubicaciones.get(Number(p.id_ubicacion));
        opcion.value = String(p.id_pedido);
        opcion.textContent = `Pedido #${p.id_pedido} — ${p.proveedor || 'Proveedor'} — ` +
            `${ubicacion?.nombre || 'Ubicación'} — ${p.estado} — Pendiente: ${p.totalPendiente}`;
        selector.appendChild(opcion);
    }
    selector.value = unicos.has(Number(seleccionado)) ? seleccionado : "";
    // El selector no depende de que el usuario adivine un producto.
    // Cuando cambia la selección, la tabla se llena con TODO el pedido.
    if (selector.value !== estadoHU21.pedidoTablaActual) {
        cargarTablaPedidoHU21();
    }
}

function cargarTablaPedidoHU21() {
    const idPedido = document.getElementById("pedidoMovimiento")?.value || "";
    estadoHU21.pedidoTablaActual = idPedido;
    if (!idPedido) {
        estadoHU21.itemsMovimiento = [];
        const destino = document.getElementById("ubicacionDestino");
        if (destino && document.getElementById("origenMercaderia")?.value === "Pedido de compra") {
            destino.value = "";
        }
        renderizarTablaMovimientoHU21();
        return;
    }
    const filas = estadoHU21.pedidos.filter(p => String(p.id_pedido) === idPedido);
    const primera = filas[0];
    const destino = document.getElementById("ubicacionDestino");
    if (destino && primera) destino.value = String(primera.id_ubicacion);
    estadoHU21.itemsMovimiento = filas.map(f => {
        const producto = estadoHU21.productos.find(p => Number(p.id_producto) === Number(f.id_producto));
        const solicitada = Number(f.cantidad_solicitada ?? f.cantidad_pendiente);
        const recibida = Number(f.cantidad_recibida ?? 0);
        return {
            id_producto: Number(f.id_producto),
            nombre: producto?.nombre || `Producto #${f.id_producto}`,
            cantidad: 0,
            solicitada,
            recibida,
            pendiente: Number(f.cantidad_pendiente),
        };
    });
    renderizarTablaMovimientoHU21();
}

function agregarProductoTablaHU21() {
    const tipo = document.getElementById('tipoMovimiento')?.value || '';
    const esMovimientoSalida = tipo === 'salida' || tipo === 'traslado';
    const origen = document.getElementById('origenMercaderia')?.value;
    if (tipo === 'entrada' && origen === 'Pedido de compra') return;
    if (tipo === 'entrada' && !['Proveedor','Devolución'].includes(origen)) {
        mostrarMensajeHU21('Seleccione primero el tipo de entrada.', 'error'); return;
    }
    if (!esMovimientoSalida && tipo !== 'entrada') return;
    const id = Number(document.getElementById('productoMovimiento')?.value || 0);
    const cantidad = Number(document.getElementById('cantidadMovimiento')?.value || 0);
    const producto = estadoHU21.productos.find(p => Number(p.id_producto) === id);
    if (!producto || !Number.isSafeInteger(cantidad) || cantidad <= 0) {
        mostrarMensajeHU21('Seleccione un producto y una cantidad entera mayor que cero.', 'error'); return;
    }
    if (esMovimientoSalida) {
        if (!estadoHU21.stockOrigenUbicacionHU22 ||
            estadoHU21.stockOrigenUbicacionHU22 !== Number(document.getElementById('ubicacionOrigen')?.value)) {
            mostrarMensajeHU21('Seleccione primero la ubicación de origen y cargue sus existencias.', 'error'); return;
        }
        const disponible = Number(estadoHU21.stockOrigenHU22.get(id) || 0);
        const existente = estadoHU21.itemsMovimiento.find(p => p.id_producto === id);
        if (cantidad + Number(existente?.cantidad || 0) > disponible) {
            mostrarMensajeHU21(disponible <= 0
                ? 'Este producto no tiene existencias disponibles en la ubicación de origen elegida. Seleccione otro producto u origen.'
                : `Stock insuficiente para este producto: hay ${disponible} unidad(es) en origen. Reduzca la cantidad.`, 'error'); return;
        }
    } else if (origen === 'Proveedor') {
        const ids = [...estadoHU21.itemsMovimiento.map(p => p.id_producto), id];
        if (proveedoresComunesHU21(ids).length === 0) {
            mostrarMensajeHU21('El producto no comparte un proveedor activo con los productos de la tabla.', 'error');
            return;
        }
    }
    const existente = estadoHU21.itemsMovimiento.find(p => p.id_producto === id);
    if (existente) existente.cantidad += cantidad;
    else estadoHU21.itemsMovimiento.push({
        id_producto: id, nombre: producto.nombre, cantidad,
        pendiente: null,
        stockDisponible: esMovimientoSalida ? Number(estadoHU21.stockOrigenHU22.get(id) || 0) : null
    });
    document.getElementById('cantidadMovimiento').value = '';
    if (!esMovimientoSalida) cargarProveedoresHU21(estadoHU21.proveedores);
    renderizarTablaMovimientoHU21();
}


function cambiarCantidadTablaHU21(evento) {
    if (!evento.target.matches('.cantidad-item-movimiento')) return;
    const id = Number(evento.target.dataset.producto);
    const item = estadoHU21.itemsMovimiento.find(p => p.id_producto === id);
    if (!item) return;
    const valor = Number(evento.target.value);
    item.cantidad = evento.target.value === '' ? 0 : valor;
    const tipo = document.getElementById('tipoMovimiento')?.value;
    const maximo = ['salida','traslado'].includes(tipo)
        ? item.stockDisponible : item.pendiente;
    const invalida = !Number.isSafeInteger(valor) || valor < 0 ||
        (maximo !== null && maximo !== undefined && valor > maximo);
    evento.target.setCustomValidity(invalida
        ? `Ingrese un entero entre 0 y ${maximo ?? 'el límite permitido'}.` : '');
    if (['salida','traslado'].includes(tipo)) {
        const saldo = evento.target.closest('tr')?.cells?.[2];
        if (saldo) saldo.textContent = !invalida
            ? String(Number(item.stockDisponible || 0) - valor) : '—';
    }
    actualizarResumenHU21();
}


function eliminarProductoTablaHU21(evento) {
    const boton = evento.target.closest(".quitar-item-movimiento");
    if (!boton) return;
    // En pedidos los ítems pertenecen al documento: 0 significa 'no recibido hoy'.
    const id = Number(boton.dataset.producto);
    estadoHU21.itemsMovimiento = estadoHU21.itemsMovimiento.filter(p => p.id_producto !== id);
    cargarProveedoresHU21(estadoHU21.proveedores);
    renderizarTablaMovimientoHU21();
}

function renderizarTablaMovimientoHU21() {
    const tbody = document.getElementById('tablaProductosMovimiento');
    if (!tbody) return;
    tbody.replaceChildren();
    const tipo = document.getElementById('tipoMovimiento')?.value;
    const esSalida = ['salida','traslado'].includes(tipo);
    const esPedido = tipo === 'entrada' && document.getElementById('origenMercaderia')?.value === 'Pedido de compra';
    const cabeceras = tbody.closest('table')?.querySelectorAll('thead th') || [];
    const nuevas = esSalida
        ? ['Producto','Stock origen','Saldo origen resultante','Destino','Cantidad a mover','Acción']
        : ['Producto','Solicitado','Ya recibido','Pendiente','Recibir ahora','Acción'];
    cabeceras.forEach((th, i) => { if (nuevas[i]) th.textContent = nuevas[i]; });
    if (!estadoHU21.itemsMovimiento.length) {
        const tr = tbody.insertRow(); const td = tr.insertCell(); td.colSpan = 6;
        td.textContent = esSalida ? 'Seleccione origen y agregue productos con stock disponible.' :
            (esPedido ? 'Seleccione un pedido para cargar sus productos.' : 'Aún no hay productos agregados.');
    }
    for (const item of estadoHU21.itemsMovimiento) {
        const tr = tbody.insertRow();
        const texto = valor => { const td = tr.insertCell(); td.textContent = String(valor); return td; };
        texto(item.nombre);
        if (esSalida) {
            texto(item.stockDisponible ?? 0);
            texto(Number.isSafeInteger(item.cantidad)
                ? Number(item.stockDisponible ?? 0) - item.cantidad : '—');
            texto(tipo === 'traslado'
                ? document.getElementById('ubicacionDestino')?.selectedOptions?.[0]?.textContent?.trim() || 'Seleccione destino'
                : 'Salida sin destino');
        } else {
            texto(esPedido ? (item.solicitada ?? '—') : '—');
            texto(esPedido ? (item.recibida ?? '—') : '—');
            texto(esPedido ? (item.pendiente ?? '—') : '—');
        }
        const tdCantidad = tr.insertCell(); const input = document.createElement('input');
        input.type = 'number'; input.min = esPedido ? '0' : '1'; input.step = '1';
        input.className = 'cantidad-item-movimiento'; input.dataset.producto = String(item.id_producto);
        input.value = String(item.cantidad); input.style.width = '110px';
        if (esPedido) input.max = String(item.pendiente);
        if (esSalida) input.max = String(item.stockDisponible ?? 0);
        input.setAttribute('aria-label', `Cantidad a registrar de ${item.nombre}`);
        tdCantidad.appendChild(input);
        const tdQuitar = tr.insertCell();
        if (esPedido) tdQuitar.textContent = '—';
        else {
            const button = document.createElement('button'); button.type = 'button';
            button.className = 'boton boton-borde quitar-item-movimiento';
            button.dataset.producto = String(item.id_producto); button.textContent = 'Quitar';
            tdQuitar.appendChild(button);
        }
    }
    actualizarResumenHU21();
}


function prepararCamposTablaHU21() {
    const tipo = document.getElementById('tipoMovimiento')?.value;
    const esEntrada = tipo === 'entrada';
    const esSalida = ['salida','traslado'].includes(tipo);
    const pedido = esEntrada && document.getElementById('origenMercaderia')?.value === 'Pedido de compra';
    for (const id of ['productoMovimiento','buscarProductoMovimiento','cantidadMovimiento']) {
        const el = document.getElementById(id);
        const grupo = el?.closest('.grupo-formulario');
        if (grupo) grupo.style.display = pedido || (!esEntrada && !esSalida) ? 'none' : '';
    }
    const prod = document.getElementById('productoMovimiento'); if (prod) prod.required = false;
    const cantidad = document.getElementById('cantidadMovimiento'); if (cantidad) cantidad.required = false;
    const destino = document.getElementById('ubicacionDestino'); if (destino) destino.disabled = pedido;
    const boton = document.getElementById('botonAgregarProductoMovimiento');
    if (boton) boton.style.display = pedido || (!esEntrada && !esSalida) ? 'none' : '';
    const mensaje = document.getElementById('instruccionTablaMovimiento');
    if (mensaje) mensaje.textContent = pedido
        ? 'Seleccione un pedido. Aparecerán sus productos y las cantidades pendientes.'
        : esSalida ? 'Seleccione la ubicación de origen, un producto con existencias y una cantidad. Puede agregar varios productos.'
        : 'Seleccione un producto, indique la cantidad y pulse «Agregar producto». Puede repetirlo.';
    const grupoPedido = document.getElementById('grupoPedidoMovimiento');
    if (grupoPedido) grupoPedido.style.display = pedido ? '' : 'none';
    const seccion = document.getElementById('seccionProductosMovimiento');
    if (seccion) seccion.style.display = esEntrada || esSalida ? '' : 'none';
}


/*=========================================================
  FORMATEAR FECHA DE PEDIDO
=========================================================*/

function formatearFechaPedidoHU21(
    valor
) {

    if (!valor) {
        return "Sin fecha";
    }


    const fecha =
        new Date(valor);


    if (
        Number.isNaN(
            fecha.getTime()
        )
    ) {
        return String(valor);
    }


    return new Intl.DateTimeFormat(
        "es-CR",
        {
            dateStyle: "short"
        }
    ).format(fecha);
}


/*=========================================================
  TIPO DE MOVIMIENTO
=========================================================*/

function actualizarTipoMovimientoHU21() {
    const tipo = document.getElementById('tipoMovimiento')?.value || '';
    const entrada = tipo === 'entrada';
    const salida = tipo === 'salida';
    const traslado = tipo === 'traslado';
    const activo = entrada || salida || traslado;
    const origenMercaderia = document.getElementById('grupoOrigenMercaderia');
    const origen = document.getElementById('grupoUbicacionOrigen');
    const destino = document.getElementById('grupoUbicacionDestino');
    const selectorOrigen = document.getElementById('ubicacionOrigen');
    const selectorDestino = document.getElementById('ubicacionDestino');
    const boton = document.getElementById('botonRegistrarMovimiento');
    for (const [grupo, visible] of [[origenMercaderia, entrada], [origen, salida || traslado],
        [destino, entrada || traslado]]) {
        if (grupo) { grupo.hidden = !visible; grupo.style.display = visible ? '' : 'none'; }
    }
    if (selectorOrigen) selectorOrigen.required = salida || traslado;
    if (selectorDestino) selectorDestino.required = entrada || traslado;
    const origenEntrada = document.getElementById('origenMercaderia');
    if (origenEntrada) origenEntrada.required = entrada;
    if (boton) boton.disabled = !activo;
    const botonNuevo = document.getElementById('botonLimpiarMovimiento');
    if (botonNuevo) {
        botonNuevo.innerHTML = salida || traslado
            ? '<i class="fa-solid fa-rotate-left"></i> Nuevo movimiento'
            : '<i class="fa-solid fa-eraser"></i> Limpiar';
        botonNuevo.title = salida || traslado
            ? 'Limpiar el formulario y comenzar otro movimiento sin recargar la página'
            : 'Limpiar el formulario';
    }
    actualizarCamposOrigenEntradaHU21();
    const grupoObs = document.getElementById('grupoObservacionesMovimiento');
    if (grupoObs && !entrada) { grupoObs.hidden = !(salida || traslado); grupoObs.style.display = (salida || traslado) ? '' : 'none'; }
    const obs = document.getElementById('observacionesMovimiento');
    if (obs && !entrada) { obs.required = salida || traslado; obs.placeholder = 'Motivo obligatorio del movimiento (por ejemplo: reposición, entrega o abastecimiento)'; }
    const grupoRef = document.getElementById('grupoReferenciaMovimiento');
    if (grupoRef && !entrada) {grupoRef.hidden = true; grupoRef.style.display = 'none';}
    const resumen = document.querySelector('#resumenEntradaMovimiento strong');
    if (resumen) resumen.textContent = entrada ? 'Resumen de la entrada' : traslado ? 'Resumen del traslado' : 'Resumen del movimiento';
    const mensaje = document.getElementById('mensajeMovimiento');
    if (!activo && tipo) {
        mostrarMensajeHU21('Este tipo de movimiento todavía no está disponible.', 'info', 'tipo-pendiente');
    } else if (mensaje?.dataset.clave === 'tipo-pendiente') ocultarMensajeHU21();
    prepararCamposTablaHU21();
    renderizarTablaMovimientoHU21();
}


/*=========================================================
  CAMPOS SEGÚN ORIGEN DE LA ENTRADA
=========================================================*/

function actualizarCamposOrigenEntradaHU21() {

    const tipo =
        document.getElementById(
            "tipoMovimiento"
        )?.value || "";


    const origen =
        document.getElementById(
            "origenMercaderia"
        )?.value || "";


    const grupoProveedor =
        document.getElementById(
            "grupoProveedorMovimiento"
        );


    const grupoPedido =
        document.getElementById(
            "grupoPedidoMovimiento"
        );


    const grupoReferencia =
        document.getElementById(
            "grupoReferenciaMovimiento"
        );


    const grupoObservaciones =
        document.getElementById(
            "grupoObservacionesMovimiento"
        );


    const proveedor =
        document.getElementById(
            "proveedorMovimiento"
        );


    const pedido =
        document.getElementById(
            "pedidoMovimiento"
        );


    const referencia =
        document.getElementById(
            "referenciaMovimiento"
        );


    const observaciones =
        document.getElementById(
            "observacionesMovimiento"
        );


    const esEntrada =
        tipo === "entrada";


    const esProveedor =
        esEntrada && origen === "Proveedor";


    const esPedido =
        esEntrada && origen === "Pedido de compra";


    const esDevolucion =
        esEntrada && origen === "Devolución";


    const mostrarProveedor =
        esProveedor;


    const mostrarPedido =
        esPedido;


    const mostrarReferencia =
        esProveedor || esPedido;


    // Motivo obligatorio también en salidas y traslados (HU-22/HU-23).
    // Esta función vuelve a ejecutarse al terminar de cargar los datos.
    const esMovimientoSalida = tipo === "salida" || tipo === "traslado";
    const mostrarObservaciones =
        esProveedor || esPedido || esDevolucion || esMovimientoSalida;


    if (grupoProveedor) {
        grupoProveedor.hidden =
            !mostrarProveedor;
    }


    if (grupoPedido) {
        grupoPedido.hidden =
            !mostrarPedido;
    }


    if (grupoReferencia) {
        grupoReferencia.hidden =
            !mostrarReferencia;
    }


    if (grupoObservaciones) {
        grupoObservaciones.hidden =
            !mostrarObservaciones;
    }


    if (proveedor) {

        proveedor.required =
            esProveedor;

        if (!mostrarProveedor) {
            proveedor.value = "";
        }
    }


    if (pedido) {

        pedido.required =
            esPedido;

        if (!mostrarPedido) {
            pedido.value = "";
        }
    }


    if (referencia) {

        referencia.required =
            esProveedor;

        referencia.placeholder =
            esProveedor
                ? "Ejemplo: FAC-10235"
                : "Opcional";

        if (!mostrarReferencia) {
            referencia.value = "";
        }
    }


    if (observaciones) {

        observaciones.required = esDevolucion || esMovimientoSalida;

        observaciones.placeholder = esDevolucion
            ? "Indique el motivo de la devolución..."
            : esMovimientoSalida
                ? "Indique el motivo obligatorio (reposición, entrega, abastecimiento...)"
                : "Información adicional sobre el movimiento...";

        if (!mostrarObservaciones) {
            observaciones.value = "";
        }
    }
    prepararCamposTablaHU21();
    cargarProveedoresHU21(estadoHU21.proveedores);
    cargarPedidosHU21(estadoHU21.pedidos);
}

/*=========================================================
  FECHA
=========================================================*/

function establecerFechaHU21() {

    const campo =
        document.getElementById(
            "fechaMovimiento"
        );


    if (!campo) {
        return;
    }


    const ahora =
        new Date();


    campo.value =
        new Intl.DateTimeFormat(
            "es-CR",
            {
                dateStyle:
                    "short",

                timeStyle:
                    "short"
            }
        ).format(
            ahora
        );
}


/*=========================================================
  RESUMEN
=========================================================*/

function actualizarResumenHU21() {
    const unidades = estadoHU21.itemsMovimiento.reduce((s,p)=>s+
        (Number.isSafeInteger(p.cantidad) && p.cantidad > 0 ? p.cantidad : 0), 0);
    const total = document.getElementById('totalUnidadesMovimiento');
    if (total) total.textContent = String(unidades);
    const resumen = document.getElementById('textoResumenEntrada');
    if (!resumen) return;
    const tipo = document.getElementById('tipoMovimiento')?.value;
    const nombre = id => document.getElementById(id)?.selectedOptions?.[0]?.textContent?.trim() || 'Sin seleccionar';
    if (['salida','traslado'].includes(tipo)) {
        resumen.textContent = `${estadoHU21.itemsMovimiento.length} producto(s) | ${unidades} unidad(es) | `+
            `Origen: ${nombre('ubicacionOrigen')}` + (tipo === 'traslado' ? ` | Destino: ${nombre('ubicacionDestino')}` : ' | Salida de inventario');
    } else {
        const origen = document.getElementById('origenMercaderia')?.value || 'Sin seleccionar';
        const pedido = document.getElementById('pedidoMovimiento');
        resumen.textContent = `${estadoHU21.itemsMovimiento.length} producto(s) en la tabla | ` +
            `${unidades} unidad(es) a registrar | Destino: ${nombre('ubicacionDestino')} | Tipo: ${origen}`+
            (origen === 'Pedido de compra' && pedido?.value ? ` | Pedido #${pedido.value}` : '');
    }
}


/*=========================================================
  REGISTRAR ENTRADA
=========================================================*/

async function registrarEntradaHU21(evento) {
    evento.preventDefault();
    const tipoMovimientoActual = document.getElementById('tipoMovimiento')?.value;
    if (tipoMovimientoActual === 'salida' || tipoMovimientoActual === 'traslado') {
        await registrarSalidaTrasladoHU22HU23();
        return;
    }
    if (tipoMovimientoActual !== 'entrada') {
        mostrarMensajeHU21('Seleccione un tipo de movimiento disponible.', 'error');
        return;
    }
    const origen = document.getElementById('origenMercaderia')?.value || '';
    const esPedido = origen === 'Pedido de compra';
    const idUbicacion = Number(document.getElementById('ubicacionDestino')?.value || 0);
    const idPedido = Number(document.getElementById('pedidoMovimiento')?.value || 0);
    const idProveedor = Number(document.getElementById('proveedorMovimiento')?.value || 0);
    const referencia = document.getElementById('referenciaMovimiento')?.value.trim() || '';
    const observaciones = document.getElementById('observacionesMovimiento')?.value.trim() || '';
    const items = estadoHU21.itemsMovimiento.filter(p => Number(p.cantidad) > 0);
    const campos = [...document.querySelectorAll('#tablaProductosMovimiento .cantidad-item-movimiento')];
    if (!['Proveedor','Devolución','Pedido de compra'].includes(origen)) {
        mostrarMensajeHU21('Indique el tipo de entrada.', 'error');return;
    }
    if (!idUbicacion) {mostrarMensajeHU21('Seleccione ubicación de destino.', 'error');return;}
    if (!items.length) {mostrarMensajeHU21('Agregue al menos un producto con cantidad mayor que cero.', 'error');return;}
    if (estadoHU21.itemsMovimiento.some(p => !Number.isSafeInteger(p.cantidad) || p.cantidad < 0 ||
        (p.pendiente !== null && p.cantidad > p.pendiente)) ||
        campos.some(c => !c.checkValidity())) {
        mostrarMensajeHU21('Revise las cantidades: solo enteros y nunca mayores a lo pendiente.', 'error');return;
    }
    if (esPedido) {
        const pedido = estadoHU21.pedidos.find(p => Number(p.id_pedido) === idPedido);
        if (!pedido || Number(pedido.id_ubicacion) !== idUbicacion) {
            mostrarMensajeHU21('Seleccione un pedido disponible.', 'error');return;
        }
        const validos = estadoHU21.pedidos.filter(p => Number(p.id_pedido) === idPedido);
        if (items.some(i => !validos.some(p => Number(p.id_producto) === i.id_producto &&
                i.cantidad <= Number(p.cantidad_pendiente)))) {
            mostrarMensajeHU21('Algún producto excede las unidades disponibles del pedido.', 'error');return;
        }
    } else if (origen === 'Proveedor' && (!idProveedor || !referencia)) {
        mostrarMensajeHU21('Seleccione proveedor e indique documento o referencia.', 'error');return;
    } else if (origen === 'Proveedor' &&
        !proveedoresComunesHU21(items.map(item => item.id_producto))
            .some(proveedor => Number(proveedor.id_proveedor) === idProveedor)) {
        mostrarMensajeHU21('El proveedor no está asociado a todos los productos de la entrada.', 'error');return;
    } else if (origen === 'Devolución' && !observaciones) {
        mostrarMensajeHU21('Indique el motivo de la devolución.', 'error');return;
    }
    const unidades = items.reduce((s,p)=>s+p.cantidad,0);
    const boton = document.getElementById('botonRegistrarMovimiento');
    if (boton) {boton.disabled=true;boton.textContent='Registrando...';}
    mostrarMensajeHU21('Registrando movimiento...', 'info', 'registrando');
    try {
        const url = esPedido
            ? '../api/compras/recepciones.php?accion=registrar_recepcion'
            : '../api/inventario/entrada-lote.php';
        const datos = esPedido
            ? {id_pedido:idPedido, items:items.map(p=>({id_producto:p.id_producto,cantidad:p.cantidad})),
                observaciones:[referencia && `Referencia: ${referencia}`,observaciones].filter(Boolean).join(' | ')}
            : {id_ubicacion:idUbicacion,id_proveedor:origen==='Proveedor'?idProveedor:null,
                origen,referencia,observaciones,
                items:items.map(p=>({id_producto:p.id_producto,cantidad:p.cantidad}))};
        const respuesta = await fetch(url,{method:'POST',credentials:'same-origin',cache:'no-store',
            headers:{Accept:'application/json','Content-Type':'application/json'},body:JSON.stringify(datos)});
        const resultado = await leerRespuestaJSONHU21(respuesta);
        if (respuesta.status===401) {window.location.href='../index/index.html';return;}
        if (!respuesta.ok || !resultado.success) throw new Error(resultado.message || 'No fue posible registrar la entrada.');
        const confirmado = !esPedido || resultado.data?.confirmado === true;
        // La confirmación se ve en el centro de la pantalla, sin desplazarse.
        // Se mantiene la barra superior para errores y mensajes de carga.
        ocultarMensajeHU21();
        mostrarNotificacionCentralHU21(
            confirmado
                ? `Movimiento #${resultado.data?.id_movimiento ?? '—'} registrado: ${unidades} unidades de ${items.length} producto(s).`
                : `Recepción #${resultado.data?.id_movimiento ?? '—'} registrada y pendiente de confirmación. El stock aún NO aumenta.`,
            confirmado ? 'exito' : 'info'
        );
        if (confirmado) actualizarTarjetaEntradasHU21(unidades);
        limpiarFormularioDespuesEntradaHU21();
        if (esPedido) await cargarDatosHU21(true);
    } catch(error) {
        console.error('HU-21 - Error registrando:',error);
        mostrarMensajeHU21(error.message || 'No fue posible registrar el movimiento.','error');
    } finally {
        if (boton) {boton.disabled=false;boton.innerHTML='<i class="fa-solid fa-floppy-disk"></i> Registrar movimiento';}
    }
}

/*=========================================================
  ACTUALIZAR TARJETA DE ENTRADAS
=========================================================*/

function actualizarTarjetaEntradasHU21(
    cantidad
) {

    const tarjeta =
        document.getElementById(
            "totalEntradas"
        );


    if (!tarjeta) {
        return;
    }


    const actual =
        Number(
            tarjeta.textContent || 0
        );


    tarjeta.textContent =
        String(
            actual + cantidad
        );
}


/*=========================================================
  LIMPIAR DESPUÉS DE REGISTRAR
=========================================================*/

function limpiarFormularioDespuesEntradaHU21() {

    const formulario =
        document.getElementById(
            "formularioMovimientoInventario"
        );


    if (!formulario) {
        return;
    }


    /*
     * Conservamos el mensaje de éxito
     * después de registrar.
     */
    estadoHU21
        .conservarMensajeTrasReset =
        true;


    formulario.reset();
}


/*=========================================================
  REINICIAR CONTROLES
=========================================================*/

function reiniciarControlesHU21(
    ocultarMensaje = true
) {

    const tipo =
        document.getElementById(
            "tipoMovimiento"
        );


    const buscador =
        document.getElementById(
            "buscarProductoMovimiento"
        );


    estadoHU21.productoSeleccionadoId = "";
    estadoHU21.itemsMovimiento = [];
    estadoHU21.pedidoTablaActual = "";
    estadoHU21.stockOrigenUbicacionHU22 = 0;
    estadoHU21.stockOrigenHU22 = new Map();

    if (tipo) {

        tipo.value =
            "";

    }


    if (buscador) {

        buscador.value =
            "";

    }


    cargarProductosHU21(
        estadoHU21.productos,
        false
    );


    cargarUbicacionesHU21(
        estadoHU21.ubicaciones
    );


    cargarProveedoresHU21(
        estadoHU21.proveedores
    );


    cargarPedidosHU21(
        estadoHU21.pedidos
    );


    actualizarResultadoBusquedaProductoHU21(
        "",
        estadoHU21.productos.length,
        false
    );


    establecerFechaHU21();


    actualizarTipoMovimientoHU21();


    prepararCamposTablaHU21();
    renderizarTablaMovimientoHU21();

    if (ocultarMensaje) {

        ocultarMensajeHU21();

    }
}


/*=========================================================
  MENSAJES
=========================================================*/

function mostrarMensajeHU21(
    texto,
    tipo = "info",
    clave = ""
) {

    const mensaje =
        document.getElementById(
            "mensajeMovimiento"
        );


    if (!mensaje) {
        return;
    }


    mensaje.hidden =
        false;


    mensaje.textContent =
        texto;


    mensaje.dataset.tipo =
        tipo;


    if (clave) {

        mensaje.dataset.clave =
            clave;

    } else {

        delete mensaje.dataset.clave;

    }


    mensaje.style.padding =
        "12px 14px";


    mensaje.style.marginBottom =
        "16px";


    mensaje.style.borderRadius =
        "8px";


    /* Éxito */

    if (tipo === "exito") {

        mensaje.style.background =
            "#eaf8ef";


        mensaje.style.color =
            "#176b3a";


        mensaje.style.border =
            "1px solid #b8e6c8";


        return;
    }


    /* Error */

    if (tipo === "error") {

        mensaje.style.background =
            "#fff0f0";


        mensaje.style.color =
            "#9f2929";


        mensaje.style.border =
            "1px solid #f3c3c3";


        return;
    }


    /* Información */

    mensaje.style.background =
        "#edf3ff";


    mensaje.style.color =
        "#34599c";


    mensaje.style.border =
        "1px solid #c8d8fb";
}


/*=========================================================
  OCULTAR MENSAJE
=========================================================*/

function ocultarMensajeHU21() {

    const mensaje =
        document.getElementById(
            "mensajeMovimiento"
        );


    if (!mensaje) {
        return;
    }


    mensaje.hidden =
        true;


    mensaje.textContent =
        "";


    delete mensaje.dataset.tipo;


    delete mensaje.dataset.clave;
}

/*=========================================================
  HU-21 — NOTIFICACIÓN CENTRAL DE CONFIRMACIÓN
  Solo se muestra al guardar correctamente un movimiento.
  No modifica la API, la base de datos ni las validaciones.
=========================================================*/
let temporizadorNotificacionCentralHU21 = null;

function mostrarNotificacionCentralHU21(texto, tipo = 'exito') {
    if (!document.getElementById('estilosNotificacionCentralHU21')) {
        const estilos = document.createElement('style');
        estilos.id = 'estilosNotificacionCentralHU21';
        estilos.textContent = `
            .hu21-notificacion-central {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 10000;
                width: min(480px, calc(100vw - 32px));
                box-sizing: border-box;
                padding: 23px 24px;
                border-radius: 15px;
                box-shadow: 0 18px 55px rgba(15, 23, 42, .24);
                border: 1px solid #a7dec0;
                background: #f0fff5;
                color: #14532d;
                font-family: inherit;
            }
            .hu21-notificacion-central.hu21-info {
                border-color: #b8cdfa;
                background: #f0f5ff;
                color: #1e40af;
            }
            .hu21-notificacion-contenido {
                display: flex;
                align-items: flex-start;
                gap: 13px;
            }
            .hu21-notificacion-icono {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 39px;
                width: 39px;
                height: 39px;
                border-radius: 50%;
                background: #dcfce7;
                font-size: 19px;
            }
            .hu21-info .hu21-notificacion-icono { background: #dbeafe; }
            .hu21-notificacion-texto { flex: 1; min-width: 0; }
            .hu21-notificacion-titulo {
                display: block;
                margin: 0 0 6px;
                font-size: 16px;
                font-weight: 700;
            }
            .hu21-notificacion-detalle {
                margin: 0;
                line-height: 1.55;
                font-size: 14px;
                overflow-wrap: anywhere;
            }
            .hu21-notificacion-cerrar {
                border: 0;
                background: transparent;
                color: inherit;
                cursor: pointer;
                font-size: 21px;
                line-height: 1;
                padding: 1px 4px;
                border-radius: 5px;
            }
            .hu21-notificacion-cerrar:focus-visible {
                outline: 2px solid currentColor;
                outline-offset: 2px;
            }
        `;
        document.head.appendChild(estilos);
    }

    let aviso = document.getElementById('notificacionCentralHU21');
    if (!aviso) {
        aviso = document.createElement('div');
        aviso.id = 'notificacionCentralHU21';
        aviso.setAttribute('role', 'status');
        aviso.setAttribute('aria-live', 'polite');
        document.body.appendChild(aviso);
    }

    aviso.className = 'hu21-notificacion-central' + (tipo === 'info' ? ' hu21-info' : '');
    aviso.replaceChildren();

    const contenido = document.createElement('div');
    contenido.className = 'hu21-notificacion-contenido';

    const icono = document.createElement('span');
    icono.className = 'hu21-notificacion-icono';
    icono.setAttribute('aria-hidden', 'true');
    icono.textContent = tipo === 'info' ? 'ℹ' : '✓';

    const textos = document.createElement('div');
    textos.className = 'hu21-notificacion-texto';
    const titulo = document.createElement('strong');
    titulo.className = 'hu21-notificacion-titulo';
    titulo.textContent = tipo === 'info' ? 'Recepción registrada' : 'Movimiento registrado';
    const detalle = document.createElement('p');
    detalle.className = 'hu21-notificacion-detalle';
    detalle.textContent = texto; // Evita interpretar contenido como HTML.
    textos.append(titulo, detalle);

    const cerrar = document.createElement('button');
    cerrar.type = 'button';
    cerrar.className = 'hu21-notificacion-cerrar';
    cerrar.setAttribute('aria-label', 'Cerrar notificación');
    cerrar.textContent = '×';
    cerrar.addEventListener('click', ocultarNotificacionCentralHU21);

    contenido.append(icono, textos, cerrar);
    aviso.appendChild(contenido);
    aviso.hidden = false;

    if (temporizadorNotificacionCentralHU21 !== null) {
        clearTimeout(temporizadorNotificacionCentralHU21);
    }
    temporizadorNotificacionCentralHU21 = setTimeout(ocultarNotificacionCentralHU21, 6500);
}

function ocultarNotificacionCentralHU21() {
    const aviso = document.getElementById('notificacionCentralHU21');
    if (aviso) aviso.hidden = true;
    if (temporizadorNotificacionCentralHU21 !== null) {
        clearTimeout(temporizadorNotificacionCentralHU21);
        temporizadorNotificacionCentralHU21 = null;
    }
}


/*=========================================================
  HU-22 / HU-23 — CONSULTAR STOCK DE ORIGEN
=========================================================*/
async function cargarStockOrigenHU22HU23() {
    const idOrigen = Number(document.getElementById('ubicacionOrigen')?.value || 0);
    estadoHU21.stockOrigenUbicacionHU22 = 0;
    estadoHU21.stockOrigenHU22 = new Map();
    if (!idOrigen) return;
    try {
        const url = `../api/inventario/salidas-traslados.php?id_ubicacion_origen=${encodeURIComponent(idOrigen)}`;
        const resp = await fetch(url, {credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
        const res = await leerRespuestaJSONHU21(resp);
        if (resp.status === 401) { window.location.href = '../index/index.html'; return; }
        if (!resp.ok || !res.success) throw new Error(res.message || 'No se pudo consultar el stock.');
        // Evita aplicar una respuesta obsoleta si se cambió rápidamente de origen.
        if (idOrigen !== Number(document.getElementById('ubicacionOrigen')?.value)) return;
        estadoHU21.stockOrigenUbicacionHU22 = idOrigen;
        estadoHU21.stockOrigenHU22 = new Map((res.data?.stock || [])
            .map(x => [Number(x.id_producto), Number(x.cantidad)]));
    } catch (error) {
        mostrarMensajeHU21(error.message || 'No fue posible cargar existencias del origen.', 'error');
    }
}

/*=========================================================
  HU-22 / HU-23 — REGISTRAR SALIDA O TRASLADO EN LOTE
=========================================================*/
async function registrarSalidaTrasladoHU22HU23() {
    const tipo = document.getElementById('tipoMovimiento')?.value || '';
    const idOrigen = Number(document.getElementById('ubicacionOrigen')?.value || 0);
    const idDestino = tipo === 'traslado' ? Number(document.getElementById('ubicacionDestino')?.value || 0) : null;
    const motivo = document.getElementById('observacionesMovimiento')?.value.trim() || '';
    const items = estadoHU21.itemsMovimiento.filter(x => Number(x.cantidad) > 0);
    if (!idOrigen || (tipo === 'traslado' && (!idDestino || idDestino === idOrigen))) {
        mostrarMensajeHU21('Seleccione origen y un destino diferente cuando corresponda.', 'error'); return;
    }
    if (!motivo) { mostrarMensajeHU21('Indique el motivo del movimiento.', 'error'); return; }
    if (!items.length) { mostrarMensajeHU21('Agregue productos con cantidades mayores que cero.', 'error'); return; }
    if (estadoHU21.stockOrigenUbicacionHU22 !== idOrigen ||
        items.some(x => !Number.isSafeInteger(x.cantidad) || x.cantidad < 0 ||
            x.cantidad > Number(estadoHU21.stockOrigenHU22.get(x.id_producto) || 0)) ||
        [...document.querySelectorAll('#tablaProductosMovimiento .cantidad-item-movimiento')]
            .some(x=>!x.checkValidity())) {
        mostrarMensajeHU21('Revise las cantidades: no se puede exceder el stock disponible en origen.', 'error');return;
    }
    const boton = document.getElementById('botonRegistrarMovimiento');
    if (boton) { boton.disabled = true; boton.textContent = 'Registrando...'; }
    mostrarMensajeHU21('Registrando movimiento...', 'info', 'registrando');
    try {
        const resp = await fetch('../api/inventario/salidas-traslados.php', {
            method:'POST',credentials:'same-origin',cache:'no-store',
            headers:{Accept:'application/json','Content-Type':'application/json'},
            body:JSON.stringify({tipo,id_ubicacion_origen:idOrigen,id_ubicacion_destino:idDestino,
                motivo,items:items.map(x=>({id_producto:x.id_producto,cantidad:x.cantidad}))})
        });
        const res = await leerRespuestaJSONHU21(resp);
        if (resp.status === 401) {window.location.href='../index/index.html';return;}
        if (!resp.ok || !res.success) throw new Error(res.message || 'No fue posible registrar el movimiento.');
        ocultarMensajeHU21();
        mostrarNotificacionCentralHU21(`Movimiento #${res.data?.id_movimiento ?? '—'} registrado: `+
            `${res.data?.unidades ?? 0} unidades de ${res.data?.productos ?? 0} producto(s).`, 'exito');
        limpiarFormularioDespuesEntradaHU21();
    } catch (error) {
        mostrarMensajeHU21(error.message || 'No fue posible guardar el movimiento.', 'error');
    } finally {
        if (boton) { boton.disabled = false; boton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Registrar movimiento'; }
    }
}
