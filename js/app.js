/*=========================================================
  VENDING MINI MARKET — Script general del sistema
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {
    inicializarNavegacionLateral();
    inicializarMostrarContrasena();
    inicializarMenuLateral();
    inicializarSesionVending();
    inicializarPaginaInventarioVending();
    inicializarPaginaMovimientosVending();
    inicializarPaginaVentas();
    inicializarDashboardVending();
    actualizarContadoresNotificacionVending();
    inicializarPaginaUsuariosVending();
    inicializarPaginaAlertasVending();
    inicializarPaginaReportesVending();
    inicializarResumenInventarioVending();
});

function inicializarNavegacionLateral() {
    const navegaciones = document.querySelectorAll(".nav-lateral");

    if (navegaciones.length === 0) {
        return;
    }

    const scriptAplicacion = Array.from(document.scripts).find((script) => {
        return script.src && new URL(script.src).pathname.endsWith("/js/app.js");
    });

    if (!scriptAplicacion) {
        return;
    }

    const raizAplicacion = new URL("../", scriptAplicacion.src);
    const rutaRaiz = raizAplicacion.pathname;
    const rutaActual = window.location.pathname.startsWith(rutaRaiz)
        ? window.location.pathname.slice(rutaRaiz.length)
        : window.location.pathname.replace(/^\/+/, "");

    const paginas = [
        {
            nombre: "Dashboard",
            ruta: "dashboard/dashboard.php",
            icono: "fa-chart-pie",
            rutasActivas: ["dashboard/dashboard.php"]
        },
        {
            nombre: "Usuarios",
            ruta: "usuarios/usuarios.php",
            icono: "fa-users",
            rutasActivas: ["usuarios/usuarios.php"]
        },
        {
            nombre: "Productos",
            ruta: "productos/productos.php",
            icono: "fa-box",
            rutasActivas: [
                "productos/productos.php",
                "productos/productos.html",
                "productos/producto-nuevo.php",
                "productos/producto-nuevo.html",
                "productos/categorias.php",
                "productos/proveedores.php"
            ]
        },
        {
            nombre: "Tiendas",
            ruta: "tienda/tienda.php",
            icono: "fa-shop",
            rutasActivas: [
                "tienda/tienda.php",
                "tienda/relaciones.php",
                "tienda/ubicacion-nueva.php"
            ]
        },
        {
            nombre: "Máquinas",
            ruta: "maquinas/maquinas.php",
            icono: "fa-cash-register",
            rutasActivas: ["maquinas/maquinas.php", "maquinas/maquina-nueva.php"]
        },
        {
            nombre: "Inventario",
            ruta: "inventario/inventario.php",
            icono: "fa-clipboard-list",
            rutasActivas: ["inventario/inventario.php", "inventario/movimientos.html"]
        },
        {
            nombre: "Compras",
            ruta: "compras/compras.php",
            icono: "fa-cart-shopping",
            rutasActivas: [
                "compras/compras.php",
                "compras/facturas.php",
                "compras/pedido.html",
                "compras/pedido.php"
            ]
        },
        {
            nombre: "Ventas",
            ruta: "ventas/ventas.html",
            icono: "fa-chart-column",
            rutasActivas: ["ventas/ventas.html"]
        },
        {
            nombre: "Alertas",
            ruta: "alertas/alertas.html",
            icono: "fa-bell",
            rutasActivas: ["alertas/alertas.html"]
        },
        {
            nombre: "Reportes",
            ruta: "reportes/reportes.php",
            icono: "fa-chart-line",
            rutasActivas: ["reportes/reportes.php"]
        },
        {
            nombre: "Bitácora",
            ruta: "bitacora/bitacora.php",
            icono: "fa-clipboard-list",
            rutasActivas: ["bitacora/bitacora.php"]
        },
        {
            nombre: "Respaldos",
            ruta: "respaldo/respaldo.php",
            icono: "fa-database",
            rutasActivas: ["respaldo/respaldo.php"]
        },
        {
            nombre: "Incidencias",
            ruta: "incidencias/incidencias.php",
            icono: "fa-triangle-exclamation",
            rutasActivas: ["incidencias/incidencias.php"]
        }
    ];

    navegaciones.forEach((navegacion) => {
        navegacion.replaceChildren();
        navegacion.setAttribute("aria-label", "Navegación principal");

        paginas.forEach((pagina) => {
            const enlace = document.createElement("a");
            const icono = document.createElement("i");
            const activa = pagina.rutasActivas.includes(rutaActual);

            enlace.href = new URL(pagina.ruta, raizAplicacion).href;
            enlace.classList.toggle("activo", activa);
            if (activa) {
                enlace.setAttribute("aria-current", "page");
            }

            icono.className = `fa-solid ${pagina.icono}`;
            enlace.append(icono, document.createTextNode(pagina.nombre));
            navegacion.appendChild(enlace);
        });
    });
}

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

    cargarTiendasVenta();
    cargarVentasServidor();
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

        return [
            ...ventasServidor,
            ...(Array.isArray(datos) ? datos : [])
        ];
    } catch (error) {
        console.error(
            "No se pudieron leer las ventas:",
            error
        );

        return ventasServidor;
    }
}

let ventasServidor = [];
let productosVenta = [];
let carritoVenta = [];

async function solicitarApiVentas(accion, opciones = {}) {
    const [nombreAccion, parametros] = accion.split("&", 2);
    const query = `accion=${encodeURIComponent(nombreAccion)}` +
        (parametros ? `&${parametros}` : "");
    const respuesta = await fetch(
        `../api/ventas/ventas.php?${query}`,
        {
            credentials: "same-origin",
            cache: "no-store",
            ...opciones
        }
    );
    const resultado = await respuesta.json();

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.message || "No fue posible completar la operación.");
    }

    return resultado.data;
}

async function cargarTiendasVenta() {
    try {
        const tiendas = await solicitarApiVentas("tiendas");
        const selectores = [
            document.getElementById("tiendaVenta"),
            document.getElementById("filtroTiendaVenta")
        ];

        selectores.forEach((selector) => {
            if (!selector) {
                return;
            }

            const opcionInicial = selector.options[0];
            selector.replaceChildren(opcionInicial);

            tiendas.forEach((tienda) => {
                const opcion = document.createElement("option");
                opcion.value = String(tienda.id_tienda);
                opcion.textContent = tienda.nombre;
                selector.appendChild(opcion);
            });
        });

        const tiendasCierre = document.getElementById("tiendaCierreCaja");
        if (tiendasCierre) {
            const opcionInicial = tiendasCierre.options[0];
            tiendasCierre.replaceChildren(opcionInicial);
            tiendas.forEach((tienda) => {
                const opcion = document.createElement("option");
                opcion.value = tienda.nombre;
                opcion.textContent = tienda.nombre;
                tiendasCierre.appendChild(opcion);
            });
        }
    } catch (error) {
        mostrarMensajeVentas(error.message, "error");
    }
}

async function cargarVentasServidor() {
    try {
        ventasServidor = await solicitarApiVentas("listar");
        mostrarVentas();
        actualizarCantidadImportacionesPendientes();
    } catch (error) {
        mostrarMensajeVentas(error.message, "error");
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

    document.getElementById("productoVenta")?.addEventListener(
        "keydown",
        (evento) => {
            if (evento.key === "Enter") {
                evento.preventDefault();
                agregarProductoCarritoVenta();
            }
        }
    );

    document.getElementById(
        "cantidadVenta"
    )?.addEventListener(
        "input",
        calcularFormularioVenta
    );

    document.getElementById("botonAgregarProductoVenta")?.addEventListener(
        "click",
        agregarProductoCarritoVenta
    );

    document.getElementById("tiendaVenta")?.addEventListener(
        "change",
        cargarProductosEnVenta
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

    modal.hidden = false;

    document.body.classList.add(
        "modal-producto-abierto"
    );

    document.getElementById("tiendaVenta")?.focus();
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

    carritoVenta = [];
    renderizarCarritoVenta();
    productosVenta = [];
    const listado = document.getElementById("productosDisponiblesVenta");
    if (listado) {
        listado.replaceChildren();
    }

    document.getElementById(
        "grupoReferenciaSinpe"
    ).hidden = true;

    document.getElementById(
        "referenciaSinpeVenta"
    ).required = false;

    limpiarTotalesFormularioVenta();
    limpiarMensajeFormularioVenta();
}

async function cargarProductosEnVenta() {
    const idTienda = document.getElementById("tiendaVenta")?.value;
    const listado = document.getElementById("productosDisponiblesVenta");
    const ubicacion = document.getElementById("ubicacionPredeterminadaVenta");

    productosVenta = [];
    carritoVenta = [];
    renderizarCarritoVenta();
    listado?.replaceChildren();

    if (!idTienda) {
        if (ubicacion) {
            ubicacion.textContent = "";
        }
        return;
    }

    try {
        const resultado = await solicitarApiVentas(
            `productos&id_tienda=${encodeURIComponent(idTienda)}`
        );
        productosVenta = resultado.productos;

        productosVenta.forEach((producto) => {
            const opcion = document.createElement("option");
            opcion.value = producto.nombre;
            opcion.label = [
                producto.codigo_barras,
                producto.codigo_articulo_retail,
                `Stock: ${producto.stock}`
            ].filter(Boolean).join(" · ");
            listado?.appendChild(opcion);
        });

        if (ubicacion) {
            ubicacion.textContent =
                `Inventario: ${resultado.ubicacion.nombre}`;
        }
    } catch (error) {
        if (ubicacion) {
            ubicacion.textContent = "";
        }
        mostrarMensajeFormularioVenta(error.message, "error");
    }
}

function buscarProductoVenta(valor) {
    const busqueda = normalizarTexto(valor);
    if (!busqueda) {
        return null;
    }

    const porCodigo = productosVenta.find((producto) =>
        [producto.codigo_barras, producto.codigo_articulo_retail]
            .some((codigo) => codigo && String(codigo).trim() === String(valor).trim())
    );

    return porCodigo || productosVenta.find(
        (producto) => normalizarTexto(producto.nombre) === busqueda
    );
}

function agregarProductoCarritoVenta() {
    const entrada = document.getElementById("productoVenta");
    const cantidadCampo = document.getElementById("cantidadVenta");
    const cantidad = Number(cantidadCampo?.value);
    const producto = buscarProductoVenta(entrada?.value || "");

    if (!producto) {
        mostrarMensajeFormularioVenta(
            "Busque un producto activo con precio configurado para esta tienda.",
            "error"
        );
        return;
    }

    if (!Number.isInteger(cantidad) || cantidad <= 0) {
        mostrarMensajeFormularioVenta("La cantidad debe ser un entero mayor que cero.", "error");
        return;
    }

    const existente = carritoVenta.find(
        (item) => item.id_producto === producto.id_producto
    );
    const cantidadNueva = cantidad + (existente?.cantidad || 0);

    if (cantidadNueva > producto.stock) {
        mostrarMensajeFormularioVenta(
            `Existencias insuficientes para ${producto.nombre}. Disponible: ${producto.stock}.`,
            "error"
        );
        return;
    }

    if (existente) {
        existente.cantidad = cantidadNueva;
    } else {
        carritoVenta.push({ ...producto, cantidad });
    }

    entrada.value = "";
    cantidadCampo.value = "1";
    limpiarMensajeFormularioVenta();
    renderizarCarritoVenta();
}

function renderizarCarritoVenta() {
    const cuerpo = document.getElementById("cuerpoCarritoVenta");
    if (!cuerpo) {
        return;
    }

    cuerpo.replaceChildren();
    carritoVenta.forEach((producto) => {
        const fila = document.createElement("tr");
        const subtotal = Number(producto.precio_venta) * producto.cantidad;
        const total = subtotal * (1 + Number(producto.impuesto_porcentaje) / 100);
        const valores = [
            producto.nombre,
            String(producto.cantidad),
            formatearMonedaCRC(producto.precio_venta),
            formatearMonedaCRC(total)
        ];

        valores.forEach((valor) => {
            const celda = document.createElement("td");
            celda.textContent = valor;
            fila.appendChild(celda);
        });

        const celdaAccion = document.createElement("td");
        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "boton-accion-producto";
        boton.setAttribute("aria-label", `Quitar ${producto.nombre}`);
        boton.innerHTML = '<i class="fa-solid fa-trash"></i>';
        boton.addEventListener("click", () => {
            carritoVenta = carritoVenta.filter(
                (item) => item.id_producto !== producto.id_producto
            );
            renderizarCarritoVenta();
        });
        celdaAccion.appendChild(boton);
        fila.appendChild(celdaAccion);
        cuerpo.appendChild(fila);
    });

    calcularFormularioVenta();
}

function obtenerCalculoVenta() {
    return carritoVenta.reduce((resumen, producto) => {
        const subtotal = Number(producto.precio_venta) * producto.cantidad;
        const total = subtotal * (1 + Number(producto.impuesto_porcentaje) / 100);
        resumen.subtotal += subtotal;
        resumen.total += total;
        resumen.impuestoMonto += total - subtotal;
        return resumen;
    }, { subtotal: 0, impuestoMonto: 0, total: 0 });
}

function calcularFormularioVenta() {
    const calculo = obtenerCalculoVenta();

    document.getElementById(
        "subtotalFormularioVenta"
    ).textContent =
        formatearMonedaCRC(calculo.subtotal);

    document.getElementById(
        "impuestoFormularioVenta"
    ).textContent = formatearMonedaCRC(calculo.impuestoMonto);

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

async function registrarVentaManual(evento) {
    evento.preventDefault();

    const idTienda = document.getElementById(
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

    if (
        !idTienda ||
        !metodoPago ||
        carritoVenta.length === 0
    ) {
        mostrarMensajeFormularioVenta(
            "Seleccione una tienda, un método de pago y agregue productos.",
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

    const formulario = document.getElementById("formularioVenta");
    const botonConfirmar = formulario?.querySelector('[type="submit"]');
    if (botonConfirmar) {
        botonConfirmar.disabled = true;
    }

    try {
        const venta = await solicitarApiVentas("registrar", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                id_tienda: Number(idTienda),
                metodo_pago: metodoPago,
                referencia_sinpe: metodoPago === "SINPE" ? referenciaSinpe : "",
                observaciones: observaciones || "",
                items: carritoVenta.map((item) => ({
                    id_producto: item.id_producto,
                    cantidad: item.cantidad
                }))
            })
        });

        ventasServidor = [venta, ...ventasServidor];
        cerrarModalVenta();
        mostrarVentas();
        mostrarMensajeVentas(
            `La venta ${venta.numero} fue registrada correctamente.`,
            "exito"
        );
    } catch (error) {
        mostrarMensajeFormularioVenta(
            error.message,
            "error"
        );
    } finally {
        if (botonConfirmar) {
            botonConfirmar.disabled = false;
        }
    }
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
        const detalleProductos = Array.isArray(venta.productos)
            ? venta.productos.map((producto) =>
                `${producto.cantidad} x ${producto.nombre}`
            ).join("\n")
            : `${venta.cantidad || 0} x ${venta.productoNombre || "Producto"}`;

        window.alert(
            [
                `Venta: ${venta.numero}`,
                `Productos:\n${detalleProductos}`,
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
    const selectorTienda = document.getElementById("filtroTiendaVenta");
    const nombreTienda = selectorTienda?.selectedOptions[0]?.textContent || "";

    return ventas.filter((venta) => {
        const texto = normalizarTexto(
            [
                venta.numero,
                venta.productoNombre ||
                    (venta.productos || []).map((producto) => producto.nombre).join(" "),
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
                String(venta.id_tienda || "") === tienda ||
                (!venta.id_tienda && venta.tienda === nombreTienda)) &&
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

    const formulario =
        document.getElementById("formularioLogin") ||
        document.querySelector("form.formulario-login");

    if (formulario) {
        formulario.addEventListener(
            "submit",
            procesarInicioSesionVending
        );
    }

    const botonCerrar =
        document.getElementById("botonCerrarSesion");

    if (botonCerrar) {
        botonCerrar.addEventListener(
            "click",
            cerrarSesionVending
        );
    }
}


async function procesarInicioSesionVending(evento) {

    evento.preventDefault();

    const campoCorreo =
        document.getElementById("correo");

    const campoContrasena =
        document.getElementById("contrasena");

    const mensaje =
        document.getElementById("mensajeLogin");

    const botonIngresar =
        document.querySelector(".boton-ingresar");

    const correo =
        campoCorreo?.value.trim() || "";

    const contrasena =
        campoContrasena?.value || "";

    if (!correo || !contrasena) {

        if (mensaje) {
            mensaje.textContent =
                "Debe ingresar el correo y la contraseña.";
        }

        return;
    }

    try {

        if (botonIngresar) {
            botonIngresar.disabled = true;
        }

        if (mensaje) {
            mensaje.textContent = "Validando acceso...";
        }

        const respuesta = await fetch(
            "../api/auth/login.php",
            {
                method: "POST",
                headers: {
                    "Content-Type":
                        "application/json"
                },
                body: JSON.stringify({
                    correo: correo,
                    contrasena: contrasena
                })
            }
        );

        const resultado =
            await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {

            if (mensaje) {
                mensaje.textContent =
                    resultado.mensaje ||
                    "No fue posible iniciar sesión.";
            }

            return;
        }

        /*
         * La autenticación ahora está manejada
         * por la sesión PHP.
         *
         * No se guarda la contraseña ni
         * la sesión en localStorage.
         */

        window.location.href =
            "../dashboard/dashboard.php";

    } catch (error) {

        console.error(
            "Error al iniciar sesión:",
            error
        );

        if (mensaje) {
            mensaje.textContent =
                "No fue posible comunicarse con el servidor.";
        }

    } finally {

        if (botonIngresar) {
            botonIngresar.disabled = false;
        }
    }
}


function mostrarModalConfirmarCerrarSesionVending() {

    return new Promise((resolve) => {

        let modal = document.getElementById("modalConfirmarCerrarSesion");

        if (!modal) {

            modal = document.createElement("div");
            modal.id = "modalConfirmarCerrarSesion";
            modal.className = "modal-producto";
            modal.hidden = true;

            modal.innerHTML = `
                <div class="modal-producto-fondo" data-cerrar-modal-cerrar-sesion></div>

                <section
                    class="modal-producto-contenido"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="tituloModalCerrarSesion"
                >

                    <div class="modal-producto-icono">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </div>

                    <h2 id="tituloModalCerrarSesion">
                        Cerrar sesión
                    </h2>

                    <p>
                        ¿Deseas cerrar sesión?
                    </p>

                    <div class="acciones-modal-producto">

                        <button
                            type="button"
                            id="botonCancelarCerrarSesion"
                            class="boton boton-borde"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            id="botonConfirmarCerrarSesion"
                            class="boton boton-azul"
                        >
                            Cerrar sesión
                        </button>

                    </div>

                </section>
            `;

            document.body.appendChild(modal);
        }

        const cerrar = (resultado) => {
            modal.hidden = true;
            resolve(resultado);
        };

        modal.querySelector("[data-cerrar-modal-cerrar-sesion]").onclick = () => cerrar(false);
        modal.querySelector("#botonCancelarCerrarSesion").onclick = () => cerrar(false);
        modal.querySelector("#botonConfirmarCerrarSesion").onclick = () => cerrar(true);

        modal.hidden = false;
    });
}

async function cerrarSesionVending(evento) {

    if (evento) {
        evento.preventDefault();
    }

    const confirmado = await mostrarModalConfirmarCerrarSesionVending();

    if (!confirmado) {
        return;
    }

    // Limpia cualquier dato de sesion viejo que haya quedado en
    // localStorage (de versiones anteriores que si lo guardaban ahi),
    // para que nunca vuelva a pisar los datos reales que renderiza PHP.
    localStorage.removeItem(CLAVE_SESION_VENDING);

    try {

        const respuesta = await fetch(
            "../api/auth/logout.php",
            {
                method: "POST"
            }
        );

        const resultado =
            await respuesta.json();

        if (resultado.exito) {

            window.location.href =
                "../index/index.html";

            return;
        }

        console.error(
            "No fue posible cerrar sesión:",
            resultado.mensaje
        );

    } catch (error) {

        console.error(
            "Error al cerrar sesión:",
            error
        );
    }
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
    cargarAlertasDashboardVending();
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

function mostrarAlertasDashboardVending(alertas) {
    const lista = document.getElementById("listaAlertasDashboard");

    if (!lista) {
        return;
    }

    lista.innerHTML = "";
    const contador = document.getElementById("totalStockCriticoDashboard");
    if (contador) contador.textContent = alertas.length;

    if (alertas.length === 0) {
        const vacio = document.createElement("div");
        vacio.className = "estado-vacio-tabla";

        const icono = document.createElement("i");
        icono.className = "fa-regular fa-bell";

        const titulo = document.createElement("strong");
        titulo.textContent = "No hay alertas de inventario activas";

        const texto = document.createElement("span");
        texto.textContent = "El stock se encuentra por encima de los mínimos configurados.";

        vacio.append(icono, titulo, texto);
        lista.appendChild(vacio);
    } else {
        alertas.slice(0, 6).forEach((datoAlerta) => {
            const tarjeta = document.createElement("div");
            tarjeta.className = "alerta-dashboard";
            const titulo = document.createElement("strong");
            titulo.textContent = `${datoAlerta.producto} · ${datoAlerta.tipo_alerta}`;
            const detalle = document.createElement("span");
            detalle.textContent =
                `${datoAlerta.tienda} / ${datoAlerta.ubicacion}: ${datoAlerta.stock_actual} disponibles (mínimo ${datoAlerta.stock_minimo})`;
            tarjeta.append(titulo, detalle);
            lista.appendChild(tarjeta);
        });
    }
}

async function cargarAlertasDashboardVending() {
    try {
        const alertas = await obtenerAlertasInventarioVending();
        mostrarAlertasDashboardVending(alertas);
        actualizarContadoresNotificacionVending(alertas);
    } catch (error) {
        console.error("No se pudieron cargar las alertas del dashboard:", error);
        const lista = document.getElementById("listaAlertasDashboard");
        if (lista) lista.textContent = "No fue posible cargar las alertas de inventario.";
    }
}

let alertasInventarioMostradas = [];
let promesaAlertasInventarioVending = null;
let secuenciaCargaAlertasVending = 0;

async function obtenerAlertasInventarioVending() {
    if (!promesaAlertasInventarioVending) {
        const parametros = new URLSearchParams({ accion: "listar", estado: "ACTIVAS" });
        promesaAlertasInventarioVending = fetch(urlApiAlertasInventarioVending(parametros), {
            credentials: "same-origin",
            headers: { Accept: "application/json" }
        }).then(async (respuesta) => {
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.success || !Array.isArray(resultado.data)) {
                throw new Error(resultado.message || "No fue posible consultar las alertas.");
            }
            return resultado.data;
        }).catch((error) => {
            promesaAlertasInventarioVending = null;
            throw error;
        });
    }
    return promesaAlertasInventarioVending;
}

function invalidarAlertasInventarioVending() {
    promesaAlertasInventarioVending = null;
}

function urlApiAlertasInventarioVending(parametros = new URLSearchParams()) {
    const scriptAplicacion = Array.from(document.scripts).find((script) => {
        return script.src && new URL(script.src).pathname.endsWith("/js/app.js");
    });
    if (!scriptAplicacion) {
        throw new Error("No se pudo determinar la ruta de la API de alertas.");
    }

    const urlApi = new URL("../api/inventario/alertas.php", scriptAplicacion.src);
    urlApi.search = parametros.toString();
    return urlApi;
}

async function solicitarApiAlertasInventarioVending(accion, datos = {}) {
    const respuesta = await fetch(urlApiAlertasInventarioVending(), {
        method: "POST",
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ ...datos, accion })
    });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.message || "No fue posible guardar la gestión de alerta.");
    }
    return resultado.data;
}

function actualizarContadoresNotificacionVending(alertas) {
    const contadores = document.querySelectorAll(".boton-notificacion .contador");
    if (contadores.length === 0) return;

    if (!alertas) {
        obtenerAlertasInventarioVending()
            .then(actualizarContadoresNotificacionVending)
            .catch((error) => console.error("No se pudieron actualizar las alertas:", error));
        return;
    }
    contadores.forEach((elemento) => {
        elemento.textContent = alertas.length;
        elemento.setAttribute("aria-label", `${alertas.length} alertas de inventario`);
    });
}


/*=========================================================
  MÓDULOS FINALES: USUARIOS, ALERTAS, REPORTES, COMPRAS,
  PEDIDOS Y FACTURAS
=========================================================*/

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

    cargarTiendasUsuarioVending();
    renderUsuariosVending();
}

async function cargarTiendasUsuarioVending() {

    const select =
        document.getElementById("tiendaUsuario");

    if (!select) {
        return;
    }

    try {

        const respuesta = await fetch(
            "../api/usuarios/listar_tiendas.php",
            {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {

            console.error(
                resultado.mensaje ||
                "No fue posible cargar las tiendas."
            );

            return;
        }

        select.innerHTML = `
            <option value="">
                Seleccione una tienda
            </option>
        `;

        (resultado.tiendas || []).forEach(tienda => {

            const opcion =
                document.createElement("option");

            opcion.value = tienda.id_tienda;

            opcion.textContent = tienda.nombre;

            select.appendChild(opcion);
        });

    } catch (error) {

        console.error(
            "Error al cargar tiendas:",
            error
        );
    }
}

async function prepararFormularioUsuarioVending(id = "") {

    const form = document.getElementById("formularioUsuario");

    form?.reset();

    document.getElementById("idUsuario").value = id;

    textoVending(
        "tituloModalUsuario",
        id ? "Editar usuario" : "Nuevo usuario"
    );

    // Si es un usuario nuevo
    if (!id) {

        document.getElementById("contrasenaUsuario").required = true;
        document.getElementById("confirmarContrasenaUsuario").required = true;

        abrirModalVending("modalUsuario");

        return;
    }

    try {

        const respuesta = await fetch(
            `../api/usuarios/obtener.php?id=${encodeURIComponent(id)}`,
            {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {

            mostrarMensajeModuloVending(
                "mensajeUsuarios",
                resultado.mensaje ||
                "No fue posible consultar el usuario.",
                "error"
            );

            return;
        }

        const usuario = resultado.usuario;

        document.getElementById("nombreUsuario").value =
            usuario.nombre || "";

        document.getElementById("apellidoUsuario").value =
            usuario.apellido || "";

        document.getElementById("correoUsuario").value =
            usuario.correo || "";

        document.getElementById("rolUsuario").value =
            usuario.id_rol || "";

        document.getElementById("tiendaUsuario").value =
    usuario.id_tienda || "";

        document.getElementById("estadoUsuario").value =
            usuario.estado === true ||
            usuario.estado === "true"
                ? "Activo"
                : "Inactivo";

        // Al editar, la contraseña no es obligatoria.
        document.getElementById("contrasenaUsuario").required = false;

        document.getElementById("confirmarContrasenaUsuario").required = false;

        abrirModalVending("modalUsuario");

    } catch (error) {

        console.error(
            "Error al consultar usuario:",
            error
        );

        mostrarMensajeModuloVending(
            "mensajeUsuarios",
            "No fue posible comunicarse con el servidor.",
            "error"
        );
    }
}

async function guardarUsuarioVending(evento) {

    evento.preventDefault();

    const id =
        document.getElementById("idUsuario")?.value || "";

    const nombre =
        document.getElementById("nombreUsuario")?.value.trim() || "";

    const apellido =
        document.getElementById("apellidoUsuario")?.value.trim() || "";

    const correo =
        document.getElementById("correoUsuario")?.value.trim() || "";

    const idRol =
        document.getElementById("rolUsuario")?.value || "";

    const estado =
        document.getElementById("estadoUsuario")?.value === "Activo";

    const contrasena =
        document.getElementById("contrasenaUsuario")?.value || "";

    const confirmarContrasena =
        document.getElementById("confirmarContrasenaUsuario")?.value || "";


    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    if (!nombre || !apellido || !correo || !idRol) {

        textoVending(
            "mensajeFormularioUsuario",
            "Debe completar todos los campos obligatorios."
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Contraseña
    |--------------------------------------------------------------------------
    */

    // Al crear un usuario, la contraseña es obligatoria.
    if (!id && !contrasena) {

        textoVending(
            "mensajeFormularioUsuario",
            "Debe ingresar una contraseña."
        );

        return;
    }


    // Si se escribe una contraseña, debe tener mínimo 8 caracteres.
    if (contrasena && contrasena.length < 8) {

        textoVending(
            "mensajeFormularioUsuario",
            "La contraseña debe tener al menos 8 caracteres."
        );

        return;
    }


    // Si se escribe una contraseña, ambas deben coincidir.
    if (contrasena !== confirmarContrasena) {

        textoVending(
            "mensajeFormularioUsuario",
            "Las contraseñas no coinciden."
        );

        return;
    }


    try {

        const botonGuardar =
            document.querySelector(
                "#formularioUsuario button[type='submit']"
            );

        if (botonGuardar) {
            botonGuardar.disabled = true;
        }


        /*
        |--------------------------------------------------------------------------
        | Enviar datos al servidor
        |--------------------------------------------------------------------------
        */

        const respuesta = await fetch(
            "../api/usuarios/guardar.php",
            {
                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({

                    id_usuario: id || null,

                    nombre: nombre,

                    apellido: apellido,

                    correo: correo,

                    id_rol: idRol,

                    id_tienda:
                        document.getElementById("tiendaUsuario")?.value || null,

                    estado: estado,

                    contrasena: contrasena

                })
            }
        );


        const resultado =
            await respuesta.json();


        /*
        |--------------------------------------------------------------------------
        | Error del servidor
        |--------------------------------------------------------------------------
        */

        if (!respuesta.ok || !resultado.exito) {

            textoVending(
                "mensajeFormularioUsuario",
                resultado.mensaje ||
                "No fue posible guardar el usuario."
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Guardado exitoso
        |--------------------------------------------------------------------------
        */

        cerrarModalVending("modalUsuario");

        mostrarMensajeModuloVending(
            "mensajeUsuarios",
            resultado.mensaje ||
            "Usuario guardado correctamente."
        );


        document
            .getElementById("formularioUsuario")
            ?.reset();


        await renderUsuariosVending();


    } catch (error) {

        console.error(
            "Error al guardar usuario:",
            error
        );

        textoVending(
            "mensajeFormularioUsuario",
            "No fue posible comunicarse con el servidor."
        );


    } finally {

        const botonGuardar =
            document.querySelector(
                "#formularioUsuario button[type='submit']"
            );

        if (botonGuardar) {
            botonGuardar.disabled = false;
        }
    }
}

async function cambiarEstadoUsuarioVending(id) {

    if (!id) {
        return;
    }

    const confirmar = confirm(
        "¿Desea cambiar el estado de este usuario?"
    );

    if (!confirmar) {
        return;
    }

    try {

        const respuesta = await fetch(
            "../api/usuarios/estado.php",
            {
                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({
                    id_usuario: id
                })
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {

            mostrarMensajeModuloVending(
                "mensajeUsuarios",
                resultado.mensaje ||
                "No fue posible cambiar el estado del usuario.",
                "error"
            );

            return;
        }

        mostrarMensajeModuloVending(
            "mensajeUsuarios",
            resultado.mensaje ||
            "Estado del usuario actualizado correctamente."
        );

        await renderUsuariosVending();

    } catch (error) {

        console.error(
            "Error al cambiar estado del usuario:",
            error
        );

        mostrarMensajeModuloVending(
            "mensajeUsuarios",
            "No fue posible comunicarse con el servidor.",
            "error"
        );
    }
}

async function renderUsuariosVending() {

    const cuerpo = document.getElementById("cuerpoTablaUsuarios");

    if (!cuerpo) {
        return;
    }

    try {

        const respuesta = await fetch(
            "../api/usuarios/listar.php",
            {
                method: "GET",
                headers: {
                    "Accept": "application/json"
                }
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {

            mostrarMensajeModuloVending(
                "mensajeUsuarios",
                resultado.mensaje ||
                "No fue posible cargar los usuarios.",
                "error"
            );

            return;
        }

        const usuarios = resultado.usuarios || [];

        /*
        |--------------------------------------------------------------------------
        | Filtros
        |--------------------------------------------------------------------------
        */

        const q = normalizarTexto(
            document.getElementById("buscarUsuario")?.value || ""
        );

        const rol =
            document.getElementById("filtroRolUsuario")?.value || "";

        const estado =
            document.getElementById("filtroEstadoUsuario")?.value || "";


        const lista = usuarios.filter(usuario => {

            const nombreCompleto =
                `${usuario.nombre || ""} ${usuario.apellido || ""}`;

            const coincideBusqueda =
                !q ||
                normalizarTexto(nombreCompleto).includes(q) ||
                normalizarTexto(usuario.correo || "").includes(q);

            const coincideRol =
                !rol ||
                usuario.rol === rol;

            const estadoTexto =
                usuario.estado === true ||
                usuario.estado === "true"
                    ? "Activo"
                    : "Inactivo";

            const coincideEstado =
                !estado ||
                estadoTexto === estado;

            return (
                coincideBusqueda &&
                coincideRol &&
                coincideEstado
            );
        });


        /*
        |--------------------------------------------------------------------------
        | Estadísticas
        |--------------------------------------------------------------------------
        */

        const usuariosActivos =
            usuarios.filter(usuario =>
                usuario.estado === true ||
                usuario.estado === "true"
            ).length;

        const administradores =
            usuarios.filter(usuario =>
                usuario.rol === "Administrador"
            ).length;

        const trabajadores =
            usuarios.filter(usuario =>
                usuario.rol === "Encargado" ||
                usuario.rol === "Dependiente"
            ).length;


        textoVending(
            "totalUsuarios",
            usuarios.length
        );

        textoVending(
            "usuariosActivos",
            usuariosActivos
        );

        textoVending(
            "totalAdministradores",
            administradores
        );

        textoVending(
            "totalTrabajadores",
            trabajadores
        );


        /*
        |--------------------------------------------------------------------------
        | Tabla
        |--------------------------------------------------------------------------
        */

        if (lista.length === 0) {

            cuerpo.innerHTML = "";

            document.getElementById(
                "contenedorTablaUsuarios"
            ).hidden = true;

            document.getElementById(
                "mensajeSinResultadosUsuarios"
            ).hidden = false;

            return;
        }


        document.getElementById(
            "contenedorTablaUsuarios"
        ).hidden = false;

        document.getElementById(
            "mensajeSinResultadosUsuarios"
        ).hidden = true;


        cuerpo.innerHTML = lista.map(usuario => {

            const nombreCompleto =
                `${usuario.nombre || ""} ${usuario.apellido || ""}`.trim();

            const estadoActivo =
                usuario.estado === true ||
                usuario.estado === "true";

            const estadoTexto =
                estadoActivo
                    ? "Activo"
                    : "Inactivo";

            return `
                <tr>

                    <td>
                        <div class="usuario-tabla">

                            <div class="usuario-tabla-icono">
                                <i class="fa-solid fa-user"></i>
                            </div>

                            <div class="usuario-tabla-datos">

                                <strong>
                                    ${escaparHTMLVending(nombreCompleto)}
                                </strong>

                                <span>
                                    ${escaparHTMLVending(usuario.correo)}
                                </span>

                            </div>

                        </div>
                    </td>

                    <td>
                        ${escaparHTMLVending(usuario.correo)}
                    </td>

                    <td>
                        ${escaparHTMLVending(usuario.rol)}
                    </td>

                    <td>
                        —
                    </td>

                    <td>
                        <span class="estado-tabla ${estadoActivo ? "activo" : "inactivo"}">
                            ${estadoTexto}
                        </span>
                    </td>

                    <td>
                        —
                    </td>

                    <td>

                        <div class="acciones-tabla">

                            <button
                                type="button"
                                class="boton-tabla"
                                data-usuario="${usuario.id_usuario}"
                                data-accion="editar"
                                title="Editar usuario"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </button>

                            <button
                                type="button"
                                class="boton-tabla"
                                data-usuario="${usuario.id_usuario}"
                                data-accion="estado"
                                title="Cambiar estado"
                            >
                                <i class="fa-solid fa-user-lock"></i>
                            </button>

                        </div>

                    </td>

                </tr>
            `;

        }).join("");


        textoVending(
            "resultadoBusquedaUsuarios",
            `Mostrando ${lista.length} de ${usuarios.length} usuarios.`
        );

    } catch (error) {

        console.error(
            "Error al cargar usuarios:",
            error
        );

        mostrarMensajeModuloVending(
            "mensajeUsuarios",
            "No fue posible comunicarse con el servidor.",
            "error"
        );
    }
}

/* ALERTAS */
function prioridadAlertaInventarioVending(tipo) {
    if (tipo === "negativo") return "Crítica";
    if (tipo === "cero") return "Alta";
    return "Media";
}

function inicializarPaginaAlertasVending() {
    const lista = document.getElementById("listaAlertas");
    if (!lista) return;

    ["filtroTipoAlerta", "filtroEstadoAlerta", "filtroTiendaAlerta"]
        .forEach((id) => document.getElementById(id)?.addEventListener("change", refrescarAlertasPaginaVending));
    let temporizadorBusqueda;
    document.getElementById("buscarAlerta")?.addEventListener("input", () => {
        window.clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = window.setTimeout(refrescarAlertasPaginaVending, 250);
    });

    document.getElementById("botonActualizarAlertas")?.addEventListener("click", async () => {
        const boton = document.getElementById("botonActualizarAlertas");
        boton.disabled = true;
        try {
            invalidarAlertasInventarioVending();
            await cargarAlertasPaginaVending();
            mostrarMensajeModuloVending("mensajeAlertas", "Alertas actualizadas.");
        } catch (error) {
            console.error("No se pudieron actualizar las alertas:", error);
            mostrarMensajeModuloVending("mensajeAlertas", error.message, "error");
        } finally {
            boton.disabled = false;
        }
    });

    document.getElementById("botonLimpiarFiltrosAlertas")?.addEventListener("click", () => {
        ["buscarAlerta", "filtroTipoAlerta", "filtroEstadoAlerta", "filtroTiendaAlerta"]
            .forEach((id) => {
                const campo = document.getElementById(id);
                if (campo) campo.value = "";
            });
        refrescarAlertasPaginaVending();
    });

    document.getElementById("formularioGestionAlerta")?.addEventListener("submit", gestionarAlertaVending);
    document.getElementById("botonCancelarGestionAlerta")?.addEventListener("click", () => cerrarModalVending("modalGestionAlerta"));
    document.querySelector("[data-cerrar-modal-alerta]")?.addEventListener("click", () => cerrarModalVending("modalGestionAlerta"));
    lista.addEventListener("click", manejarAccionListaAlertasVending);
    lista.addEventListener("toggle", cargarHistorialAlertaVending, true);

    cargarTiendasAlertaVending().catch((error) => {
        console.error("No se pudieron cargar las tiendas de alertas:", error);
        mostrarMensajeModuloVending("mensajeAlertas", error.message, "error");
    });
    refrescarAlertasPaginaVending();
}

async function cargarTiendasAlertaVending() {
    const parametros = new URLSearchParams({ accion: "tiendas" });
    const respuesta = await fetch(urlApiAlertasInventarioVending(parametros), {
        credentials: "same-origin",
        headers: { Accept: "application/json" }
    });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.success || !Array.isArray(resultado.data)) {
        throw new Error(resultado.message || "No fue posible consultar las tiendas.");
    }

    const selector = document.getElementById("filtroTiendaAlerta");
    selector.replaceChildren(new Option("Todas las tiendas", ""));
    resultado.data.forEach((tienda) => {
        selector.add(new Option(tienda.nombre, tienda.id_tienda));
    });
}

function refrescarAlertasPaginaVending() {
    cargarAlertasPaginaVending().catch((error) => {
        console.error("No se pudieron cargar las alertas:", error);
        mostrarMensajeModuloVending("mensajeAlertas", error.message, "error");
    });
}

async function cargarAlertasPaginaVending() {
    const secuenciaCarga = ++secuenciaCargaAlertasVending;
    const filtros = {
        producto: document.getElementById("buscarAlerta")?.value.trim() || "",
        tipo: document.getElementById("filtroTipoAlerta")?.value || "",
        estado: document.getElementById("filtroEstadoAlerta")?.value || "ACTIVAS",
        tienda: document.getElementById("filtroTiendaAlerta")?.value || ""
    };
    if (!filtros.producto && !filtros.tipo && !filtros.tienda && filtros.estado === "ACTIVAS") {
        const alertas = await obtenerAlertasInventarioVending();
        if (secuenciaCarga !== secuenciaCargaAlertasVending) return alertas;
        renderAlertasVending(alertas);
        actualizarContadoresNotificacionVending(alertas);
        return alertas;
    }

    const parametros = new URLSearchParams({ accion: "listar" });
    Object.entries(filtros).forEach(([clave, valor]) => {
        if (valor) parametros.set(clave, valor);
    });

    const respuesta = await fetch(urlApiAlertasInventarioVending(parametros), {
        credentials: "same-origin",
        headers: { Accept: "application/json" }
    });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.success || !Array.isArray(resultado.data)) {
        throw new Error(resultado.message || "No fue posible consultar las alertas.");
    }

    if (secuenciaCarga !== secuenciaCargaAlertasVending) return resultado.data;
    renderAlertasVending(resultado.data);
    actualizarContadoresNotificacionVending(resultado.data);
    return resultado.data;
}

function renderAlertasVending(alertas) {
    const contenedor = document.getElementById("listaAlertas");
    if (!contenedor) return;

    alertasInventarioMostradas = alertas;
    const todos = alertasInventarioMostradas;
    textoVending("totalAlertas", todos.length);
    textoVending("alertasCriticas", todos.filter((alerta) => ["negativo", "cero"].includes(alerta.tipo_alerta)).length);
    textoVending("alertasPendientes", todos.filter((alerta) => alerta.tipo_alerta === "bajo").length);

    contenedor.replaceChildren(...todos.map((alerta) => {
        const prioridadAlerta = prioridadAlertaInventarioVending(alerta.tipo_alerta);
        const tarjeta = document.createElement("article");
        tarjeta.className = `tarjeta-alerta alerta-${normalizarTexto(prioridadAlerta)}`;
        tarjeta.dataset.alerta = alerta.id_alerta;

        const icono = document.createElement("div");
        icono.className = "alerta-icono";
        const imagen = document.createElement("i");
        imagen.className = "fa-solid fa-triangle-exclamation";
        icono.appendChild(imagen);

        const contenido = document.createElement("div");
        contenido.className = "alerta-contenido";
        const encabezado = document.createElement("h3");
        encabezado.textContent = alerta.producto;
        const detalle = document.createElement("p");
        detalle.textContent = `${alerta.tienda} / ${alerta.ubicacion} · Stock: ${alerta.stock_actual ?? "No disponible"} · Mínimo: ${alerta.stock_minimo ?? alerta.stock_minimo_generacion}`;
        const metadatos = document.createElement("div");
        metadatos.className = "alerta-metadatos";
        [
            `Stock ${alerta.tipo_alerta}`,
            alerta.ultimo_tipo_movimiento
                ? `Último movimiento: ${alerta.ultimo_tipo_movimiento} (${alerta.ultimo_estado_movimiento || "sin estado"})`
                : "Sin movimientos registrados",
            `Fecha del último movimiento: ${alerta.ultimo_movimiento ? formatearFechaHoraVenta(alerta.ultimo_movimiento) : "Fecha no disponible"}`,
            `Generada: ${formatearFechaHoraVenta(alerta.fecha_generacion)}`,
            `Estado: ${alerta.estado}`,
            `Prioridad: ${prioridadAlerta}`
        ].forEach((dato) => {
            const elemento = document.createElement("span");
            elemento.textContent = dato;
            metadatos.appendChild(elemento);
        });
        contenido.append(encabezado, detalle, metadatos);

        if (alerta.responsable || alerta.ultimas_observaciones || alerta.ultima_justificacion) {
            const gestion = document.createElement("p");
            gestion.className = "alerta-ultima-gestion";
            gestion.textContent = [
                alerta.ultima_accion ? `Última acción: ${alerta.ultima_accion}` : "",
                alerta.responsable ? `Responsable: ${alerta.responsable}` : "",
                alerta.fecha_ultima_gestion ? `Fecha: ${formatearFechaHoraVenta(alerta.fecha_ultima_gestion)}` : "",
                alerta.ultimas_observaciones ? `Observaciones: ${alerta.ultimas_observaciones}` : "",
                alerta.ultima_justificacion ? `Justificación: ${alerta.ultima_justificacion}` : "",
                alerta.ultima_evidencia ? `Evidencia: ${alerta.ultima_evidencia}` : ""
            ].filter(Boolean).join(" · ");
            contenido.appendChild(gestion);
        }

        const acciones = document.createElement("div");
        acciones.className = "alerta-acciones";
        if (["PENDIENTE", "ATENDIDA"].includes(alerta.estado)) {
            [
                ["ATENDIDA", "Atender", "fa-check"],
                ["CERRADA", "Cerrar", "fa-lock"],
                ["DESCARTADA", "Descartar", "fa-ban"]
            ].forEach(([estado, etiqueta, claseIcono]) => {
                const boton = document.createElement("button");
                boton.type = "button";
                boton.className = "boton boton-borde boton-tabla";
                boton.dataset.gestionAlerta = estado;
                boton.dataset.idAlerta = alerta.id_alerta;
                const iconoAccion = document.createElement("i");
                iconoAccion.className = `fa-solid ${claseIcono}`;
                boton.append(iconoAccion, document.createTextNode(` ${etiqueta}`));
                acciones.appendChild(boton);
            });
        }
        [
            ["../compras/pedido.php", "Reabastecer", "fa-cart-plus"],
            ["../inventario/inventario.php", "Inventario", "fa-clipboard-check"]
        ].forEach(([url, etiqueta, claseIcono]) => {
            const enlace = document.createElement("a");
            enlace.className = "boton boton-borde boton-tabla";
            enlace.href = url;
            const iconoAccion = document.createElement("i");
            iconoAccion.className = `fa-solid ${claseIcono}`;
            enlace.append(iconoAccion, document.createTextNode(` ${etiqueta}`));
            acciones.appendChild(enlace);
        });

        const historial = document.createElement("details");
        historial.className = "alerta-historial";
        const resumenHistorial = document.createElement("summary");
        resumenHistorial.textContent = "Ver historial de gestión";
        const cuerpoHistorial = document.createElement("div");
        cuerpoHistorial.dataset.historialAlerta = alerta.id_alerta;
        cuerpoHistorial.textContent = "Abre para consultar el historial.";
        historial.append(resumenHistorial, cuerpoHistorial);

        tarjeta.append(icono, contenido, acciones, historial);
        return tarjeta;
    }));

    const estado = document.getElementById("filtroEstadoAlerta")?.value || "ACTIVAS";
    const hayFiltros = Boolean(
        document.getElementById("buscarAlerta")?.value.trim()
        || document.getElementById("filtroTipoAlerta")?.value
        || document.getElementById("filtroTiendaAlerta")?.value
        || (estado && estado !== "ACTIVAS")
    );
    mostrarElementoVending("mensajeSinAlertas", todos.length === 0 && !hayFiltros);
    mostrarElementoVending("mensajeSinResultadosAlertas", todos.length === 0 && hayFiltros);
    textoVending("resultadoBusquedaAlertas", `Mostrando ${todos.length} alerta(s)`);
}

function actualizarCamposGestionAlertaVending() {
    const accion = document.getElementById("accionGestionAlerta")?.value || "";
    const requiereAtencion = accion === "ATENDIDA";
    const evidencia = document.getElementById("evidenciaGestionAlerta");
    const observaciones = document.getElementById("observacionesGestionAlerta");
    const justificacion = document.getElementById("justificacionGestionAlerta");

    document.getElementById("grupoEvidenciaGestionAlerta").hidden = !requiereAtencion;
    document.getElementById("grupoObservacionesGestionAlerta").hidden = !requiereAtencion;
    document.getElementById("grupoJustificacionGestionAlerta").hidden = requiereAtencion;
    evidencia.required = requiereAtencion;
    observaciones.required = requiereAtencion;
    justificacion.required = !requiereAtencion;
}

function abrirGestionAlertaVending(idAlerta, accion) {
    const formulario = document.getElementById("formularioGestionAlerta");
    formulario.reset();
    const mensaje = document.getElementById("mensajeGestionAlerta");
    mensaje.hidden = true;
    mensaje.textContent = "";
    document.getElementById("idAlertaGestion").value = idAlerta;
    document.getElementById("accionGestionAlerta").value = accion;
    document.getElementById("tituloGestionAlerta").textContent =
        accion === "ATENDIDA" ? "Atender alerta" : accion === "CERRADA" ? "Cerrar alerta" : "Descartar alerta";
    actualizarCamposGestionAlertaVending();
    abrirModalVending("modalGestionAlerta");
}

function manejarAccionListaAlertasVending(evento) {
    const boton = evento.target.closest("button[data-gestion-alerta]");
    if (boton) {
        abrirGestionAlertaVending(boton.dataset.idAlerta, boton.dataset.gestionAlerta);
    }
}

async function gestionarAlertaVending(evento) {
    evento.preventDefault();
    const formulario = evento.currentTarget;
    const boton = formulario.querySelector('[type="submit"]');
    boton.disabled = true;
    try {
        await solicitarApiAlertasInventarioVending("gestionar", {
            id_alerta: Number(document.getElementById("idAlertaGestion").value),
            estado: document.getElementById("accionGestionAlerta").value,
            evidencia: document.getElementById("evidenciaGestionAlerta").value.trim(),
            observaciones: document.getElementById("observacionesGestionAlerta").value.trim(),
            justificacion: document.getElementById("justificacionGestionAlerta").value.trim()
        });
        invalidarAlertasInventarioVending();
    } catch (error) {
        console.error("No se pudo guardar la gestión de la alerta:", error);
        mostrarMensajeModuloVending("mensajeGestionAlerta", error.message, "error");
        boton.disabled = false;
        return;
    } finally {
        boton.disabled = false;
    }
    cerrarModalVending("modalGestionAlerta");
    mostrarMensajeModuloVending("mensajeAlertas", "Gestión de alerta registrada.");
    refrescarAlertasPaginaVending();
}

async function cargarHistorialAlertaVending(evento) {
    const detalles = evento.target;
    if (!(detalles instanceof HTMLDetailsElement) || !detalles.open) return;
    const contenedor = detalles.querySelector("[data-historial-alerta]");
    if (!contenedor || contenedor.dataset.cargado === "true") return;

    const parametros = new URLSearchParams({
        accion: "historial",
        id_alerta: contenedor.dataset.historialAlerta
    });
    try {
        const respuesta = await fetch(urlApiAlertasInventarioVending(parametros), {
            credentials: "same-origin",
            headers: { Accept: "application/json" }
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.success || !Array.isArray(resultado.data)) {
            throw new Error(resultado.message || "No fue posible cargar el historial.");
        }
        contenedor.replaceChildren(...resultado.data.map((eventoGestion) => {
            const registro = document.createElement("p");
            registro.textContent = [
                `${formatearFechaHoraVenta(eventoGestion.fecha)} · ${eventoGestion.accion}`,
                eventoGestion.responsable ? `Responsable: ${eventoGestion.responsable}` : "Sistema",
                eventoGestion.evidencia ? `Evidencia: ${eventoGestion.evidencia}` : "",
                eventoGestion.observaciones ? `Observaciones: ${eventoGestion.observaciones}` : "",
                eventoGestion.justificacion ? `Justificación: ${eventoGestion.justificacion}` : ""
            ].filter(Boolean).join(" · ");
            return registro;
        }));
        contenedor.dataset.cargado = "true";
    } catch (error) {
        console.error("No se pudo cargar el historial de la alerta:", error);
        contenedor.textContent = error.message;
    }
}

/* REPORTES */
function inicializarPaginaReportesVending(){
    const form=document.getElementById("formularioReporte"); if(!form)return;
    document.getElementById("fechaInicioReporte").value=fechaHoyVending(); document.getElementById("fechaFinReporte").value=fechaHoyVending();
    form.addEventListener("submit",e=>{e.preventDefault();generarReporteVending();});
    document.getElementById("botonLimpiarReporte")?.addEventListener("click",()=>{form.reset();document.getElementById("fechaInicioReporte").value=fechaHoyVending();document.getElementById("fechaFinReporte").value=fechaHoyVending();});
    document.getElementById("botonImprimirReporte")?.addEventListener("click",()=>window.print());
    document.getElementById("botonExportarReporte")?.addEventListener("click",exportarReporteVending);
    inicializarConciliacionVending();
}

function urlApiResumenInventarioVending() {
    const scriptAplicacion = Array.from(document.scripts).find((script) => {
        return script.src && new URL(script.src).pathname.endsWith("/js/app.js");
    });
    if (!scriptAplicacion) {
        throw new Error("No se pudo determinar la ruta de la API del resumen.");
    }
    return new URL("../api/inventario/resumen.php", scriptAplicacion.src);
}

function mostrarMensajeResumenInventarioVending(mensaje, error = false) {
    const contenedor = document.getElementById("mensajeResumenInventario");
    if (!contenedor) return;
    contenedor.hidden = !mensaje;
    contenedor.textContent = mensaje;
    contenedor.classList.toggle("mensaje-error", error);
    contenedor.classList.toggle("mensaje-exito", !error && Boolean(mensaje));
}

function mostrarHistorialResumenInventarioVending(registros) {
    const cuerpo = document.getElementById("historialResumenInventario");
    if (!cuerpo) return;
    if (!registros.length) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 6;
        celda.textContent = "Todavía no hay ejecuciones registradas.";
        fila.appendChild(celda);
        cuerpo.replaceChildren(fila);
        return;
    }

    cuerpo.replaceChildren(...registros.map((registro) => {
        const fila = document.createElement("tr");
        const valores = [
            registro.fecha_ejecucion ? formatearFechaHoraVenta(registro.fecha_ejecucion) : "—",
            `${registro.periodo_inicio} al ${registro.periodo_fin}`,
            registro.responsable || "No disponible",
            registro.correo_destino,
            registro.estado_envio,
            registro.error || "—"
        ];
        valores.forEach((valor) => {
            const celda = document.createElement("td");
            celda.textContent = valor;
            fila.appendChild(celda);
        });
        return fila;
    }));
}

async function cargarResumenInventarioVending() {
    const respuesta = await fetch(`${urlApiResumenInventarioVending()}?accion=configuracion`, {
        credentials: "same-origin",
        headers: { Accept: "application/json" }
    });
    const resultado = await respuesta.json();
    if (!respuesta.ok || !resultado.success || !resultado.data?.configuracion) {
        throw new Error(resultado.message || "No fue posible cargar la configuración del resumen.");
    }

    const config = resultado.data.configuracion;
    document.getElementById("correoResumenInventario").value = config.correo_destino || "";
    document.getElementById("frecuenciaResumenInventario").value = config.frecuencia || "DIARIA";
    document.getElementById("diaResumenInventario").value = String(config.dia_semana || 1);
    document.getElementById("horaResumenInventario").value = config.hora || "07:00";
    document.getElementById("habilitadoResumenInventario").checked =
        config.habilitado === true || config.habilitado === "t" || config.habilitado === "1";
    actualizarDiaResumenInventarioVending();
    document.getElementById("proximaEjecucionResumenInventario").textContent =
        config.proxima_ejecucion
            ? formatearFechaHoraVenta(config.proxima_ejecucion)
            : "Sin programar";
    mostrarHistorialResumenInventarioVending(resultado.data.historial || []);
}

function actualizarDiaResumenInventarioVending() {
    const grupo = document.getElementById("grupoDiaResumenInventario");
    const semanal = document.getElementById("frecuenciaResumenInventario")?.value === "SEMANAL";
    if (grupo) grupo.hidden = !semanal;
}

function inicializarResumenInventarioVending() {
    const formulario = document.getElementById("formularioResumenInventario");
    if (!formulario) return;

    document.getElementById("frecuenciaResumenInventario")
        ?.addEventListener("change", actualizarDiaResumenInventarioVending);
    formulario.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        const boton = formulario.querySelector('button[type="submit"]');
        boton.disabled = true;
        mostrarMensajeResumenInventarioVending("");
        const datos = {
            habilitado: document.getElementById("habilitadoResumenInventario").checked,
            frecuencia: document.getElementById("frecuenciaResumenInventario").value,
            dia_semana: Number(document.getElementById("diaResumenInventario").value),
            hora: document.getElementById("horaResumenInventario").value,
            correo_destino: document.getElementById("correoResumenInventario").value.trim()
        };
        try {
            const respuesta = await fetch(urlApiResumenInventarioVending(), {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ accion: "guardar", ...datos })
            });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.message || "No fue posible guardar la configuración.");
            }
            mostrarMensajeResumenInventarioVending(resultado.message || "Configuración guardada.");
            await cargarResumenInventarioVending();
        } catch (error) {
            mostrarMensajeResumenInventarioVending(error.message, true);
        } finally {
            boton.disabled = false;
        }
    });

    cargarResumenInventarioVending().catch((error) => {
        console.error("No se pudo cargar el resumen periódico:", error);
        mostrarMensajeResumenInventarioVending(error.message, true);
        mostrarHistorialResumenInventarioVending([]);
    });
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

let resultadosConciliacionVending = [];
let idConciliacionVending = null;

function solicitarApiConciliacionVending(accion, opciones = {}) {
    return fetch(`../api/ventas/conciliacion.php?accion=${encodeURIComponent(accion)}`, {
        credentials: "same-origin",
        cache: "no-store",
        ...opciones
    }).then(async respuesta => {
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.success) {
            throw new Error(resultado.message || "No fue posible completar la conciliación.");
        }
        return resultado.data;
    });
}

function inicializarConciliacionVending() {
    const formulario = document.getElementById("formularioConciliacion");
    if (!formulario) return;

    const hoy = fechaHoyVending();
    document.getElementById("fechaInicioConciliacion").value = hoy;
    document.getElementById("fechaFinConciliacion").value = hoy;

    solicitarApiConciliacionVending("tiendas")
        .then(tiendas => {
            const selector = document.getElementById("tiendaConciliacion");
            tiendas.forEach(tienda => {
                const opcion = document.createElement("option");
                opcion.value = String(tienda.id_tienda);
                opcion.textContent = tienda.nombre;
                selector.appendChild(opcion);
            });
        })
        .catch(error => mostrarMensajeConciliacionVending(error.message, "error"));

    formulario.addEventListener("submit", generarConciliacionVending);
    document.getElementById("botonExportarConciliacion")?.addEventListener(
        "click",
        exportarConciliacionVending
    );
    document.getElementById("cuerpoConciliacion").addEventListener(
        "click",
        manejarAccionConciliacionVending
    );
}

async function generarConciliacionVending(evento) {
    evento.preventDefault();
    const formulario = document.getElementById("formularioConciliacion");
    const boton = formulario.querySelector('[type="submit"]');
    const fechaInicio = document.getElementById("fechaInicioConciliacion").value;
    const fechaFin = document.getElementById("fechaFinConciliacion").value;

    if (fechaInicio > fechaFin) {
        mostrarMensajeConciliacionVending("La fecha inicial no puede ser posterior a la fecha final.", "error");
        return;
    }

    boton.disabled = true;
    try {
        const resultado = await solicitarApiConciliacionVending("generar", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin,
                id_tienda: Number(document.getElementById("tiendaConciliacion").value || 0)
            })
        });
        idConciliacionVending = resultado.id_conciliacion;
        resultadosConciliacionVending = resultado.resultados;
        renderizarConciliacionVending();
        mostrarMensajeConciliacionVending(
            `Conciliación ${resultado.id_conciliacion} guardada. Fuente: ${resultado.fuente}.`,
            "exito"
        );
    } catch (error) {
        mostrarMensajeConciliacionVending(error.message, "error");
    } finally {
        boton.disabled = false;
    }
}

function renderizarConciliacionVending() {
    const cuerpo = document.getElementById("cuerpoConciliacion");
    const contenedor = document.getElementById("contenedorConciliacion");
    const vacio = document.getElementById("mensajeSinDatosConciliacion");
    const resumen = document.getElementById("resumenConciliacion");
    if (!cuerpo || !contenedor || !vacio || !resumen) return;

    cuerpo.innerHTML = resultadosConciliacionVending.map(fila => {
        const diferenciaStock = fila.diferencia_existencia === null
            ? "Sin saldo registrado"
            : String(fila.diferencia_existencia);
        const utilidad = fila.utilidad_estimada === null ||
            Number(fila.lineas_sin_costo) > 0
            ? "Costo histórico no disponible"
            : monedaVending(fila.utilidad_estimada);
        const discrepancia = Number(fila.diferencia_venta_movimiento) !== 0 ||
            Number(fila.diferencia_existencia || 0) !== 0;

        return `<tr class="${discrepancia ? "fila-conciliacion-diferencia" : ""}">
            <td>${escaparHTMLVending(fila.producto)}</td>
            <td>${escaparHTMLVending(`${fila.tienda} / ${fila.ubicacion}`)}</td>
            <td>${fila.cantidad_vendida}</td>
            <td>${fila.cantidad_movimiento}</td>
            <td>${fila.diferencia_venta_movimiento}</td>
            <td>${fila.existencia_actual}</td>
            <td>${fila.saldo_ultimo_movimiento === null ? "—" : fila.saldo_ultimo_movimiento}</td>
            <td>${escaparHTMLVending(diferenciaStock)}</td>
            <td>${escaparHTMLVending(utilidad)}</td>
            <td>
                <div class="acciones-fila-conciliacion">
                    <input type="text" maxlength="500" value="${escaparHTMLVending(fila.observacion || "")}"
                        aria-label="Observación para ${escaparHTMLVending(fila.producto)}"
                        data-observacion-conciliacion="${fila.id_detalle}">
                    <button type="button" class="boton boton-borde"
                        data-accion-conciliacion="observacion" data-id-detalle="${fila.id_detalle}">
                        Guardar observación
                    </button>
                    <div class="campo-ajuste-conciliacion">
                        <label for="ajusteConciliacion${fila.id_detalle}">Ajuste (+/- unidades)</label>
                        <input type="number" step="1" id="ajusteConciliacion${fila.id_detalle}"
                            data-ajuste-conciliacion="${fila.id_detalle}">
                        <button type="button" class="boton boton-borde"
                            data-accion-conciliacion="ajustar" data-id-detalle="${fila.id_detalle}">
                            Aplicar ajuste
                        </button>
                    </div>
                </div>
            </td>
        </tr>`;
    }).join("");

    const diferencias = resultadosConciliacionVending.filter(fila =>
        Number(fila.diferencia_venta_movimiento) !== 0 ||
        Number(fila.diferencia_existencia || 0) !== 0
    );
    const utilidadDisponible = resultadosConciliacionVending
        .filter(fila => fila.utilidad_estimada !== null && Number(fila.lineas_sin_costo) === 0)
        .reduce((total, fila) => total + Number(fila.utilidad_estimada), 0);

    document.getElementById("periodoConciliacion").textContent =
        `Periodo: ${document.getElementById("fechaInicioConciliacion").value} al ${document.getElementById("fechaFinConciliacion").value}`;
    document.getElementById("cantidadDiferenciasConciliacion").textContent =
        `${diferencias.length} producto(s) con diferencias`;
    document.getElementById("utilidadConciliacion").textContent =
        `Utilidad estimada: ${monedaVending(utilidadDisponible)}`;
    resumen.hidden = resultadosConciliacionVending.length === 0;
    contenedor.hidden = resultadosConciliacionVending.length === 0;
    vacio.hidden = resultadosConciliacionVending.length > 0;
}

function exportarConciliacionVending() {
    if (!resultadosConciliacionVending.length) {
        mostrarMensajeConciliacionVending("Primero genera una conciliación.", "error");
        return;
    }

    const columnas = [
        ["Tienda", "tienda"],
        ["Ubicación", "ubicacion"],
        ["Producto", "producto"],
        ["Cantidad vendida", "cantidad_vendida"],
        ["Cantidad salida", "cantidad_movimiento"],
        ["Diferencia ventas y salida", "diferencia_venta_movimiento"],
        ["Existencia actual", "existencia_actual"],
        ["Saldo último movimiento", "saldo_ultimo_movimiento"],
        ["Diferencia de existencia", "diferencia_existencia"],
        ["Utilidad estimada", "utilidad_estimada"],
        ["Observación", "observacion"]
    ];
    const csv = [
        columnas.map(([titulo]) => titulo),
        ...resultadosConciliacionVending.map(fila =>
            columnas.map(([, clave]) => fila[clave] ?? "")
        )
    ].map(fila => fila.map(valor =>
        `"${String(valor).replace(/"/g, '""')}"`
    ).join(",")).join("\r\n");
    const archivo = new Blob(["\ufeff", csv], { type: "text/csv;charset=utf-8" });
    const enlace = document.createElement("a");
    enlace.href = URL.createObjectURL(archivo);
    enlace.download = `conciliacion-${idConciliacionVending}.csv`;
    enlace.click();
    URL.revokeObjectURL(enlace.href);
}

async function manejarAccionConciliacionVending(evento) {
    const boton = evento.target.closest("button[data-accion-conciliacion]");
    if (!boton) return;

    const idDetalle = Number(boton.dataset.idDetalle);
    const accion = boton.dataset.accionConciliacion;
    const campoObservacion = document.querySelector(`[data-observacion-conciliacion="${idDetalle}"]`);
    const observacion = campoObservacion?.value.trim() || "";

    if (!observacion) {
        mostrarMensajeConciliacionVending("Agregue una observación antes de guardar o ajustar.", "error");
        campoObservacion?.focus();
        return;
    }

    const body = { id_detalle: idDetalle, observacion };
    if (accion === "ajustar") {
        const campoAjuste = document.querySelector(`[data-ajuste-conciliacion="${idDetalle}"]`);
        const cantidad = Number(campoAjuste?.value);
        if (!Number.isInteger(cantidad) || cantidad === 0) {
            mostrarMensajeConciliacionVending("Indique una cantidad de ajuste entera distinta de cero.", "error");
            campoAjuste?.focus();
            return;
        }
        if (!window.confirm(`¿Autoriza el ajuste de ${cantidad > 0 ? "+" : ""}${cantidad} unidades?`)) {
            return;
        }
        body.cantidad_ajuste = cantidad;
    }

    boton.disabled = true;
    try {
        const resultado = await solicitarApiConciliacionVending(accion, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body)
        });
        const fila = resultadosConciliacionVending.find(item => item.id_detalle === idDetalle);
        if (fila) {
            fila.observacion = observacion;
            if (accion === "ajustar") {
                fila.existencia_actual = resultado.cantidad_despues;
                fila.saldo_ultimo_movimiento = resultado.cantidad_despues;
                fila.diferencia_existencia = 0;
            }
        }
        renderizarConciliacionVending();
        mostrarMensajeConciliacionVending(
            accion === "ajustar" ? "Ajuste autorizado y registrado en el inventario." : "Observación guardada.",
            "exito"
        );
    } catch (error) {
        mostrarMensajeConciliacionVending(error.message, "error");
    } finally {
        boton.disabled = false;
    }
}

function mostrarMensajeConciliacionVending(mensaje, tipo) {
    const elemento = document.getElementById("mensajeConciliacion");
    if (!elemento) return;
    elemento.textContent = mensaje;
    elemento.className = `mensaje-producto mensaje-${tipo}`;
    elemento.hidden = false;
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
function opcionesProductoProveedorVending(){const productos=window._productosProveedorVending||[];if(!productos.length)return '<option value="">Sin productos para este proveedor</option>';return '<option value="">Seleccione...</option>'+productos.map(p=>`<option value="${p.nombre}" data-costo="${p.costo||0}">${p.nombre}</option>`).join("");}
function opcionesImpuestoVending(){const impuestos=window._impuestosVending||[];if(!impuestos.length)return '<option value="13">13%</option>';return impuestos.map((imp,i)=>`<option value="${imp.porcentaje}" ${i===0?"selected":""}>${imp.nombre} (${imp.porcentaje}%)</option>`).join("");}
function agregarFilaProductoVending(listaId,mensajeId,preset=null){const lista=document.getElementById(listaId);const fila=document.createElement("div");fila.className="fila-producto-compra";const esCompra=listaId==="listaProductosCompra";const campoProducto=esCompra?`<select class="item-nombre" required>${opcionesProductoProveedorVending()}</select>`:`<input class="item-nombre" required placeholder="Producto">`;const campoIva=esCompra?`<select class="item-iva">${opcionesImpuestoVending()}</select>`:`<input class="item-iva" type="number" min="0" step="0.01" value="13">`;fila.innerHTML=`<div class="grupo-formulario"><label>Producto</label>${campoProducto}</div><div class="grupo-formulario"><label>Cantidad</label><input class="item-cantidad" type="number" min="1" value="1" autocomplete="off" required></div><div class="grupo-formulario"><label>Precio</label><input class="item-precio" type="number" min="0" step="0.01" value="0" autocomplete="off" required></div><div class="grupo-formulario"><label>Impuesto</label>${campoIva}</div><button type="button" class="boton-eliminar-producto-compra"><i class="fa-solid fa-trash"></i></button>`;fila.querySelector("button").addEventListener("click",()=>{fila.remove();calcularTotalesLineasVending(listaId);mostrarElementoVending(mensajeId,!lista.children.length)});if(esCompra){fila.querySelector(".item-nombre").addEventListener("change",(e)=>{const opcion=e.target.selectedOptions[0];fila.querySelector(".item-precio").value=opcion?Number(opcion.dataset.costo||0):0;calcularTotalesLineasVending(listaId);});fila.querySelector(".item-iva").addEventListener("change",()=>calcularTotalesLineasVending(listaId));}lista.appendChild(fila);if(preset){const campoNombre=fila.querySelector(".item-nombre");if(campoNombre.tagName==="SELECT"){if([...campoNombre.options].some(o=>o.value===preset.nombre))campoNombre.value=preset.nombre;}else{campoNombre.value=preset.nombre||"";}fila.querySelector(".item-cantidad").value=preset.cantidad??1;fila.querySelector(".item-precio").value=preset.precio??0;const campoIvaEl=fila.querySelector(".item-iva");if(campoIvaEl.tagName==="SELECT"){if([...campoIvaEl.options].some(o=>Number(o.value)===Number(preset.iva)))campoIvaEl.value=preset.iva;}else{campoIvaEl.value=preset.iva??13;}}else{fila.querySelector(".item-cantidad").value=1;fila.querySelector(".item-precio").value=0;}fila.querySelectorAll("input").forEach(i=>i.addEventListener("input",()=>calcularTotalesLineasVending(listaId)));mostrarElementoVending(mensajeId,false);calcularTotalesLineasVending(listaId);}
function extraerLineasVending(listaId){return [...document.querySelectorAll(`#${listaId} .fila-producto-compra`)].map(f=>({nombre:f.querySelector(".item-nombre").value.trim(),cantidad:Number(f.querySelector(".item-cantidad").value)||0,precio:Number(f.querySelector(".item-precio").value)||0,iva:Number(f.querySelector(".item-iva")?.value)||0}));}
function calcularTotalesLineasVending(listaId){const items=extraerLineasVending(listaId);const sub=items.reduce((s,i)=>s+i.cantidad*i.precio,0);const iva=items.reduce((s,i)=>s+i.cantidad*i.precio*i.iva/100,0);if(listaId.includes("Compra")){textoVending("subtotalCompra",monedaVending(sub));textoVending("impuestoCompra",monedaVending(iva));textoVending("totalCompra",monedaVending(sub+iva));}else{textoVending("cantidadTotalPedido",items.reduce((s,i)=>s+i.cantidad,0));textoVending("subtotalPedido",monedaVending(sub));textoVending("totalPedido",monedaVending(sub+iva));}return{sub,iva,total:sub+iva,items};}
function renderTablaSimpleVending(c){const todos=leerListaVending(c.clave);const q=normalizarTexto(document.getElementById(c.buscar)?.value||"");const lista=todos.filter(x=>(!q||normalizarTexto(JSON.stringify(x)).includes(q))&&Object.entries(c.filtros).every(([k,id])=>{const v=document.getElementById(id)?.value||"";return !v||String(x[k]||"")===v}));document.getElementById(c.cuerpo).innerHTML=lista.map(c.fila).join("");c.resumen.forEach(([id,fn])=>textoVending(id,fn(todos)));mostrarElementoVending(c.contenedor,lista.length>0);mostrarElementoVending(c.vacio,todos.length===0);mostrarElementoVending(c.sinResultados,todos.length>0&&lista.length===0);textoVending(c.resultado,`${lista.length} resultado(s)`);}
