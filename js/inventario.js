/*=========================================================
  HU-20 — CONSULTA DE STOCK DE INVENTARIO
=========================================================*/

/*
 * Este archivo maneja únicamente la pantalla de Inventario.
 *
 * Flujo:
 * inventario.php
 *      ↓
 * inventario.js
 *      ↓
 * api/inventario/stock.php
 *      ↓
 * Supabase
 */

const estadoInventarioHU20 = {
    pagina: 1,
    porPagina: 25,
    totalPaginas: 1,
    temporizadorBusqueda: null
};


/*=========================================================
  INICIALIZACIÓN
=========================================================*/

function inicializarPaginaInventarioVending() {
    const cuerpoTabla = document.getElementById(
        "tablaInventario"
    );

    /*
     * Si no estamos en inventario.php,
     * no ejecutamos nada.
     */
    if (!cuerpoTabla) {
        return;
    }

    /*
     * Evita inicializar dos veces en caso de que app.js
     * también invoque esta función.
     */
    if (cuerpoTabla.dataset.hu20Inicializado === "1") {
        return;
    }

    cuerpoTabla.dataset.hu20Inicializado = "1";

    prepararFiltroEstadoInventarioHU20();
    prepararEtiquetaUbicacionInventarioHU20();
    registrarEventosInventarioHU20();

    cargarInventarioHU20();
}


/*
 * Inicialización independiente.
 *
 * Esto permite que inventario.js funcione aunque después
 * cambiemos la estructura de app.js.
 */
document.addEventListener(
    "DOMContentLoaded",
    inicializarPaginaInventarioVending
);


/*=========================================================
  EVENTOS
=========================================================*/

function registrarEventosInventarioHU20() {
    const buscador = document.getElementById(
        "buscarInventario"
    );

    const filtroUbicacion = document.getElementById(
        "filtroUbicacion"
    );

    const filtroCategoria = document.getElementById(
        "filtroCategoria"
    );

    const filtroEstado = document.getElementById(
        "filtroEstado"
    );

    const botonLimpiar = document.getElementById(
        "botonLimpiarFiltrosInventario"
    );

    const botonAnterior = document.getElementById(
        "botonPaginaAnteriorInventario"
    );

    const botonSiguiente = document.getElementById(
        "botonPaginaSiguienteInventario"
    );


    /*
     * Búsqueda con pequeña espera para evitar
     * consultar la API por cada tecla inmediatamente.
     */
    buscador?.addEventListener("input", () => {
        clearTimeout(
            estadoInventarioHU20.temporizadorBusqueda
        );

        estadoInventarioHU20.temporizadorBusqueda =
            setTimeout(() => {
                estadoInventarioHU20.pagina = 1;
                cargarInventarioHU20();
            }, 300);
    });


    filtroUbicacion?.addEventListener(
        "change",
        () => {
            estadoInventarioHU20.pagina = 1;
            cargarInventarioHU20();
        }
    );


    filtroCategoria?.addEventListener(
        "change",
        () => {
            estadoInventarioHU20.pagina = 1;
            cargarInventarioHU20();
        }
    );


    filtroEstado?.addEventListener(
        "change",
        () => {
            estadoInventarioHU20.pagina = 1;
            cargarInventarioHU20();
        }
    );


    botonLimpiar?.addEventListener(
        "click",
        () => {
            if (buscador) {
                buscador.value = "";
            }

            if (filtroUbicacion) {
                filtroUbicacion.value = "";
            }

            if (filtroCategoria) {
                filtroCategoria.value = "";
            }

            if (filtroEstado) {
                filtroEstado.value = "";
            }

            estadoInventarioHU20.pagina = 1;

            cargarInventarioHU20();
        }
    );


    botonAnterior?.addEventListener(
        "click",
        () => {
            if (estadoInventarioHU20.pagina <= 1) {
                return;
            }

            estadoInventarioHU20.pagina--;

            cargarInventarioHU20();
        }
    );


    botonSiguiente?.addEventListener(
        "click",
        () => {
            if (
                estadoInventarioHU20.pagina >=
                estadoInventarioHU20.totalPaginas
            ) {
                return;
            }

            estadoInventarioHU20.pagina++;

            cargarInventarioHU20();
        }
    );
}


/*=========================================================
  FILTROS
=========================================================*/

function prepararFiltroEstadoInventarioHU20() {
    const selector = document.getElementById(
        "filtroEstado"
    );

    if (!selector) {
        return;
    }

    /*
     * Los estados corresponden exactamente
     * con los definidos por stock.php.
     */
    selector.innerHTML = `
        <option value="">
            Todos los estados
        </option>

        <option value="normal">
            Normal
        </option>

        <option value="stock-bajo">
            Stock bajo
        </option>

        <option value="agotado">
            Agotado
        </option>

        <option value="stock-negativo">
            Stock negativo
        </option>
    `;
}


function prepararEtiquetaUbicacionInventarioHU20() {
    const etiqueta = document.querySelector(
        'label[for="filtroUbicacion"]'
    );

    if (etiqueta) {
        etiqueta.textContent = "Ubicación";
    }
}


function cargarFiltrosInventarioHU20(filtros) {
    if (!filtros) {
        return;
    }

    cargarUbicacionesFiltroInventarioHU20(
        filtros.ubicaciones || []
    );

    cargarCategoriasFiltroInventarioHU20(
        filtros.categorias || []
    );
}


function cargarUbicacionesFiltroInventarioHU20(
    ubicaciones
) {
    const selector = document.getElementById(
        "filtroUbicacion"
    );

    if (!selector) {
        return;
    }

    const valorActual = selector.value;

    selector.innerHTML = "";

    const opcionTodas = document.createElement(
        "option"
    );

    opcionTodas.value = "";
    opcionTodas.textContent = "Todas las ubicaciones";

    selector.appendChild(opcionTodas);


    ubicaciones.forEach((ubicacion) => {
        const opcion = document.createElement(
            "option"
        );

        opcion.value = String(
            ubicacion.id_ubicacion
        );

        opcion.textContent =
            `${ubicacion.tienda} — ` +
            `${ubicacion.ubicacion} ` +
            `(${ubicacion.tipo})`;

        selector.appendChild(opcion);
    });


    /*
     * Mantener filtro seleccionado después
     * de actualizar los datos.
     */
    if (
        valorActual &&
        Array.from(selector.options).some(
            (opcion) =>
                opcion.value === valorActual
        )
    ) {
        selector.value = valorActual;
    }
}


function cargarCategoriasFiltroInventarioHU20(
    categorias
) {
    const selector = document.getElementById(
        "filtroCategoria"
    );

    if (!selector) {
        return;
    }

    const valorActual = selector.value;

    selector.innerHTML = "";

    const opcionTodas = document.createElement(
        "option"
    );

    opcionTodas.value = "";
    opcionTodas.textContent =
        "Todas las categorías";

    selector.appendChild(opcionTodas);


    categorias.forEach((categoria) => {
        const opcion = document.createElement(
            "option"
        );

        opcion.value = String(
            categoria.id_categoria
        );

        opcion.textContent =
            categoria.nombre;

        selector.appendChild(opcion);
    });


    if (
        valorActual &&
        Array.from(selector.options).some(
            (opcion) =>
                opcion.value === valorActual
        )
    ) {
        selector.value = valorActual;
    }
}


/*=========================================================
  CONSULTAR API
=========================================================*/

async function cargarInventarioHU20() {
    const cuerpoTabla = document.getElementById(
        "tablaInventario"
    );

    if (!cuerpoTabla) {
        return;
    }

    const parametros = new URLSearchParams();

    const busqueda = document.getElementById(
        "buscarInventario"
    )?.value.trim();

    const ubicacion = document.getElementById(
        "filtroUbicacion"
    )?.value;

    const categoria = document.getElementById(
        "filtroCategoria"
    )?.value;

    const estado = document.getElementById(
        "filtroEstado"
    )?.value;


    if (busqueda) {
        parametros.set(
            "busqueda",
            busqueda
        );
    }

    if (ubicacion) {
        parametros.set(
            "id_ubicacion",
            ubicacion
        );
    }

    if (categoria) {
        parametros.set(
            "id_categoria",
            categoria
        );
    }

    if (estado) {
        parametros.set(
            "estado",
            estado
        );
    }

    parametros.set(
        "pagina",
        String(estadoInventarioHU20.pagina)
    );

    parametros.set(
        "por_pagina",
        String(estadoInventarioHU20.porPagina)
    );


    const url = new URL(
        "../api/inventario/stock.php",
        window.location.href
    );

    url.search = parametros.toString();


    mostrarCargandoInventarioHU20();


    try {
        const respuesta = await fetch(
            url,
            {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store",
                headers: {
                    "Accept": "application/json"
                }
            }
        );


        const resultado = await respuesta.json();


        if (!respuesta.ok || !resultado.success) {
            if (respuesta.status === 401) {
                window.location.href =
                    "../index/index.html";

                return;
            }

            throw new Error(
                resultado.message ||
                "No fue posible consultar el inventario."
            );
        }


        const datos = resultado.data || {};

        cargarFiltrosInventarioHU20(
            datos.filtros
        );

        mostrarTablaInventarioHU20(
            datos.inventario || []
        );

        actualizarResumenInventarioHU20(
            datos.resumen || {}
        );

        actualizarPaginacionInventarioHU20(
            datos.paginacion || {}
        );

    } catch (error) {
        console.error(
            "Error consultando inventario:",
            error
        );

        mostrarErrorInventarioHU20(
            error.message ||
            "No fue posible consultar el inventario."
        );
    }
}


/*
 * Compatibilidad con llamadas que todavía
 * puedan existir dentro de app.js.
 */
function mostrarInventarioVending() {
    estadoInventarioHU20.pagina = 1;

    return cargarInventarioHU20();
}


/*=========================================================
  TABLA
=========================================================*/

function mostrarTablaInventarioHU20(
    inventario
) {
    const cuerpo = document.getElementById(
        "tablaInventario"
    );

    const contenedorTabla =
        document.getElementById(
            "contenedorTablaInventario"
        );

    const mensajeVacio =
        document.getElementById(
            "mensajeSinInventario"
        );

    const mensajeSinResultados =
        document.getElementById(
            "mensajeSinResultadosInventario"
        );

    const resultadoBusqueda =
        document.getElementById(
            "resultadoBusquedaInventario"
        );


    if (!cuerpo) {
        return;
    }


    cuerpo.innerHTML = "";


    const hayResultados =
        inventario.length > 0;


    if (contenedorTabla) {
        contenedorTabla.hidden =
            !hayResultados;
    }


    const hayFiltrosActivos =
        Boolean(
            document.getElementById(
                "buscarInventario"
            )?.value.trim()
        ) ||
        Boolean(
            document.getElementById(
                "filtroUbicacion"
            )?.value
        ) ||
        Boolean(
            document.getElementById(
                "filtroCategoria"
            )?.value
        ) ||
        Boolean(
            document.getElementById(
                "filtroEstado"
            )?.value
        );


    if (mensajeVacio) {
        mensajeVacio.hidden =
            hayResultados ||
            hayFiltrosActivos;
    }


    if (mensajeSinResultados) {
        mensajeSinResultados.hidden =
            hayResultados ||
            !hayFiltrosActivos;

        const titulo =
            mensajeSinResultados.querySelector("h3");

        const descripcion =
            mensajeSinResultados.querySelector("p");

        if (titulo) {
            titulo.textContent =
                "No se encontraron existencias";
        }

        if (descripcion) {
            descripcion.textContent =
                "Cambia los filtros o el texto de búsqueda.";
        }
    }


    inventario.forEach((registro) => {
        cuerpo.appendChild(
            crearFilaInventarioHU20(registro)
        );
    });


    if (resultadoBusqueda) {
        resultadoBusqueda.textContent =
            hayResultados
                ? `${inventario.length} registro(s) mostrado(s).`
                : hayFiltrosActivos
                    ? "No se encontraron existencias con los filtros seleccionados."
                    : "No hay existencias registradas.";
    }
}


function crearFilaInventarioHU20(registro) {
    const fila = document.createElement("tr");


    /* Producto */
    fila.appendChild(
        crearCeldaInventarioHU20(
            registro.producto ||
            "Sin nombre"
        )
    );


    /* Código retail: identificador interno del catálogo */
    fila.appendChild(
        crearCeldaInventarioHU20(
            registro.codigo_articulo_retail || "—"
        )
    );

    /* Código de barras: mostrarlo por separado, sin sustituir el retail */
    fila.appendChild(
        crearCeldaInventarioHU20(
            registro.codigo_barras || "—"
        )
    );


    /* Ubicación */
    fila.appendChild(
        crearCeldaInventarioHU20(
            `${registro.tienda} — ${registro.ubicacion}`
        )
    );


    /* Stock actual */
    fila.appendChild(
        crearCeldaInventarioHU20(
            registro.stock_actual
        )
    );


    /* Stock mínimo */
    fila.appendChild(
        crearCeldaInventarioHU20(
            registro.stock_minimo
        )
    );


    /* Estado */
    const celdaEstado =
        document.createElement("td");

    const insignia =
        document.createElement("span");

    insignia.className =
        obtenerClaseEstadoInventarioHU20(
            registro.estado
        );

    insignia.textContent =
        registro.estado_texto ||
        "Sin estado";

    celdaEstado.appendChild(insignia);

    fila.appendChild(celdaEstado);


    /* Último movimiento */
    let textoMovimiento =
        "Sin movimientos";

    if (registro.ultimo_movimiento) {
        textoMovimiento =
            formatearFechaInventarioHU20(
                registro.ultimo_movimiento
            );

        if (registro.ultimo_tipo_movimiento) {
            textoMovimiento +=
                ` · ${registro.ultimo_tipo_movimiento}`;
        }
    }

    fila.appendChild(
        crearCeldaInventarioHU20(
            textoMovimiento
        )
    );


    /* Acciones */
    const celdaAcciones =
        document.createElement("td");

    const enlaceMovimiento =
        document.createElement("a");

    enlaceMovimiento.className =
        "boton boton-borde boton-tabla";

    enlaceMovimiento.textContent =
        "Movimiento";

    enlaceMovimiento.href =
        "movimientos.php?" +
        new URLSearchParams({
            producto:
                registro.id_producto,

            ubicacion:
                registro.id_ubicacion
        }).toString();


    celdaAcciones.appendChild(
        enlaceMovimiento
    );

    fila.appendChild(
        celdaAcciones
    );


    return fila;
}


function crearCeldaInventarioHU20(valor) {
    const celda =
        document.createElement("td");

    celda.textContent =
        valor === null ||
        valor === undefined
            ? ""
            : String(valor);

    return celda;
}


function obtenerClaseEstadoInventarioHU20(
    estado
) {
    switch (estado) {
        case "normal":
            return "badge badge-verde";

        case "stock-bajo":
            return "badge badge-naranja";

        case "agotado":
            return "badge badge-rojo";

        case "stock-negativo":
            return "badge badge-rojo";

        default:
            return "badge";
    }
}


/*=========================================================
  TARJETAS DE RESUMEN
=========================================================*/

function actualizarResumenInventarioHU20(
    resumen
) {
    asignarTextoInventarioHU20(
        "totalStockInventario",
        resumen.total_stock ?? 0
    );


    /*
     * Stock crítico:
     * bajo mínimo + existencia negativa.
     */
    const totalCriticos =
        Number(
            resumen.productos_stock_bajo || 0
        ) +
        Number(
            resumen.productos_negativos || 0
        );


    asignarTextoInventarioHU20(
        "totalStockCriticoInventario",
        totalCriticos
    );


    asignarTextoInventarioHU20(
        "totalProductosAgotadosInventario",
        resumen.productos_agotados ?? 0
    );


    asignarTextoInventarioHU20(
        "totalMovimientosDiaInventario",
        resumen.movimientos_dia ?? 0
    );
}


function asignarTextoInventarioHU20(
    id,
    valor
) {
    const elemento =
        document.getElementById(id);

    if (elemento) {
        elemento.textContent =
            String(valor);
    }
}


/*=========================================================
  PAGINACIÓN
=========================================================*/

function actualizarPaginacionInventarioHU20(
    paginacion
) {
    const pagina =
        Number(paginacion.pagina || 1);

    const totalPaginas =
        Math.max(
            1,
            Number(
                paginacion.total_paginas || 1
            )
        );

    const totalRegistros =
        Number(
            paginacion.total_registros || 0
        );


    estadoInventarioHU20.pagina =
        pagina;

    estadoInventarioHU20.totalPaginas =
        totalPaginas;


    const texto =
        document.getElementById(
            "textoPaginacionInventario"
        );

    const anterior =
        document.getElementById(
            "botonPaginaAnteriorInventario"
        );

    const siguiente =
        document.getElementById(
            "botonPaginaSiguienteInventario"
        );


    if (texto) {
        const hayFiltrosActivos =
            Boolean(
                document.getElementById(
                    "buscarInventario"
                )?.value.trim()
            ) ||
            Boolean(
                document.getElementById(
                    "filtroUbicacion"
                )?.value
            ) ||
            Boolean(
                document.getElementById(
                    "filtroCategoria"
                )?.value
            ) ||
            Boolean(
                document.getElementById(
                    "filtroEstado"
                )?.value
            );

        texto.textContent =
            totalRegistros > 0
                ? `Página ${pagina} de ${totalPaginas} · ${totalRegistros} registro(s)`
                : hayFiltrosActivos
                    ? "Sin resultados para los filtros seleccionados"
                    : "No hay existencias registradas";
    }


    if (anterior) {
        anterior.disabled =
            pagina <= 1;
    }


    if (siguiente) {
        siguiente.disabled =
            pagina >= totalPaginas;
    }
}


/*=========================================================
  ESTADOS DE CARGA Y ERROR
=========================================================*/

function mostrarCargandoInventarioHU20() {
    const resultado =
        document.getElementById(
            "resultadoBusquedaInventario"
        );

    if (resultado) {
        resultado.textContent =
            "Consultando inventario...";
    }
}


function mostrarErrorInventarioHU20(
    mensaje
) {
    const cuerpo =
        document.getElementById(
            "tablaInventario"
        );

    const contenedorTabla =
        document.getElementById(
            "contenedorTablaInventario"
        );

    const resultado =
        document.getElementById(
            "resultadoBusquedaInventario"
        );


    if (cuerpo) {
        cuerpo.innerHTML = "";
    }


    if (contenedorTabla) {
        contenedorTabla.hidden = true;
    }


    if (resultado) {
        resultado.textContent =
            mensaje;
    }


    actualizarResumenInventarioHU20({
        total_stock: 0,
        productos_stock_bajo: 0,
        productos_agotados: 0,
        productos_negativos: 0,
        movimientos_dia: 0
    });
}


/*=========================================================
  FORMATO DE FECHA
=========================================================*/

function formatearFechaInventarioHU20(
    fecha
) {
    if (!fecha) {
        return "Sin movimientos";
    }

    const valor =
        new Date(fecha);

    if (
        Number.isNaN(
            valor.getTime()
        )
    ) {
        return String(fecha);
    }

    return new Intl.DateTimeFormat(
        "es-CR",
        {
            dateStyle: "short",
            timeStyle: "short"
        }
    ).format(valor);
}