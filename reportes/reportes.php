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

                    <button
                        type="button"
                        id="botonExportarReporte"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-pdf"></i>
                        Exportar PDF
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

                <?php if (esAdministradorOGerente()): ?>
                    <span class="pestanas-divisor" aria-hidden="true"></span>

                    <button type="button" class="pestana-reporte pestana-herramienta" role="tab" aria-selected="false" data-vista="conciliacion">
                        <i class="fa-solid fa-scale-balanced"></i>
                        <span>Conciliación</span>
                    </button>
                <?php endif; ?>

                <?php if (esAdministrador()): ?>
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
                            <label for="tiendaReporte">Tienda</label>
                            <select id="tiendaReporte" name="tiendaReporte"></select>
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

                        <div class="grupo-formulario" data-filtro="busqueda">
                            <label for="busquedaReporte">Producto</label>
                            <input type="search" id="busquedaReporte" name="busquedaReporte" placeholder="Nombre o código de barras" autocomplete="off">
                        </div>

                        <div class="grupo-formulario" data-filtro="categoria">
                            <label for="categoriaReporte">Categoría</label>
                            <select id="categoriaReporte" name="categoriaReporte"></select>
                        </div>

                        <div class="grupo-formulario" data-filtro="proveedor">
                            <label for="proveedorReporte">Proveedor</label>
                            <select id="proveedorReporte" name="proveedorReporte"></select>
                        </div>

                        <div class="grupo-formulario" data-filtro="ubicacion">
                            <label for="ubicacionReporte">Ubicación</label>
                            <select id="ubicacionReporte" name="ubicacionReporte"></select>
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

            <?php if (esAdministradorOGerente()): ?>
                <div id="vistaConciliacion" hidden>
                <section class="panel panel-conciliacion" aria-labelledby="tituloConciliacion">
                    <div class="panel-encabezado">
                        <div>
                            <h2 id="tituloConciliacion">Conciliación de ventas e inventario</h2>
                            <p class="descripcion-panel">
                                Compara las ventas manuales con sus salidas de inventario y los saldos actuales.
                                Las importaciones Nayax aún no se incluyen porque sus filas no se almacenan en el sistema.
                            </p>
                        </div>
                    </div>

                    <form id="formularioConciliacion">
                        <div class="cuadricula-filtros-reportes">
                            <div class="grupo-formulario">
                                <label for="tiendaConciliacion">Tienda</label>
                                <select id="tiendaConciliacion" name="id_tienda">
                                    <option value="">Todas las tiendas</option>
                                </select>
                            </div>
                            <div class="grupo-formulario">
                                <label for="fechaInicioConciliacion">Fecha inicial</label>
                                <input type="date" id="fechaInicioConciliacion" required>
                            </div>
                            <div class="grupo-formulario">
                                <label for="fechaFinConciliacion">Fecha final</label>
                                <input type="date" id="fechaFinConciliacion" required>
                            </div>
                            <div class="grupo-formulario grupo-boton-conciliacion">
                                <label>&nbsp;</label>
                                <button type="submit" class="boton boton-azul">
                                    <i class="fa-solid fa-scale-balanced"></i>
                                    Conciliar periodo
                                </button>
                            </div>
                        </div>
                    </form>

                    <p id="mensajeConciliacion" class="mensaje-producto" aria-live="polite" hidden></p>

                    <div id="resumenConciliacion" class="resumen-conciliacion" hidden>
                        <span id="periodoConciliacion"></span>
                        <span id="cantidadDiferenciasConciliacion"></span>
                        <span id="utilidadConciliacion"></span>
                        <button type="button" id="botonExportarConciliacion" class="boton boton-borde">
                            <i class="fa-solid fa-file-export"></i>
                            Exportar resultados
                        </button>
                    </div>

                    <div id="contenedorConciliacion" class="contenedor-tabla" hidden>
                        <table class="tabla-datos tabla-conciliacion">
                            <caption class="texto-solo-lectores">Diferencias de ventas e inventario</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Producto</th>
                                    <th scope="col">Tienda / ubicación</th>
                                    <th scope="col">Vendido</th>
                                    <th scope="col">Salida vinculada</th>
                                    <th scope="col">Diferencia venta / salida</th>
                                    <th scope="col">Existencia actual</th>
                                    <th scope="col">Último saldo de movimiento</th>
                                    <th scope="col">Diferencia de existencia</th>
                                    <th scope="col">Utilidad estimada</th>
                                    <th scope="col">Observación y ajuste autorizado</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoConciliacion"></tbody>
                        </table>
                    </div>

                    <div id="mensajeSinDatosConciliacion" class="estado-vacio-productos" hidden>
                        <i class="fa-solid fa-circle-check"></i>
                        <h3>Sin ventas manuales para conciliar</h3>
                        <p>No se encontraron ventas en las fechas seleccionadas.</p>
                    </div>
                </section>
                </div>
            <?php endif; ?>

        </main>

    </div>


    <!-- ==========================================
         MODAL: EXPORTAR REPORTE A PDF
    =========================================== -->

    <div id="modalExportarReporte" class="modal-producto" hidden>

        <div class="modal-producto-fondo" data-cerrar-modal-exportar-reporte></div>

        <section
            class="modal-producto-contenido modal-exportar-reporte"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalExportarReporte"
        >

            <header class="exportar-encabezado">
                <div class="exportar-encabezado-icono"><i class="fa-solid fa-file-pdf"></i></div>
                <div>
                    <h2 id="tituloModalExportarReporte">Exportar reporte a PDF</h2>
                    <p>Elige el reporte, el periodo y las tiendas. Cada tipo se descarga con su propio diseño.</p>
                </div>
                <button type="button" class="boton-cerrar-modal" id="botonCerrarExportarReporte" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <form id="formularioExportarReporte" novalidate>

                <section class="exportar-seccion">
                    <h3><span>1</span> Tipo de reporte</h3>
                    <div class="exportar-tipos" id="tiposExportarReporte" role="radiogroup" aria-label="Tipo de reporte"></div>
                </section>

                <section class="exportar-seccion" id="seccionPeriodoExportar">
                    <h3><span>2</span> Periodo</h3>

                    <div data-exportar="periodo">
                        <div class="exportar-modos" role="radiogroup" aria-label="Forma de elegir el periodo">
                            <label class="opcion-exportar"><input type="radio" name="modoPeriodoExportar" value="mes" checked><span>Por mes</span></label>
                            <label class="opcion-exportar"><input type="radio" name="modoPeriodoExportar" value="rango"><span>Rango de fechas</span></label>
                        </div>

                        <div class="exportar-fila" data-modo="mes">
                            <div class="grupo-formulario">
                                <label for="mesExportarReporte">Mes</label>
                                <select id="mesExportarReporte"><option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option><option value="04">Abril</option><option value="05">Mayo</option><option value="06">Junio</option><option value="07">Julio</option><option value="08">Agosto</option><option value="09">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option></select>
                            </div>
                            <div class="grupo-formulario">
                                <label for="anioExportarReporte">Año</label>
                                <select id="anioExportarReporte"></select>
                            </div>
                        </div>

                        <div class="exportar-fila" data-modo="rango" hidden>
                            <div class="grupo-formulario">
                                <label for="desdeExportarReporte">Desde</label>
                                <input type="date" id="desdeExportarReporte">
                            </div>
                            <div class="grupo-formulario">
                                <label for="hastaExportarReporte">Hasta</label>
                                <input type="date" id="hastaExportarReporte">
                            </div>
                        </div>
                    </div>

                    <div class="exportar-fila" data-exportar="dia" hidden>
                        <div class="grupo-formulario">
                            <label for="diaExportarReporte">Día del cierre</label>
                            <input type="date" id="diaExportarReporte">
                        </div>
                    </div>

                    <p class="exportar-ayuda" data-exportar="sin-periodo" hidden>
                        <i class="fa-solid fa-circle-info"></i>
                        El inventario se exporta con las existencias a la fecha y hora de la descarga.
                    </p>
                </section>

                <section class="exportar-seccion">
                    <h3><span>3</span> <b id="tituloTiendasExportar">Tiendas</b></h3>
                    <div class="lista-tiendas-exportar" id="tiendasExportarReporte"></div>
                </section>

                <section class="exportar-seccion" id="seccionFiltrosExportar">
                    <h3><span>4</span> Filtros <small>(opcionales)</small></h3>
                    <div class="exportar-fila">
                        <div class="grupo-formulario" data-exportar="categoria">
                            <label for="categoriaExportarReporte">Categoría</label>
                            <select id="categoriaExportarReporte"></select>
                        </div>
                        <div class="grupo-formulario" data-exportar="proveedor">
                            <label for="proveedorExportarReporte">Proveedor</label>
                            <select id="proveedorExportarReporte"></select>
                        </div>
                        <div class="grupo-formulario" data-exportar="busqueda">
                            <label for="busquedaExportarReporte">Producto</label>
                            <input type="search" id="busquedaExportarReporte" placeholder="Nombre o código de barras" autocomplete="off">
                        </div>
                        <div class="grupo-formulario" data-exportar="estado" hidden>
                            <label for="estadoExportarReporte">Estado del stock</label>
                            <select id="estadoExportarReporte">
                                <option value="">Todos</option>
                                <option value="CRITICO">Solo críticos</option>
                                <option value="AGOTADO">Agotados</option>
                                <option value="BAJO_MINIMO">Bajo mínimo</option>
                                <option value="NEGATIVO">Negativos</option>
                                <option value="NORMAL">Normales</option>
                            </select>
                        </div>
                        <div class="grupo-formulario" data-exportar="limite" hidden>
                            <label for="limiteExportarReporte">Productos en los rankings</label>
                            <select id="limiteExportarReporte">
                                <option value="10">Top 10</option>
                                <option value="15" selected>Top 15</option>
                                <option value="25">Top 25</option>
                                <option value="50">Top 50</option>
                            </select>
                        </div>
                    </div>
                    <p class="exportar-ayuda" data-exportar="sin-filtros" hidden>
                        <i class="fa-solid fa-circle-info"></i>
                        El cierre diario consolida todo el día de la tienda, por eso no admite filtros.
                    </p>
                </section>

                <label class="opcion-exportar exportar-detalle" data-exportar="detalle">
                    <input type="checkbox" id="detalleExportarReporte" checked>
                    <span>Incluir el detalle completo <small id="textoDetalleExportar">(productos, existencias o bajas)</small></span>
                </label>

                <p id="mensajeExportarReporte" class="mensaje-login" aria-live="polite"></p>

                <div class="exportar-acciones">
                    <button type="button" class="boton boton-borde" id="botonCancelarExportarReporte">Cancelar</button>
                    <button type="submit" class="boton boton-azul" id="botonGenerarExportarReporte">
                        <i class="fa-solid fa-download"></i>
                        Descargar PDF
                    </button>
                </div>

            </form>

        </section>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/vending-comun.js"></script>
    <script src="../js/reportes.js"></script>

</body>
</html>