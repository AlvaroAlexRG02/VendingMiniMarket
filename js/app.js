/*=========================================================
  VENDING MINI MARKET — Script general del sistema
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {
    inicializarMostrarContrasena();
    inicializarMenuLateral();
    inicializarSesionVending();
    inicializarFormularioProducto();
    inicializarPaginaProductos();
    inicializarPaginaInventarioVending();
    inicializarPaginaMovimientosVending();
    inicializarPaginaVentas();
    inicializarDashboardVending();
    actualizarPerfilSuperiorVending();
    actualizarContadoresNotificacionVending();
    inicializarPaginaUsuariosVending();
    inicializarPaginaAlertasVending();
    inicializarPaginaReportesVending();
    inicializarPaginaComprasVending();
    inicializarPaginaPedidosVending();
    inicializarPaginaFacturasVending();
});

/*=========================================================
  INICIO DE SESIÓN
=========================================================*/

/**
 * Alterna la visibilidad del campo de contraseña.
 */
function inicializarMostrarContrasena() {
    const boton = document.getElementById(
        "botonMostrarContrasena"
    );

    const campo = document.getElementById("contrasena");
    const icono = document.getElementById("iconoContrasena");

    if (!boton || !campo || !icono) {
        return;
    }

    boton.addEventListener("click", () => {
        const contrasenaVisible = campo.type === "text";

        if (contrasenaVisible) {
            campo.type = "password";

            icono.classList.remove("fa-eye-slash");
            icono.classList.add("fa-eye");

            boton.setAttribute(
                "title",
                "Mostrar contraseña"
            );

            boton.setAttribute(
                "aria-label",
                "Mostrar contraseña"
            );
        } else {
            campo.type = "text";

            icono.classList.remove("fa-eye");
            icono.classList.add("fa-eye-slash");

            boton.setAttribute(
                "title",
                "Ocultar contraseña"
            );

            boton.setAttribute(
                "aria-label",
                "Ocultar contraseña"
            );
        }
    });
}

/*=========================================================
  MENÚ LATERAL
=========================================================*/

/**
 * Colapsa o expande la barra lateral.
 */
function inicializarMenuLateral() {
    const botonMenu = document.querySelector(".boton-menu");

    const barraLateral = document.querySelector(
        ".barra-lateral"
    );

    if (!botonMenu || !barraLateral) {
        return;
    }

    botonMenu.addEventListener("click", () => {
        barraLateral.classList.toggle(
            "barra-lateral-abierta"
        );
    });
}

/*=========================================================
  REGISTRO DE PRODUCTOS
=========================================================*/

/**
 * Configura el formulario de registro de productos.
 */
function inicializarFormularioProducto() {
    const formulario = document.getElementById(
        "formularioProducto"
    );

    const campoImagen = document.getElementById(
        "imagenProducto"
    );

    const botonLimpiar = document.getElementById(
        "botonLimpiarProducto"
    );

    if (!formulario) {
        return;
    }

    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();

        try {
            const producto = await obtenerDatosProducto();

            const errorValidacion =
                validarDatosProducto(producto);

            if (errorValidacion) {
                mostrarMensajeProducto(
                    errorValidacion,
                    "error"
                );

                return;
            }

            const productos = obtenerProductosGuardados();

            const productoDuplicado =
                buscarProductoDuplicado(
                    producto,
                    productos
                );

            if (productoDuplicado) {
                mostrarMensajeProducto(
                    "Ya existe un producto con un nombre o código semejante.",
                    "error"
                );

                return;
            }

            productos.push(producto);

            guardarProductos(productos);

            mostrarMensajeProducto(
                "El producto fue guardado correctamente.",
                "exito"
            );

            formulario.reset();
            limpiarVistaPreviaProducto();

            setTimeout(() => {
                window.location.href = "productos.html";
            }, 1200);
        } catch (error) {
            console.error(
                "No se pudo guardar el producto:",
                error
            );

            mostrarMensajeProducto(
                "No fue posible guardar el producto.",
                "error"
            );
        }
    });

    if (campoImagen) {
        campoImagen.addEventListener("change", async () => {
            const archivo = campoImagen.files[0];

            if (!archivo) {
                limpiarVistaPreviaProducto();
                return;
            }

            if (!archivo.type.startsWith("image/")) {
                mostrarMensajeProducto(
                    "El archivo seleccionado no es una imagen.",
                    "error"
                );

                campoImagen.value = "";
                limpiarVistaPreviaProducto();

                return;
            }

            const limiteBytes = 800 * 1024;

            if (archivo.size > limiteBytes) {
                mostrarMensajeProducto(
                    "La imagen debe pesar menos de 800 KB.",
                    "error"
                );

                campoImagen.value = "";
                limpiarVistaPreviaProducto();

                return;
            }

            try {
                const imagenBase64 =
                    await convertirImagenABase64(archivo);

                mostrarVistaPreviaProducto(imagenBase64);
            } catch (error) {
                console.error(error);

                mostrarMensajeProducto(
                    "No fue posible cargar la imagen.",
                    "error"
                );
            }
        });
    }

    if (botonLimpiar) {
        botonLimpiar.addEventListener("click", () => {
            limpiarVistaPreviaProducto();
            limpiarMensajeProducto();
        });
    }
}

/**
 * Obtiene los datos escritos en producto-nuevo.html.
 */
async function obtenerDatosProducto() {
    const campoNombre =
        document.getElementById("nombreProducto");

    const campoCodigo =
        document.getElementById("codigoProducto");

    const campoCategoria =
        document.getElementById("categoriaProducto");

    const campoProveedor =
        document.getElementById("proveedorProducto");

    const campoPrecioCompra =
        document.getElementById("precioCompraProducto");

    const campoPrecioVenta =
        document.getElementById("precioVentaProducto");

    const campoStock =
        document.getElementById("stockProducto");

    const campoStockMinimo =
        document.getElementById("stockMinimoProducto");

    const campoUbicacion =
        document.getElementById("ubicacionProducto");

    const campoEstado =
        document.getElementById("estadoProducto");

    const campoImpuesto =
        document.getElementById("impuestoProducto");

    const campoDescripcion =
        document.getElementById("descripcionProducto");

    const campoImagen =
        document.getElementById("imagenProducto");

    let imagen = "";

    if (
        campoImagen &&
        campoImagen.files &&
        campoImagen.files.length > 0
    ) {
        imagen = await convertirImagenABase64(
            campoImagen.files[0]
        );
    }

    return {
        id: generarIdProducto(),

        nombre: campoNombre
            ? campoNombre.value.trim()
            : "",

        codigo: campoCodigo
            ? campoCodigo.value.trim()
            : "",

        categoria: campoCategoria
            ? campoCategoria.value
            : "",

        proveedor: campoProveedor
            ? campoProveedor.value.trim()
            : "",

        precioCompra: campoPrecioCompra
            ? Number(campoPrecioCompra.value)
            : 0,

        precioVenta: campoPrecioVenta
            ? Number(campoPrecioVenta.value)
            : 0,

        stock: campoStock
            ? Number(campoStock.value)
            : 0,

        stockMinimo: campoStockMinimo
            ? Number(campoStockMinimo.value)
            : 0,

        ubicacion: campoUbicacion
            ? campoUbicacion.value
            : "",

        estado: campoEstado
            ? campoEstado.value
            : "Activo",

        impuesto: campoImpuesto
            ? Number(campoImpuesto.value)
            : 0,

        descripcion: campoDescripcion
            ? campoDescripcion.value.trim()
            : "",

        imagen,

        fechaRegistro: new Date().toISOString(),

        fechaActualizacion: new Date().toISOString()
    };
}

/**
 * Valida datos numéricos y campos requeridos.
 */
function validarDatosProducto(producto) {
    if (!producto.nombre) {
        return "Debe ingresar el nombre del producto.";
    }

    if (!producto.codigo) {
        return "Debe ingresar el código del producto.";
    }

    if (!producto.categoria) {
        return "Debe seleccionar una categoría.";
    }

    if (!producto.proveedor) {
        return "Debe ingresar el proveedor.";
    }

    if (
        !Number.isFinite(producto.precioCompra) ||
        producto.precioCompra < 0
    ) {
        return "El precio de compra no es válido.";
    }

    if (
        !Number.isFinite(producto.precioVenta) ||
        producto.precioVenta < 0
    ) {
        return "El precio de venta no es válido.";
    }

    if (
        !Number.isInteger(producto.stock) ||
        producto.stock < 0
    ) {
        return "El stock debe ser un número entero mayor o igual a cero.";
    }

    if (
        !Number.isInteger(producto.stockMinimo) ||
        producto.stockMinimo < 0
    ) {
        return "El stock mínimo debe ser un número entero mayor o igual a cero.";
    }

    return "";
}

/**
 * Revisa posibles duplicados.
 */
function buscarProductoDuplicado(
    productoNuevo,
    productos
) {
    const nombreNuevo = normalizarTexto(
        productoNuevo.nombre
    );

    const codigoNuevo = normalizarTexto(
        productoNuevo.codigo
    );

    return productos.find((productoGuardado) => {
        const mismoNombre =
            normalizarTexto(productoGuardado.nombre) ===
            nombreNuevo;

        const mismoCodigo =
            normalizarTexto(productoGuardado.codigo) ===
            codigoNuevo;

        return mismoNombre || mismoCodigo;
    });
}

/*=========================================================
  ALMACENAMIENTO DE PRODUCTOS
=========================================================*/

/**
 * Obtiene los productos almacenados en localStorage.
 */
function obtenerProductosGuardados() {
    try {
        const productosGuardados = localStorage.getItem(
            "productosVending"
        );

        if (!productosGuardados) {
            return [];
        }

        const productos = JSON.parse(
            productosGuardados
        );

        return Array.isArray(productos)
            ? productos
            : [];
    } catch (error) {
        console.error(
            "No se pudieron leer los productos:",
            error
        );

        return [];
    }
}

/**
 * Guarda la lista completa de productos.
 */
function guardarProductos(productos) {
    try {
        localStorage.setItem(
            "productosVending",
            JSON.stringify(productos)
        );

        return true;
    } catch (error) {
        console.error(
            "No se pudieron guardar los productos:",
            error
        );

        return false;
    }
}

/**
 * Genera un identificador interno.
 */
function generarIdProducto() {
    return `producto-${Date.now()}-${Math.floor(
        Math.random() * 1000
    )}`;
}

/*=========================================================
  IMAGEN DEL PRODUCTO
=========================================================*/

/**
 * Convierte una imagen en Base64.
 */
function convertirImagenABase64(archivo) {
    return new Promise((resolve, reject) => {
        const lector = new FileReader();

        lector.onload = () => {
            resolve(lector.result);
        };

        lector.onerror = () => {
            reject(
                new Error(
                    "No fue posible leer la imagen."
                )
            );
        };

        lector.readAsDataURL(archivo);
    });
}

/**
 * Muestra la imagen seleccionada.
 */
function mostrarVistaPreviaProducto(imagenBase64) {
    const contenedor = document.getElementById(
        "contenedorVistaPrevia"
    );

    const imagen = document.getElementById(
        "vistaPreviaProducto"
    );

    if (!contenedor || !imagen) {
        return;
    }

    imagen.src = imagenBase64;
    contenedor.hidden = false;
}

/**
 * Limpia la imagen seleccionada.
 */
function limpiarVistaPreviaProducto() {
    const contenedor = document.getElementById(
        "contenedorVistaPrevia"
    );

    const imagen = document.getElementById(
        "vistaPreviaProducto"
    );

    if (imagen) {
        imagen.src = "";
    }

    if (contenedor) {
        contenedor.hidden = true;
    }
}

/*=========================================================
  MENSAJES DEL FORMULARIO
=========================================================*/

/**
 * Muestra un mensaje de éxito o error.
 */
function mostrarMensajeProducto(mensaje, tipo) {
    const contenedor = document.getElementById(
        "mensajeProducto"
    );

    if (!contenedor) {
        return;
    }

    contenedor.textContent = mensaje;

    contenedor.className =
        `mensaje-producto mensaje-${tipo}`;

    contenedor.hidden = false;
}

/**
 * Limpia el mensaje del formulario.
 */
function limpiarMensajeProducto() {
    const contenedor = document.getElementById(
        "mensajeProducto"
    );

    if (!contenedor) {
        return;
    }

    contenedor.textContent = "";
    contenedor.className = "mensaje-producto";
    contenedor.hidden = true;
}

/*=========================================================
  PÁGINA DE PRODUCTOS
=========================================================*/

/**
 * Inicializa búsqueda, filtros y tabla.
 */
function inicializarPaginaProductos() {
    const cuerpoTabla = document.getElementById(
        "cuerpoTablaProductos"
    );

    if (!cuerpoTabla) {
        return;
    }

    const buscador = document.getElementById(
        "buscarProducto"
    );

    const filtroCategoria = document.getElementById(
        "filtroCategoriaProducto"
    );

    const filtroEstado = document.getElementById(
        "filtroEstadoProducto"
    );

    const botonLimpiar = document.getElementById(
        "botonLimpiarFiltrosProductos"
    );

    cargarCategoriasFiltroProductos();
    mostrarProductos();

    if (buscador) {
        buscador.addEventListener("input", () => {
            mostrarProductos();
        });
    }

    if (filtroCategoria) {
        filtroCategoria.addEventListener(
            "change",
            () => {
                mostrarProductos();
            }
        );
    }

    if (filtroEstado) {
        filtroEstado.addEventListener(
            "change",
            () => {
                mostrarProductos();
            }
        );
    }

    if (botonLimpiar) {
        botonLimpiar.addEventListener("click", () => {
            if (buscador) {
                buscador.value = "";
            }

            if (filtroCategoria) {
                filtroCategoria.value = "";
            }

            if (filtroEstado) {
                filtroEstado.value = "";
            }

            mostrarProductos();
            buscador?.focus();
        });
    }

    inicializarModalEstadoProducto();
}

/**
 * Carga las categorías existentes.
 */
function cargarCategoriasFiltroProductos() {
    const selector = document.getElementById(
        "filtroCategoriaProducto"
    );

    if (!selector) {
        return;
    }

    const productos = obtenerProductosGuardados();

    const categorias = [
        ...new Set(
            productos
                .map((producto) =>
                    producto.categoria
                        ? producto.categoria.trim()
                        : ""
                )
                .filter(Boolean)
        )
    ].sort((categoriaA, categoriaB) =>
        categoriaA.localeCompare(
            categoriaB,
            "es"
        )
    );

    selector.innerHTML =
        '<option value="">Todas las categorías</option>';

    categorias.forEach((categoria) => {
        const opcion = document.createElement("option");

        opcion.value = categoria;
        opcion.textContent = categoria;

        selector.appendChild(opcion);
    });
}

/**
 * Filtra y presenta los productos.
 */
function mostrarProductos() {
    const cuerpoTabla = document.getElementById(
        "cuerpoTablaProductos"
    );

    const contenedorTabla = document.getElementById(
        "contenedorTablaProductos"
    );

    const mensajeVacio = document.getElementById(
        "mensajeSinProductos"
    );

    const mensajeSinResultados =
        document.getElementById(
            "mensajeSinResultadosProductos"
        );

    const resultadoBusqueda =
        document.getElementById(
            "resultadoBusquedaProductos"
        );

    if (!cuerpoTabla) {
        return;
    }

    const productos = obtenerProductosGuardados();

    actualizarResumenProductos(productos);

    const productosFiltrados =
        filtrarProductos(productos);

    cuerpoTabla.innerHTML = "";

    const hayProductos = productos.length > 0;

    const hayResultados =
        productosFiltrados.length > 0;

    if (contenedorTabla) {
        contenedorTabla.hidden =
            !hayProductos || !hayResultados;
    }

    if (mensajeVacio) {
        mensajeVacio.hidden = hayProductos;
    }

    if (mensajeSinResultados) {
        mensajeSinResultados.hidden =
            !hayProductos || hayResultados;
    }

    if (resultadoBusqueda) {
        resultadoBusqueda.textContent = hayProductos
            ? `${productosFiltrados.length} producto(s) encontrado(s).`
            : "";
    }

    productosFiltrados.forEach((producto) => {
        cuerpoTabla.appendChild(
            crearFilaProducto(producto)
        );
    });
}

/**
 * Aplica búsqueda y filtros.
 */
function filtrarProductos(productos) {
    const buscador = document.getElementById(
        "buscarProducto"
    );

    const filtroCategoria = document.getElementById(
        "filtroCategoriaProducto"
    );

    const filtroEstado = document.getElementById(
        "filtroEstadoProducto"
    );

    const textoBusqueda = normalizarTexto(
        buscador ? buscador.value : ""
    );

    const categoriaSeleccionada =
        filtroCategoria
            ? filtroCategoria.value
            : "";

    const estadoSeleccionado =
        filtroEstado
            ? filtroEstado.value
            : "";

    return productos.filter((producto) => {
        const textoProducto = normalizarTexto(
            [
                producto.nombre,
                producto.codigo,
                producto.categoria,
                producto.proveedor
            ].join(" ")
        );

        const coincideBusqueda =
            !textoBusqueda ||
            textoProducto.includes(textoBusqueda);

        const coincideCategoria =
            !categoriaSeleccionada ||
            producto.categoria ===
                categoriaSeleccionada;

        const coincideEstado =
            !estadoSeleccionado ||
            producto.estado ===
                estadoSeleccionado;

        return (
            coincideBusqueda &&
            coincideCategoria &&
            coincideEstado
        );
    });
}

/**
 * Construye una fila sin insertar texto inseguro.
 */
function crearFilaProducto(producto) {
    const fila = document.createElement("tr");

    const celdaProducto = document.createElement("td");
    const bloqueProducto = document.createElement("div");

    bloqueProducto.className = "producto-tabla";

    const contenedorImagen =
        document.createElement("div");

    contenedorImagen.className =
        "producto-tabla-imagen";

    if (producto.imagen) {
        const imagen = document.createElement("img");

        imagen.src = producto.imagen;
        imagen.alt = `Imagen de ${producto.nombre}`;

        contenedorImagen.appendChild(imagen);
    } else {
        const icono = document.createElement("i");

        icono.className = "fa-solid fa-box";
        icono.setAttribute("aria-hidden", "true");

        contenedorImagen.appendChild(icono);
    }

    const informacion =
        document.createElement("div");

    informacion.className =
        "producto-tabla-informacion";

    const nombre = document.createElement("strong");
    nombre.textContent =
        producto.nombre || "Sin nombre";

    const descripcion = document.createElement("span");

    descripcion.textContent =
        producto.descripcion ||
        producto.ubicacion ||
        "Sin descripción";

    informacion.appendChild(nombre);
    informacion.appendChild(descripcion);

    bloqueProducto.appendChild(contenedorImagen);
    bloqueProducto.appendChild(informacion);

    celdaProducto.appendChild(bloqueProducto);

    const celdaCodigo = crearCeldaTexto(
        producto.codigo || "Sin código"
    );

    const celdaCategoria = crearCeldaTexto(
        producto.categoria || "Sin categoría"
    );

    const celdaProveedor = crearCeldaTexto(
        producto.proveedor || "Sin proveedor"
    );

    const celdaCosto = crearCeldaTexto(
        formatearMonedaCRC(producto.precioCompra)
    );

    const celdaImpuesto = crearCeldaTexto(
        `${Number(producto.impuesto) || 0}%`
    );

    const celdaStock = document.createElement("td");

    const insigniaStock =
        document.createElement("span");

    const stock = Number(producto.stock) || 0;

    const stockMinimo =
        Number(producto.stockMinimo) || 0;

    if (stock === 0) {
        insigniaStock.className =
            "insignia insignia-roja";

        insigniaStock.textContent = "Sin stock";
    } else if (stock <= stockMinimo) {
        insigniaStock.className =
            "insignia insignia-amarilla";

        insigniaStock.textContent =
            `${stock} - Stock bajo`;
    } else {
        insigniaStock.className =
            "insignia insignia-verde";

        insigniaStock.textContent =
            `${stock} unidades`;
    }

    celdaStock.appendChild(insigniaStock);

    const celdaEstado = document.createElement("td");

    const insigniaEstado =
        document.createElement("span");

    const estado =
        producto.estado === "Inactivo"
            ? "Inactivo"
            : "Activo";

    insigniaEstado.className =
        estado === "Activo"
            ? "insignia insignia-verde"
            : "insignia insignia-gris";

    insigniaEstado.textContent = estado;

    celdaEstado.appendChild(insigniaEstado);

    const celdaAcciones =
        document.createElement("td");

    const grupoAcciones =
        document.createElement("div");

    grupoAcciones.className =
        "acciones-producto-tabla";

    const botonEstado =
        document.createElement("button");

    botonEstado.type = "button";

    botonEstado.className =
        estado === "Activo"
            ? "boton-accion-producto boton-inactivar-producto"
            : "boton-accion-producto boton-activar-producto";

    botonEstado.title =
        estado === "Activo"
            ? "Inactivar producto"
            : "Activar producto";

    botonEstado.setAttribute(
        "aria-label",
        `${botonEstado.title}: ${producto.nombre}`
    );

    botonEstado.innerHTML =
        estado === "Activo"
            ? '<i class="fa-solid fa-ban"></i>'
            : '<i class="fa-solid fa-circle-check"></i>';

    botonEstado.addEventListener("click", () => {
        abrirModalEstadoProducto(producto.id);
    });

    grupoAcciones.appendChild(botonEstado);
    celdaAcciones.appendChild(grupoAcciones);

    fila.appendChild(celdaProducto);
    fila.appendChild(celdaCodigo);
    fila.appendChild(celdaCategoria);
    fila.appendChild(celdaProveedor);
    fila.appendChild(celdaCosto);
    fila.appendChild(celdaImpuesto);
    fila.appendChild(celdaStock);
    fila.appendChild(celdaEstado);
    fila.appendChild(celdaAcciones);

    return fila;
}

/**
 * Crea una celda de texto.
 */
function crearCeldaTexto(texto) {
    const celda = document.createElement("td");

    celda.textContent = String(texto);

    return celda;
}

/**
 * Actualiza los contadores.
 */
function actualizarResumenProductos(productos) {
    const total = document.getElementById(
        "totalProductos"
    );

    const activos = document.getElementById(
        "productosActivos"
    );

    const inactivos = document.getElementById(
        "productosInactivos"
    );

    const stockBajo = document.getElementById(
        "productosStockBajo"
    );

    if (total) {
        total.textContent = productos.length;
    }

    if (activos) {
        activos.textContent = productos.filter(
            (producto) =>
                producto.estado !== "Inactivo"
        ).length;
    }

    if (inactivos) {
        inactivos.textContent = productos.filter(
            (producto) =>
                producto.estado === "Inactivo"
        ).length;
    }

    if (stockBajo) {
        stockBajo.textContent = productos.filter(
            (producto) => {
                const stock =
                    Number(producto.stock) || 0;

                const minimo =
                    Number(producto.stockMinimo) || 0;

                return stock <= minimo;
            }
        ).length;
    }
}

/*=========================================================
  ACTIVAR O INACTIVAR PRODUCTOS
=========================================================*/

let idProductoCambioEstado = null;

/**
 * Prepara el cuadro de confirmación.
 */
function inicializarModalEstadoProducto() {
    const botonCancelar = document.getElementById(
        "botonCancelarEstadoProducto"
    );

    const botonConfirmar = document.getElementById(
        "botonConfirmarEstadoProducto"
    );

    const fondos = document.querySelectorAll(
        "[data-cerrar-modal-producto]"
    );

    if (botonCancelar) {
        botonCancelar.addEventListener(
            "click",
            cerrarModalEstadoProducto
        );
    }

    if (botonConfirmar) {
        botonConfirmar.addEventListener(
            "click",
            confirmarCambioEstadoProducto
        );
    }

    fondos.forEach((fondo) => {
        fondo.addEventListener(
            "click",
            cerrarModalEstadoProducto
        );
    });

    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") {
            cerrarModalEstadoProducto();
        }
    });
}

/**
 * Abre la confirmación del cambio de estado.
 */
function abrirModalEstadoProducto(idProducto) {
    const productos = obtenerProductosGuardados();

    const producto = productos.find(
        (elemento) =>
            elemento.id === idProducto
    );

    const modal = document.getElementById(
        "modalEstadoProducto"
    );

    const texto = document.getElementById(
        "textoModalEstadoProducto"
    );

    const botonConfirmar = document.getElementById(
        "botonConfirmarEstadoProducto"
    );

    if (
        !producto ||
        !modal ||
        !texto ||
        !botonConfirmar
    ) {
        return;
    }

    idProductoCambioEstado = idProducto;

    const nuevoEstado =
        producto.estado === "Inactivo"
            ? "Activo"
            : "Inactivo";

    texto.textContent =
        `El producto "${producto.nombre}" cambiará al estado ${nuevoEstado}.`;

    botonConfirmar.textContent =
        nuevoEstado === "Activo"
            ? "Activar producto"
            : "Inactivar producto";

    modal.hidden = false;

    document.body.classList.add(
        "modal-producto-abierto"
    );

    botonConfirmar.focus();
}

/**
 * Cierra el cuadro de confirmación.
 */
function cerrarModalEstadoProducto() {
    const modal = document.getElementById(
        "modalEstadoProducto"
    );

    if (!modal || modal.hidden) {
        return;
    }

    modal.hidden = true;
    idProductoCambioEstado = null;

    document.body.classList.remove(
        "modal-producto-abierto"
    );
}

/**
 * Cambia el estado sin eliminar el registro.
 */
function confirmarCambioEstadoProducto() {
    if (!idProductoCambioEstado) {
        return;
    }

    const productos = obtenerProductosGuardados();

    const indice = productos.findIndex(
        (producto) =>
            producto.id === idProductoCambioEstado
    );

    if (indice === -1) {
        cerrarModalEstadoProducto();
        return;
    }

    productos[indice].estado =
        productos[indice].estado === "Inactivo"
            ? "Activo"
            : "Inactivo";

    productos[indice].fechaActualizacion =
        new Date().toISOString();

    guardarProductos(productos);

    cerrarModalEstadoProducto();
    cargarCategoriasFiltroProductos();
    mostrarProductos();
}

/*=========================================================
  UTILIDADES
=========================================================*/

/**
 * Convierte texto para realizar búsquedas consistentes.
 */
function normalizarTexto(texto) {
    return String(texto || "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

/**
 * Convierte un número al formato de colones.
 */
function formatearMonedaCRC(valor) {
    return new Intl.NumberFormat(
        "es-CR",
        {
            style: "currency",
            currency: "CRC",
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }
    ).format(Number(valor) || 0);
}

/*=========================================================
  MÓDULO DE VENTAS
=========================================================*/

function inicializarPaginaVentas() {
    const cuerpoTabla = document.getElementById(
        "cuerpoTablaVentas"
    );

    if (!cuerpoTabla) {
        return;
    }

    inicializarRegistroVenta();
    inicializarImportacionDirectaNayax();
    inicializarCierreCaja();
    inicializarFiltrosVentas();

    cargarProductosEnVenta();
    mostrarVentas();
    establecerFechaCierreActual();
}

/*=========================================================
  IMPORTACIÓN DIRECTA DE ARCHIVO NAYAX
=========================================================*/

function inicializarImportacionDirectaNayax() {
    const botonImportar = document.getElementById(
        "botonImportarVentasNayax"
    );

    const campoArchivo = document.getElementById(
        "archivoVentasNayax"
    );

    if (!botonImportar || !campoArchivo) {
        return;
    }

    botonImportar.addEventListener("click", () => {
        /*
         * Reinicia el campo para permitir volver a
         * seleccionar el mismo archivo.
         */
        campoArchivo.value = "";
        campoArchivo.click();
    });

    campoArchivo.addEventListener("change", () => {
        const archivo = campoArchivo.files[0];

        if (!archivo) {
            return;
        }

        procesarArchivoNayaxSeleccionado(archivo);
    });
}

function procesarArchivoNayaxSeleccionado(archivo) {
    const extension = obtenerExtensionArchivo(
        archivo.name
    );

    const extensionesPermitidas = [
        "csv",
        "xlsx",
        "xls"
    ];

    if (!extensionesPermitidas.includes(extension)) {
        mostrarMensajeVentas(
            "El documento debe tener formato CSV, XLSX o XLS.",
            "error"
        );

        return;
    }

    const limiteArchivo = 10 * 1024 * 1024;

    if (archivo.size > limiteArchivo) {
        mostrarMensajeVentas(
            "El archivo no puede superar los 10 MB.",
            "error"
        );

        return;
    }

    const importaciones = obtenerImportacionesNayax();

    const archivoDuplicado = importaciones.some(
        (importacion) =>
            importacion.nombreArchivo === archivo.name &&
            Number(importacion.tamanio) === archivo.size
    );

    if (archivoDuplicado) {
        mostrarMensajeVentas(
            "Este documento de Nayax ya fue seleccionado anteriormente.",
            "error"
        );

        mostrarInformacionArchivoNayax(
            archivo,
            "Duplicado"
        );

        return;
    }

    const importacion = {
        id: `nayax-${Date.now()}`,
        nombreArchivo: archivo.name,
        tamanio: archivo.size,
        tipo: archivo.type || extension,
        fechaSeleccion: new Date().toISOString(),
        estado: "Pendiente",
        usuarioResponsable: obtenerSesionVending()?.nombre || "Administrador"
    };

    importaciones.push(importacion);

    localStorage.setItem(
        "importacionesNayax",
        JSON.stringify(importaciones)
    );

    mostrarInformacionArchivoNayax(
        archivo,
        "Pendiente"
    );

    actualizarCantidadImportacionesPendientes();

    mostrarMensajeVentas(
        `El archivo "${archivo.name}" fue agregado y quedó pendiente de validación.`,
        "exito"
    );
}

function obtenerExtensionArchivo(nombreArchivo) {
    const partes = String(nombreArchivo).split(".");

    if (partes.length < 2) {
        return "";
    }

    return partes.pop().toLowerCase();
}

function obtenerImportacionesNayax() {
    try {
        const datos = JSON.parse(
            localStorage.getItem(
                "importacionesNayax"
            ) || "[]"
        );

        return Array.isArray(datos)
            ? datos
            : [];
    } catch (error) {
        console.error(
            "No se pudieron leer las importaciones:",
            error
        );

        return [];
    }
}

function mostrarInformacionArchivoNayax(
    archivo,
    estado
) {
    const contenedor = document.getElementById(
        "informacionArchivoNayax"
    );

    const nombre = document.getElementById(
        "nombreArchivoNayax"
    );

    const detalle = document.getElementById(
        "detalleArchivoNayax"
    );

    const insignia = document.getElementById(
        "estadoArchivoNayax"
    );

    if (
        !contenedor ||
        !nombre ||
        !detalle ||
        !insignia
    ) {
        return;
    }

    nombre.textContent = archivo.name;

    detalle.textContent =
        `${formatearTamanioArchivo(archivo.size)} · ` +
        `${obtenerExtensionArchivo(archivo.name).toUpperCase()}`;

    insignia.textContent =
        estado === "Duplicado"
            ? "Archivo duplicado"
            : "Pendiente de validación";

    insignia.className =
        estado === "Duplicado"
            ? "insignia insignia-roja"
            : "insignia insignia-amarilla";

    contenedor.hidden = false;
}

function formatearTamanioArchivo(bytes) {
    if (bytes < 1024) {
        return `${bytes} bytes`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(
        bytes /
        (1024 * 1024)
    ).toFixed(1)} MB`;
}

function actualizarCantidadImportacionesPendientes() {
    const elemento = document.getElementById(
        "ventasPendientes"
    );

    if (!elemento) {
        return;
    }

    const importacionesPendientes =
        obtenerImportacionesNayax().filter(
            (importacion) =>
                importacion.estado === "Pendiente"
        ).length;

    const ventasPendientes =
        obtenerVentasGuardadas().filter(
            (venta) =>
                venta.estado === "Pendiente"
        ).length;

    elemento.textContent =
        importacionesPendientes +
        ventasPendientes;
}

/*=========================================================
  ALMACENAMIENTO DE VENTAS
=========================================================*/

function obtenerVentasGuardadas() {
    try {
        const datos = JSON.parse(
            localStorage.getItem(
                "ventasVending"
            ) || "[]"
        );

        return Array.isArray(datos)
            ? datos
            : [];
    } catch (error) {
        console.error(
            "No se pudieron leer las ventas:",
            error
        );

        return [];
    }
}

function guardarVentas(ventas) {
    try {
        localStorage.setItem(
            "ventasVending",
            JSON.stringify(ventas)
        );

        return true;
    } catch (error) {
        console.error(
            "No se pudieron guardar las ventas:",
            error
        );

        return false;
    }
}

function generarIdVenta() {
    return `venta-${Date.now()}-${Math.floor(
        Math.random() * 1000
    )}`;
}

function generarNumeroVenta() {
    const ventas = obtenerVentasGuardadas();

    return `V-${String(
        ventas.length + 1
    ).padStart(5, "0")}`;
}

/*=========================================================
  REGISTRO DE VENTA MANUAL
=========================================================*/

function inicializarRegistroVenta() {
    const botonesAbrir = [
        document.getElementById("botonNuevaVenta"),
        document.getElementById("botonPrimeraVenta")
    ];

    botonesAbrir.forEach((boton) => {
        boton?.addEventListener(
            "click",
            abrirModalVenta
        );
    });

    document.getElementById(
        "botonCerrarModalVenta"
    )?.addEventListener(
        "click",
        cerrarModalVenta
    );

    document.getElementById(
        "botonCancelarVenta"
    )?.addEventListener(
        "click",
        cerrarModalVenta
    );

    document.querySelectorAll(
        "[data-cerrar-modal-venta]"
    ).forEach((fondo) => {
        fondo.addEventListener(
            "click",
            cerrarModalVenta
        );
    });

    document.getElementById(
        "productoVenta"
    )?.addEventListener(
        "change",
        actualizarProductoSeleccionadoVenta
    );

    document.getElementById(
        "cantidadVenta"
    )?.addEventListener(
        "input",
        calcularFormularioVenta
    );

    document.getElementById(
        "metodoPagoVenta"
    )?.addEventListener(
        "change",
        actualizarCampoSinpe
    );

    document.getElementById(
        "formularioVenta"
    )?.addEventListener(
        "submit",
        registrarVentaManual
    );
}

function abrirModalVenta() {
    const modal = document.getElementById(
        "modalVenta"
    );

    if (!modal) {
        return;
    }

    cargarProductosEnVenta();

    modal.hidden = false;

    document.body.classList.add(
        "modal-producto-abierto"
    );

    document.getElementById(
        "tiendaVenta"
    )?.focus();
}

function cerrarModalVenta() {
    const modal = document.getElementById(
        "modalVenta"
    );

    if (!modal || modal.hidden) {
        return;
    }

    modal.hidden = true;

    document.body.classList.remove(
        "modal-producto-abierto"
    );

    document.getElementById(
        "formularioVenta"
    )?.reset();

    document.getElementById(
        "grupoReferenciaSinpe"
    ).hidden = true;

    document.getElementById(
        "referenciaSinpeVenta"
    ).required = false;

    limpiarTotalesFormularioVenta();
    limpiarMensajeFormularioVenta();
}

function cargarProductosEnVenta() {
    const selector = document.getElementById(
        "productoVenta"
    );

    if (!selector) {
        return;
    }

    const productos = obtenerProductosGuardados().filter(
        (producto) =>
            producto.estado !== "Inactivo" &&
            Number(producto.stock) > 0
    );

    selector.innerHTML = `
        <option value="">
            Seleccione un producto
        </option>
    `;

    productos.forEach((producto) => {
        const opcion = document.createElement("option");

        opcion.value = producto.id;

        opcion.textContent =
            `${producto.nombre} - Stock: ${producto.stock}`;

        selector.appendChild(opcion);
    });
}

function actualizarProductoSeleccionadoVenta() {
    const selector = document.getElementById(
        "productoVenta"
    );

    const precioCampo = document.getElementById(
        "precioUnitarioVenta"
    );

    const impuestoCampo = document.getElementById(
        "impuestoVenta"
    );

    if (
        !selector ||
        !precioCampo ||
        !impuestoCampo
    ) {
        return;
    }

    const producto = obtenerProductosGuardados().find(
        (elemento) =>
            elemento.id === selector.value
    );

    if (!producto) {
        precioCampo.value = "";
        impuestoCampo.value = "";
        limpiarTotalesFormularioVenta();
        return;
    }

    precioCampo.value =
        Number(producto.precioVenta) || 0;

    impuestoCampo.value =
        Number(producto.impuesto) || 0;

    calcularFormularioVenta();
}

function obtenerCalculoVenta() {
    const productoId = document.getElementById(
        "productoVenta"
    )?.value;

    const cantidad = Number(
        document.getElementById(
            "cantidadVenta"
        )?.value
    );

    const producto = obtenerProductosGuardados().find(
        (elemento) =>
            elemento.id === productoId
    );

    if (
        !producto ||
        !Number.isInteger(cantidad) ||
        cantidad <= 0
    ) {
        return {
            producto: null,
            cantidad: 0,
            subtotal: 0,
            impuestoMonto: 0,
            total: 0
        };
    }

    const precioUnitario =
        Number(producto.precioVenta) || 0;

    const porcentajeImpuesto =
        Number(producto.impuesto) || 0;

    const subtotal =
        precioUnitario * cantidad;

    const impuestoMonto =
        subtotal *
        (porcentajeImpuesto / 100);

    return {
        producto,
        cantidad,
        precioUnitario,
        porcentajeImpuesto,
        subtotal,
        impuestoMonto,
        total: subtotal + impuestoMonto
    };
}

function calcularFormularioVenta() {
    const calculo = obtenerCalculoVenta();

    document.getElementById(
        "subtotalFormularioVenta"
    ).textContent =
        formatearMonedaCRC(calculo.subtotal);

    document.getElementById(
        "impuestoFormularioVenta"
    ).textContent =
        formatearMonedaCRC(
            calculo.impuestoMonto
        );

    document.getElementById(
        "totalFormularioVenta"
    ).textContent =
        formatearMonedaCRC(calculo.total);
}

function limpiarTotalesFormularioVenta() {
    const ids = [
        "subtotalFormularioVenta",
        "impuestoFormularioVenta",
        "totalFormularioVenta"
    ];

    ids.forEach((id) => {
        const elemento =
            document.getElementById(id);

        if (elemento) {
            elemento.textContent = "₡0";
        }
    });
}

function actualizarCampoSinpe() {
    const metodoPago = document.getElementById(
        "metodoPagoVenta"
    );

    const grupo = document.getElementById(
        "grupoReferenciaSinpe"
    );

    const referencia = document.getElementById(
        "referenciaSinpeVenta"
    );

    if (
        !metodoPago ||
        !grupo ||
        !referencia
    ) {
        return;
    }

    const esSinpe =
        metodoPago.value === "SINPE";

    grupo.hidden = !esSinpe;
    referencia.required = esSinpe;

    if (!esSinpe) {
        referencia.value = "";
    }
}

function registrarVentaManual(evento) {
    evento.preventDefault();

    const tienda = document.getElementById(
        "tiendaVenta"
    )?.value;

    const metodoPago = document.getElementById(
        "metodoPagoVenta"
    )?.value;

    const referenciaSinpe = document.getElementById(
        "referenciaSinpeVenta"
    )?.value.trim();

    const observaciones = document.getElementById(
        "observacionesVenta"
    )?.value.trim();

    const calculo = obtenerCalculoVenta();

    if (
        !tienda ||
        !metodoPago ||
        !calculo.producto
    ) {
        mostrarMensajeFormularioVenta(
            "Complete todos los campos obligatorios.",
            "error"
        );

        return;
    }

    if (
        calculo.cantidad >
        Number(calculo.producto.stock)
    ) {
        mostrarMensajeFormularioVenta(
            "La cantidad supera el stock disponible.",
            "error"
        );

        return;
    }

    if (
        metodoPago === "SINPE" &&
        !referenciaSinpe
    ) {
        mostrarMensajeFormularioVenta(
            "Ingrese la referencia del SINPE.",
            "error"
        );

        return;
    }

    const venta = {
        id: generarIdVenta(),
        numero: generarNumeroVenta(),
        fecha: new Date().toISOString(),
        tienda,
        origen: "Manual",
        metodoPago,
        referenciaSinpe:
            metodoPago === "SINPE"
                ? referenciaSinpe
                : "",
        productoId: calculo.producto.id,
        productoNombre:
            calculo.producto.nombre,
        cantidad: calculo.cantidad,
        precioUnitario:
            calculo.precioUnitario,
        subtotal: calculo.subtotal,
        impuesto: calculo.impuestoMonto,
        total: calculo.total,
        estado: "Confirmada",
        usuario: "Administrador",
        observaciones: observaciones || ""
    };

    const ventas = obtenerVentasGuardadas();

    ventas.push(venta);

    if (!guardarVentas(ventas)) {
        mostrarMensajeFormularioVenta(
            "No fue posible guardar la venta.",
            "error"
        );

        return;
    }

    cerrarModalVenta();
    mostrarVentas();

    mostrarMensajeVentas(
        `La venta ${venta.numero} fue registrada correctamente.`,
        "exito"
    );
}

/*=========================================================
  TABLA Y FILTROS
=========================================================*/

function mostrarVentas() {
    const cuerpo = document.getElementById(
        "cuerpoTablaVentas"
    );

    if (!cuerpo) {
        return;
    }

    const ventas = obtenerVentasGuardadas();
    const filtradas = filtrarVentas(ventas);

    const tabla = document.getElementById(
        "contenedorTablaVentas"
    );

    const vacio = document.getElementById(
        "mensajeSinVentas"
    );

    const sinResultados = document.getElementById(
        "mensajeSinResultadosVentas"
    );

    cuerpo.innerHTML = "";

    const hayVentas = ventas.length > 0;
    const hayResultados = filtradas.length > 0;

    if (tabla) {
        tabla.hidden =
            !hayVentas || !hayResultados;
    }

    if (vacio) {
        vacio.hidden = hayVentas;
    }

    if (sinResultados) {
        sinResultados.hidden =
            !hayVentas || hayResultados;
    }

    filtradas.forEach((venta) => {
        cuerpo.appendChild(
            crearFilaVenta(venta)
        );
    });

    const resultado = document.getElementById(
        "resultadoBusquedaVentas"
    );

    if (resultado) {
        resultado.textContent = hayVentas
            ? `${filtradas.length} venta(s) encontrada(s).`
            : "";
    }

    actualizarResumenVentas(ventas);
}

function crearFilaVenta(venta) {
    const fila = document.createElement("tr");

    fila.appendChild(
        crearCeldaTexto(venta.numero)
    );

    fila.appendChild(
        crearCeldaTexto(
            formatearFechaHoraVenta(venta.fecha)
        )
    );

    fila.appendChild(
        crearCeldaTexto(venta.tienda)
    );

    fila.appendChild(
        crearCeldaTexto(venta.origen)
    );

    fila.appendChild(
        crearCeldaTexto(venta.metodoPago)
    );

    fila.appendChild(
        crearCeldaTexto(
            formatearMonedaCRC(venta.subtotal)
        )
    );

    fila.appendChild(
        crearCeldaTexto(
            formatearMonedaCRC(venta.impuesto)
        )
    );

    fila.appendChild(
        crearCeldaTexto(
            formatearMonedaCRC(venta.total)
        )
    );

    const celdaEstado =
        document.createElement("td");

    const estado =
        document.createElement("span");

    estado.className =
        venta.estado === "Confirmada"
            ? "insignia insignia-verde"
            : "insignia insignia-amarilla";

    estado.textContent = venta.estado;

    celdaEstado.appendChild(estado);
    fila.appendChild(celdaEstado);

    const celdaAcciones =
        document.createElement("td");

    const boton = document.createElement("button");

    boton.type = "button";
    boton.className = "boton-accion-producto";
    boton.title = "Ver detalle";
    boton.innerHTML =
        '<i class="fa-solid fa-eye"></i>';

    boton.addEventListener("click", () => {
        window.alert(
            [
                `Venta: ${venta.numero}`,
                `Producto: ${venta.productoNombre}`,
                `Cantidad: ${venta.cantidad}`,
                `Tienda: ${venta.tienda}`,
                `Método: ${venta.metodoPago}`,
                `Total: ${formatearMonedaCRC(venta.total)}`
            ].join("\n")
        );
    });

    celdaAcciones.appendChild(boton);
    fila.appendChild(celdaAcciones);

    return fila;
}

function inicializarFiltrosVentas() {
    const ids = [
        "buscarVenta",
        "fechaDesdeVenta",
        "fechaHastaVenta",
        "filtroTiendaVenta",
        "filtroOrigenVenta",
        "filtroMetodoPagoVenta",
        "filtroEstadoVenta"
    ];

    ids.forEach((id) => {
        const campo =
            document.getElementById(id);

        if (!campo) {
            return;
        }

        campo.addEventListener(
            campo.type === "search"
                ? "input"
                : "change",
            mostrarVentas
        );
    });

    document.getElementById(
        "botonLimpiarFiltrosVentas"
    )?.addEventListener("click", () => {
        ids.forEach((id) => {
            const campo =
                document.getElementById(id);

            if (campo) {
                campo.value = "";
            }
        });

        mostrarVentas();
    });
}

function filtrarVentas(ventas) {
    const busqueda = normalizarTexto(
        document.getElementById(
            "buscarVenta"
        )?.value
    );

    const desde = document.getElementById(
        "fechaDesdeVenta"
    )?.value;

    const hasta = document.getElementById(
        "fechaHastaVenta"
    )?.value;

    const tienda = document.getElementById(
        "filtroTiendaVenta"
    )?.value;

    const origen = document.getElementById(
        "filtroOrigenVenta"
    )?.value;

    const metodo = document.getElementById(
        "filtroMetodoPagoVenta"
    )?.value;

    const estado = document.getElementById(
        "filtroEstadoVenta"
    )?.value;

    return ventas.filter((venta) => {
        const texto = normalizarTexto(
            [
                venta.numero,
                venta.productoNombre,
                venta.tienda,
                venta.usuario
            ].join(" ")
        );

        const fecha =
            String(venta.fecha).slice(0, 10);

        return (
            (!busqueda ||
                texto.includes(busqueda)) &&
            (!desde || fecha >= desde) &&
            (!hasta || fecha <= hasta) &&
            (!tienda ||
                venta.tienda === tienda) &&
            (!origen ||
                venta.origen === origen) &&
            (!metodo ||
                venta.metodoPago === metodo) &&
            (!estado ||
                venta.estado === estado)
        );
    });
}

/*=========================================================
  RESUMEN DE VENTAS
=========================================================*/

function actualizarResumenVentas(ventas) {
    const hoy = obtenerFechaActualISO();

    const ventasHoy = ventas.filter(
        (venta) =>
            String(venta.fecha).slice(0, 10) === hoy &&
            venta.estado === "Confirmada"
    );

    const total = ventasHoy.reduce(
        (acumulado, venta) =>
            acumulado +
            Number(venta.total || 0),
        0
    );

    const totalElemento =
        document.getElementById(
            "totalVentasDia"
        );

    const cantidadElemento =
        document.getElementById(
            "cantidadVentasDia"
        );

    if (totalElemento) {
        totalElemento.textContent =
            formatearMonedaCRC(total);
    }

    if (cantidadElemento) {
        cantidadElemento.textContent =
            ventasHoy.length;
    }

    actualizarCantidadImportacionesPendientes();
    actualizarDiferenciaCaja();
}

/*=========================================================
  CIERRE DIARIO
=========================================================*/

function inicializarCierreCaja() {
    document.getElementById(
        "botonAbrirCierreCaja"
    )?.addEventListener(
        "click",
        abrirModalCierreCaja
    );

    document.getElementById(
        "botonCerrarModalCierre"
    )?.addEventListener(
        "click",
        cerrarModalCierreCaja
    );

    document.getElementById(
        "botonCancelarCierreCaja"
    )?.addEventListener(
        "click",
        cerrarModalCierreCaja
    );

    document.querySelectorAll(
        "[data-cerrar-modal-cierre]"
    ).forEach((fondo) => {
        fondo.addEventListener(
            "click",
            cerrarModalCierreCaja
        );
    });

    document.getElementById(
        "formularioCierreCaja"
    )?.addEventListener(
        "submit",
        guardarCierreCaja
    );
}

function abrirModalCierreCaja() {
    const modal = document.getElementById(
        "modalCierreCaja"
    );

    if (!modal) {
        return;
    }

    establecerFechaCierreActual();

    modal.hidden = false;

    document.body.classList.add(
        "modal-producto-abierto"
    );
}

function cerrarModalCierreCaja() {
    const modal = document.getElementById(
        "modalCierreCaja"
    );

    if (!modal || modal.hidden) {
        return;
    }

    modal.hidden = true;

    document.body.classList.remove(
        "modal-producto-abierto"
    );
}

function establecerFechaCierreActual() {
    const campo = document.getElementById(
        "fechaCierreCaja"
    );

    if (campo && !campo.value) {
        campo.value = obtenerFechaActualISO();
    }
}

function guardarCierreCaja(evento) {
    evento.preventDefault();

    const tienda = document.getElementById(
        "tiendaCierreCaja"
    )?.value;

    const fecha = document.getElementById(
        "fechaCierreCaja"
    )?.value;

    if (!tienda || !fecha) {
        return;
    }

    const efectivo = Number(
        document.getElementById(
            "montoEfectivoCierre"
        )?.value || 0
    );

    const sinpe = Number(
        document.getElementById(
            "montoSinpeCierre"
        )?.value || 0
    );

    const tarjeta = Number(
        document.getElementById(
            "montoTarjetaCierre"
        )?.value || 0
    );

    const cierres = obtenerCierresCaja();

    const repetido = cierres.some(
        (cierre) =>
            cierre.tienda === tienda &&
            cierre.fecha === fecha
    );

    if (repetido) {
        document.getElementById(
            "mensajeCierreCaja"
        ).textContent =
            "Ya existe un cierre para esa tienda y fecha.";

        return;
    }

    cierres.push({
        id: `cierre-${Date.now()}`,
        tienda,
        fecha,
        efectivo,
        sinpe,
        tarjeta,
        totalContado:
            efectivo + sinpe + tarjeta,
        diferencia: 0,
        usuarioResponsable:
            obtenerSesionVending()?.nombre || "Administrador",
        observaciones:
            document.getElementById(
                "observacionesCierreCaja"
            )?.value.trim() || "",
        fechaRegistro:
            new Date().toISOString()
    });

    localStorage.setItem(
        "cierresCajaVending",
        JSON.stringify(cierres)
    );

    cerrarModalCierreCaja();
    actualizarDiferenciaCaja();

    mostrarMensajeVentas(
        "El cierre diario fue guardado correctamente.",
        "exito"
    );
}

function obtenerCierresCaja() {
    try {
        const datos = JSON.parse(
            localStorage.getItem(
                "cierresCajaVending"
            ) || "[]"
        );

        return Array.isArray(datos)
            ? datos
            : [];
    } catch (error) {
        return [];
    }
}

function actualizarDiferenciaCaja() {
    const elemento = document.getElementById(
        "diferenciaCajaDia"
    );

    if (!elemento) {
        return;
    }

    const hoy = obtenerFechaActualISO();

    const diferencia = obtenerCierresCaja()
        .filter((cierre) =>
            cierre.fecha === hoy
        )
        .reduce(
            (total, cierre) =>
                total +
                Number(cierre.diferencia || 0),
            0
        );

    elemento.textContent =
        formatearMonedaCRC(diferencia);
}

/*=========================================================
  MENSAJES Y UTILIDADES DE VENTAS
=========================================================*/

function mostrarMensajeFormularioVenta(
    mensaje,
    tipo
) {
    const elemento = document.getElementById(
        "mensajeFormularioVenta"
    );

    if (!elemento) {
        return;
    }

    elemento.textContent = mensaje;

    elemento.className =
        `mensaje-login mensaje-${tipo}`;
}

function limpiarMensajeFormularioVenta() {
    const elemento = document.getElementById(
        "mensajeFormularioVenta"
    );

    if (elemento) {
        elemento.textContent = "";
        elemento.className = "mensaje-login";
    }
}

function mostrarMensajeVentas(mensaje, tipo) {
    const elemento = document.getElementById(
        "mensajeVentas"
    );

    if (!elemento) {
        return;
    }

    elemento.textContent = mensaje;
    elemento.className =
        `mensaje-producto mensaje-${tipo}`;

    elemento.hidden = false;

    window.setTimeout(() => {
        elemento.hidden = true;
    }, 5000);
}

function obtenerFechaActualISO() {
    const fecha = new Date();

    const anio = fecha.getFullYear();

    const mes = String(
        fecha.getMonth() + 1
    ).padStart(2, "0");

    const dia = String(
        fecha.getDate()
    ).padStart(2, "0");

    return `${anio}-${mes}-${dia}`;
}

function formatearFechaHoraVenta(fechaISO) {
    const fecha = new Date(fechaISO);

    if (Number.isNaN(fecha.getTime())) {
        return "Fecha no disponible";
    }

    return new Intl.DateTimeFormat(
        "es-CR",
        {
            dateStyle: "short",
            timeStyle: "short"
        }
    ).format(fecha);
}



/*=========================================================
  INTEGRACIÓN GENERAL: SESIÓN, DASHBOARD, INVENTARIO
  Y MOVIMIENTOS
=========================================================*/

const CLAVE_MOVIMIENTOS_VENDING = "movimientosVending";
const CLAVE_USUARIOS_VENDING = "usuariosVending";
const CLAVE_SESION_VENDING = "sesionVending";

function leerListaVending(clave) {
    try {
        const datos = JSON.parse(localStorage.getItem(clave) || "[]");
        return Array.isArray(datos) ? datos : [];
    } catch (error) {
        console.error(`No se pudo leer ${clave}:`, error);
        return [];
    }
}

function guardarListaVending(clave, datos) {
    try {
        localStorage.setItem(clave, JSON.stringify(datos));
        return true;
    } catch (error) {
        console.error(`No se pudo guardar ${clave}:`, error);
        return false;
    }
}

function generarIdVending(prefijo) {
    return `${prefijo}-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
}

function obtenerSesionVending() {
    try {
        return JSON.parse(localStorage.getItem(CLAVE_SESION_VENDING) || "null");
    } catch (error) {
        return null;
    }
}

function crearUsuariosInicialesVending() {
    const existentes = leerListaVending(CLAVE_USUARIOS_VENDING);

    if (existentes.length > 0) {
        return existentes;
    }

    const usuarios = [
        {
            id: "usuario-admin",
            nombre: "Administrador",
            correo: "admin@vending.com",
            contrasena: "Admin123",
            rol: "Administrador",
            activo: true
        },
        {
            id: "usuario-trabajador",
            nombre: "Trabajador",
            correo: "trabajador@vending.com",
            contrasena: "Trabajador123",
            rol: "Trabajador",
            activo: true
        }
    ];

    guardarListaVending(CLAVE_USUARIOS_VENDING, usuarios);
    return usuarios;
}

function inicializarSesionVending() {
    crearUsuariosInicialesVending();

    const formulario =
        document.getElementById("formularioLogin") ||
        document.querySelector("form.formulario-login");

    if (formulario) {
        formulario.addEventListener("submit", procesarInicioSesionVending);
    }

    const botonCerrar = document.getElementById("botonCerrarSesion");

    if (botonCerrar) {
        botonCerrar.addEventListener("click", () => {
            localStorage.removeItem(CLAVE_SESION_VENDING);
        });
    }
}

function procesarInicioSesionVending(evento) {
    evento.preventDefault();

    const campoCorreo =
        document.getElementById("correo") ||
        document.getElementById("usuario") ||
        document.querySelector('input[type="email"]');

    const campoContrasena = document.getElementById("contrasena");
    const mensaje =
        document.getElementById("mensajeLogin") ||
        document.querySelector(".mensaje-login");

    const correo = campoCorreo?.value.trim() || "";
    const contrasena = campoContrasena?.value || "";

    const usuario = crearUsuariosInicialesVending().find((item) =>
        normalizarTexto(item.correo) === normalizarTexto(correo) &&
        item.contrasena === contrasena &&
        item.activo !== false
    );

    if (!usuario) {
        if (mensaje) {
            mensaje.textContent = "El correo o la contraseña no son correctos.";
        }
        return;
    }

    localStorage.setItem(
        CLAVE_SESION_VENDING,
        JSON.stringify({
            id: usuario.id,
            nombre: usuario.nombre,
            correo: usuario.correo,
            rol: usuario.rol
        })
    );

    window.location.href = "../dashboard/dashboard.html";
}

function actualizarPerfilSuperiorVending() {
    const sesion = obtenerSesionVending();

    if (!sesion) {
        return;
    }

    const nombre = document.getElementById("nombreUsuarioSuperior");
    const correo = document.getElementById("correoUsuarioSuperior");

    if (nombre) {
        nombre.textContent = sesion.nombre;
    }

    if (correo) {
        correo.textContent = `${sesion.correo} · ${sesion.rol}`;
    }
}

function obtenerMovimientosVending() {
    return leerListaVending(CLAVE_MOVIMIENTOS_VENDING);
}

function fechaLocalISOVending(fecha = new Date()) {
    const desplazamiento = fecha.getTimezoneOffset() * 60000;

    return new Date(fecha.getTime() - desplazamiento)
        .toISOString()
        .slice(0, 10);
}

function obtenerEstadoStockVending(producto) {
    const stock = Number(producto.stock) || 0;
    const minimo = Number(producto.stockMinimo) || 0;

    if (stock <= 0) {
        return { valor: "agotado", texto: "Agotado" };
    }

    if (stock <= Math.max(1, Math.floor(minimo / 2))) {
        return { valor: "critico", texto: "Crítico" };
    }

    if (stock <= minimo) {
        return { valor: "stock-bajo", texto: "Stock bajo" };
    }

    return { valor: "normal", texto: "Normal" };
}

function inicializarPaginaInventarioVending() {
    const cuerpo = document.getElementById("tablaInventario");

    if (!cuerpo) {
        return;
    }

    [
        "buscarInventario",
        "filtroUbicacion",
        "filtroCategoria",
        "filtroEstado"
    ].forEach((id) => {
        const elemento = document.getElementById(id);
        elemento?.addEventListener("input", mostrarInventarioVending);
        elemento?.addEventListener("change", mostrarInventarioVending);
    });

    document.getElementById("botonLimpiarFiltrosInventario")
        ?.addEventListener("click", () => {
            [
                "buscarInventario",
                "filtroUbicacion",
                "filtroCategoria",
                "filtroEstado"
            ].forEach((id) => {
                const elemento = document.getElementById(id);
                if (elemento) elemento.value = "";
            });

            mostrarInventarioVending();
        });

    mostrarInventarioVending();
}

function mostrarInventarioVending() {
    const cuerpo = document.getElementById("tablaInventario");

    if (!cuerpo) {
        return;
    }

    const productos = obtenerProductosGuardados();
    const movimientos = obtenerMovimientosVending();
    const busqueda = normalizarTexto(
        document.getElementById("buscarInventario")?.value || ""
    );
    const tienda = document.getElementById("filtroUbicacion")?.value || "";
    const categoria = document.getElementById("filtroCategoria")?.value || "";
    const estado = document.getElementById("filtroEstado")?.value || "";

    const filtrados = productos.filter((producto) => {
        const texto = normalizarTexto([
            producto.nombre,
            producto.codigo,
            producto.proveedor
        ].join(" "));

        const estadoStock = obtenerEstadoStockVending(producto).valor;

        return (
            (!busqueda || texto.includes(busqueda)) &&
            (!tienda || producto.ubicacion === tienda) &&
            (!categoria || producto.categoria === categoria) &&
            (!estado || estadoStock === estado)
        );
    });

    cuerpo.innerHTML = "";

    filtrados.forEach((producto) => {
        cuerpo.appendChild(
            crearFilaInventarioVending(producto, movimientos)
        );
    });

    actualizarResumenInventarioVending(productos, movimientos);

    const hayProductos = productos.length > 0;
    const hayResultados = filtrados.length > 0;

    const tabla = document.getElementById("contenedorTablaInventario");
    const vacio = document.getElementById("mensajeSinInventario");
    const sinResultados = document.getElementById(
        "mensajeSinResultadosInventario"
    );
    const resultado = document.getElementById(
        "resultadoBusquedaInventario"
    );
    const paginacion = document.getElementById(
        "textoPaginacionInventario"
    );

    if (tabla) tabla.hidden = !hayResultados;
    if (vacio) vacio.hidden = hayProductos;
    if (sinResultados) {
        sinResultados.hidden = !hayProductos || hayResultados;
    }
    if (resultado) {
        resultado.textContent = hayProductos
            ? `${filtrados.length} producto(s) encontrado(s).`
            : "";
    }
    if (paginacion) {
        paginacion.textContent = hayProductos
            ? `Mostrando ${filtrados.length} de ${productos.length} productos`
            : "No hay productos registrados";
    }
}

function actualizarResumenInventarioVending(productos, movimientos) {
    const hoy = fechaLocalISOVending();

    const valores = {
        totalStockInventario: productos.reduce(
            (total, producto) => total + Number(producto.stock || 0),
            0
        ),
        totalStockCriticoInventario: productos.filter((producto) =>
            ["critico", "stock-bajo"].includes(
                obtenerEstadoStockVending(producto).valor
            )
        ).length,
        totalProductosAgotadosInventario: productos.filter(
            (producto) =>
                obtenerEstadoStockVending(producto).valor === "agotado"
        ).length,
        totalMovimientosDiaInventario: movimientos.filter(
            (movimiento) => movimiento.fecha === hoy
        ).length
    };

    Object.entries(valores).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    });
}

function crearFilaInventarioVending(producto, movimientos) {
    const fila = document.createElement("tr");
    const estado = obtenerEstadoStockVending(producto);

    const ultimoMovimiento = movimientos
        .filter((movimiento) => movimiento.productoId === producto.id)
        .sort(
            (a, b) =>
                new Date(b.fechaRegistro) - new Date(a.fechaRegistro)
        )[0];

    fila.appendChild(crearCeldaTexto(producto.nombre));
    fila.appendChild(crearCeldaTexto(producto.codigo));
    fila.appendChild(crearCeldaTexto(producto.ubicacion));
    fila.appendChild(crearCeldaTexto(producto.stock));
    fila.appendChild(crearCeldaTexto(producto.stockMinimo));

    const celdaEstado = document.createElement("td");
    const etiqueta = document.createElement("span");

    etiqueta.className = estado.valor === "normal"
        ? "badge badge-verde"
        : estado.valor === "agotado"
            ? "badge badge-rojo"
            : "badge badge-naranja";

    etiqueta.textContent = estado.texto;
    celdaEstado.appendChild(etiqueta);
    fila.appendChild(celdaEstado);

    fila.appendChild(
        crearCeldaTexto(
            ultimoMovimiento
                ? formatearFechaHoraVenta(
                    ultimoMovimiento.fechaRegistro
                )
                : "Sin movimientos"
        )
    );

    const celdaAcciones = document.createElement("td");
    const enlace = document.createElement("a");

    enlace.href =
        `movimientos.html?producto=${encodeURIComponent(producto.id)}`;
    enlace.className = "boton boton-borde boton-tabla";
    enlace.textContent = "Movimiento";

    celdaAcciones.appendChild(enlace);
    fila.appendChild(celdaAcciones);

    return fila;
}

function inicializarPaginaMovimientosVending() {
    const formulario = document.getElementById("formularioMovimiento");

    if (!formulario) {
        return;
    }

    cargarProductosMovimientoVending();
    cargarResponsableMovimientoVending();
    establecerFechaMovimientoVending();
    aplicarParametrosMovimientoVending();
    actualizarDestinoMovimientoVending();

    document.getElementById("tipoMovimiento")
        ?.addEventListener("change", actualizarDestinoMovimientoVending);

    formulario.addEventListener("submit", registrarMovimientoVending);

    [
        "buscarMovimiento",
        "filtroTipoMovimiento",
        "filtroUbicacionMovimiento",
        "filtroFecha"
    ].forEach((id) => {
        const elemento = document.getElementById(id);
        elemento?.addEventListener("input", mostrarMovimientosVending);
        elemento?.addEventListener("change", mostrarMovimientosVending);
    });

    document.getElementById("botonLimpiarFiltros")
        ?.addEventListener("click", () => {
            [
                "buscarMovimiento",
                "filtroTipoMovimiento",
                "filtroUbicacionMovimiento",
                "filtroFecha"
            ].forEach((id) => {
                const elemento = document.getElementById(id);
                if (elemento) elemento.value = "";
            });

            mostrarMovimientosVending();
        });

    document.getElementById("botonExportarMovimientos")
        ?.addEventListener("click", exportarMovimientosVending);

    mostrarMovimientosVending();
}

function cargarProductosMovimientoVending() {
    const selector = document.getElementById("productoMovimiento");

    if (!selector) {
        return;
    }

    selector.innerHTML = `
        <option value="">
            Seleccione un producto
        </option>
    `;

    obtenerProductosGuardados()
        .filter((producto) => producto.estado !== "Inactivo")
        .sort((a, b) => a.nombre.localeCompare(b.nombre, "es"))
        .forEach((producto) => {
            const opcion = document.createElement("option");

            opcion.value = producto.id;
            opcion.textContent =
                `${producto.nombre} - ${producto.ubicacion} - Stock: ${producto.stock}`;

            selector.appendChild(opcion);
        });
}

function cargarResponsableMovimientoVending() {
    const selector = document.getElementById("responsableMovimiento");

    if (!selector) {
        return;
    }

    const sesion = obtenerSesionVending() || {
        id: "usuario-admin",
        nombre: "Administrador"
    };

    selector.innerHTML = "";

    const opcion = document.createElement("option");
    opcion.value = sesion.id;
    opcion.textContent = sesion.nombre;
    opcion.selected = true;

    selector.appendChild(opcion);
}

function establecerFechaMovimientoVending() {
    const campo = document.getElementById("fechaMovimiento");

    if (campo && !campo.value) {
        campo.value = fechaLocalISOVending();
    }
}

function aplicarParametrosMovimientoVending() {
    const parametros = new URLSearchParams(window.location.search);
    const tipo = parametros.get("tipo");
    const producto = parametros.get("producto");

    const campoTipo = document.getElementById("tipoMovimiento");
    const campoProducto = document.getElementById("productoMovimiento");

    if (tipo && campoTipo) campoTipo.value = tipo;
    if (producto && campoProducto) campoProducto.value = producto;
}

function actualizarDestinoMovimientoVending() {
    const tipo = document.getElementById("tipoMovimiento")?.value || "";
    const destino = document.getElementById("ubicacionDestino");
    const grupo = document.getElementById("grupoUbicacionDestino");

    if (!destino || !grupo) {
        return;
    }

    const esTraslado = tipo === "traslado";

    grupo.hidden = !esTraslado;
    destino.required = esTraslado;

    if (!esTraslado) {
        destino.value = "";
    }
}

function registrarMovimientoVending(evento) {
    evento.preventDefault();

    const tipo = document.getElementById("tipoMovimiento")?.value || "";
    const productoId =
        document.getElementById("productoMovimiento")?.value || "";
    const origen = document.getElementById("ubicacionOrigen")?.value || "";
    const destino = document.getElementById("ubicacionDestino")?.value || "";
    const cantidad = Number(
        document.getElementById("cantidadMovimiento")?.value || 0
    );
    const fecha = document.getElementById("fechaMovimiento")?.value || "";
    const referencia =
        document.getElementById("referenciaMovimiento")?.value.trim() ||
        `MOV-${Date.now()}`;
    const responsable =
        document.getElementById("responsableMovimiento")
            ?.selectedOptions?.[0]?.textContent || "Administrador";
    const observaciones =
        document.getElementById("observacionesMovimiento")?.value.trim() || "";

    if (!tipo || !productoId || !origen || cantidad <= 0 || !fecha) {
        mostrarMensajeMovimientoVending(
            "Complete todos los campos requeridos.",
            "error"
        );
        return;
    }

    if (tipo === "traslado" && (!destino || destino === origen)) {
        mostrarMensajeMovimientoVending(
            "Seleccione una tienda de destino diferente.",
            "error"
        );
        return;
    }

    const productos = obtenerProductosGuardados();
    const producto = productos.find((item) => item.id === productoId);

    if (!producto) {
        mostrarMensajeMovimientoVending(
            "El producto seleccionado no existe.",
            "error"
        );
        return;
    }

    if (producto.ubicacion !== origen) {
        mostrarMensajeMovimientoVending(
            `El producto está asignado a ${producto.ubicacion}.`,
            "error"
        );
        return;
    }

    const stockActual = Number(producto.stock) || 0;

    if (
        ["salida", "merma", "traslado"].includes(tipo) &&
        cantidad > stockActual
    ) {
        mostrarMensajeMovimientoVending(
            "La cantidad supera el stock disponible.",
            "error"
        );
        return;
    }

    if (tipo === "entrada") {
        producto.stock = stockActual + cantidad;
    }

    if (tipo === "salida" || tipo === "merma") {
        producto.stock = stockActual - cantidad;
    }

    if (tipo === "ajuste") {
        producto.stock = cantidad;
    }

    /*
     * En esta versión cada producto posee una tienda principal.
     * Un traslado cambia la tienda asignada sin alterar la cantidad total.
     */
    if (tipo === "traslado") {
        producto.ubicacion = destino;
    }

    producto.fechaActualizacion = new Date().toISOString();
    guardarProductos(productos);

    const movimientos = obtenerMovimientosVending();

    movimientos.push({
        id: generarIdVending("movimiento"),
        tipo,
        productoId,
        productoNombre: producto.nombre,
        origen,
        destino: tipo === "traslado" ? destino : "",
        cantidad,
        fecha,
        referencia,
        responsable,
        observaciones,
        fechaRegistro: new Date().toISOString()
    });

    guardarListaVending(CLAVE_MOVIMIENTOS_VENDING, movimientos);

    mostrarMensajeMovimientoVending(
        "El movimiento fue registrado correctamente.",
        "exito"
    );

    evento.currentTarget.reset();
    establecerFechaMovimientoVending();
    cargarProductosMovimientoVending();
    cargarResponsableMovimientoVending();
    actualizarDestinoMovimientoVending();
    mostrarMovimientosVending();
    actualizarContadoresNotificacionVending();
}

function mostrarMensajeMovimientoVending(mensaje, tipo) {
    const contenedor = document.getElementById("mensajeMovimiento");

    if (!contenedor) {
        return;
    }

    contenedor.textContent = mensaje;
    contenedor.className = `mensaje-producto mensaje-${tipo}`;
    contenedor.hidden = false;
}

function mostrarMovimientosVending() {
    const cuerpo = document.getElementById("tablaMovimientos");

    if (!cuerpo) {
        return;
    }

    const movimientos = obtenerMovimientosVending();
    const busqueda = normalizarTexto(
        document.getElementById("buscarMovimiento")?.value || ""
    );
    const tipo =
        document.getElementById("filtroTipoMovimiento")?.value || "";
    const tienda =
        document.getElementById("filtroUbicacionMovimiento")?.value || "";
    const fecha = document.getElementById("filtroFecha")?.value || "";

    const filtrados = movimientos
        .filter((movimiento) => {
            const texto = normalizarTexto([
                movimiento.productoNombre,
                movimiento.referencia,
                movimiento.responsable
            ].join(" "));

            return (
                (!busqueda || texto.includes(busqueda)) &&
                (!tipo || movimiento.tipo === tipo) &&
                (
                    !tienda ||
                    movimiento.origen === tienda ||
                    movimiento.destino === tienda
                ) &&
                (!fecha || movimiento.fecha === fecha)
            );
        })
        .sort(
            (a, b) =>
                new Date(b.fechaRegistro) - new Date(a.fechaRegistro)
        );

    cuerpo.innerHTML = "";

    filtrados.forEach((movimiento) => {
        cuerpo.appendChild(crearFilaMovimientoVending(movimiento));
    });

    actualizarResumenMovimientosVending(movimientos);

    const hayMovimientos = movimientos.length > 0;
    const hayResultados = filtrados.length > 0;

    const tabla = document.getElementById("contenedorTablaMovimientos");
    const vacio = document.getElementById("mensajeSinMovimientos");
    const sinResultados = document.getElementById(
        "mensajeSinResultadosMovimientos"
    );
    const resultado = document.getElementById(
        "resultadoBusquedaMovimientos"
    );
    const paginacion = document.getElementById(
        "textoPaginacionMovimientos"
    );

    if (tabla) tabla.hidden = !hayResultados;
    if (vacio) vacio.hidden = hayMovimientos;
    if (sinResultados) {
        sinResultados.hidden = !hayMovimientos || hayResultados;
    }
    if (resultado) {
        resultado.textContent = hayMovimientos
            ? `${filtrados.length} movimiento(s) encontrado(s).`
            : "";
    }
    if (paginacion) {
        paginacion.textContent = hayMovimientos
            ? `Mostrando ${filtrados.length} de ${movimientos.length} movimientos`
            : "No hay movimientos registrados";
    }
}

function actualizarResumenMovimientosVending(movimientos) {
    const hoy = fechaLocalISOVending();
    const movimientosHoy = movimientos.filter(
        (movimiento) => movimiento.fecha === hoy
    );

    const valores = {
        totalEntradas: movimientosHoy
            .filter((movimiento) => movimiento.tipo === "entrada")
            .reduce(
                (total, movimiento) =>
                    total + Number(movimiento.cantidad || 0),
                0
            ),
        totalSalidas: movimientosHoy
            .filter((movimiento) => movimiento.tipo === "salida")
            .reduce(
                (total, movimiento) =>
                    total + Number(movimiento.cantidad || 0),
                0
            ),
        totalAjustes: movimientosHoy.filter(
            (movimiento) => movimiento.tipo === "ajuste"
        ).length,
        totalMermas: movimientosHoy
            .filter((movimiento) => movimiento.tipo === "merma")
            .reduce(
                (total, movimiento) =>
                    total + Number(movimiento.cantidad || 0),
                0
            )
    };

    Object.entries(valores).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    });
}

function crearFilaMovimientoVending(movimiento) {
    const fila = document.createElement("tr");

    fila.appendChild(crearCeldaTexto(movimiento.fecha));
    fila.appendChild(crearCeldaTexto(movimiento.referencia));
    fila.appendChild(crearCeldaTexto(movimiento.productoNombre));
    fila.appendChild(
        crearCeldaTexto(
            movimiento.tipo.charAt(0).toUpperCase() +
            movimiento.tipo.slice(1)
        )
    );
    fila.appendChild(crearCeldaTexto(movimiento.origen));
    fila.appendChild(crearCeldaTexto(movimiento.destino || "No aplica"));
    fila.appendChild(crearCeldaTexto(movimiento.cantidad));
    fila.appendChild(crearCeldaTexto(movimiento.responsable));
    fila.appendChild(crearCeldaTexto("Registrado"));

    return fila;
}

function exportarMovimientosVending() {
    const movimientos = obtenerMovimientosVending();

    if (movimientos.length === 0) {
        mostrarMensajeMovimientoVending(
            "No hay movimientos para exportar.",
            "error"
        );
        return;
    }

    const filas = [
        [
            "Fecha",
            "Referencia",
            "Producto",
            "Tipo",
            "Origen",
            "Destino",
            "Cantidad",
            "Responsable"
        ],
        ...movimientos.map((movimiento) => [
            movimiento.fecha,
            movimiento.referencia,
            movimiento.productoNombre,
            movimiento.tipo,
            movimiento.origen,
            movimiento.destino,
            movimiento.cantidad,
            movimiento.responsable
        ])
    ];

    descargarCsvVending("movimientos-inventario.csv", filas);
}

function descargarCsvVending(nombre, filas) {
    const contenido = filas
        .map((fila) =>
            fila
                .map((valor) =>
                    `"${String(valor ?? "").replace(/"/g, '""')}"`
                )
                .join(",")
        )
        .join("\n");

    const archivo = new Blob(["\uFEFF" + contenido], {
        type: "text/csv;charset=utf-8"
    });

    const enlace = document.createElement("a");
    enlace.href = URL.createObjectURL(archivo);
    enlace.download = nombre;
    enlace.click();
    URL.revokeObjectURL(enlace.href);
}

function inicializarDashboardVending() {
    const totalProductos = document.getElementById(
        "totalProductosDashboard"
    );

    if (!totalProductos) {
        return;
    }

    const fecha = document.getElementById("fechaDashboard");

    if (fecha && !fecha.value) {
        fecha.value = fechaLocalISOVending();
    }

    fecha?.addEventListener("change", mostrarDashboardVending);

    document.getElementById("filtroTiendaDashboard")
        ?.addEventListener("change", mostrarDashboardVending);

    mostrarDashboardVending();
}

function mostrarDashboardVending() {
    const productos = obtenerProductosGuardados();
    const movimientos = obtenerMovimientosVending();
    const ventas = obtenerVentasGuardadas();

    const tienda =
        document.getElementById("filtroTiendaDashboard")?.value || "";
    const fecha =
        document.getElementById("fechaDashboard")?.value ||
        fechaLocalISOVending();

    const productosFiltrados = productos.filter(
        (producto) => !tienda || producto.ubicacion === tienda
    );

    const movimientosFiltrados = movimientos.filter(
        (movimiento) =>
            (!tienda ||
                movimiento.origen === tienda ||
                movimiento.destino === tienda) &&
            movimiento.fecha === fecha
    );

    const ventasFiltradas = ventas.filter(
        (venta) =>
            (!tienda || venta.tienda === tienda) &&
            String(venta.fecha).slice(0, 10) === fecha
    );

    const valores = {
        totalProductosDashboard: productosFiltrados.length,
        totalStockCriticoDashboard: productosFiltrados.filter(
            (producto) =>
                ["critico", "stock-bajo", "agotado"].includes(
                    obtenerEstadoStockVending(producto).valor
                )
        ).length,
        totalVentasDashboard: formatearMonedaCRC(
            ventasFiltradas.reduce(
                (total, venta) => total + Number(venta.total || 0),
                0
            )
        ),
        totalMovimientosDashboard: movimientosFiltrados.length
    };

    Object.entries(valores).forEach(([id, valor]) => {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    });

    mostrarMovimientosDashboardVending(movimientosFiltrados);
    mostrarAlertasDashboardVending(productosFiltrados);
}

function mostrarMovimientosDashboardVending(movimientos) {
    const cuerpo = document.getElementById("tablaMovimientosDashboard");

    if (!cuerpo) {
        return;
    }

    cuerpo.innerHTML = "";

    const recientes = [...movimientos]
        .sort(
            (a, b) =>
                new Date(b.fechaRegistro) - new Date(a.fechaRegistro)
        )
        .slice(0, 5);

    if (recientes.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = 5;
        celda.textContent = "No hay movimientos registrados.";
        celda.style.textAlign = "center";
        celda.style.padding = "40px";

        fila.appendChild(celda);
        cuerpo.appendChild(fila);
        return;
    }

    recientes.forEach((movimiento) => {
        const fila = document.createElement("tr");

        fila.appendChild(
            crearCeldaTexto(
                formatearFechaHoraVenta(movimiento.fechaRegistro)
            )
        );
        fila.appendChild(crearCeldaTexto(movimiento.tipo));
        fila.appendChild(
            crearCeldaTexto(
                `${movimiento.productoNombre}: ${movimiento.cantidad} unidad(es)`
            )
        );
        fila.appendChild(crearCeldaTexto(movimiento.referencia));
        fila.appendChild(crearCeldaTexto(movimiento.responsable));

        cuerpo.appendChild(fila);
    });
}

function mostrarAlertasDashboardVending(productos) {
    const lista = document.getElementById("listaAlertasDashboard");

    if (!lista) {
        return;
    }

    const alertas = productos.filter(
        (producto) =>
            obtenerEstadoStockVending(producto).valor !== "normal"
    );

    lista.innerHTML = "";

    if (alertas.length === 0) {
        const vacio = document.createElement("div");
        vacio.className = "estado-vacio-tabla";

        const icono = document.createElement("i");
        icono.className = "fa-regular fa-bell";

        const titulo = document.createElement("strong");
        titulo.textContent = "No hay alertas registradas";

        const texto = document.createElement("span");
        texto.textContent = "Las alertas del sistema aparecerán aquí.";

        vacio.append(icono, titulo, texto);
        lista.appendChild(vacio);
    } else {
        alertas.slice(0, 6).forEach((producto) => {
            const alerta = document.createElement("div");
            alerta.className = "alerta-dashboard";
            alerta.textContent =
                `${producto.nombre}: ${obtenerEstadoStockVending(producto).texto} (${producto.stock} unidades)`;
            lista.appendChild(alerta);
        });
    }
}

function actualizarContadoresNotificacionVending() {
    const cantidad = obtenerProductosGuardados().filter(
        (producto) =>
            obtenerEstadoStockVending(producto).valor !== "normal"
    ).length;

    document.querySelectorAll(".boton-notificacion .contador")
        .forEach((elemento) => {
            elemento.textContent = cantidad;
            elemento.setAttribute(
                "aria-label",
                `${cantidad} notificaciones`
            );
        });
}


/*=========================================================
  MÓDULOS FINALES: USUARIOS, ALERTAS, REPORTES, COMPRAS,
  PEDIDOS Y FACTURAS
=========================================================*/

const CLAVE_ALERTAS_VENDING = "alertasVending";
const CLAVE_COMPRAS_VENDING = "comprasVending";
const CLAVE_PEDIDOS_VENDING = "pedidosVending";
const CLAVE_FACTURAS_VENDING = "facturasVending";

function textoVending(id, valor) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.textContent = valor;
}

function mostrarElementoVending(id, mostrar) {
    const elemento = document.getElementById(id);
    if (elemento) elemento.hidden = !mostrar;
}

function monedaVending(valor) {
    return new Intl.NumberFormat("es-CR", {
        style: "currency", currency: "CRC", maximumFractionDigits: 2
    }).format(Number(valor) || 0);
}

function escaparHTMLVending(valor) {
    return String(valor ?? "").replace(/[&<>'"]/g, caracter => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;"
    })[caracter]);
}

function mostrarMensajeModuloVending(id, mensaje, tipo = "exito") {
    const elemento = document.getElementById(id);
    if (!elemento) return;
    elemento.textContent = mensaje;
    elemento.className = `mensaje-producto mensaje-${tipo}`;
    elemento.hidden = false;
    window.setTimeout(() => { elemento.hidden = true; }, 3500);
}

function abrirModalVending(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.hidden = false;
        document.body.classList.add("modal-producto-abierto");
    }
}

function cerrarModalVending(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.hidden = true;
        document.body.classList.remove("modal-producto-abierto");
    }
}

function fechaHoyVending() { return fechaLocalISOVending(new Date()); }

/* USUARIOS */
function inicializarPaginaUsuariosVending() {
    if (!document.getElementById("cuerpoTablaUsuarios")) return;
    crearUsuariosInicialesVending();
    const abrir = () => prepararFormularioUsuarioVending();
    document.getElementById("botonNuevoUsuario")?.addEventListener("click", abrir);
    document.getElementById("botonPrimerUsuario")?.addEventListener("click", abrir);
    document.getElementById("botonCerrarModalUsuario")?.addEventListener("click", () => cerrarModalVending("modalUsuario"));
    document.getElementById("botonCancelarUsuario")?.addEventListener("click", () => cerrarModalVending("modalUsuario"));
    document.querySelector("[data-cerrar-modal-usuario]")?.addEventListener("click", () => cerrarModalVending("modalUsuario"));
    document.getElementById("formularioUsuario")?.addEventListener("submit", guardarUsuarioVending);
    ["buscarUsuario","filtroRolUsuario","filtroTiendaUsuario","filtroEstadoUsuario"].forEach(id =>
        document.getElementById(id)?.addEventListener(id === "buscarUsuario" ? "input" : "change", renderUsuariosVending));
    document.getElementById("botonLimpiarFiltrosUsuarios")?.addEventListener("click", () => {
        ["buscarUsuario","filtroRolUsuario","filtroTiendaUsuario","filtroEstadoUsuario"].forEach(id => {
            const e=document.getElementById(id); if(e)e.value="";
        }); renderUsuariosVending();
    });
    document.getElementById("cuerpoTablaUsuarios")?.addEventListener("click", evento => {
        const boton=evento.target.closest("button[data-usuario]"); if(!boton)return;
        boton.dataset.accion === "editar" ? prepararFormularioUsuarioVending(boton.dataset.usuario) : cambiarEstadoUsuarioVending(boton.dataset.usuario);
    });
    renderUsuariosVending();
}

function prepararFormularioUsuarioVending(id = "") {
    const form=document.getElementById("formularioUsuario"); form?.reset();
    document.getElementById("idUsuario").value=id;
    const usuario=leerListaVending(CLAVE_USUARIOS_VENDING).find(u=>u.id===id);
    textoVending("tituloModalUsuario", usuario ? "Editar usuario" : "Nuevo usuario");
    if(usuario){
        document.getElementById("nombreUsuario").value=usuario.nombre||"";
        document.getElementById("nombreAccesoUsuario").value=usuario.usuario||"";
        document.getElementById("correoUsuario").value=usuario.correo||"";
        document.getElementById("rolUsuario").value=usuario.rol||"Trabajador";
        document.getElementById("tiendaUsuario").value=usuario.tienda||"Ultrapark 1";
        document.getElementById("estadoUsuario").value=usuario.activo===false?"Inactivo":"Activo";
        document.getElementById("contrasenaUsuario").required=false;
        document.getElementById("confirmarContrasenaUsuario").required=false;
    }
    abrirModalVending("modalUsuario");
}

function guardarUsuarioVending(evento) {
    evento.preventDefault();
    const id=document.getElementById("idUsuario").value;
    const clave=document.getElementById("contrasenaUsuario").value;
    const confirmar=document.getElementById("confirmarContrasenaUsuario").value;
    if((!id || clave) && clave.length<8){ textoVending("mensajeFormularioUsuario","La contraseña debe tener al menos 8 caracteres."); return; }
    if(clave!==confirmar){ textoVending("mensajeFormularioUsuario","Las contraseñas no coinciden."); return; }
    const usuarios=leerListaVending(CLAVE_USUARIOS_VENDING);
    const correo=document.getElementById("correoUsuario").value.trim();
    if(usuarios.some(u=>u.correo.toLowerCase()===correo.toLowerCase() && u.id!==id)){
        textoVending("mensajeFormularioUsuario","Ya existe un usuario con ese correo."); return;
    }
    const previo=usuarios.find(u=>u.id===id);
    const registro={
        id:id||generarIdVending("USR"), nombre:document.getElementById("nombreUsuario").value.trim(),
        usuario:document.getElementById("nombreAccesoUsuario").value.trim(), correo,
        rol:document.getElementById("rolUsuario").value, tienda:document.getElementById("tiendaUsuario").value,
        activo:document.getElementById("estadoUsuario").value==="Activo",
        contrasena:clave||previo?.contrasena||"", fechaRegistro:previo?.fechaRegistro||fechaHoyVending()
    };
    const indice=usuarios.findIndex(u=>u.id===registro.id);
    indice>=0 ? usuarios.splice(indice,1,registro) : usuarios.push(registro);
    guardarListaVending(CLAVE_USUARIOS_VENDING,usuarios); cerrarModalVending("modalUsuario");
    mostrarMensajeModuloVending("mensajeUsuarios","Usuario guardado correctamente."); renderUsuariosVending();
}

function cambiarEstadoUsuarioVending(id){
    const usuarios=leerListaVending(CLAVE_USUARIOS_VENDING); const usuario=usuarios.find(u=>u.id===id); if(!usuario)return;
    if(usuario.id==="usuario-admin" && usuario.activo!==false){ alert("El administrador principal no puede desactivarse."); return; }
    usuario.activo=usuario.activo===false; guardarListaVending(CLAVE_USUARIOS_VENDING,usuarios); renderUsuariosVending();
}

function renderUsuariosVending(){
    const usuarios=leerListaVending(CLAVE_USUARIOS_VENDING); const q=normalizarTexto(document.getElementById("buscarUsuario")?.value||"");
    const rol=document.getElementById("filtroRolUsuario")?.value||""; const tienda=document.getElementById("filtroTiendaUsuario")?.value||"";
    const estado=document.getElementById("filtroEstadoUsuario")?.value||"";
    const lista=usuarios.filter(u=>(!q||normalizarTexto(`${u.nombre} ${u.usuario||""} ${u.correo}`).includes(q))&&(!rol||u.rol===rol)&&(!tienda||u.tienda===tienda)&&(!estado||(u.activo===false?"Inactivo":"Activo")===estado));
    textoVending("totalUsuarios",usuarios.length); textoVending("usuariosActivos",usuarios.filter(u=>u.activo!==false).length);
    textoVending("totalAdministradores",usuarios.filter(u=>u.rol==="Administrador").length); textoVending("totalTrabajadores",usuarios.filter(u=>u.rol==="Trabajador").length);
    const cuerpo=document.getElementById("cuerpoTablaUsuarios");
    cuerpo.innerHTML=lista.map(u=>`<tr><td><div class="usuario-tabla"><div class="usuario-tabla-icono"><i class="fa-solid fa-user"></i></div><div class="usuario-tabla-datos"><strong>${escaparHTMLVending(u.nombre)}</strong><span>${escaparHTMLVending(u.usuario||"")}</span></div></div></td><td>${escaparHTMLVending(u.correo)}</td><td>${escaparHTMLVending(u.rol)}</td><td>${escaparHTMLVending(u.tienda||"Sin asignar")}</td><td><span class="badge ${u.activo===false?"badge-rojo":"badge-verde"}">${u.activo===false?"Inactivo":"Activo"}</span></td><td>${escaparHTMLVending(u.fechaRegistro||"-")}</td><td><div class="acciones-usuario-tabla"><button class="boton-accion-producto" data-usuario="${u.id}" data-accion="editar" title="Editar"><i class="fa-solid fa-pen"></i></button><button class="boton-accion-producto ${u.activo===false?"boton-activar-producto":"boton-inactivar-producto"}" data-usuario="${u.id}" data-accion="estado" title="Cambiar estado"><i class="fa-solid fa-power-off"></i></button></div></td></tr>`).join("");
    mostrarElementoVending("contenedorTablaUsuarios",lista.length>0); mostrarElementoVending("mensajeSinUsuarios",usuarios.length===0);
    mostrarElementoVending("mensajeSinResultadosUsuarios",usuarios.length>0&&lista.length===0); textoVending("resultadoBusquedaUsuarios",`${lista.length} resultado(s)`);
}

/* ALERTAS */
function obtenerAlertasVending(){
    const guardadas=leerListaVending(CLAVE_ALERTAS_VENDING); const mapa=new Map(guardadas.map(a=>[a.id,a]));
    obtenerProductosGuardados().forEach(p=>{
        const estado=obtenerEstadoStockVending(p); if(estado.valor==="normal")return;
        const id=`stock-${p.id||p.codigo}`; mapa.set(id,{...(mapa.get(id)||{}),id,tipo:"Inventario",prioridad:estado.valor==="agotado"?"Crítica":"Alta",titulo:estado.valor==="agotado"?"Producto agotado":"Stock bajo",descripcion:`${p.nombre} tiene ${Number(p.stock)||0} unidades disponibles.`,tienda:p.ubicacion||"Sin asignar",fecha:fechaHoyVending(),estado:mapa.get(id)?.estado||"Pendiente"});
    });
    const lista=[...mapa.values()]; guardarListaVending(CLAVE_ALERTAS_VENDING,lista); return lista;
}
function inicializarPaginaAlertasVending(){
    if(!document.getElementById("listaAlertas"))return;
    ["buscarAlerta","filtroTipoAlerta","filtroPrioridadAlerta","filtroTiendaAlerta","filtroEstadoAlerta"].forEach(id=>document.getElementById(id)?.addEventListener(id==="buscarAlerta"?"input":"change",renderAlertasVending));
    document.getElementById("botonActualizarAlertas")?.addEventListener("click",()=>{renderAlertasVending();mostrarMensajeModuloVending("mensajeAlertas","Alertas actualizadas.");});
    document.getElementById("botonMarcarTodasAlertas")?.addEventListener("click",()=>{const a=obtenerAlertasVending();a.forEach(x=>x.estado="Atendida");guardarListaVending(CLAVE_ALERTAS_VENDING,a);renderAlertasVending();});
    document.getElementById("botonLimpiarFiltrosAlertas")?.addEventListener("click",()=>{["buscarAlerta","filtroTipoAlerta","filtroPrioridadAlerta","filtroTiendaAlerta","filtroEstadoAlerta"].forEach(id=>{const e=document.getElementById(id);if(e)e.value=""});renderAlertasVending();});
    document.getElementById("listaAlertas")?.addEventListener("click",e=>{const b=e.target.closest("button[data-alerta]");if(!b)return;const a=obtenerAlertasVending();const x=a.find(i=>i.id===b.dataset.alerta);if(x)x.estado=x.estado==="Atendida"?"Pendiente":"Atendida";guardarListaVending(CLAVE_ALERTAS_VENDING,a);renderAlertasVending();});
    renderAlertasVending();
}
function renderAlertasVending(){
    const alertas=obtenerAlertasVending(); const q=normalizarTexto(document.getElementById("buscarAlerta")?.value||"");
    const filtros=["Tipo","Prioridad","Tienda","Estado"].reduce((o,n)=>(o[n.toLowerCase()]=document.getElementById(`filtro${n}Alerta`)?.value||"",o),{});
    const lista=alertas.filter(a=>(!q||normalizarTexto(`${a.titulo} ${a.descripcion} ${a.tienda}`).includes(q))&&(!filtros.tipo||a.tipo===filtros.tipo)&&(!filtros.prioridad||a.prioridad===filtros.prioridad)&&(!filtros.tienda||a.tienda===filtros.tienda)&&(!filtros.estado||a.estado===filtros.estado));
    textoVending("totalAlertas",alertas.length);textoVending("alertasCriticas",alertas.filter(a=>a.prioridad==="Crítica").length);textoVending("alertasPendientes",alertas.filter(a=>a.estado!=="Atendida").length);textoVending("alertasAtendidas",alertas.filter(a=>a.estado==="Atendida").length);
    document.getElementById("listaAlertas").innerHTML=lista.map(a=>`<article class="tarjeta-alerta alerta-${normalizarTexto(a.prioridad)} ${a.estado==="Atendida"?"alerta-atendida":""}"><div class="alerta-icono"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="alerta-contenido"><h3>${escaparHTMLVending(a.titulo)}</h3><p>${escaparHTMLVending(a.descripcion)}</p><div class="alerta-metadatos"><span>${escaparHTMLVending(a.tipo)}</span><span>${escaparHTMLVending(a.tienda)}</span><span>${escaparHTMLVending(a.fecha)}</span><span>${escaparHTMLVending(a.prioridad)}</span></div></div><div class="alerta-acciones"><button class="boton boton-borde boton-tabla" data-alerta="${a.id}">${a.estado==="Atendida"?"Reabrir":"Atender"}</button></div></article>`).join("");
    mostrarElementoVending("mensajeSinAlertas",alertas.length===0);mostrarElementoVending("mensajeSinResultadosAlertas",alertas.length>0&&lista.length===0);textoVending("resultadoBusquedaAlertas",`${lista.length} resultado(s)`);
    document.querySelectorAll(".boton-notificacion .contador").forEach(e=>e.textContent=alertas.filter(a=>a.estado!=="Atendida").length);
}

/* REPORTES */
function inicializarPaginaReportesVending(){
    const form=document.getElementById("formularioReporte"); if(!form)return;
    document.getElementById("fechaInicioReporte").value=fechaHoyVending(); document.getElementById("fechaFinReporte").value=fechaHoyVending();
    form.addEventListener("submit",e=>{e.preventDefault();generarReporteVending();});
    document.getElementById("botonLimpiarReporte")?.addEventListener("click",()=>{form.reset();document.getElementById("fechaInicioReporte").value=fechaHoyVending();document.getElementById("fechaFinReporte").value=fechaHoyVending();});
    document.getElementById("botonImprimirReporte")?.addEventListener("click",()=>window.print());
    document.getElementById("botonExportarReporte")?.addEventListener("click",exportarReporteVending);
}
function datosReporteVending(tipo){
    if(tipo==="Ventas")return leerListaVending("ventasVending"); if(tipo==="Inventario"||tipo==="Productos")return obtenerProductosGuardados();
    if(tipo==="Movimientos")return obtenerMovimientosVending(); if(tipo==="Compras")return leerListaVending(CLAVE_COMPRAS_VENDING); if(tipo==="Cierres de caja")return leerListaVending("cierresCajaVending"); return [];
}
function generarReporteVending(){
    const tipo=document.getElementById("tipoReporte").value, tienda=document.getElementById("tiendaReporte").value, inicio=document.getElementById("fechaInicioReporte").value, fin=document.getElementById("fechaFinReporte").value;
    if(inicio&&fin&&inicio>fin){mostrarMensajeModuloVending("mensajeReportes","La fecha inicial no puede ser posterior a la final.","error");return;}
    let datos=datosReporteVending(tipo).filter(x=>!tienda||(x.tienda||x.ubicacion||x.destino||x.origen)===tienda);
    datos=datos.filter(x=>{const f=String(x.fecha||x.fechaRegistro||"").slice(0,10);return !f||((!inicio||f>=inicio)&&(!fin||f<=fin));});
    const claves=datos.length?Object.keys(datos[0]).filter(k=>!["id","contrasena","imagen","observaciones"].includes(k)).slice(0,8):[];
    document.getElementById("encabezadoTablaReporte").innerHTML=`<tr>${claves.map(k=>`<th>${escaparHTMLVending(k.replace(/([A-Z])/g," $1"))}</th>`).join("")}</tr>`;
    document.getElementById("cuerpoTablaReporte").innerHTML=datos.map(x=>`<tr>${claves.map(k=>`<td>${typeof x[k]==="number"&&/total|precio|monto|subtotal|impuesto/i.test(k)?monedaVending(x[k]):escaparHTMLVending(Array.isArray(x[k])?x[k].length:x[k])}</td>`).join("")}</tr>`).join("");
    const montos=datos.map(x=>Number(x.total||x.monto||x.precioVenta||0)); const suma=montos.reduce((a,b)=>a+b,0);
    textoVending("valorIndicadorReporte1",monedaVending(suma));textoVending("valorIndicadorReporte2",datos.length);textoVending("valorIndicadorReporte3",monedaVending(Math.max(0,...montos)));textoVending("valorIndicadorReporte4",monedaVending(datos.length?suma/datos.length:0));
    textoVending("resumenTipoReporte",tipo);textoVending("resumenTiendaReporte",tienda||"Todas las tiendas");textoVending("resumenFechasReporte",`${inicio||"Inicio"} al ${fin||"Hoy"}`);textoVending("fechaGeneracionReporte",new Date().toLocaleString("es-CR"));
    mostrarElementoVending("resumenPeriodoReporte",true);mostrarElementoVending("contenedorTablaReporte",datos.length>0);mostrarElementoVending("mensajeSinDatosReporte",datos.length===0);mostrarElementoVending("mensajeReporteInicial",false);
}
function exportarReporteVending(){
    const filas=[...document.querySelectorAll("#contenedorTablaReporte tr")].map(f=>[...f.children].map(c=>`"${c.textContent.trim().replace(/"/g,'""')}"`).join(","));
    if(!filas.length){mostrarMensajeModuloVending("mensajeReportes","Primero genera un reporte.","error");return;}
    const blob=new Blob(["\ufeff"+filas.join("\n")],{type:"text/csv;charset=utf-8"});const a=document.createElement("a");a.href=URL.createObjectURL(blob);a.download="reporte-vending.csv";a.click();URL.revokeObjectURL(a.href);
}

/* CRUD GENÉRICO DE COMPRAS, PEDIDOS Y FACTURAS */
function configurarModuloListadoVending(config){
    if(!document.getElementById(config.cuerpo))return;
    config.inicializar?.();
    const abrir=()=>config.preparar(); document.getElementById(config.botonNuevo)?.addEventListener("click",abrir);document.getElementById(config.botonPrimero)?.addEventListener("click",abrir);
    document.getElementById(config.botonCerrar)?.addEventListener("click",()=>cerrarModalVending(config.modal));document.getElementById(config.botonCancelar)?.addEventListener("click",()=>cerrarModalVending(config.modal));
    document.querySelector(config.selectorFondo)?.addEventListener("click",()=>cerrarModalVending(config.modal));document.getElementById(config.formulario)?.addEventListener("submit",config.guardar);
    config.filtros.forEach(id=>document.getElementById(id)?.addEventListener(id.startsWith("buscar")?"input":"change",config.render));
    document.getElementById(config.botonLimpiar)?.addEventListener("click",()=>{config.filtros.forEach(id=>{const e=document.getElementById(id);if(e)e.value=""});config.render();});
    config.render();
}
function inicializarPaginaComprasVending(){configurarModuloListadoVending({cuerpo:"cuerpoTablaCompras",botonNuevo:"botonNuevaCompra",botonPrimero:"botonPrimeraCompra",botonCerrar:"botonCerrarModalCompra",botonCancelar:"botonCancelarCompra",modal:"modalCompra",selectorFondo:"[data-cerrar-modal-compra]",formulario:"formularioCompra",botonLimpiar:"botonLimpiarFiltrosCompras",filtros:["buscarCompra","filtroProveedorCompra","filtroTiendaCompra","filtroEstadoCompra","filtroFechaCompra"],preparar:prepararCompraVending,guardar:guardarCompraVending,render:renderComprasVending,inicializar:()=>document.getElementById("botonAgregarProductoCompra")?.addEventListener("click",()=>agregarFilaProductoVending("listaProductosCompra","mensajeSinProductosCompra"))});}
function prepararCompraVending(){document.getElementById("formularioCompra").reset();document.getElementById("numeroCompra").value=`COM-${Date.now().toString().slice(-6)}`;document.getElementById("fechaCompra").value=fechaHoyVending();document.getElementById("listaProductosCompra").innerHTML="";agregarFilaProductoVending("listaProductosCompra","mensajeSinProductosCompra");abrirModalVending("modalCompra");}
function agregarFilaProductoVending(listaId,mensajeId){const lista=document.getElementById(listaId);const fila=document.createElement("div");fila.className="fila-producto-compra";fila.innerHTML=`<div class="grupo-formulario"><label>Producto</label><input class="item-nombre" required placeholder="Producto"></div><div class="grupo-formulario"><label>Cantidad</label><input class="item-cantidad" type="number" min="1" value="1" required></div><div class="grupo-formulario"><label>Precio</label><input class="item-precio" type="number" min="0" step="0.01" value="0" required></div><div class="grupo-formulario"><label>Impuesto %</label><input class="item-iva" type="number" min="0" step="0.01" value="13"></div><button type="button" class="boton-eliminar-producto-compra"><i class="fa-solid fa-trash"></i></button>`;fila.querySelector("button").addEventListener("click",()=>{fila.remove();calcularTotalesLineasVending(listaId);mostrarElementoVending(mensajeId,!lista.children.length)});fila.querySelectorAll("input").forEach(i=>i.addEventListener("input",()=>calcularTotalesLineasVending(listaId)));lista.appendChild(fila);mostrarElementoVending(mensajeId,false);calcularTotalesLineasVending(listaId);}
function extraerLineasVending(listaId){return [...document.querySelectorAll(`#${listaId} .fila-producto-compra`)].map(f=>({nombre:f.querySelector(".item-nombre").value.trim(),cantidad:Number(f.querySelector(".item-cantidad").value)||0,precio:Number(f.querySelector(".item-precio").value)||0,iva:Number(f.querySelector(".item-iva")?.value)||0}));}
function calcularTotalesLineasVending(listaId){const items=extraerLineasVending(listaId);const sub=items.reduce((s,i)=>s+i.cantidad*i.precio,0);const iva=items.reduce((s,i)=>s+i.cantidad*i.precio*i.iva/100,0);if(listaId.includes("Compra")){textoVending("subtotalCompra",monedaVending(sub));textoVending("impuestoCompra",monedaVending(iva));textoVending("totalCompra",monedaVending(sub+iva));}else{textoVending("cantidadTotalPedido",items.reduce((s,i)=>s+i.cantidad,0));textoVending("subtotalPedido",monedaVending(sub));textoVending("totalPedido",monedaVending(sub+iva));}return{sub,iva,total:sub+iva,items};}
function guardarCompraVending(e){e.preventDefault();const t=calcularTotalesLineasVending("listaProductosCompra");if(!t.items.length||t.items.some(i=>!i.nombre||i.cantidad<=0)){textoVending("mensajeFormularioCompra","Agrega al menos un producto válido.");return;}const a=leerListaVending(CLAVE_COMPRAS_VENDING);a.push({id:generarIdVending("COM"),numero:document.getElementById("numeroCompra").value,fecha:document.getElementById("fechaCompra").value,proveedor:document.getElementById("proveedorCompra").value.trim(),tienda:document.getElementById("tiendaCompra").value,referencia:document.getElementById("referenciaCompra").value.trim(),estado:document.getElementById("estadoCompra").value,productos:t.items,subtotal:t.sub,impuesto:t.iva,total:t.total});guardarListaVending(CLAVE_COMPRAS_VENDING,a);cerrarModalVending("modalCompra");renderComprasVending();mostrarMensajeModuloVending("mensajeCompras","Compra registrada correctamente.");}
function renderComprasVending(){renderTablaSimpleVending({clave:CLAVE_COMPRAS_VENDING,prefijo:"Compra",cuerpo:"cuerpoTablaCompras",contenedor:"contenedorTablaCompras",vacio:"mensajeSinCompras",sinResultados:"mensajeSinResultadosCompras",resultado:"resultadoBusquedaCompras",buscar:"buscarCompra",filtros:{proveedor:"filtroProveedorCompra",tienda:"filtroTiendaCompra",estado:"filtroEstadoCompra",fecha:"filtroFechaCompra"},resumen:[['totalCompras',a=>a.length],['comprasPendientes',a=>a.filter(x=>x.estado==='Pendiente').length],['comprasRecibidas',a=>a.filter(x=>x.estado==='Recibida').length],['montoTotalCompras',a=>monedaVending(a.reduce((s,x)=>s+Number(x.total||0),0))]],fila:x=>`<tr><td>${escaparHTMLVending(x.numero)}</td><td>${x.fecha}</td><td>${escaparHTMLVending(x.proveedor)}</td><td>${escaparHTMLVending(x.tienda)}</td><td>${x.productos?.length||0}</td><td>${monedaVending(x.subtotal)}</td><td>${monedaVending(x.impuesto)}</td><td>${monedaVending(x.total)}</td><td><span class="badge badge-${x.estado==='Recibida'?'verde':x.estado==='Cancelada'?'rojo':'naranja'}">${x.estado}</span></td><td>-</td></tr>`});}
function renderTablaSimpleVending(c){const todos=leerListaVending(c.clave);const q=normalizarTexto(document.getElementById(c.buscar)?.value||"");const lista=todos.filter(x=>(!q||normalizarTexto(JSON.stringify(x)).includes(q))&&Object.entries(c.filtros).every(([k,id])=>{const v=document.getElementById(id)?.value||"";return !v||String(x[k]||"")===v}));document.getElementById(c.cuerpo).innerHTML=lista.map(c.fila).join("");c.resumen.forEach(([id,fn])=>textoVending(id,fn(todos)));mostrarElementoVending(c.contenedor,lista.length>0);mostrarElementoVending(c.vacio,todos.length===0);mostrarElementoVending(c.sinResultados,todos.length>0&&lista.length===0);textoVending(c.resultado,`${lista.length} resultado(s)`);}

function inicializarPaginaPedidosVending(){configurarModuloListadoVending({cuerpo:"cuerpoTablaPedidos",botonNuevo:"botonNuevoPedido",botonPrimero:"botonPrimerPedido",botonCerrar:"botonCerrarModalPedido",botonCancelar:"botonCancelarPedido",modal:"modalPedido",selectorFondo:"[data-cerrar-modal-pedido]",formulario:"formularioPedido",botonLimpiar:"botonLimpiarFiltrosPedidos",filtros:["buscarPedido","filtroProveedorPedido","filtroTiendaPedido","filtroEstadoPedido","filtroFechaPedido"],preparar:prepararPedidoVending,guardar:guardarPedidoVending,render:renderPedidosVending,inicializar:()=>document.getElementById("botonAgregarProductoPedido")?.addEventListener("click",()=>agregarFilaProductoVending("listaProductosPedido","mensajeSinProductosPedido"))});}
function prepararPedidoVending(){document.getElementById("formularioPedido").reset();document.getElementById("numeroPedido").value=`PED-${Date.now().toString().slice(-6)}`;document.getElementById("fechaPedido").value=fechaHoyVending();document.getElementById("responsablePedido").value=obtenerSesionVending()?.nombre||"";document.getElementById("listaProductosPedido").innerHTML="";agregarFilaProductoVending("listaProductosPedido","mensajeSinProductosPedido");abrirModalVending("modalPedido");}
function guardarPedidoVending(e){e.preventDefault();const t=calcularTotalesLineasVending("listaProductosPedido");if(!t.items.length||t.items.some(i=>!i.nombre)){textoVending("mensajeFormularioPedido","Agrega productos válidos.");return;}const a=leerListaVending(CLAVE_PEDIDOS_VENDING);a.push({id:generarIdVending("PED"),numero:document.getElementById("numeroPedido").value,fecha:document.getElementById("fechaPedido").value,proveedor:document.getElementById("proveedorPedido").value.trim(),tienda:document.getElementById("tiendaPedido").value,responsable:document.getElementById("responsablePedido").value.trim(),estado:document.getElementById("estadoPedido").value,productos:t.items,total:t.total});guardarListaVending(CLAVE_PEDIDOS_VENDING,a);cerrarModalVending("modalPedido");renderPedidosVending();mostrarMensajeModuloVending("mensajePedidos","Pedido guardado correctamente.");}
function renderPedidosVending(){renderTablaSimpleVending({clave:CLAVE_PEDIDOS_VENDING,cuerpo:"cuerpoTablaPedidos",contenedor:"contenedorTablaPedidos",vacio:"mensajeSinPedidos",sinResultados:"mensajeSinResultadosPedidos",resultado:"resultadoBusquedaPedidos",buscar:"buscarPedido",filtros:{proveedor:"filtroProveedorPedido",tienda:"filtroTiendaPedido",estado:"filtroEstadoPedido",fecha:"filtroFechaPedido"},resumen:[['totalPedidos',a=>a.length],['pedidosPendientes',a=>a.filter(x=>x.estado==='Pendiente').length],['pedidosAprobados',a=>a.filter(x=>x.estado==='Aprobado').length],['totalProductosPedidos',a=>a.reduce((s,x)=>s+(x.productos||[]).reduce((n,i)=>n+Number(i.cantidad||0),0),0)]],fila:x=>`<tr><td>${x.numero}</td><td>${x.fecha}</td><td>${escaparHTMLVending(x.proveedor)}</td><td>${x.tienda}</td><td>${escaparHTMLVending(x.responsable)}</td><td>${x.productos?.length||0}</td><td>${monedaVending(x.total)}</td><td><span class="badge badge-${x.estado==='Aprobado'?'verde':x.estado==='Rechazado'?'rojo':'naranja'}">${x.estado}</span></td><td>-</td></tr>`});}

function inicializarPaginaFacturasVending(){configurarModuloListadoVending({cuerpo:"cuerpoTablaFacturas",botonNuevo:"botonNuevaFactura",botonPrimero:"botonPrimeraFactura",botonCerrar:"botonCerrarModalFactura",botonCancelar:"botonCancelarFactura",modal:"modalFactura",selectorFondo:"[data-cerrar-modal-factura]",formulario:"formularioFactura",botonLimpiar:"botonLimpiarFiltrosFacturas",filtros:["buscarFactura","filtroProveedorFactura","filtroTiendaFactura","filtroEstadoFactura","filtroFechaFactura"],preparar:prepararFacturaVending,guardar:guardarFacturaVending,render:renderFacturasVending,inicializar:()=>{["subtotalFactura","impuestoFactura"].forEach(id=>document.getElementById(id)?.addEventListener("input",calcularFacturaVending));document.getElementById("archivoFactura")?.addEventListener("change",mostrarArchivoFacturaVending);document.getElementById("botonQuitarArchivoFactura")?.addEventListener("click",()=>{document.getElementById("archivoFactura").value="";mostrarElementoVending("informacionArchivoFactura",false);});}});}
function prepararFacturaVending(){document.getElementById("formularioFactura").reset();document.getElementById("fechaFactura").value=fechaHoyVending();document.getElementById("fechaVencimientoFactura").value=fechaHoyVending();const select=document.getElementById("compraRelacionadaFactura");select.innerHTML='<option value="">Sin compra relacionada</option>'+leerListaVending(CLAVE_COMPRAS_VENDING).map(c=>`<option value="${c.numero}">${c.numero} - ${escaparHTMLVending(c.proveedor)}</option>`).join('');calcularFacturaVending();abrirModalVending("modalFactura");}
function calcularFacturaVending(){const s=Number(document.getElementById("subtotalFactura")?.value)||0,i=Number(document.getElementById("impuestoFactura")?.value)||0,t=s+i;const total=document.getElementById("totalFactura");if(total)total.value=t.toFixed(2);textoVending("resumenSubtotalFactura",monedaVending(s));textoVending("resumenImpuestoFactura",monedaVending(i));textoVending("resumenTotalFactura",monedaVending(t));}
function mostrarArchivoFacturaVending(){const f=document.getElementById("archivoFactura")?.files?.[0];if(!f){mostrarElementoVending("informacionArchivoFactura",false);return;}textoVending("nombreArchivoFactura",f.name);textoVending("detalleArchivoFactura",`${(f.size/1024).toFixed(1)} KB`);mostrarElementoVending("informacionArchivoFactura",true);}
function guardarFacturaVending(e){e.preventDefault();calcularFacturaVending();const a=leerListaVending(CLAVE_FACTURAS_VENDING);a.push({id:generarIdVending("FAC"),numero:document.getElementById("numeroFactura").value.trim(),fecha:document.getElementById("fechaFactura").value,vencimiento:document.getElementById("fechaVencimientoFactura").value,proveedor:document.getElementById("proveedorFactura").value.trim(),compra:document.getElementById("compraRelacionadaFactura").value,tienda:document.getElementById("tiendaFactura").value,subtotal:Number(document.getElementById("subtotalFactura").value)||0,impuesto:Number(document.getElementById("impuestoFactura").value)||0,total:Number(document.getElementById("totalFactura").value)||0,estado:document.getElementById("estadoFactura").value,metodoPago:document.getElementById("metodoPagoFactura").value,fechaPago:document.getElementById("fechaPagoFactura").value});guardarListaVending(CLAVE_FACTURAS_VENDING,a);cerrarModalVending("modalFactura");renderFacturasVending();mostrarMensajeModuloVending("mensajeFacturas","Factura guardada correctamente.");}
function renderFacturasVending(){renderTablaSimpleVending({clave:CLAVE_FACTURAS_VENDING,cuerpo:"cuerpoTablaFacturas",contenedor:"contenedorTablaFacturas",vacio:"mensajeSinFacturas",sinResultados:"mensajeSinResultadosFacturas",resultado:"resultadoBusquedaFacturas",buscar:"buscarFactura",filtros:{proveedor:"filtroProveedorFactura",tienda:"filtroTiendaFactura",estado:"filtroEstadoFactura",fecha:"filtroFechaFactura"},resumen:[['totalFacturas',a=>a.length],['facturasPendientes',a=>a.filter(x=>x.estado==='Pendiente').length],['facturasPagadas',a=>a.filter(x=>x.estado==='Pagada').length],['montoTotalFacturas',a=>monedaVending(a.reduce((s,x)=>s+Number(x.total||0),0))]],fila:x=>`<tr><td>${escaparHTMLVending(x.numero)}</td><td>${x.fecha}</td><td>${x.vencimiento}</td><td>${escaparHTMLVending(x.proveedor)}</td><td>${escaparHTMLVending(x.compra||'-')}</td><td>${x.tienda}</td><td>${monedaVending(x.subtotal)}</td><td>${monedaVending(x.impuesto)}</td><td>${monedaVending(x.total)}</td><td><span class="badge badge-${x.estado==='Pagada'?'verde':x.estado==='Vencida'||x.estado==='Anulada'?'rojo':'naranja'}">${x.estado}</span></td><td>-</td></tr>`});}
