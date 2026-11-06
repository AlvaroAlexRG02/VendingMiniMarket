<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";

/*
 * Sin sesión iniciada no se puede entrar a la página,
 * ni escribiendo el enlace directamente en el navegador.
 */
if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

/*
 * Evitar que el navegador muestre la página después de
 * cerrar sesión utilizando la caché.
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");



?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Consulta de máquinas expendedoras de Vending Mini Market">

    <title>Máquinas | Vending Mini Market</title>

    <?php vmEstilosGlobales(["catalogo.css"]); ?>
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

        <?php vmBarraSuperior(); ?>

        <main class="contenido-app">

            <!-- ENCABEZADO -->
            <div class="encabezado-pagina">

                <div>
                    <h1>Máquinas</h1>
                    <p>Consulta las máquinas expendedoras y la ubicación a la que pertenece cada una.</p>
                </div>

                <?php if (esAdministrador()): ?>
                <div class="acciones-encabezado-catalogo">
                    <a href="maquina-nueva.php" class="boton boton-azul">
                        <i class="fa-solid fa-plus"></i>
                        Nueva máquina
                    </a>
                </div>
                <?php endif; ?>

            </div>

            <!-- MENSAJES -->
            <div id="mensajeMaquina" class="mensaje-producto" aria-live="polite"></div>

            <!-- RESUMEN -->
            <section class="resumen-productos" aria-label="Resumen de máquinas">

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-cash-register"></i></div>
                    <div>
                        <span>Total de máquinas</span>
                        <strong id="totalMaquinas">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <span>Máquinas activas</span>
                        <strong id="maquinasActivas">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div>
                        <span>Máquinas inactivas</span>
                        <strong id="maquinasInactivas">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-shop"></i></div>
                    <div>
                        <span>Ubicaciones con máquinas</span>
                        <strong id="tiendasConMaquinas">0</strong>
                    </div>
                </article>

            </section>

            <!-- LISTADO -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Máquinas registradas</h2>
                        <p class="descripcion-panel">
                            Busca por código, nombre, punto operativo o modelo.
                        </p>
                    </div>
                </div>

                <!-- FILTROS -->
                <div class="barra-filtros productos-filtros">

                    <div class="campo-busqueda-productos">
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <label for="buscarMaquina" class="texto-solo-lectores">Buscar máquinas</label>

                        <input type="search"
                               id="buscarMaquina"
                               placeholder="Buscar por código, nombre, punto operativo o modelo..."
                               autocomplete="off">
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroTiendaMaquina">Ubicación</label>
                        <select id="filtroTiendaMaquina">
                            <option value="">Todas las ubicaciones</option>
                        </select>
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroEstadoMaquina">Estado</label>
                        <select id="filtroEstadoMaquina">
                            <option value="">Todos los estados</option>
                            <option value="activas">Activas</option>
                            <option value="inactivas">Inactivas</option>
                        </select>
                    </div>

                    <button type="button" id="botonLimpiarFiltrosMaquinas" class="boton boton-borde">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <p id="resultadoBusquedaMaquinas"
                   class="resultado-busqueda-productos"
                   aria-live="polite"></p>

                <!-- TABLA -->
                <div id="contenedorTablaMaquinas" class="contenedor-tabla"
                     data-es-admin="<?= esAdministrador() ? '1' : '0' ?>">

                    <table class="tabla-datos">

                        <caption class="texto-solo-lectores">Máquinas expendedoras registradas</caption>

                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Ubicación</th>
                                <th scope="col">Modelo / tipo</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="cuerpoTablaMaquinas"></tbody>

                    </table>

                </div>

                <!-- SIN MÁQUINAS -->
                <div id="mensajeSinMaquinas" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-cash-register"></i>
                    <h3>No hay máquinas registradas</h3>
                    <p>Cuando se registre la primera máquina aparecerá en este listado.</p>
                    <?php if (esAdministrador()): ?>
                    <a href="maquina-nueva.php" class="boton boton-azul">
                        <i class="fa-solid fa-plus"></i>
                        Registrar máquina
                    </a>
                    <?php endif; ?>
                </div>

                <!-- SIN RESULTADOS -->
                <div id="mensajeSinResultadosMaquinas" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3>No se encontraron coincidencias</h3>
                    <p>Cambia el texto de búsqueda o limpia los filtros seleccionados.</p>
                </div>

            </section>

        </main>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/maquinas.js?v=<?= filemtime(__DIR__ . '/../js/maquinas.js') ?>"></script>
</body>
</html>
