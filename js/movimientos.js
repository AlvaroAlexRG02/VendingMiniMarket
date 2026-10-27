/*=========================================================
  HU-21 — ENTRADA DE MERCADERÍA
  VendingMiniMarket
=========================================================*/

const estadoHU21 = {
    productos: [],
    ubicaciones: [],
    proveedores: [],
    pedidos: [],
    productoSeleccionadoId: "",
    conservarMensajeTrasReset: false
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

            actualizarResumenHU21();

        }
    );


    destino?.addEventListener(
        "change",
        actualizarResumenHU21
    );


    cantidad?.addEventListener(
        "input",
        actualizarResumenHU21
    );


    origenMercaderia?.addEventListener(
        "change",
        () => {
            actualizarCamposOrigenEntradaHU21();
            actualizarResumenHU21();
        }
    );


    proveedor?.addEventListener(
        "change",
        actualizarResumenHU21
    );


    pedido?.addEventListener(
        "change",
        actualizarResumenHU21
    );


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

async function cargarDatosHU21() {

    mostrarMensajeHU21(
        "Cargando datos del formulario...",
        "info",
        "cargando"
    );

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
            Array.isArray(
                resultado.data?.proveedores
            )
                ? resultado.data.proveedores
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


        actualizarCamposOrigenEntradaHU21();


        actualizarResultadoBusquedaProductoHU21(
            "",
            estadoHU21.productos.length,
            false
        );


        ocultarMensajeHU21();


    } catch (error) {

        console.error(
            "HU-21 - Error cargando datos:",
            error
        );


        mostrarMensajeHU21(
            error.message ||
            "No fue posible cargar los datos del formulario.",
            "error"
        );

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


    estadoHU21.productoSeleccionadoId =
        id;


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
     * Al borrar la búsqueda,
     * volvemos a mostrar todos los productos.
     * Si ya existía un producto seleccionado,
     * se conserva.
     */
    if (!busqueda) {

        cargarProductosHU21(
            estadoHU21.productos,
            true
        );


        actualizarResultadoBusquedaProductoHU21(
            "",
            estadoHU21.productos.length,
            false
        );


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

function cargarProveedoresHU21(
    proveedores
) {

    const selector =
        document.getElementById(
            "proveedorMovimiento"
        );


    if (!selector) {
        return;
    }


    const seleccionado =
        selector.value;


    selector.innerHTML = "";


    const opcionInicial =
        document.createElement(
            "option"
        );


    opcionInicial.value = "";


    opcionInicial.textContent =
        proveedores.length > 0
            ? "Seleccione un proveedor"
            : "No hay proveedores disponibles";


    selector.appendChild(
        opcionInicial
    );


    proveedores.forEach(
        (proveedor) => {

            const opcion =
                document.createElement(
                    "option"
                );


            opcion.value =
                String(
                    proveedor.id_proveedor
                );


            opcion.textContent =
                proveedor.nombre ||
                `Proveedor #${proveedor.id_proveedor}`;


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
                opcion.value === seleccionado
        )
    ) {
        selector.value = seleccionado;
    }
}


/*=========================================================
  PEDIDOS DE COMPRA
=========================================================*/

function cargarPedidosHU21(
    pedidos
) {

    const selector =
        document.getElementById(
            "pedidoMovimiento"
        );


    if (!selector) {
        return;
    }


    const seleccionado =
        selector.value;


    selector.innerHTML = "";


    const opcionInicial =
        document.createElement(
            "option"
        );


    opcionInicial.value = "";


    opcionInicial.textContent =
        pedidos.length > 0
            ? "Seleccione un pedido"
            : "No hay pedidos disponibles";


    selector.appendChild(
        opcionInicial
    );


    pedidos.forEach(
        (pedido) => {

            const opcion =
                document.createElement(
                    "option"
                );


            opcion.value =
                String(
                    pedido.id_pedido
                );


            const proveedor =
                pedido.proveedor ||
                "Proveedor sin nombre";


            const fecha =
                formatearFechaPedidoHU21(
                    pedido.fecha_pedido
                );


            const estado =
                pedido.estado ||
                "Sin estado";


            opcion.textContent =
                `Pedido #${pedido.id_pedido} — ` +
                `${proveedor} — ${fecha} — ${estado}`;


            opcion.dataset.idProveedor =
                String(
                    pedido.id_proveedor || ""
                );


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
                opcion.value === seleccionado
        )
    ) {
        selector.value = seleccionado;
    }
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

    const tipo =
        document.getElementById(
            "tipoMovimiento"
        )?.value;


    const esEntrada =
        tipo === "entrada";


    const grupoOrigenMercaderia =
        document.getElementById(
            "grupoOrigenMercaderia"
        );


    const grupoUbicacionOrigen =
        document.getElementById(
            "grupoUbicacionOrigen"
        );


    const origenMercaderia =
        document.getElementById(
            "origenMercaderia"
        );


    const destino =
        document.getElementById(
            "ubicacionDestino"
        );


    const boton =
        document.getElementById(
            "botonRegistrarMovimiento"
        );


    if (grupoOrigenMercaderia) {

        grupoOrigenMercaderia.hidden =
            !esEntrada;

    }


    if (grupoUbicacionOrigen) {

        grupoUbicacionOrigen.hidden =
            esEntrada;

    }


    if (origenMercaderia) {

        origenMercaderia.required =
            esEntrada;

    }


    if (destino) {

        destino.required =
            esEntrada;

    }


    /*
     * Ajusta los campos especiales de Entrada
     * según el origen seleccionado.
     */
    actualizarCamposOrigenEntradaHU21();


    /*
     * Por ahora solamente Entrada
     * está conectada a la base de datos.
     */
    if (boton) {

        boton.disabled =
            !esEntrada;

    }


    if (!esEntrada) {

        mostrarMensajeHU21(
            "Este tipo de movimiento aún no está disponible.",
            "info",
            "tipo-pendiente"
        );

        return;
    }


    const mensaje =
        document.getElementById(
            "mensajeMovimiento"
        );


    if (
        mensaje?.dataset.clave ===
        "tipo-pendiente"
    ) {

        ocultarMensajeHU21();

    }
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


    const mostrarObservaciones =
        esProveedor || esPedido || esDevolucion;


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

        observaciones.required =
            esDevolucion;

        observaciones.placeholder =
            esDevolucion
                ? "Indique el motivo de la devolución..."
                : "Información adicional sobre el movimiento...";

        if (!mostrarObservaciones) {
            observaciones.value = "";
        }
    }
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

    const resumen =
        document.getElementById(
            "textoResumenEntrada"
        );


    if (!resumen) {
        return;
    }


    const producto =
        document.getElementById(
            "productoMovimiento"
        );


    const destino =
        document.getElementById(
            "ubicacionDestino"
        );


    const cantidad =
        Number(
            document.getElementById(
                "cantidadMovimiento"
            )?.value || 0
        );


    if (
        !producto?.value &&
        !destino?.value &&
        cantidad <= 0
    ) {

        resumen.textContent =
            "Seleccione producto, ubicación y cantidad.";

        return;
    }


    const nombreProducto =
        producto?.value
            ? producto
                .selectedOptions[0]
                ?.textContent
                ?.trim()
            : "Sin seleccionar";


    const nombreDestino =
        destino?.value
            ? destino
                .selectedOptions[0]
                ?.textContent
                ?.trim()
            : "Sin seleccionar";


    const origen =
        document.getElementById(
            "origenMercaderia"
        )?.value || "";


    let detalleOrigen =
        origen || "Sin seleccionar";


    if (origen === "Proveedor") {

        const proveedor =
            document.getElementById(
                "proveedorMovimiento"
            );

        if (proveedor?.value) {
            detalleOrigen +=
                `: ${proveedor.selectedOptions[0]?.textContent?.trim() || ""}`;
        }
    }


    if (origen === "Pedido de compra") {

        const pedido =
            document.getElementById(
                "pedidoMovimiento"
            );

        if (pedido?.value) {
            detalleOrigen +=
                `: ${pedido.selectedOptions[0]?.textContent?.trim() || ""}`;
        }
    }


    resumen.textContent =
        `Producto: ${nombreProducto} | ` +
        `Destino: ${nombreDestino} | ` +
        `Cantidad: ${cantidad > 0 ? cantidad : 0} | ` +
        `Origen: ${detalleOrigen}`;
}


/*=========================================================
  REGISTRAR ENTRADA
=========================================================*/

async function registrarEntradaHU21(
    evento
) {

    evento.preventDefault();


    const tipo =
        document.getElementById(
            "tipoMovimiento"
        )?.value;


    if (tipo !== "entrada") {

        mostrarMensajeHU21(
            "Este tipo de movimiento aún no está disponible.",
            "info",
            "tipo-pendiente"
        );

        return;
    }


    const idProducto =
        Number(
            document.getElementById(
                "productoMovimiento"
            )?.value || 0
        );


    const idUbicacion =
        Number(
            document.getElementById(
                "ubicacionDestino"
            )?.value || 0
        );


    const cantidad =
        Number(
            document.getElementById(
                "cantidadMovimiento"
            )?.value || 0
        );


    const origen =
        document.getElementById(
            "origenMercaderia"
        )?.value || "";


    const proveedorTexto =
        document.getElementById(
            "proveedorMovimiento"
        )?.value.trim() || "";


    const pedidoTexto =
        document.getElementById(
            "pedidoMovimiento"
        )?.value.trim() || "";


    const referencia =
        document.getElementById(
            "referenciaMovimiento"
        )?.value.trim() || "";


    const observaciones =
        document.getElementById(
            "observacionesMovimiento"
        )?.value.trim() || "";


    /* ======================================================
       VALIDACIONES
    ====================================================== */

    if (idProducto <= 0) {

        mostrarMensajeHU21(
            "Debe seleccionar un producto.",
            "error"
        );

        return;
    }


    if (idUbicacion <= 0) {

        mostrarMensajeHU21(
            "Debe seleccionar una ubicación de destino.",
            "error"
        );

        return;
    }


    if (
        !Number.isInteger(
            cantidad
        ) ||
        cantidad <= 0
    ) {

        mostrarMensajeHU21(
            "La cantidad debe ser un número entero mayor que cero.",
            "error"
        );

        return;
    }


    const origenesPermitidos = [
        "Proveedor",
        "Pedido de compra",
        "Devolución"
    ];


    if (!origenesPermitidos.includes(origen)) {

        mostrarMensajeHU21(
            "Debe indicar un origen válido para la mercadería.",
            "error"
        );

        return;
    }


    let idProveedor =
        null;


    if (origen === "Proveedor") {

        if (proveedorTexto === "") {

            mostrarMensajeHU21(
                "Debe seleccionar un proveedor.",
                "error"
            );

            return;
        }


        idProveedor =
            Number(
                proveedorTexto
            );


        if (
            !Number.isInteger(
                idProveedor
            ) ||
            idProveedor <= 0 ||
            !estadoHU21.proveedores.some(
                (proveedor) =>
                    Number(proveedor.id_proveedor) ===
                    idProveedor
            )
        ) {

            mostrarMensajeHU21(
                "El proveedor seleccionado no es válido.",
                "error"
            );

            return;
        }
    }


    if (
        origen === "Proveedor" &&
        !referencia
    ) {

        mostrarMensajeHU21(
            "Debe indicar el documento o referencia del proveedor.",
            "error"
        );

        return;
    }


    if (
        origen === "Devolución" &&
        !observaciones
    ) {

        mostrarMensajeHU21(
            "Debe indicar el motivo de la devolución.",
            "error"
        );

        return;
    }


    let idPedido =
        null;


    if (origen === "Pedido de compra") {

        if (pedidoTexto === "") {

            mostrarMensajeHU21(
                "Debe seleccionar un pedido de compra.",
                "error"
            );

            return;
        }


        idPedido =
            Number(
                pedidoTexto
            );


        if (
            !Number.isInteger(
                idPedido
            ) ||
            idPedido <= 0 ||
            !estadoHU21.pedidos.some(
                (pedido) =>
                    Number(pedido.id_pedido) ===
                    idPedido
            )
        ) {

            mostrarMensajeHU21(
                "El pedido seleccionado no es válido o ya no está disponible.",
                "error"
            );

            return;
        }
    }


    const datos = {

        id_producto:
            idProducto,

        id_ubicacion:
            idUbicacion,

        cantidad:
            cantidad,

        origen:
            origen,

        referencia:
            referencia,

        observaciones:
            observaciones,

        id_pedido:
            idPedido,

        id_proveedor:
            idProveedor

    };


    /* ======================================================
       BLOQUEAR BOTÓN
    ====================================================== */

    const boton =
        document.getElementById(
            "botonRegistrarMovimiento"
        );


    if (boton) {

        boton.disabled =
            true;


        boton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';

    }


    mostrarMensajeHU21(
        "Registrando entrada de mercadería...",
        "info",
        "registrando"
    );


    /* ======================================================
       LLAMAR API
    ====================================================== */

    try {

        const respuesta =
            await fetch(
                "../api/inventario/entrada.php",
                {
                    method:
                        "POST",

                    credentials:
                        "same-origin",

                    cache:
                        "no-store",

                    headers: {

                        "Accept":
                            "application/json",

                        "Content-Type":
                            "application/json"

                    },

                    body:
                        JSON.stringify(
                            datos
                        )
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
                "No fue posible registrar la entrada."
            );
        }


        const respuestaDatos =
            resultado.data || {};


        mostrarMensajeHU21(
            `${resultado.message} ` +
            `Stock anterior: ` +
            `${respuestaDatos.saldo_anterior ?? 0}. ` +
            `Nuevo stock: ` +
            `${respuestaDatos.saldo_resultante ?? 0}. ` +
            `Movimiento #` +
            `${respuestaDatos.id_movimiento ?? ""}.`,
            "exito",
            "entrada-registrada"
        );


        actualizarTarjetaEntradasHU21(
            cantidad
        );


        limpiarFormularioDespuesEntradaHU21();


    } catch (error) {

        console.error(
            "HU-21 - Error registrando entrada:",
            error
        );


        mostrarMensajeHU21(
            error.message ||
            "No fue posible registrar la entrada de mercadería.",
            "error"
        );


    } finally {

        if (boton) {

            boton.disabled =
                false;


            boton.innerHTML =
                '<i class="fa-solid fa-floppy-disk"></i> Registrar movimiento';

        }
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


    estadoHU21.productoSeleccionadoId =
        "";


    if (tipo) {

        tipo.value =
            "entrada";

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


    actualizarResumenHU21();


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