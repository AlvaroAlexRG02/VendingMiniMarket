/*=========================================================
  VENDING MINI MARKET — Módulo Tienda (ubicaciones)
  HU-16: las ubicaciones se guardan en la base de datos
  mediante api/tienda/tienda.php. La relación "abastecida
  por" usa api/abastecimiento/abastecimiento.php (HU-18).
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {
    inicializarPaginaTiendaVending();
});

const API_TIENDA_VENDING = "../api/tienda/tienda.php";
const API_ABASTECIMIENTO_TIENDA_VENDING = "../api/abastecimiento/abastecimiento.php";

let ubicacionesTiendaVending = [];
let historialTiendaVending = [];
let apiTiendaVending = null;
let apiAbastecimientoTiendaVending = null;

async function inicializarPaginaTiendaVending() {
    const formulario = document.getElementById("formularioTienda");
    const formularioRelacion = document.getElementById("formularioRelacionTienda");
    const botonLimpiar = document.getElementById("botonLimpiarTienda");

    if (!formulario || !formularioRelacion) {
        return;
    }

    if (!window.MaquinasApi) {
        mostrarMensajeTiendaVending("No fue posible cargar el cliente de la API.", "error");
        return;
    }

    apiTiendaVending = window.MaquinasApi.crearCliente(API_TIENDA_VENDING);
    apiAbastecimientoTiendaVending = window.MaquinasApi.crearCliente(API_ABASTECIMIENTO_TIENDA_VENDING);

    eliminarDatosLocalesObsoletosTienda();

    formulario.addEventListener("submit", manejarRegistroTiendaVending);
    formularioRelacion.addEventListener("submit", guardarRelacionOperativaTiendaVending);
    document.getElementById("origenRelacionTienda")?.addEventListener("change", () => actualizarDestinoRelacionTienda());
    document.getElementById("tipoUbicacionTienda")?.addEventListener("change", actualizarCampoAbastecidaPorTienda);
    document.getElementById("estadoUbicacionTienda")?.addEventListener("change", actualizarCampoAbastecidaPorTienda);

    if (botonLimpiar) {
        botonLimpiar.addEventListener("click", () => {
            formulario.reset();
            actualizarCampoAbastecidaPorTienda();
            limpiarMensajeTiendaVending();
        });
    }

    actualizarCampoAbastecidaPorTienda();
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
        const [ubicaciones, historial] = await Promise.all([
            apiTiendaVending("listar"),
            apiTiendaVending("historial")
        ]);

        ubicacionesTiendaVending = Array.isArray(ubicaciones.data) ? ubicaciones.data : [];
        historialTiendaVending = Array.isArray(historial.data) ? historial.data : [];

        renderizarModuloTiendaVending();
        return true;
    } catch (error) {
        mostrarMensajeTiendaVending(error.message || "No fue posible cargar las ubicaciones.", "error");
        return false;
    }
}


/*=========================================================
  REGISTRO DE UBICACIONES
=========================================================*/

async function manejarRegistroTiendaVending(evento) {
    evento.preventDefault();

    const formulario = evento.target;
    const nombre = document.getElementById("nombreUbicacionTienda")?.value.trim() || "";
    const tipo = document.getElementById("tipoUbicacionTienda")?.value || "";
    const estado = document.getElementById("estadoUbicacionTienda")?.value || "Activa";
    const principal = document.getElementById("principalUbicacionTienda")?.value === "true";
    const abastecidaPor = document.getElementById("abastecidaPorTienda")?.value || "";
    const observaciones = document.getElementById("observacionesUbicacionTienda")?.value.trim() || "";

    if (!nombre || !tipo) {
        mostrarMensajeTiendaVending("Debe completar el nombre y el tipo de la ubicación.", "error");
        return;
    }

    const botonGuardar = formulario.querySelector('button[type="submit"]');
    botonGuardar.disabled = true;

    try {
        const resultado = await apiTiendaVending("crear", {
            nombre,
            tipo,
            estado: estado === "Activa",
            es_principal: principal,
            observaciones,
            id_abastecedora: abastecidaPor ? Number(abastecidaPor) : 0
        });

        formulario.reset();
        actualizarCampoAbastecidaPorTienda();
        await recargarModuloTiendaVending();

        mostrarMensajeTiendaVending(resultado.message || "La ubicación fue registrada correctamente.", "exito");
    } catch (error) {
        mostrarMensajeTiendaVending(error.message, "error");
    } finally {
        botonGuardar.disabled = false;
    }
}

/** Una bodega o una ubicación inactiva no pueden tener una ubicación que las abastezca. */
function actualizarCampoAbastecidaPorTienda() {
    const select = document.getElementById("abastecidaPorTienda");

    if (!select) {
        return;
    }

    const tipo = document.getElementById("tipoUbicacionTienda")?.value || "";
    const estado = document.getElementById("estadoUbicacionTienda")?.value || "Activa";
    const bloqueada = tipo === "Bodega" || estado !== "Activa";

    if (bloqueada) {
        select.value = "";
    }

    select.disabled = bloqueada;
    select.title = bloqueada
        ? "Solo una tienda activa puede ser abastecida por otra ubicación."
        : "";
}


/*=========================================================
  RELACIÓN OPERATIVA DE ABASTECIMIENTO
=========================================================*/

async function guardarRelacionOperativaTiendaVending(evento) {
    evento.preventDefault();

    const origenId = document.getElementById("origenRelacionTienda")?.value || "";
    const destinoId = document.getElementById("destinoRelacionTienda")?.value || "";

    if (!origenId || !destinoId || origenId === destinoId) {
        mostrarMensajeTiendaVending("Debe seleccionar dos ubicaciones distintas para configurar la relación.", "error");
        return;
    }

    const botonGuardar = evento.target.querySelector('button[type="submit"]');
    botonGuardar.disabled = true;

    try {
        const resultado = await apiAbastecimientoTiendaVending("crear_relacion", {
            id_tienda_origen: Number(origenId),
            destino_tipo: "UBICACION",
            id_destino: Number(destinoId)
        });

        await recargarModuloTiendaVending();
        mostrarMensajeTiendaVending(resultado.message || "La relación operativa fue registrada correctamente.", "exito");
    } catch (error) {
        mostrarMensajeTiendaVending(error.message, "error");
    } finally {
        botonGuardar.disabled = false;
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

    if (boton) {
        boton.disabled = true;
    }

    try {
        const resultado = await apiTiendaVending("cambiar_estado", {
            id_tienda: ubicacion.id_tienda,
            estado: !ubicacion.estado
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
  RENDERIZADO
=========================================================*/

function renderizarModuloTiendaVending() {
    actualizarResumenTiendaVending(ubicacionesTiendaVending);
    renderizarPrincipalesTiendaVending(ubicacionesTiendaVending);
    renderizarTablaTiendaVending(ubicacionesTiendaVending);
    renderizarHistorialTiendaVending(historialTiendaVending);
    poblarSelectAbastecimientoTienda();
    poblarSelectRelacionTienda();
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

function renderizarPrincipalesTiendaVending(ubicaciones) {
    renderizarTarjetasUbicacionTienda(
        "listaPrincipalesTienda",
        ubicaciones.filter((ubicacion) => ubicacion.es_principal),
        "No hay ubicaciones principales registradas."
    );

    renderizarTarjetasUbicacionTienda(
        "listaSecundariasTienda",
        ubicaciones.filter((ubicacion) => !ubicacion.es_principal),
        "No hay ubicaciones secundarias registradas."
    );
}

function renderizarTarjetasUbicacionTienda(idContenedor, lista, mensajeVacio) {
    const contenedor = document.getElementById(idContenedor);

    if (!contenedor) {
        return;
    }

    if (lista.length === 0) {
        contenedor.innerHTML = `<p class="texto-vacio-tienda">${escaparHtmlTienda(mensajeVacio)}</p>`;
        return;
    }

    contenedor.innerHTML = lista.map((ubicacion) => {
        const abastecidaPor = textoAbastecidaPorTienda(ubicacion);

        return `
        <article class="tarjeta-principal-tienda">
            <div class="tarjeta-principal-tienda-encabezado">
                <strong>${escaparHtmlTienda(ubicacion.nombre)}</strong>
                <span class="insignia ${ubicacion.estado ? "insignia-verde" : "insignia-rojo"}">${etiquetaEstadoTienda(ubicacion)}</span>
            </div>
            <p>${escaparHtmlTienda(ubicacion.tipo_etiqueta)}</p>
            <small>${abastecidaPor ? `Abastecida por ${escaparHtmlTienda(abastecidaPor)}` : "Sin relación de abastecimiento"}</small>
        </article>
    `;
    }).join("");
}

function renderizarTablaTiendaVending(ubicaciones) {
    const cuerpo = document.getElementById("cuerpoTablaTiendas");

    if (!cuerpo) {
        return;
    }

    cuerpo.innerHTML = ubicaciones.map((ubicacion) => {
        const abastecidaPor = textoAbastecidaPorTienda(ubicacion);

        return `
            <tr>
                <td>${escaparHtmlTienda(ubicacion.nombre)}</td>
                <td>${escaparHtmlTienda(ubicacion.tipo_etiqueta)}</td>
                <td>
                    <span class="insignia ${ubicacion.estado ? "insignia-verde" : "insignia-rojo"}">
                        ${etiquetaEstadoTienda(ubicacion)}
                    </span>
                </td>
                <td>${ubicacion.es_principal ? "Sí" : "No"}</td>
                <td>${abastecidaPor ? escaparHtmlTienda(abastecidaPor) : "Sin relación"}</td>
                <td>
                    <button type="button" class="boton boton-borde boton-tabla-tienda" data-ubicacion="${escaparAtributoTienda(ubicacion.id_tienda)}">
                        ${ubicacion.estado ? "Desactivar" : "Activar"}
                    </button>
                </td>
            </tr>
        `;
    }).join("");

    cuerpo.querySelectorAll(".boton-tabla-tienda").forEach((boton) => {
        boton.addEventListener("click", () => {
            alternarEstadoUbicacionTiendaVending(boton.dataset.ubicacion || "", boton);
        });
    });
}

function renderizarHistorialTiendaVending(historial) {
    const cuerpo = document.getElementById("cuerpoHistorialTiendas");

    if (!cuerpo) {
        return;
    }

    cuerpo.innerHTML = historial.map((evento) => `
        <tr>
            <td>${escaparHtmlTienda(formatearFechaHoraVenta(normalizarFechaTienda(evento.fecha)))}</td>
            <td>${escaparHtmlTienda(evento.accion)}</td>
            <td>${escaparHtmlTienda(evento.ubicacion)}</td>
            <td>${escaparHtmlTienda(evento.detalle)}</td>
            <td>${escaparHtmlTienda(evento.responsable)}</td>
        </tr>
    `).join("");
}

/** PostgreSQL devuelve "2026-10-06 23:56:09.587672+00"; se convierte a ISO 8601 para que todos los navegadores lo lean. */
function normalizarFechaTienda(fecha) {
    return String(fecha || "")
        .replace(" ", "T")
        .replace(/([+-]\d{2})$/, "$1:00");
}


/*=========================================================
  SELECTORES
=========================================================*/

/** "Abastecida por" del formulario de registro: ubicaciones activas. */
function poblarSelectAbastecimientoTienda() {
    const select = document.getElementById("abastecidaPorTienda");

    if (!select) {
        return;
    }

    const valorActual = select.value;
    const activas = ubicacionesTiendaVending.filter((ubicacion) => ubicacion.estado);

    select.innerHTML = '<option value="">Sin relación operativa</option>' + activas.map((ubicacion) => `
        <option value="${escaparAtributoTienda(ubicacion.id_tienda)}">${escaparHtmlTienda(ubicacion.nombre)}</option>
    `).join("");

    select.value = activas.some((ubicacion) => String(ubicacion.id_tienda) === valorActual) ? valorActual : "";
}

/** Relación de abastecimiento: el origen puede ser cualquier ubicación activa. */
function poblarSelectRelacionTienda() {
    const selectOrigen = document.getElementById("origenRelacionTienda");
    const selectDestino = document.getElementById("destinoRelacionTienda");

    if (!selectOrigen || !selectDestino) {
        return;
    }

    const origenActual = selectOrigen.value;
    const destinoActual = selectDestino.value;
    const activas = ubicacionesTiendaVending.filter((ubicacion) => ubicacion.estado);

    selectOrigen.innerHTML = activas.map((ubicacion) => `
        <option value="${escaparAtributoTienda(ubicacion.id_tienda)}">${escaparHtmlTienda(ubicacion.nombre)}</option>
    `).join("");

    selectOrigen.value = activas.some((ubicacion) => String(ubicacion.id_tienda) === origenActual)
        ? origenActual
        : (activas[0] ? String(activas[0].id_tienda) : "");

    actualizarDestinoRelacionTienda(destinoActual);
}

/** El destino excluye al origen elegido y solo admite tiendas activas (una bodega no puede ser abastecida). */
function actualizarDestinoRelacionTienda(destinoPreferido) {
    const selectOrigen = document.getElementById("origenRelacionTienda");
    const selectDestino = document.getElementById("destinoRelacionTienda");

    if (!selectOrigen || !selectDestino) {
        return;
    }

    const origenId = selectOrigen.value;
    const destinoActual = destinoPreferido !== undefined ? destinoPreferido : selectDestino.value;
    const disponibles = ubicacionesTiendaVending.filter((ubicacion) =>
        ubicacion.estado
        && ubicacion.tipo === "TIENDA"
        && String(ubicacion.id_tienda) !== origenId
    );

    selectDestino.innerHTML = disponibles.map((ubicacion) => `
        <option value="${escaparAtributoTienda(ubicacion.id_tienda)}">${escaparHtmlTienda(ubicacion.nombre)}</option>
    `).join("");

    selectDestino.value = disponibles.some((ubicacion) => String(ubicacion.id_tienda) === destinoActual)
        ? destinoActual
        : (disponibles[0] ? String(disponibles[0].id_tienda) : "");
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
