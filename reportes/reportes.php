<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";


if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

// Los reportes no están disponibles para el Dependiente.
if (esDependiente()) {
    header("Location: ../dashboard/dashboard.php");
    exit;
}


/*
 * Ubicación asignada al usuario (HU-19), leída de la base en cada carga.
 */

/*
 * Evitar que el navegador muestre el dashboard
 * después de cerrar sesión utilizando la caché.
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Reportes del sistema Vending Mini Market"
    >

    <title>Reportes | Vending Mini Market</title>

    <?php vmEstilosGlobales(); ?>

</head>

<body class="pagina-app">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->

    <?php vmBarraLateral(); ?>

    <!-- ==========================================
         COLUMNA PRINCIPAL
    =========================================== -->

    <div class="columna-principal">

        <!-- ==========================================
             BARRA SUPERIOR
        =========================================== -->

        <?php vmBarraSuperior(); ?>

        <!-- ==========================================
             CONTENIDO
        =========================================== -->

        <main class="contenido-app">

            <!-- Encabezado -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Reportes</h1>

                    <p>
                        Ventas, utilidad, inventario, rotación y pérdidas
                        por tienda y periodo, para apoyar la toma de decisiones.
                    </p>

                </div>

                <div class="acciones-encabezado-reportes">

                    <button
                        type="button"
                        id="botonImprimirReporte"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-print"></i>
                        Imprimir
                    </button>

                </div>

            </div>

            <!-- Mensaje -->

            <div
                id="mensajeReportes"
                class="mensaje-producto"
                aria-live="polite"
                hidden
            ></div>

            <!-- ==========================================
                 TIPO DE REPORTE Y FILTROS
            =========================================== -->

            <nav class="pestanas-reportes" role="tablist" aria-label="Tipo de reporte">

                <button type="button" class="pestana-reporte activa" role="tab" aria-selected="true" data-reporte="ventas">
                    <i class="fa-solid fa-cash-register"></i>
                    <span>Ventas</span>
                </button>

                <?php if (esAdministradorOGerente()): ?>
                    <button type="button" class="pestana-reporte" role="tab" aria-selected="false" data-reporte="utilidad">
                        <i class="fa-solid fa-sack-dollar"></i>
                        <span>Utilidad</span>
                    </button>
                <?php endif; ?>

                <button type="button" class="pestana-reporte" role="tab" aria-selected="false" data-reporte="inventario">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Inventario</span>
                </button>

                <button type="button" class="pestana-reporte" role="tab" aria-selected="false" data-reporte="rotacion">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Rotación y pérdidas</span>
                </button>

                <button type="button" class="pestana-reporte" role="tab" aria-selected="false" data-reporte="cierre">
                    <i class="fa-solid fa-lock"></i>
                    <span>Cierre diario</span>
                </button>

                <?php if (esAdministrador()): ?>
                    <span class="pestanas-divisor" aria-hidden="true"></span>

                    <button type="button" class="pestana-reporte pestana-herramienta" role="tab" aria-selected="false" data-vista="resumen">
                        <i class="fa-solid fa-envelope-open-text"></i>
                        <span>Resumen programado</span>
                    </button>
                <?php endif; ?>

            </nav>

            <div id="vistaReportes">

            <section class="panel panel-filtros-reportes">

                <div class="panel-encabezado">
                    <div>
                        <h2 id="tituloFiltrosReporte">Filtros del reporte</h2>
                        <p class="descripcion-panel" id="descripcionFiltrosReporte">Elige el periodo, la tienda y los criterios; el reporte se actualiza al generarlo.</p>
                    </div>
                </div>

                <form id="formularioReporte">

                    <div class="atajos-periodo-reporte" data-filtro="periodo">
                        <span>Periodo rápido</span>
                        <button type="button" class="atajo-periodo" data-atajo="hoy">Hoy</button>
                        <button type="button" class="atajo-periodo" data-atajo="7dias">Últimos 7 días</button>
                        <button type="button" class="atajo-periodo" data-atajo="mes">Este mes</button>
                        <button type="button" class="atajo-periodo" data-atajo="mesAnterior">Mes anterior</button>
                    </div>

                    <div class="cuadricula-filtros-reportes">

                        <div class="grupo-formulario" data-filtro="tienda">
                            <label id="etiquetaTiendaReporte">Tienda</label>
                            <div id="tiendaReporte" class="selector-multiple" aria-labelledby="etiquetaTiendaReporte"></div>
                        </div>

                        <div class="grupo-formulario" data-filtro="periodo">
                            <label for="fechaInicioReporte">Fecha inicial</label>
                            <input type="date" id="fechaInicioReporte" name="fechaInicioReporte" required>
                        </div>

                        <div class="grupo-formulario" data-filtro="periodo">
                            <label for="fechaFinReporte">Fecha final</label>
                            <input type="date" id="fechaFinReporte" name="fechaFinReporte" required>
                        </div>

                        <div class="grupo-formulario" data-filtro="dia">
                            <label for="fechaCierreReporte">Día a cerrar</label>
                            <input type="date" id="fechaCierreReporte" name="fechaCierreReporte">
                        </div>

                        <div class="grupo-formulario" data-filtro="proveedor">
                            <label id="etiquetaProveedorReporte">Proveedor</label>
                            <div id="proveedorReporte" class="selector-multiple" aria-labelledby="etiquetaProveedorReporte"></div>
                        </div>

                        <div class="grupo-formulario" data-filtro="productos">
                            <label id="etiquetaProductosReporte">Productos del proveedor</label>
                            <div id="productosReporte" class="selector-multiple" aria-labelledby="etiquetaProductosReporte"></div>
                        </div>

                        <div class="grupo-formulario" data-filtro="estado">
                            <label for="estadoReporte">Estado del stock</label>
                            <select id="estadoReporte" name="estadoReporte">
                                <option value="">Todos</option>
                                <option value="CRITICO">Solo críticos</option>
                                <option value="AGOTADO">Agotados</option>
                                <option value="BAJO_MINIMO">Bajo mínimo</option>
                                <option value="NEGATIVO">Negativos</option>
                                <option value="NORMAL">Normales</option>
                            </select>
                        </div>

                    </div>

                    <div class="acciones-formulario-reporte">
                        <button type="button" id="botonLimpiarReporte" class="boton boton-borde">
                            <i class="fa-solid fa-eraser"></i>
                            Limpiar
                        </button>
                        <button type="submit" class="boton boton-azul">
                            <i class="fa-solid fa-chart-column"></i>
                            Generar reporte
                        </button>
                        <button type="button" id="botonExportarReporte" class="boton boton-borde" disabled
                                title="Genera el reporte para poder exportarlo">
                            <i class="fa-solid fa-file-pdf"></i>
                            Exportar PDF
                        </button>
                    </div>

                </form>

            </section>

            <!-- ==========================================
                 RESULTADO (lo dibuja js/reportes.js)
            =========================================== -->

            <div id="areaReporte" aria-live="polite">
                <div class="estado-vacio-productos">
                    <i class="fa-solid fa-chart-line"></i>
                    <h3>Cargando reporte…</h3>
                </div>
            </div>

            </div>


            <?php if (esAdministrador()): ?>
                <div id="vistaResumen" hidden>
                <section class="panel" aria-labelledby="tituloResumenInventario">
                    <div class="panel-encabezado">
                        <div>
                            <h2 id="tituloResumenInventario">Resumen periódico del inventario</h2>
                            <p class="descripcion-panel">
                                Configura el envío diario o semanal de existencias críticas, alertas pendientes
                                y movimientos confirmados del periodo.
                            </p>
                        </div>
                    </div>

                    <form id="formularioResumenInventario">
                        <div class="configuracion-resumen-grid">
                            <div class="grupo-formulario">
                                <label for="correoResumenInventario">Correo de destino</label>
                                <input
                                    type="email"
                                    id="correoResumenInventario"
                                    maxlength="254"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="grupo-formulario">
                                <label for="frecuenciaResumenInventario">Frecuencia</label>
                                <select id="frecuenciaResumenInventario" required>
                                    <option value="DIARIA">Diaria</option>
                                    <option value="SEMANAL">Semanal</option>
                                </select>
                            </div>

                            <div class="grupo-formulario" id="grupoDiaResumenInventario" hidden>
                                <label for="diaResumenInventario">Día de envío</label>
                                <select id="diaResumenInventario">
                                    <option value="1">Lunes</option>
                                    <option value="2">Martes</option>
                                    <option value="3">Miércoles</option>
                                    <option value="4">Jueves</option>
                                    <option value="5">Viernes</option>
                                    <option value="6">Sábado</option>
                                    <option value="7">Domingo</option>
                                </select>
                            </div>

                            <div class="grupo-formulario">
                                <label for="horaResumenInventario">Hora de envío</label>
                                <input type="time" id="horaResumenInventario" value="07:00" required>
                            </div>
                        </div>

                        <label class="opcion-resumen-inventario" for="habilitadoResumenInventario">
                            <input type="checkbox" id="habilitadoResumenInventario">
                            Activar el envío automático
                        </label>

                        <p class="descripcion-panel">
                            La tarea del servidor debe ejecutar
                            <code>scripts/ejecutar_resumen_inventario.php</code> cada 5 minutos.
                            El sistema enviará el resumen cuando llegue la próxima ejecución programada.
                            Los horarios se interpretan en la zona horaria de Costa Rica.
                        </p>

                        <div
                            id="mensajeResumenInventario"
                            class="mensaje-producto"
                            aria-live="polite"
                            hidden
                        ></div>

                        <div class="acciones-formulario-reporte">
                            <button type="submit" class="boton boton-azul">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Guardar configuración
                            </button>
                        </div>
                    </form>

                    <div class="resumen-programacion-inventario">
                        <strong>Próximo envío:</strong>
                        <span id="proximaEjecucionResumenInventario">Sin programar</span>
                    </div>

                    <h3>Historial de envíos</h3>
                    <div class="contenedor-tabla" id="contenedorHistorialResumenInventario">
                        <table class="tabla-datos">
                            <caption class="texto-solo-lectores">Historial de ejecuciones del resumen periódico</caption>
                            <thead>
                                <tr>
                                    <th>Fecha de ejecución</th>
                                    <th>Periodo</th>
                                    <th>Responsable</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody id="historialResumenInventario">
                                <tr><td colspan="6">Cargando historial…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
                </div>
            <?php endif; ?>

        </main>

    </div>


    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/vending-comun.js"></script>
    <script src="../js/reportes.js"></script>

</body>
</html>