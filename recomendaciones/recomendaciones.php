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
        .recomendacion-acciones { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-top:14px; padding-top:14px; border-top:1px solid #e2e8f0; }
        .recomendacion-config { display:flex; flex-wrap:wrap; align-items:end; gap:12px; }
        .recomendacion-config label { display:grid; gap:6px; font-weight:600; }
        .recomendacion-config input, .recomendacion-comentario { min-height:40px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; }
        .recomendacion-lista { display:grid; gap:16px; }
        .recomendacion-item {
            padding:18px; border:1px solid #e2e8f0; border-left:4px solid #cbd5e1;
            border-radius:10px; background:#fff; transition:box-shadow .15s ease;
        }
        .recomendacion-item:hover { box-shadow:0 2px 10px rgba(15,23,42,.06); }
        .recomendacion-item.recomendacion-estado-pendiente { border-left-color:#f59e0b; }
        .recomendacion-item.recomendacion-estado-revisada { border-left-color:#3b82f6; }
        .recomendacion-item.recomendacion-estado-aceptada { border-left-color:#16a34a; }
        .recomendacion-item.recomendacion-estado-descartada { border-left-color:#94a3b8; }
        .recomendacion-encabezado { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:10px; }
        .recomendacion-titulo { display:flex; flex-direction:column; gap:4px; }
        .recomendacion-titulo h3 { margin:2px 0 0; font-size:16px; }
        .recomendacion-subtitulo { margin:0; color:#64748b; font-size:13px; display:flex; flex-wrap:wrap; gap:4px 14px; }
        .recomendacion-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(135px, 1fr)); gap:10px; margin:14px 0; }
        .recomendacion-stat { background:#f8fafc; border-radius:8px; padding:8px 10px; }
        .recomendacion-stat span { display:block; font-size:10.5px; color:#64748b; text-transform:uppercase; letter-spacing:.03em; margin-bottom:2px; }
        .recomendacion-stat strong { font-size:14px; color:#1e293b; }
        .recomendacion-nota { margin:0 0 4px; color:#475569; }
        .recomendacion-item details { margin-top:10px; }
        .recomendacion-item details summary { cursor:pointer; font-weight:600; color:#334155; font-size:13.5px; }
        .recomendacion-item ul, .recomendacion-item ol { padding-left:22px; margin:8px 0 0; }
        .recomendacion-comentario { width:min(100%, 440px); }
        .recomendacion-mensaje { margin:12px 0; min-height:1.4em; }
        .recomendacion-barra {
            display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between;
            gap:14px; margin-bottom:18px;
        }
        .recomendacion-filtros { display:flex; flex-wrap:wrap; gap:12px; }
        .recomendacion-filtros label { display:grid; gap:6px; font-weight:600; font-size:13.5px; }
        .recomendacion-filtros select { min-height:40px; min-width:180px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; }
        .recomendacion-barra-acciones { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .recomendacion-resumen { color:#64748b; font-size:13px; white-space:nowrap; }
        .recomendacion-ia {
            margin-top:14px; padding:14px; border-radius:10px;
            background:linear-gradient(135deg, #eff6ff 0%, #f5f3ff 100%);
            border:1px solid #c7d2fe;
            box-shadow:inset 3px 0 0 0 #6366f1;
        }
        .recomendacion-ia p { white-space:pre-line; margin:0 0 8px; }
        .recomendacion-ia small { color:#64748b; }
        .recomendacion-ia-chip {
            display:inline-flex; align-items:center; gap:5px;
            background:linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color:#fff; font-size:10.5px; font-weight:700; letter-spacing:.03em;
            padding:3px 9px; border-radius:999px; margin-right:8px; vertical-align:middle;
        }
        .recomendacion-ia-chip i { color:#fff; }
        .recomendacion-item .recomendacion-ia summary {
            cursor:pointer; font-weight:700; color:#4338ca; list-style:none; margin-bottom:6px;
        }
        .recomendacion-ia summary::-webkit-details-marker { display:none; }
        .recomendacion-ia summary::after {
            content:"\f078"; font-family:"Font Awesome 6 Free"; font-weight:900;
            font-size:11px; margin-left:8px; display:inline-block; color:#6366f1; transition:transform .15s ease;
        }
        .recomendacion-ia[open] summary::after { transform:rotate(180deg); }
        .recomendacion-ia .recomendacion-ia-titulo { font-weight:700; color:#4338ca; }
        .recomendacion-alternativa { margin-top:14px; padding:14px; border-left:4px solid #0ea5e9; border-radius:8px; background:#f0f9ff; }
        .recomendacion-alternativa p { white-space:pre-line; margin:0 0 8px; }
        @media (max-width:600px) {
            .recomendacion-item { padding:14px; }
            .recomendacion-barra { align-items:stretch; }
        }
    </style>
</head>
<body class="pagina-app">
    <?php vmBarraLateral(); ?>
    <div class="columna-principal">
        <?php vmBarraSuperior(); ?>
        <main class="contenido-app">
            <section class="encabezado-pagina">
                <div>
                    <h1><i class="fa-solid fa-wand-magic-sparkles"></i> Recomendaciones de productos</h1>
                    <p>Análisis por tienda de las unidades vendidas en los últimos 30 días. Las sugerencias no cambian el catálogo ni el inventario.</p>
                </div>
                <button type="button" class="boton boton-azul" id="generarRecomendaciones">
                    <i class="fa-solid fa-arrows-rotate"></i> Analizar ahora
                </button>
            </section>

            <p id="mensajeRecomendaciones" class="recomendacion-mensaje" role="status" aria-live="polite"></p>

            <section class="grilla-estadisticas" aria-label="Resumen de recomendaciones">
                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Total de sugerencias</div>
                            <div class="tarjeta-estadistica-valor" id="totalRecomendaciones">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Registradas en el sistema</div>
                </article>
                <article class="tarjeta-estadistica borde-naranja">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-clock"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Pendientes</div>
                            <div class="tarjeta-estadistica-valor" id="recomendacionesPendientes">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Esperan revisión</div>
                </article>
                <article class="tarjeta-estadistica borde-morado">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Revisadas</div>
                            <div class="tarjeta-estadistica-valor" id="recomendacionesRevisadas">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Atendidas, listas para decisión</div>
                </article>
            </section>

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
                <p>Flujo: agrega una observación y marca la sugerencia como revisada. Después aparecerá en el filtro «Revisada», donde podrás aceptarla o descartarla. Para descartarla, el comentario es obligatorio.</p>
                <div class="recomendacion-barra">
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
                    <div class="recomendacion-barra-acciones">
                        <span id="resumenRecomendaciones" class="recomendacion-resumen"></span>
                        <button type="button" class="boton boton-borde" id="alternarVistaRecomendaciones">
                            <i class="fa-solid fa-list"></i> Mostrar todas
                        </button>
                    </div>
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
        const totalRecomendaciones = document.getElementById("totalRecomendaciones");
        const recomendacionesPendientes = document.getElementById("recomendacionesPendientes");
        const recomendacionesRevisadas = document.getElementById("recomendacionesRevisadas");
        const filtroTiendaRecomendacion = document.getElementById("filtroTiendaRecomendacion");
        const filtroEstadoRecomendacion = document.getElementById("filtroEstadoRecomendacion");
        const ordenRecomendaciones = document.getElementById("ordenRecomendaciones");
        const resumenRecomendaciones = document.getElementById("resumenRecomendaciones");
        const botonAlternarVista = document.getElementById("alternarVistaRecomendaciones");
        let mostrandoTodas = false;
        const paginadorRecomendaciones = Paginador.crear({
            despuesDe: listaRecomendaciones,
            unidad: "recomendaciones",
            alCambiar: pagina => cargarRecomendaciones(pagina).catch(error => {
                mensajeRecomendaciones.textContent = error.message;
            })
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

        function formatearNumeroRecomendacion(valor) {
            return Number(valor ?? 0).toLocaleString("es-CR");
        }

        function actualizarBotonVista() {
            botonAlternarVista.innerHTML = mostrandoTodas
                ? '<i class="fa-solid fa-table-list"></i> Volver a paginar'
                : '<i class="fa-solid fa-list"></i> Mostrar todas';
        }

        async function solicitarRecomendaciones(opciones = {}) {
            const { query, ...configuracion } = opciones;
            const url = query ? `${apiRecomendaciones}?${query}` : apiRecomendaciones;
            const respuesta = await fetch(url, {
                headers: { "Accept": "application/json", ...(opciones.body ? { "Content-Type": "application/json" } : {}) },
                ...configuracion
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

        function tarjetaRecomendacion(recomendacion) {
            const ventasDiarias = recomendacion.ventas_por_dia.length
                ? `<ul>${recomendacion.ventas_por_dia.map(dia => `<li>${escaparRecomendacion(dia.fecha)}: ${escaparRecomendacion(dia.unidades)} unidades</li>`).join("")}</ul>`
                : "<p>No se registraron ventas en el periodo.</p>";
            const historial = recomendacion.historial.length
                ? `<ol>${recomendacion.historial.map(evento => `<li>${escaparRecomendacion(evento.estado_anterior || "Nueva")} → ${escaparRecomendacion(evento.estado_nuevo)} · ${escaparRecomendacion(evento.responsable)} · ${escaparRecomendacion(formatearFechaRecomendacion(evento.fecha))}${evento.comentario ? ` — ${escaparRecomendacion(evento.comentario)}` : ""}</li>`).join("")}</ol>`
                : "<p>Sin cambios registrados.</p>";
            const explicacionIA = recomendacion.ia_explicacion
                ? `<details class="recomendacion-ia" open>
                    <summary><span class="recomendacion-ia-chip"><i class="fa-solid fa-wand-magic-sparkles"></i> IA</span>Explicación generada</summary>
                    <p>${escaparRecomendacion(recomendacion.ia_explicacion)}</p>
                    <small>Modelo: ${escaparRecomendacion(recomendacion.ia_modelo || "-")} · Generada: ${escaparRecomendacion(formatearFechaRecomendacion(recomendacion.ia_fecha_generacion))}</small>
                    <div><button type="button" class="boton boton-borde" data-ia="${recomendacion.id_recomendacion}">Regenerar explicación</button></div>
                </details>`
                : `<div class="recomendacion-ia">
                    <p><span class="recomendacion-ia-chip"><i class="fa-solid fa-wand-magic-sparkles"></i> IA</span>La IA puede explicar los datos de esta sugerencia. La decisión sigue siendo tuya.</p>
                    <button type="button" class="boton boton-borde" data-ia="${recomendacion.id_recomendacion}">Generar explicación con IA</button>
                </div>`;

            let acciones = "";
            const textoComentario = recomendacion.estado === "REVISADA"
                ? "Comentario (obligatorio al descartar)"
                : "Observación para registrar la revisión";
            if (recomendacion.estado === "PENDIENTE") {
                acciones = `<button type="button" class="boton boton-borde" data-estado="REVISADA" data-id="${recomendacion.id_recomendacion}">Marcar revisada</button>`;
            } else if (recomendacion.estado === "REVISADA") {
                acciones = `<button type="button" class="boton boton-azul" data-estado="ACEPTADA" data-id="${recomendacion.id_recomendacion}">Aceptar</button>
                    <button type="button" class="boton boton-borde" data-estado="DESCARTADA" data-id="${recomendacion.id_recomendacion}">Descartar</button>`;
            }

            const alternativaHtml = recomendacion.alternativas.length
                ? recomendacion.alternativas.map(alternativa => {
                    const accionesAlternativa = alternativa.estado === "PENDIENTE"
                        ? `<div class="recomendacion-acciones">
                            <input class="recomendacion-comentario" data-alternativa-comentario maxlength="1000" placeholder="Comentario (obligatorio al descartar)" aria-label="Comentario sobre la alternativa ${escaparRecomendacion(alternativa.producto_sugerido)}">
                            <button type="button" class="boton boton-azul" data-alternativa-estado="ACEPTADA" data-alternativa-id="${alternativa.id_alternativa}">Aceptar alternativa</button>
                            <button type="button" class="boton boton-borde" data-alternativa-estado="DESCARTADA" data-alternativa-id="${alternativa.id_alternativa}">Descartar alternativa</button>
                           </div>`
                        : `<p>Decisión: <strong>${escaparRecomendacion(alternativa.estado)}</strong>${alternativa.comentario_decision ? ` — ${escaparRecomendacion(alternativa.comentario_decision)}` : ""} · ${escaparRecomendacion(formatearFechaRecomendacion(alternativa.fecha_actualizacion))}</p>`;
                    return `<div class="recomendacion-alternativa">
                        <strong><i class="fa-solid fa-right-left"></i> Producto alternativo sugerido: ${escaparRecomendacion(alternativa.producto_sugerido)}</strong>
                        <p>${escaparRecomendacion(alternativa.justificacion)}</p>
                        <div class="recomendacion-stats">
                            <div class="recomendacion-stat"><span>Rotación sugerido</span><strong>${escaparRecomendacion(alternativa.unidades_vendidas_sugerido)}</strong></div>
                            <div class="recomendacion-stat"><span>Rotación original</span><strong>${escaparRecomendacion(alternativa.unidades_vendidas_original)}</strong></div>
                            <div class="recomendacion-stat"><span>Existencia sugerido</span><strong>${escaparRecomendacion(alternativa.stock_actual_sugerido)}</strong></div>
                        </div>
                        ${accionesAlternativa}
                    </div>`;
                }).join("")
                : `<p class="recomendacion-nota">No se encontró un producto alternativo con mejor rotación y existencia en la misma categoría y tienda.</p>`;

            const insignias = { PENDIENTE: "badge-naranja", REVISADA: "badge-azul", ACEPTADA: "badge-verde", DESCARTADA: "badge-gris" };
            const etiquetas = { PENDIENTE: "Pendiente", REVISADA: "Revisada", ACEPTADA: "Aceptada", DESCARTADA: "Descartada" };

            return `<article class="recomendacion-item recomendacion-estado-${recomendacion.estado.toLowerCase()}">
                <header class="recomendacion-encabezado">
                    <div class="recomendacion-titulo">
                        <span class="badge ${insignias[recomendacion.estado] || "badge-gris"}">${etiquetas[recomendacion.estado] || recomendacion.estado}</span>
                        <h3>${escaparRecomendacion(recomendacion.producto)}</h3>
                        <p class="recomendacion-subtitulo">
                            <span><i class="fa-solid fa-store"></i> ${escaparRecomendacion(recomendacion.tienda)}</span>
                            <span><i class="fa-solid fa-tag"></i> ${escaparRecomendacion(recomendacion.categoria || "Sin categoría")}</span>
                        </p>
                    </div>
                </header>
                <div class="recomendacion-stats">
                    <div class="recomendacion-stat"><span>Ventas (30 días)</span><strong>${escaparRecomendacion(recomendacion.unidades_vendidas)} / ${escaparRecomendacion(recomendacion.umbral_unidades)}</strong></div>
                    <div class="recomendacion-stat"><span>Existencia actual</span><strong>${escaparRecomendacion(recomendacion.stock_actual)}</strong></div>
                    <div class="recomendacion-stat"><span>Ubicación</span><strong>${escaparRecomendacion(recomendacion.ubicaciones || "Sin existencia")}</strong></div>
                    <div class="recomendacion-stat"><span>Periodo</span><strong>${escaparRecomendacion(recomendacion.periodo_inicio)} a ${escaparRecomendacion(recomendacion.periodo_fin)}</strong></div>
                </div>
                <p class="recomendacion-nota">La rotación está por debajo del umbral. Revisa si conviene reducir compras, sustituir o eliminar el producto; el sistema no ejecuta ninguna acción automáticamente.</p>
                ${alternativaHtml}
                <details><summary>Ventas por día</summary>${ventasDiarias}</details>
                <details><summary>Historial de revisión y decisiones</summary>${historial}</details>
                ${explicacionIA}
                ${acciones ? `<div class="recomendacion-acciones"><input class="recomendacion-comentario" maxlength="1000" placeholder="${textoComentario}" aria-label="${textoComentario} para ${escaparRecomendacion(recomendacion.producto)}">${acciones}</div>` : ""}
            </article>`;
        }

        function renderizarRecomendaciones(recomendaciones) {
            if (!recomendaciones.length) {
                const estadoSeleccionado = filtroEstadoRecomendacion.value;
                listaRecomendaciones.innerHTML = estadoSeleccionado
                    ? `<p>No hay recomendaciones con estado «${escaparRecomendacion(filtroEstadoRecomendacion.selectedOptions[0].textContent)}» que coincidan con los filtros seleccionados.</p>`
                    : "<p>No hay recomendaciones. Selecciona «Analizar ahora» para revisar las ventas.</p>";
                return;
            }
            listaRecomendaciones.innerHTML = recomendaciones.map(tarjetaRecomendacion).join("");
        }

        async function cargarRecomendaciones(pagina = 1) {
            const parametros = new URLSearchParams();
            if (filtroEstadoRecomendacion.value) parametros.set("estado", filtroEstadoRecomendacion.value);
            if (filtroTiendaRecomendacion.value) parametros.set("tienda", filtroTiendaRecomendacion.value);
            parametros.set("orden", ordenRecomendaciones.value);
            if (mostrandoTodas) {
                parametros.set("todas", "1");
            } else {
                parametros.set("pagina", String(pagina));
            }

            const resultado = await solicitarRecomendaciones({ query: parametros.toString() });

            umbralUnidades.value = resultado.data.configuracion.umbral_unidades;
            fechaUmbral.textContent = `Actualizado: ${formatearFechaRecomendacion(resultado.data.configuracion.fecha_actualizacion)}`;
            totalRecomendaciones.textContent = resultado.data.conteos.total;
            recomendacionesPendientes.textContent = resultado.data.conteos.pendientes;
            recomendacionesRevisadas.textContent = resultado.data.conteos.revisadas;
            const conteosPorEstado = {
                PENDIENTE: resultado.data.conteos.pendientes,
                REVISADA: resultado.data.conteos.revisadas,
                ACEPTADA: resultado.data.conteos.aceptadas,
                DESCARTADA: resultado.data.conteos.descartadas
            };
            [...filtroEstadoRecomendacion.options].forEach(opcion => {
                if (!opcion.value) return;
                const cantidad = conteosPorEstado[opcion.value];
                const nombre = opcion.dataset.nombre || opcion.textContent.replace(/\s*\(\d+\)$/, "");
                opcion.dataset.nombre = nombre;
                opcion.textContent = `${nombre} (${cantidad})`;
            });

            const tiendaActual = filtroTiendaRecomendacion.value;
            filtroTiendaRecomendacion.innerHTML = '<option value="">Todas las tiendas</option>'
                + resultado.data.tiendas
                    .map(tienda => `<option value="${tienda.id_tienda}">${escaparRecomendacion(tienda.nombre)}</option>`)
                    .join("");
            filtroTiendaRecomendacion.value = tiendaActual;

            const { total, pagina: paginaActual, por_pagina: porPagina, todas } = resultado.data.paginacion;
            const cantidadMostrada = resultado.data.recomendaciones.length;
            if (todas) {
                paginadorRecomendaciones.ocultar();
                resumenRecomendaciones.textContent = total
                    ? `Mostrando las ${formatearNumeroRecomendacion(cantidadMostrada)} de ${formatearNumeroRecomendacion(total)} recomendaciones`
                    : "";
            } else if (!total) {
                paginadorRecomendaciones.ocultar();
                resumenRecomendaciones.textContent = "";
            } else {
                paginadorRecomendaciones.actualizar(total, paginaActual);
                const desde = (paginaActual - 1) * porPagina + 1;
                const hasta = desde + cantidadMostrada - 1;
                resumenRecomendaciones.textContent =
                    `Mostrando del ${formatearNumeroRecomendacion(desde)} al ${formatearNumeroRecomendacion(hasta)} de ${formatearNumeroRecomendacion(total)} recomendaciones`;
            }

            renderizarRecomendaciones(resultado.data.recomendaciones);
        }

        [filtroTiendaRecomendacion, filtroEstadoRecomendacion, ordenRecomendaciones].forEach(control => {
            control.addEventListener("change", () => {
                paginadorRecomendaciones.reiniciar();
                cargarRecomendaciones(1).catch(error => {
                    mensajeRecomendaciones.textContent = error.message;
                    listaRecomendaciones.innerHTML = `<p>${escaparRecomendacion(error.message)}</p>`;
                });
            });
        });

        botonAlternarVista.addEventListener("click", () => {
            mostrandoTodas = !mostrandoTodas;
            actualizarBotonVista();
            paginadorRecomendaciones.reiniciar();
            cargarRecomendaciones(1).catch(error => {
                mensajeRecomendaciones.textContent = error.message;
                listaRecomendaciones.innerHTML = `<p>${escaparRecomendacion(error.message)}</p>`;
            });
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
                mensajeRecomendaciones.textContent =
                    `Análisis completado. Recomendaciones nuevas: ${resultado.data.creadas} · Alternativas sugeridas: ${resultado.data.alternativas_creadas ?? 0}.`;
                paginadorRecomendaciones.reiniciar();
                await cargarRecomendaciones(1);
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
                await cargarRecomendaciones(paginadorRecomendaciones.pagina);
            } catch (error) {
                mensajeRecomendaciones.textContent = error.message;
            }
        });

        listaRecomendaciones.addEventListener("click", async evento => {
            const botonIA = evento.target.closest("button[data-ia]");
            if (botonIA) {
                botonIA.disabled = true;
                botonIA.textContent = "Generando explicación...";
                mensajeRecomendaciones.textContent = "Solicitando explicación a la IA (Groq)...";
                try {
                    await solicitarRecomendaciones({
                        method: "POST",
                        body: JSON.stringify({
                            accion: "explicar_ia",
                            id_recomendacion: botonIA.dataset.ia
                        })
                    });
                    mensajeRecomendaciones.textContent = "Explicación generada y guardada.";
                    await cargarRecomendaciones(paginadorRecomendaciones.pagina);
                } catch (error) {
                    mensajeRecomendaciones.textContent = error.message;
                    botonIA.disabled = false;
                    botonIA.textContent = "Reintentar explicación con IA";
                }
                return;
            }

            const botonAlternativa = evento.target.closest("button[data-alternativa-estado]");
            if (botonAlternativa) {
                const contenedorAlternativa = botonAlternativa.closest(".recomendacion-alternativa");
                const campoComentarioAlt = contenedorAlternativa?.querySelector("[data-alternativa-comentario]");
                const comentarioAlt = campoComentarioAlt?.value.trim() || "";
                if (botonAlternativa.dataset.alternativaEstado === "DESCARTADA" && !comentarioAlt) {
                    mensajeRecomendaciones.textContent = "Agrega un comentario para explicar por qué descartas esta alternativa.";
                    campoComentarioAlt?.focus();
                    return;
                }
                botonAlternativa.disabled = true;
                try {
                    await solicitarRecomendaciones({
                        method: "POST",
                        body: JSON.stringify({
                            accion: "alternativa_decision",
                            id_alternativa: botonAlternativa.dataset.alternativaId,
                            estado: botonAlternativa.dataset.alternativaEstado,
                            comentario: comentarioAlt
                        })
                    });
                    mensajeRecomendaciones.textContent = botonAlternativa.dataset.alternativaEstado === "ACEPTADA"
                        ? "Alternativa aceptada y decisión registrada."
                        : "Alternativa descartada y motivo registrado.";
                    await cargarRecomendaciones(paginadorRecomendaciones.pagina);
                } catch (error) {
                    mensajeRecomendaciones.textContent = error.message;
                    botonAlternativa.disabled = false;
                }
                return;
            }

            const boton = evento.target.closest("button[data-estado]");
            if (!boton) return;
            const articulo = boton.closest(".recomendacion-item");
            const campoComentario = articulo.querySelector(".recomendacion-comentario");
            const comentario = campoComentario?.value.trim() || "";
            if (boton.dataset.estado === "REVISADA" && !comentario) {
                mensajeRecomendaciones.textContent = "Agrega una observación para registrar la revisión.";
                campoComentario?.focus();
                return;
            }
            if (boton.dataset.estado === "DESCARTADA" && !comentario) {
                mensajeRecomendaciones.textContent = "Agrega un comentario para explicar por qué descartas esta recomendación.";
                campoComentario?.focus();
                return;
            }
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
                if (boton.dataset.estado === "REVISADA") {
                    filtroEstadoRecomendacion.value = "REVISADA";
                    mensajeRecomendaciones.textContent = "Recomendación revisada y registrada. Se muestran las revisadas para que puedas aceptarla o descartarla.";
                    paginadorRecomendaciones.reiniciar();
                    await cargarRecomendaciones(1);
                } else {
                    if (boton.dataset.estado === "ACEPTADA") {
                        mensajeRecomendaciones.textContent = "Recomendación aceptada y decisión registrada en el historial y la bitácora.";
                    } else {
                        mensajeRecomendaciones.textContent = "Recomendación descartada y motivo registrado en el historial y la bitácora.";
                    }
                    await cargarRecomendaciones(paginadorRecomendaciones.pagina);
                }
            } catch (error) {
                mensajeRecomendaciones.textContent = error.message;
                boton.disabled = false;
            }
        });

        actualizarBotonVista();
        cargarRecomendaciones(1).catch(error => {
            listaRecomendaciones.innerHTML = `<p>${escaparRecomendacion(error.message)}</p>`;
        });
    </script>
</body>
</html>
