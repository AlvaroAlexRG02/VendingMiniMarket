<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";


if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

/*
 * Ubicación asignada al usuario (HU-19), leída de la base en cada carga.
 */
$ubicacionUsuario = resumenUbicacionUsuario($pdo);

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
        content="Panel principal del sistema Vending Mini Market"
    >

    <title>Dashboard | Vending Mini Market</title>

    <!-- Hoja de estilos principal -->
    <?php vmEstilosGlobales(); ?>

    <!-- Biblioteca de iconos Font Awesome -->
</head>

<body class="pagina-app">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->

    
<?php
?>

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
             CONTENIDO PRINCIPAL
        =========================================== -->

        <main class="contenido-app">

            <!-- ==========================================
                 UBICACIÓN ASIGNADA (HU-19)
            =========================================== -->

            <?php if ($ubicacionUsuario["sin_ubicacion"]): ?>

            <div id="ubicacionAsignadaDashboard" class="mensaje-producto mensaje-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Sin ubicación asignada. Solicite al administrador que le asigne una ubicación para poder operar.
            </div>

            <?php endif; ?>

            <!-- ==========================================
                 ENCABEZADO DEL DASHBOARD
            =========================================== -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Dashboard</h1>

                    <p id="mensajeBienvenidaDashboard">
                        Bienvenido al sistema Vending Mini Market
                    </p>

                    <?php if (!$ubicacionUsuario["sin_ubicacion"]): ?>
                    <p class="etiqueta-ubicacion-dashboard" id="ubicacionAsignadaDashboard">
                        <i class="fa-solid fa-location-dot"></i>
                        <?php if ($ubicacionUsuario["todas"]): ?>
                            Acceso a todas las ubicaciones
                        <?php else: ?>
                            <?= $ubicacionUsuario["cantidad"] > 1 ? "Ubicaciones" : "Ubicación" ?>:
                            <strong><?= htmlspecialchars($ubicacionUsuario["texto"], ENT_QUOTES, "UTF-8") ?></strong>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>

                </div>

                <!-- Filtros principales -->

                <div class="acciones-dashboard">

                    <div class="selector-filtro selector-dashboard">

                        <label for="filtroTiendaDashboard">
                            Tienda
                        </label>

                        <select id="filtroTiendaDashboard"></select>

                    </div>

                    <div class="selector-filtro selector-dashboard">

                        <label for="fechaDashboard">
                            Fecha
                        </label>

                        <div class="fecha-dashboard-grupo">
                            <input
                                type="date"
                                id="fechaDashboard"
                            >
                            <button type="button" class="atajo-periodo" id="botonHoyDashboard">Hoy</button>
                            <button type="button" class="atajo-periodo" id="botonAyerDashboard">Ayer</button>
                        </div>

                    </div>

                </div>

            </div>

            <!-- ==========================================
                 TARJETAS ESTADÍSTICAS
            =========================================== -->

            <div class="separador-seccion" role="separator"><span><i class="fa-solid fa-gauge-high"></i>Resumen del día</span></div>

            <section
                class="grilla-estadisticas"
                aria-label="Resumen del negocio"
            >

                <a href="../reportes/reportes.php" title="Ver reporte de ventas" class="tarjeta-estadistica tarjeta-enlace borde-verde">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Ventas del día</div>
                            <div class="tarjeta-estadistica-valor" id="totalVentasDashboard">₡0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio" id="detalleVentasDashboard">Ventas confirmadas</div>
                </a>

                <?php if (esAdministradorOGerente()): ?>
                <a href="../reportes/reportes.php?reporte=utilidad" title="Ver reporte de utilidad" class="tarjeta-estadistica tarjeta-enlace borde-morado">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Utilidad estimada</div>
                            <div class="tarjeta-estadistica-valor" id="utilidadDashboard">₡0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio" id="detalleUtilidadDashboard">Ingresos menos costo</div>
                </a>
                <?php endif; ?>

                <a href="../inventario/inventario.php" title="Ver inventario" class="tarjeta-estadistica tarjeta-enlace borde-naranja">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Inventario bajo</div>
                            <div class="tarjeta-estadistica-valor" id="stockCriticoDashboard">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio" id="detalleStockDashboard">Productos que requieren atención</div>
                </a>

                <a href="../inventario/movimientos.html" title="Ver movimientos" class="tarjeta-estadistica tarjeta-enlace">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-right-left"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Movimientos del día</div>
                            <div class="tarjeta-estadistica-valor" id="totalMovimientosDashboard">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Entradas, salidas, ajustes y traslados</div>
                </a>

            </section>

            <div class="separador-seccion" role="separator"><span><i class="fa-solid fa-chart-column"></i>Ventas</span></div>

            <div class="grilla-graficos">

                <section class="panel">
                    <div class="panel-encabezado">
                        <div>
                            <h2>Tendencia de ventas</h2>
                            <p class="descripcion-panel">Ingresos (sin IVA) de los últimos 7 días hasta la fecha elegida.</p>
                        </div>
                    </div>
                    <div id="tendenciaVentasDashboard"></div>
                </section>

                <?php if (esAdministradorOGerente()): ?>
                <section class="panel" id="panelVentasTiendaDashboard" hidden>
                    <div class="panel-encabezado">
                        <div>
                            <h2>Ventas por ubicación</h2>
                            <p class="descripcion-panel">Comparación de las tiendas en el día elegido.</p>
                        </div>
                    </div>
                    <div id="barrasVentasTiendaDashboard"></div>
                </section>
                <?php endif; ?>

            </div>

            <!-- ==========================================
                 MOVIMIENTOS Y ALERTAS
            =========================================== -->

<div class="separador-seccion" role="separator"><span><i class="fa-solid fa-list-check"></i>Actividad y alertas</span></div>

                        <section class="grilla-dos-columnas">

                <!-- ==========================================
                     ÚLTIMOS MOVIMIENTOS
                =========================================== -->

                <article class="panel">

                    <div class="panel-encabezado">

                        <div>

                            <h2>Últimos movimientos</h2>

                            <p class="descripcion-panel">
                                Actividad reciente del inventario.
                            </p>

                        </div>

                        <a
                            href="../inventario/movimientos.html"
                            class="enlace-ver-todo"
                        >
                            Ver todos

                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>

                    <div class="contenedor-tabla">

                        <table class="tabla-datos">

                            <caption class="texto-solo-lectores">
                                Últimos movimientos de inventario
                            </caption>

                            <thead>
                                <tr>
                                    <th scope="col">Fecha y hora</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col">Referencia</th>
                                    <th scope="col">Usuario</th>
                                </tr>
                            </thead>

                            <tbody id="tablaMovimientosDashboard">

                                <tr id="filaSinMovimientosDashboard">

                                    <td colspan="5">

                                        <div class="estado-vacio-tabla">

                                            <i
                                                class="fa-solid fa-arrow-right-arrow-left"
                                            ></i>

                                            <strong>
                                                No hay movimientos registrados
                                            </strong>

                                            <span>
                                                Los movimientos recientes
                                                aparecerán en esta sección.
                                            </span>

                                        </div>

                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </article>

                <!-- ==========================================
                     ALERTAS
                =========================================== -->

                <article class="panel">

                    <div class="panel-encabezado">

                        <div>

                            <h2>Alertas</h2>

                            <p class="descripcion-panel">
                                Avisos que requieren atención.
                            </p>

                        </div>

                        <a
                            href="../alertas/alertas.html"
                            class="enlace-ver-todo"
                        >
                            Ver todas

                            <i class="fa-solid fa-chevron-right"></i>
                        </a>

                    </div>

                    <div
                        class="lista-alertas-dashboard"
                        id="listaAlertasDashboard"
                    >

                        <div
                            class="estado-vacio-tabla"
                            id="mensajeSinAlertasDashboard"
                        >

                            <i class="fa-regular fa-bell"></i>

                            <strong>
                                No hay alertas registradas
                            </strong>

                            <span>
                                Las alertas del sistema aparecerán aquí.
                            </span>

                        </div>

                    </div>

                </article>

            </section>

        </main>

    </div>

    <!-- JavaScript general -->
    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/vending-comun.js"></script>
    <script src="../js/dashboard.js"></script>

</body>
</html>