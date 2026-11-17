<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/permisos.php';
require_once __DIR__ . '/../config/layout.php';

if (empty($_SESSION['usuario'])) {
    header('Location: ../index/index.html');
    exit;
}
if (!esAdministradorOGerente()) {
    http_response_code(403);
    exit('Acceso no autorizado.');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recomendaciones de rotación de productos por tienda">
    <title>Recomendaciones | Vending Mini Market</title>
    <?php vmEstilosGlobales(); ?>
    <style>
        .recomendacion-acciones { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
        .recomendacion-config { display:flex; flex-wrap:wrap; align-items:end; gap:12px; }
        .recomendacion-config label { display:grid; gap:6px; font-weight:600; }
        .recomendacion-config input, .recomendacion-comentario { min-height:40px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; }
        .recomendacion-lista { display:grid; gap:16px; }
        .recomendacion-item { padding:18px; border:1px solid #e2e8f0; border-radius:10px; background:#fff; }
        .recomendacion-item h3 { margin:0 0 8px; }
        .recomendacion-meta { display:flex; flex-wrap:wrap; gap:8px 18px; color:#475569; }
        .recomendacion-item details { margin-top:12px; }
        .recomendacion-item ul { padding-left:22px; }
        .recomendacion-comentario { width:min(100%, 440px); }
        .recomendacion-mensaje { margin:12px 0; min-height:1.4em; }
        .recomendacion-filtros { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
        .recomendacion-filtros label { display:grid; gap:6px; font-weight:600; }
        .recomendacion-filtros select { min-height:40px; min-width:180px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; }
        @media (max-width:600px) { .recomendacion-item { padding:14px; } }
    </style>
</head>
<body class="pagina-app">
    <?php vmBarraLateral(); ?>
    <div class="columna-principal">
        <?php vmBarraSuperior(); ?>
        <main class="contenido-app">
            <section class="encabezado-pagina">
                <div>
                    <h1><i class="fa-solid fa-lightbulb"></i> Recomendaciones de productos</h1>
                    <p>Análisis por tienda de las unidades vendidas en los últimos 30 días. Las sugerencias no cambian el catálogo ni el inventario.</p>
                </div>
                <button type="button" class="boton boton-azul" id="generarRecomendaciones">
                    <i class="fa-solid fa-arrows-rotate"></i> Analizar ahora
                </button>
            </section>

            <p id="mensajeRecomendaciones" class="recomendacion-mensaje" role="status" aria-live="polite"></p>

            <section class="panel">
                <h2>Umbral global de ventas</h2>
                <p>Se sugerirán productos que vendieron menos unidades que el umbral durante los últimos 30 días en una tienda.</p>
                <form id="formularioUmbral" class="recomendacion-config">
                    <label for="umbralUnidades">Unidades mínimas esperadas</label>
                    <input type="number" id="umbralUnidades" min="1" max="100000" required>
                    <button type="submit" class="boton boton-borde">Guardar umbral</button>
                    <span id="fechaUmbral"></span>
                </form>
            </section>

            <section class="panel">
                <h2>Recomendaciones</h2>
                <div class="recomendacion-filtros">
                    <label for="filtroTiendaRecomendacion">Tienda
                        <select id="filtroTiendaRecomendacion">
                            <option value="">Todas las tiendas</option>
                        </select>
                    </label>
                    <label for="filtroEstadoRecomendacion">Estado
                        <select id="filtroEstadoRecomendacion">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="REVISADA">Revisada</option>
                            <option value="ACEPTADA">Aceptada</option>
                            <option value="DESCARTADA">Descartada</option>
                        </select>
                    </label>
                    <label for="ordenRecomendaciones">Ordenar por
                        <select id="ordenRecomendaciones">
                            <option value="prioridad">Prioridad</option>
                            <option value="inventario">Mayor inventario disponible</option>
                            <option value="reciente">Fecha de generación: más reciente</option>
                            <option value="antigua">Fecha de generación: más antigua</option>
                        </select>
                    </label>
                </div>
                <div id="listaRecomendaciones" class="recomendacion-lista" aria-live="polite">
                    <p>Cargando recomendaciones...</p>
                </div>
            </section>
        </main>
    </div>
    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script>
        const apiRecomendaciones = "../api/recomendaciones/recomendaciones.php";
        const listaRecomendaciones = document.getElementById("listaRecomendaciones");
        const mensajeRecomendaciones = document.getElementById("mensajeRecomendaciones");
        const umbralUnidades = document.getElementById("umbralUnidades");
        const fechaUmbral = document.getElementById("fechaUmbral");
        const filtroTiendaRecomendacion = document.getElementById("filtroTiendaRecomendacion");
        const filtroEstadoRecomendacion = document.getElementById("filtroEstadoRecomendacion");
        const ordenRecomendaciones = document.getElementById("ordenRecomendaciones");
        let recomendacionesCargadas = [];
        const paginadorRecomendaciones = Paginador.crear({
            despuesDe: listaRecomendaciones,
            unidad: "recomendaciones",
            filtros: ["#filtroTiendaRecomendacion", "#filtroEstadoRecomendacion", "#ordenRecomendaciones"],
            alCambiar: () => renderizarRecomendaciones()
        });

        function escaparRecomendacion(valor) {
            return String(valor ?? "").replace(/[&<>"']/g, caracter => ({
                "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
            })[caracter]);
        }

        function formatearFechaRecomendacion(valor) {
            if (!valor) return "-";
            const fecha = new Date(valor);
            return Number.isNaN(fecha.getTime()) ? String(valor) : fecha.toLocaleString("es-CR");
        }

        async function solicitarRecomendaciones(opciones = {}) {
            const respuesta = await fetch(apiRecomendaciones, {
                headers: { "Accept": "application/json", ...(opciones.body ? { "Content-Type": "application/json" } : {}) },
                ...opciones
            });
            const texto = await respuesta.text();
            let resultado;
            try {
                resultado = JSON.parse(texto);
            } catch {
                const detalle = texto.trim()
                    ? ` Respuesta recibida: ${texto.trim().slice(0, 300)}`
                    : " La respuesta de la API está vacía.";
                throw new Error(`La API de recomendaciones respondió con HTTP ${respuesta.status} y no devolvió JSON.${detalle}`);
            }
            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.message || "No fue posible consultar las recomendaciones.");
            }
            return resultado;
        }

        function renderizarRecomendaciones(recomendaciones = recomendacionesCargadas) {
            recomendacionesCargadas = recomendaciones;
            if (!recomendacionesCargadas.length) {
                paginadorRecomendaciones.ocultar();
                listaRecomendaciones.innerHTML = "<p>No hay recomendaciones. Selecciona «Analizar ahora» para revisar las ventas.</p>";
                return;
            }

            const tiendaSeleccionada = filtroTiendaRecomendacion.value;
            const estadoSeleccionado = filtroEstadoRecomendacion.value;
            const ordenarPor = ordenRecomendaciones.value;
            const filtradas = recomendacionesCargadas.filter(recomendacion =>
                (!tiendaSeleccionada || String(recomendacion.id_tienda) === tiendaSeleccionada)
                && (!estadoSeleccionado || recomendacion.estado === estadoSeleccionado)
            );
            const ordenadas = [...filtradas].sort((a, b) => {
                if (ordenarPor === "inventario") {
                    return b.stock_actual - a.stock_actual
                        || a.unidades_vendidas - b.unidades_vendidas
                        || a.producto.localeCompare(b.producto, "es");
                }
                if (ordenarPor === "reciente" || ordenarPor === "antigua") {
                    const diferencia = new Date(b.fecha_generacion) - new Date(a.fecha_generacion);
                    return ordenarPor === "reciente" ? diferencia : -diferencia;
                }
                const prioridadEstado = { PENDIENTE: 0, REVISADA: 1, ACEPTADA: 2, DESCARTADA: 3 };
                return prioridadEstado[a.estado] - prioridadEstado[b.estado]
                    || a.unidades_vendidas - b.unidades_vendidas
                    || new Date(b.fecha_generacion) - new Date(a.fecha_generacion);
            });
            if (!ordenadas.length) {
                paginadorRecomendaciones.paginar([]);
                listaRecomendaciones.innerHTML = "<p>No hay recomendaciones que coincidan con los filtros seleccionados.</p>";
                return;
            }

            const recomendacionesPagina = paginadorRecomendaciones.paginar(ordenadas);
            listaRecomendaciones.innerHTML = recomendacionesPagina.map(recomendacion => {
                const ventasDiarias = recomendacion.ventas_por_dia.length
                    ? `<ul>${recomendacion.ventas_por_dia.map(dia => `<li>${escaparRecomendacion(dia.fecha)}: ${escaparRecomendacion(dia.unidades)} unidades</li>`).join("")}</ul>`
                    : "<p>No se registraron ventas en el periodo.</p>";
                const historial = recomendacion.historial.length
                    ? `<ol>${recomendacion.historial.map(evento => `<li>${escaparRecomendacion(evento.estado_anterior || "Nueva")} → ${escaparRecomendacion(evento.estado_nuevo)} · ${escaparRecomendacion(evento.responsable)} · ${escaparRecomendacion(formatearFechaRecomendacion(evento.fecha))}${evento.comentario ? ` — ${escaparRecomendacion(evento.comentario)}` : ""}</li>`).join("")}</ol>`
                    : "<p>Sin cambios registrados.</p>";
                let acciones = "";
                if (recomendacion.estado === "PENDIENTE") {
                    acciones = `<button type="button" class="boton boton-borde" data-estado="REVISADA" data-id="${recomendacion.id_recomendacion}">Marcar revisada</button>`;
                } else if (recomendacion.estado === "REVISADA") {
                    acciones = `<button type="button" class="boton boton-azul" data-estado="ACEPTADA" data-id="${recomendacion.id_recomendacion}">Aceptar</button>
                        <button type="button" class="boton boton-borde" data-estado="DESCARTADA" data-id="${recomendacion.id_recomendacion}">Descartar</button>`;
                }

                return `<article class="recomendacion-item">
                    <h3>${escaparRecomendacion(recomendacion.producto)} · ${escaparRecomendacion(recomendacion.tienda)}</h3>
                    <div class="recomendacion-meta">
                        <span>Estado: <strong>${escaparRecomendacion(recomendacion.estado)}</strong></span>
                        <span>Categoría: ${escaparRecomendacion(recomendacion.categoria || "Sin categoría")}</span>
                        <span>Ventas (30 días): ${escaparRecomendacion(recomendacion.unidades_vendidas)} de umbral ${escaparRecomendacion(recomendacion.umbral_unidades)}</span>
                        <span>Existencia actual: ${escaparRecomendacion(recomendacion.stock_actual)}</span>
                        <span>Ubicación: ${escaparRecomendacion(recomendacion.ubicaciones || "Sin existencia registrada")}</span>
                        <span>Periodo: ${escaparRecomendacion(recomendacion.periodo_inicio)} a ${escaparRecomendacion(recomendacion.periodo_fin)}</span>
                    </div>
                    <p>La rotación está por debajo del umbral. Revisa si conviene reducir compras o considerar la posible sustitución del producto; el sistema no propone un sustituto.</p>
                    <details><summary>Historial de ventas</summary>${ventasDiarias}</details>
                    <details><summary>Historial de revisión y decisiones</summary>${historial}</details>
                    ${acciones ? `<div class="recomendacion-acciones"><input class="recomendacion-comentario" maxlength="1000" placeholder="Observación / comentario" aria-label="Observación o comentario para ${escaparRecomendacion(recomendacion.producto)}">${acciones}</div>` : ""}
                </article>`;
            }).join("");
        }

        async function cargarRecomendaciones() {
            const resultado = await solicitarRecomendaciones();
            umbralUnidades.value = resultado.data.configuracion.umbral_unidades;
            fechaUmbral.textContent = `Actualizado: ${formatearFechaRecomendacion(resultado.data.configuracion.fecha_actualizacion)}`;
            const tiendaActual = filtroTiendaRecomendacion.value;
            const tiendas = new Map(resultado.data.recomendaciones.map(recomendacion => [
                String(recomendacion.id_tienda),
                recomendacion.tienda
            ]));
            filtroTiendaRecomendacion.innerHTML = '<option value="">Todas las tiendas</option>'
                + [...tiendas.entries()]
                    .sort((a, b) => a[1].localeCompare(b[1], "es"))
                    .map(([id, nombre]) => `<option value="${escaparRecomendacion(id)}">${escaparRecomendacion(nombre)}</option>`)
                    .join("");
            filtroTiendaRecomendacion.value = tiendas.has(tiendaActual) ? tiendaActual : "";
            renderizarRecomendaciones(resultado.data.recomendaciones);
        }

        [filtroTiendaRecomendacion, filtroEstadoRecomendacion, ordenRecomendaciones].forEach(control => {
            control.addEventListener("change", () => renderizarRecomendaciones());
        });

        document.getElementById("generarRecomendaciones").addEventListener("click", async evento => {
            const boton = evento.currentTarget;
            boton.disabled = true;
            mensajeRecomendaciones.textContent = "Analizando ventas confirmadas de los últimos 30 días...";
            try {
                const resultado = await solicitarRecomendaciones({
                    method: "POST",
                    body: JSON.stringify({ accion: "generar" })
                });
                mensajeRecomendaciones.textContent = `Análisis completado. Recomendaciones nuevas: ${resultado.data.creadas}.`;
                await cargarRecomendaciones();
            } catch (error) {
                mensajeRecomendaciones.textContent = error.message;
            } finally {
                boton.disabled = false;
            }
        });

        document.getElementById("formularioUmbral").addEventListener("submit", async evento => {
            evento.preventDefault();
            mensajeRecomendaciones.textContent = "";
            try {
                await solicitarRecomendaciones({
                    method: "POST",
                    body: JSON.stringify({ accion: "umbral", umbral_unidades: umbralUnidades.value })
                });
                mensajeRecomendaciones.textContent = "Umbral guardado.";
                await cargarRecomendaciones();
            } catch (error) {
                mensajeRecomendaciones.textContent = error.message;
            }
        });

        listaRecomendaciones.addEventListener("click", async evento => {
            const boton = evento.target.closest("button[data-estado]");
            if (!boton) return;
            const articulo = boton.closest(".recomendacion-item");
            const comentario = articulo.querySelector(".recomendacion-comentario")?.value || "";
            boton.disabled = true;
            try {
                await solicitarRecomendaciones({
                    method: "POST",
                    body: JSON.stringify({
                        accion: "estado",
                        id_recomendacion: boton.dataset.id,
                        estado: boton.dataset.estado,
                        comentario
                    })
                });
                mensajeRecomendaciones.textContent = "Estado actualizado y registrado en la bitácora.";
                await cargarRecomendaciones();
            } catch (error) {
                mensajeRecomendaciones.textContent = error.message;
                boton.disabled = false;
            }
        });

        cargarRecomendaciones().catch(error => {
            listaRecomendaciones.innerHTML = `<p>${escaparRecomendacion(error.message)}</p>`;
        });
    </script>
</body>
</html>
