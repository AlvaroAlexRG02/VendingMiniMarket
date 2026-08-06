/*=========================================================
  VENDING MINI MARKET — Script general del sistema
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {
    inicializarMostrarContrasena();
    inicializarMenuLateral();
    inicializarFormularioProducto();
    inicializarPaginaProductos();
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