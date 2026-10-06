document.addEventListener("DOMContentLoaded", () => {
    inicializarPaginaTiendaVending();
});

const CLAVE_TIENDAS_VENDING = "tiendasVending";
const CLAVE_HISTORIAL_TIENDAS_VENDING = "historialTiendasVending";

function inicializarPaginaTiendaVending() {
    const formulario = document.getElementById("formularioTienda");
    const formularioRelacion = document.getElementById("formularioRelacionTienda");
    const botonLimpiar = document.getElementById("botonLimpiarTienda");

    if (!formulario || !formularioRelacion) {
        return;
    }

    asegurarDatosInicialesTiendasVending();
    poblarSelectAbastecimientoTienda();
    poblarSelectRelacionTienda();
    renderizarModuloTiendaVending();

    formulario.addEventListener("submit", manejarRegistroTiendaVending);
    formularioRelacion.addEventListener("submit", guardarRelacionOperativaTiendaVending);

    if (botonLimpiar) {
        botonLimpiar.addEventListener("click", () => {
            formulario.reset();
            poblarSelectAbastecimientoTienda();
            limpiarMensajeTiendaVending();
        });
    }
}

function asegurarDatosInicialesTiendasVending() {
    const ubicaciones = leerListaTiendaVending(CLAVE_TIENDAS_VENDING);

    if (ubicaciones.length > 0) {
        return;
    }

    const fechaActual = new Date().toISOString();
    const datosIniciales = [
        {
            id: "ubicacion-up1",
            nombre: "UP1",
            tipo: "Tienda",
            estado: "Activa",
            principal: true,
            abastecidaPor: "",
            observaciones: "Ubicación principal de operación.",
            fechaRegistro: fechaActual
        },
        {
            id: "ubicacion-up2",
            nombre: "UP2",
            tipo: "Tienda",
            estado: "Activa",
            principal: true,
            abastecidaPor: "",
            observaciones: "Ubicación principal con función de abastecimiento.",
            fechaRegistro: fechaActual
        },
        {
            id: "ubicacion-ultralag",
            nombre: "UltraLag",
            tipo: "Bodega",
            estado: "Activa",
            principal: true,
            abastecidaPor: "UP2",
            observaciones: "Ubicación principal abastecida por UP2.",
            fechaRegistro: fechaActual
        }
    ];

    const historialInicial = [
        crearEventoHistorialTiendaVending("Registro inicial", "UP1", "Ubicación principal creada para operación multiubicación."),
        crearEventoHistorialTiendaVending("Registro inicial", "UP2", "Ubicación principal creada para operación multiubicación."),
        crearEventoHistorialTiendaVending("Registro inicial", "UltraLag", "Ubicación principal creada para operación multiubicación."),
        crearEventoHistorialTiendaVending("Relación operativa", "UltraLag", "UP2 configurada como punto de abastecimiento de UltraLag.")
    ];

    guardarListaTiendaVending(CLAVE_TIENDAS_VENDING, datosIniciales);
    guardarListaTiendaVending(CLAVE_HISTORIAL_TIENDAS_VENDING, historialInicial);
}

function manejarRegistroTiendaVending(evento) {
    evento.preventDefault();

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

    const ubicaciones = obtenerUbicacionesTiendaVending();
    const existe = ubicaciones.some((ubicacion) => normalizarTexto(ubicacion.nombre) === normalizarTexto(nombre));

    if (existe) {
        mostrarMensajeTiendaVending("Ya existe una ubicación registrada con ese nombre.", "error");
        return;
    }

    const nuevaUbicacion = {
        id: generarIdVending("ubicacion"),
        nombre,
        tipo,
        estado,
        principal,
        abastecidaPor,
        observaciones,
        fechaRegistro: new Date().toISOString()
    };

    ubicaciones.push(nuevaUbicacion);
    guardarListaTiendaVending(CLAVE_TIENDAS_VENDING, ubicaciones);

    agregarEventoHistorialTiendaVending(
        "Registro de ubicación",
        nombre,
        abastecidaPor
            ? `Ubicación creada como ${tipo.toLowerCase()} con relación operativa desde ${abastecidaPor}.`
            : `Ubicación creada como ${tipo.toLowerCase()} en estado ${estado.toLowerCase()}.`
    );

    evento.target.reset();
    poblarSelectAbastecimientoTienda();
    poblarSelectRelacionTienda();
    renderizarModuloTiendaVending();

    mostrarMensajeTiendaVending("La ubicación fue registrada correctamente.", "exito");
}

function guardarRelacionOperativaTiendaVending(evento) {
    evento.preventDefault();

    const origenId = document.getElementById("origenRelacionTienda")?.value || "";
    const destinoId = document.getElementById("destinoRelacionTienda")?.value || "";

    if (!origenId || !destinoId || origenId === destinoId) {
        mostrarMensajeTiendaVending("Debe seleccionar dos ubicaciones distintas para configurar la relación.", "error");
        return;
    }

    const ubicaciones = obtenerUbicacionesTiendaVending();
    const origen = ubicaciones.find((ubicacion) => ubicacion.id === origenId);
    const destino = ubicaciones.find((ubicacion) => ubicacion.id === destinoId);

    if (!origen || !destino) {
        mostrarMensajeTiendaVending("No fue posible identificar las ubicaciones seleccionadas.", "error");
        return;
    }

    destino.abastecidaPor = origen.nombre;
    guardarListaTiendaVending(CLAVE_TIENDAS_VENDING, ubicaciones);

    agregarEventoHistorialTiendaVending(
        "Relación operativa",
        destino.nombre,
        `${origen.nombre} quedó configurada como punto de abastecimiento de ${destino.nombre}.`
    );

    renderizarModuloTiendaVending();
    mostrarMensajeTiendaVending("La relación operativa fue actualizada correctamente.", "exito");
}

function renderizarModuloTiendaVending() {
    const ubicaciones = obtenerUbicacionesTiendaVending();
    const historial = obtenerHistorialTiendaVending();

    actualizarResumenTiendaVending(ubicaciones);
    renderizarPrincipalesTiendaVending(ubicaciones);
    renderizarTablaTiendaVending(ubicaciones);
    renderizarHistorialTiendaVending(historial);
    poblarSelectAbastecimientoTienda();
    poblarSelectRelacionTienda();
}

function actualizarResumenTiendaVending(ubicaciones) {
    const relaciones = ubicaciones.filter((ubicacion) => ubicacion.abastecidaPor).length;

    asignarTextoTienda("totalUbicacionesTienda", String(ubicaciones.length));
    asignarTextoTienda("totalPrincipalesTienda", String(ubicaciones.filter((ubicacion) => ubicacion.principal).length));
    asignarTextoTienda("totalActivasTienda", String(ubicaciones.filter((ubicacion) => ubicacion.estado === "Activa").length));
    asignarTextoTienda("totalRelacionesTienda", String(relaciones));
}

function renderizarPrincipalesTiendaVending(ubicaciones) {
    const contenedor = document.getElementById("listaPrincipalesTienda");

    if (!contenedor) {
        return;
    }

    const principales = ubicaciones.filter((ubicacion) => ubicacion.principal);

    contenedor.innerHTML = principales.map((ubicacion) => `
        <article class="tarjeta-principal-tienda">
            <div class="tarjeta-principal-tienda-encabezado">
                <strong>${escaparHtmlTienda(ubicacion.nombre)}</strong>
                <span class="insignia ${ubicacion.estado === "Activa" ? "insignia-verde" : "insignia-rojo"}">${escaparHtmlTienda(ubicacion.estado)}</span>
            </div>
            <p>${escaparHtmlTienda(ubicacion.tipo)}</p>
            <small>${ubicacion.abastecidaPor ? `Abastecida por ${escaparHtmlTienda(ubicacion.abastecidaPor)}` : "Sin relación de abastecimiento"}</small>
        </article>
    `).join("");
}

function renderizarTablaTiendaVending(ubicaciones) {
    const cuerpo = document.getElementById("cuerpoTablaTiendas");

    if (!cuerpo) {
        return;
    }

    cuerpo.innerHTML = ubicaciones.map((ubicacion) => {
        const etiquetaPrincipal = ubicacion.principal ? "Sí" : "No";
        const textoBoton = ubicacion.estado === "Activa" ? "Desactivar" : "Activar";

        return `
            <tr>
                <td>${escaparHtmlTienda(ubicacion.nombre)}</td>
                <td>${escaparHtmlTienda(ubicacion.tipo)}</td>
                <td>
                    <span class="insignia ${ubicacion.estado === "Activa" ? "insignia-verde" : "insignia-rojo"}">
                        ${escaparHtmlTienda(ubicacion.estado)}
                    </span>
                </td>
                <td>${etiquetaPrincipal}</td>
                <td>${ubicacion.abastecidaPor ? escaparHtmlTienda(ubicacion.abastecidaPor) : "Sin relación"}</td>
                <td>
                    <button type="button" class="boton boton-borde boton-tabla-tienda" data-ubicacion="${escaparAtributoTienda(ubicacion.id)}">
                        ${textoBoton}
                    </button>
                </td>
            </tr>
        `;
    }).join("");

    cuerpo.querySelectorAll(".boton-tabla-tienda").forEach((boton) => {
        boton.addEventListener("click", () => {
            alternarEstadoUbicacionTiendaVending(boton.dataset.ubicacion || "");
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
            <td>${escaparHtmlTienda(formatearFechaHoraVenta(evento.fecha))}</td>
            <td>${escaparHtmlTienda(evento.accion)}</td>
            <td>${escaparHtmlTienda(evento.ubicacion)}</td>
            <td>${escaparHtmlTienda(evento.detalle)}</td>
            <td>${escaparHtmlTienda(evento.responsable)}</td>
        </tr>
    `).join("");
}

function alternarEstadoUbicacionTiendaVending(idUbicacion) {
    const ubicaciones = obtenerUbicacionesTiendaVending();
    const ubicacion = ubicaciones.find((item) => item.id === idUbicacion);

    if (!ubicacion) {
        mostrarMensajeTiendaVending("No fue posible actualizar el estado de la ubicación.", "error");
        return;
    }

    ubicacion.estado = ubicacion.estado === "Activa" ? "Inactiva" : "Activa";
    guardarListaTiendaVending(CLAVE_TIENDAS_VENDING, ubicaciones);

    agregarEventoHistorialTiendaVending(
        "Cambio de estado",
        ubicacion.nombre,
        `La ubicación fue marcada como ${ubicacion.estado.toLowerCase()} y el historial operativo se conservó.`
    );

    renderizarModuloTiendaVending();
    mostrarMensajeTiendaVending("El estado de la ubicación se actualizó correctamente.", "exito");
}

function poblarSelectAbastecimientoTienda() {
    const select = document.getElementById("abastecidaPorTienda");

    if (!select) {
        return;
    }

    const valorActual = select.value;
    const ubicaciones = obtenerUbicacionesTiendaVending().filter((ubicacion) => ubicacion.estado === "Activa");

    select.innerHTML = '<option value="">Sin relación operativa</option>' + ubicaciones.map((ubicacion) => `
        <option value="${escaparAtributoTienda(ubicacion.nombre)}">${escaparHtmlTienda(ubicacion.nombre)}</option>
    `).join("");

    select.value = ubicaciones.some((ubicacion) => ubicacion.nombre === valorActual) ? valorActual : "";
}

function poblarSelectRelacionTienda() {
    const selectOrigen = document.getElementById("origenRelacionTienda");
    const selectDestino = document.getElementById("destinoRelacionTienda");

    if (!selectOrigen || !selectDestino) {
        return;
    }

    const ubicaciones = obtenerUbicacionesTiendaVending();
    const opciones = ubicaciones.map((ubicacion) => `
        <option value="${escaparAtributoTienda(ubicacion.id)}">${escaparHtmlTienda(ubicacion.nombre)}</option>
    `).join("");

    selectOrigen.innerHTML = opciones;
    selectDestino.innerHTML = opciones;

    const origenPorDefecto = ubicaciones.find((ubicacion) => ubicacion.nombre === "UP2")?.id || ubicaciones[0]?.id || "";
    const destinoPorDefecto = ubicaciones.find((ubicacion) => ubicacion.nombre === "UltraLag")?.id || ubicaciones[1]?.id || "";

    selectOrigen.value = origenPorDefecto;
    selectDestino.value = destinoPorDefecto;
}

function obtenerUbicacionesTiendaVending() {
    return leerListaTiendaVending(CLAVE_TIENDAS_VENDING);
}

function obtenerHistorialTiendaVending() {
    return leerListaTiendaVending(CLAVE_HISTORIAL_TIENDAS_VENDING)
        .slice()
        .sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
}

function agregarEventoHistorialTiendaVending(accion, ubicacion, detalle) {
    const historial = obtenerHistorialTiendaVending();
    historial.push(crearEventoHistorialTiendaVending(accion, ubicacion, detalle));
    guardarListaTiendaVending(CLAVE_HISTORIAL_TIENDAS_VENDING, historial);
}

function crearEventoHistorialTiendaVending(accion, ubicacion, detalle) {
    return {
        id: generarIdVending("historial-tienda"),
        accion,
        ubicacion,
        detalle,
        responsable: obtenerNombreUsuarioTiendaVending(),
        fecha: new Date().toISOString()
    };
}

function obtenerNombreUsuarioTiendaVending() {
    return document.getElementById("nombreUsuarioSuperior")?.textContent.trim() || "Administrador";
}

function leerListaTiendaVending(clave) {
    try {
        const datos = JSON.parse(localStorage.getItem(clave) || "[]");
        return Array.isArray(datos) ? datos : [];
    } catch (error) {
        return [];
    }
}

function guardarListaTiendaVending(clave, datos) {
    localStorage.setItem(clave, JSON.stringify(datos));
}

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