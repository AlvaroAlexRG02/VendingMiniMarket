/*=========================================================
  VENDING MINI MARKET — Módulo Tienda (ubicaciones)
  HU-16: las ubicaciones se guardan en la base de datos
  mediante api/tienda/tienda.php. Aquí se listan, se ve su
  historial y se activan o inactivan; el registro y la edición
  están en ubicacion-nueva.php y las relaciones de
  abastecimiento en relaciones.php (HU-18).
  Requiere: app.js, catalogo-comun.js y maquinas-api.js antes.
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {
    inicializarPaginaTiendaVending();
});

const API_TIENDA_VENDING = "../api/tienda/tienda.php";

let ubicacionesTiendaVending = [];
let apiTiendaVending = null;

async function inicializarPaginaTiendaVending() {
    if (!document.getElementById("cuerpoTablaTiendas")) {
        return;
    }

    if (!window.MaquinasApi || !window.CatalogoComun) {
        mostrarMensajeTiendaVending("No fue posible cargar los componentes de la página.", "error");
        return;
    }

    apiTiendaVending = window.MaquinasApi.crearCliente(API_TIENDA_VENDING);

    eliminarDatosLocalesObsoletosTienda();

    document.getElementById("cuerpoTablaTiendas").addEventListener("click", (evento) => {
        const boton = evento.target.closest("[data-accion]");

        if (!boton) {
            return;
        }

        const id = boton.dataset.id || "";

        if (boton.dataset.accion === "historial") {
            verHistorialUbicacionTienda(id);
        } else {
            alternarEstadoUbicacionTiendaVending(id, boton);
        }
    });

    await recargarModuloTiendaVending();
}

/** Las ubicaciones ya no se guardan en el navegador: se borran los datos de la versión anterior. */
function eliminarDatosLocalesObsoletosTienda() {
    try {
        localStorage.removeItem("tiendasVending");
        localStorage.removeItem("historialTiendasVending");
    } catch (error) {
        // Sin acceso a localStorage: no hay nada que limpiar.
    }
}

async function recargarModuloTiendaVending() {
    try {
        const ubicaciones = await apiTiendaVending("listar");

        ubicacionesTiendaVending = Array.isArray(ubicaciones.data) ? ubicaciones.data : [];

        renderizarModuloTiendaVending();
        return true;
    } catch (error) {
        mostrarMensajeTiendaVending(error.message || "No fue posible cargar las ubicaciones.", "error");
        return false;
    }
}


/*=========================================================
  ACTIVAR / INACTIVAR
=========================================================*/

async function alternarEstadoUbicacionTiendaVending(idUbicacion, boton) {
    const ubicacion = ubicacionesTiendaVending.find((item) => String(item.id_tienda) === String(idUbicacion));

    if (!ubicacion) {
        mostrarMensajeTiendaVending("No fue posible actualizar el estado de la ubicación.", "error");
        return;
    }

    const activar = !ubicacion.estado;

    const confirmado = await window.CatalogoComun.confirmar({
        titulo: activar ? "Activar ubicación" : "Inactivar ubicación",
        texto: activar
            ? `"${ubicacion.nombre}" volverá a aceptar operaciones.`
            : `"${ubicacion.nombre}" dejará de aceptar nuevas operaciones, pero su historial se conserva.`,
        textoConfirmar: activar ? "Activar" : "Inactivar",
        peligro: !activar
    });

    if (!confirmado) {
        return;
    }

    if (boton) {
        boton.disabled = true;
    }

    try {
        const resultado = await apiTiendaVending("cambiar_estado", {
            id_tienda: ubicacion.id_tienda,
            estado: activar
        });

        await recargarModuloTiendaVending();
        mostrarMensajeTiendaVending(resultado.message || "El estado de la ubicación se actualizó correctamente.", "exito");
    } catch (error) {
        if (boton) {
            boton.disabled = false;
        }
        mostrarMensajeTiendaVending(error.message, "error");
    }
}


/*=========================================================
  HISTORIAL DE UNA UBICACIÓN — viene de bitacora_auditoria
=========================================================*/

const ACCIONES_HISTORIAL_UBICACION_TIENDA = {
    UBICACION: {
        CREAR: "Ubicación registrada",
        EDITAR: "Ubicación editada",
        INACTIVAR: "Ubicación inactivada",
        ACTIVAR: "Ubicación activada"
    },
    RELACION_ABASTECIMIENTO: {
        CREAR: "Relación de abastecimiento configurada",
        EDITAR: "Relación de abastecimiento modificada",
        INACTIVAR: "Relación de abastecimiento inactivada",
        ACTIVAR: "Relación de abastecimiento activada"
    }
};

const ETIQUETAS_CAMBIO_UBICACION_TIENDA = {
    nombre: "Nombre",
    tipo: "Tipo",
    es_principal: "Principal",
    observaciones: "Observaciones",
    estado: "Estado"
};

function formatearValorCambioTienda(clave, valor) {
    if (valor === null || valor === undefined || valor === "") {
        return "—";
    }

    if (clave === "estado") {
        return valor ? "Activa" : "Inactiva";
    }

    if (clave === "es_principal") {
        return valor ? "Sí" : "No";
    }

    if (clave === "tipo") {
        return valor === "BODEGA" ? "Bodega" : "Tienda";
    }

    return String(valor);
}

function cambiosUbicacionTienda(antes, despues) {
    if (!antes || !despues) {
        return [];
    }

    return Object.keys(ETIQUETAS_CAMBIO_UBICACION_TIENDA)
        .filter((clave) => String(antes[clave] ?? "") !== String(despues[clave] ?? ""))
        .map((clave) =>
            `${ETIQUETAS_CAMBIO_UBICACION_TIENDA[clave]}: ${formatearValorCambioTienda(clave, antes[clave])} → ${formatearValorCambioTienda(clave, despues[clave])}`
        );
}

/** Detalle de un registro: cambios de campos o la relación de que se trata. */
function detalleHistorialUbicacionTienda(registro) {
    if (registro.entidad === "RELACION_ABASTECIMIENTO") {
        const dato = registro.despues || registro.antes || {};
        const texto = dato.origen_nombre && dato.destino_nombre
            ? `${dato.origen_nombre} → ${dato.destino_nombre}`
            : "";

        return texto ? [texto] : [];
    }

    if (registro.accion === "CREAR") {
        const despues = registro.despues || {};

        return [
            `Tipo: ${formatearValorCambioTienda("tipo", despues.tipo)}`,
            `Estado inicial: ${formatearValorCambioTienda("estado", despues.estado)}`
        ];
    }

    return cambiosUbicacionTienda(registro.antes, registro.despues);
}

async function verHistorialUbicacionTienda(idUbicacion) {
    const C = window.CatalogoComun;
    const ubicacion = ubicacionesTiendaVending.find((item) => String(item.id_tienda) === String(idUbicacion));

    try {
        const { data } = await apiTiendaVending("historial_ubicacion", null, { id: idUbicacion });
        const contenedor = document.createElement("div");

        if (!data.length) {
            contenedor.innerHTML = "<p>Esta ubicación todavía no tiene movimientos registrados.</p>";
        } else {
            const lista = document.createElement("ul");
            lista.className = "lista-historial";

            data.forEach((registro) => {
                const elemento = document.createElement("li");
                const titulo = (ACCIONES_HISTORIAL_UBICACION_TIENDA[registro.entidad] || {})[registro.accion] || registro.accion;
                const detalle = detalleHistorialUbicacionTienda(registro);

                elemento.innerHTML = `
                    <strong>${C.escapeHtml(titulo)}</strong>
                    <small>
                        ${C.escapeHtml(C.fechaHora(normalizarFechaTienda(registro.fecha)))}
                        ${registro.usuario ? " · " + C.escapeHtml(registro.usuario) : ""}
                    </small>
                    ${detalle.length
                        ? `<ul>${detalle.map((linea) => `<li>${C.escapeHtml(linea)}</li>`).join("")}</ul>`
                        : ""}
                `;

                lista.appendChild(elemento);
            });

            contenedor.appendChild(lista);
        }

        C.mostrarDialogo(`Historial: ${ubicacion ? ubicacion.nombre : "ubicación"}`, contenedor);
    } catch (error) {
        mostrarMensajeTiendaVending(error.message, "error");
    }
}


/*=========================================================
  RENDERIZADO
=========================================================*/

function renderizarModuloTiendaVending() {
    actualizarResumenTiendaVending(ubicacionesTiendaVending);
    renderizarTablaTiendaVending(ubicacionesTiendaVending);
}

function etiquetaEstadoTienda(ubicacion) {
    return ubicacion.estado ? "Activa" : "Inactiva";
}

function textoAbastecidaPorTienda(ubicacion) {
    const origenes = Array.isArray(ubicacion.abastecida_por) ? ubicacion.abastecida_por : [];

    return origenes.map((origen) => origen.nombre).join(", ");
}

function actualizarResumenTiendaVending(ubicaciones) {
    const relaciones = ubicaciones.filter((ubicacion) => textoAbastecidaPorTienda(ubicacion)).length;

    asignarTextoTienda("totalUbicacionesTienda", String(ubicaciones.length));
    asignarTextoTienda("totalPrincipalesTienda", String(ubicaciones.filter((ubicacion) => ubicacion.es_principal).length));
    asignarTextoTienda("totalActivasTienda", String(ubicaciones.filter((ubicacion) => ubicacion.estado).length));
    asignarTextoTienda("totalRelacionesTienda", String(relaciones));
}

function renderizarTablaTiendaVending(ubicaciones) {
    const cuerpo = document.getElementById("cuerpoTablaTiendas");

    if (!cuerpo) {
        return;
    }

    cuerpo.innerHTML = ubicaciones.map((ubicacion) => {
        const abastecidaPor = textoAbastecidaPorTienda(ubicacion);
        const nombre = escaparHtmlTienda(ubicacion.nombre);
        const id = escaparAtributoTienda(ubicacion.id_tienda);

        return `
            <tr>
                <td>${nombre}</td>
                <td>${escaparHtmlTienda(ubicacion.tipo_etiqueta)}</td>
                <td>
                    <span class="insignia ${ubicacion.estado ? "insignia-verde" : "insignia-rojo"}">
                        ${etiquetaEstadoTienda(ubicacion)}
                    </span>
                </td>
                <td>${ubicacion.es_principal ? "Sí" : "No"}</td>
                <td>${abastecidaPor ? escaparHtmlTienda(abastecidaPor) : "Sin relación"}</td>
                <td>
                    <div class="acciones-producto-tabla">
                        <button type="button"
                                class="boton-accion-producto boton-historial-producto"
                                data-accion="historial" data-id="${id}"
                                title="Ver historial" aria-label="Ver historial de ${nombre}">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </button>
                        <a href="ubicacion-nueva.php?id=${id}"
                           class="boton-accion-producto boton-editar-producto"
                           title="Editar ubicación" aria-label="Editar ${nombre}">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        ${ubicacion.estado
                            ? `<button type="button"
                                       class="boton-accion-producto boton-inactivar-producto"
                                       data-accion="inactivar" data-id="${id}"
                                       title="Inactivar ubicación" aria-label="Inactivar ${nombre}">
                                    <i class="fa-solid fa-ban"></i>
                               </button>`
                            : `<button type="button"
                                       class="boton-accion-producto boton-activar-producto"
                                       data-accion="activar" data-id="${id}"
                                       title="Activar ubicación" aria-label="Activar ${nombre}">
                                    <i class="fa-solid fa-circle-check"></i>
                               </button>`}
                    </div>
                </td>
            </tr>
        `;
    }).join("");
}

/** PostgreSQL devuelve "2026-10-06 23:56:09.587672+00"; se convierte a ISO 8601 para que todos los navegadores lo lean. */
function normalizarFechaTienda(fecha) {
    return String(fecha || "")
        .replace(" ", "T")
        .replace(/([+-]\d{2})$/, "$1:00");
}


/*=========================================================
  UTILIDADES
=========================================================*/

function asignarTextoTienda(id, valor) {
    const elemento = document.getElementById(id);

    if (elemento) {
        elemento.textContent = valor;
    }
}

function mostrarMensajeTiendaVending(mensaje, tipo) {
    const elemento = document.getElementById("mensajeTienda");

    if (!elemento) {
        return;
    }

    elemento.textContent = mensaje;
    elemento.className = `mensaje-producto mensaje-${tipo}`;
    elemento.hidden = false;

    window.setTimeout(() => {
        elemento.hidden = true;
    }, 4500);
}

function limpiarMensajeTiendaVending() {
    const elemento = document.getElementById("mensajeTienda");

    if (elemento) {
        elemento.textContent = "";
        elemento.className = "mensaje-producto";
    }
}

function escaparHtmlTienda(valor) {
    return String(valor)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#39;");
}

function escaparAtributoTienda(valor) {
    return escaparHtmlTienda(valor);
}
