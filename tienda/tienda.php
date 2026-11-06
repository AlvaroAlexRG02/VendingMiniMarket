<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

if (!esAdministradorOGerente()) {
    header("Location: ../dashboard/dashboard.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Administración de tiendas y bodegas de Vending Mini Market">
    <title>Tienda | Vending Mini Market</title>

    <?php vmEstilosGlobales(["catalogo.css"]); ?>
</head>

<body class="pagina-app">

    
<?php
?>

<!-- ==========================================
     BARRA LATERAL
=========================================== -->

<?php vmBarraLateral(); ?>


    <div class="columna-principal">

        <?php vmBarraSuperior(); ?>

        <main class="contenido-app">

            <div class="encabezado-pagina">

                <div>
                    <h1>Tienda</h1>
                    <p>Gestiona tiendas, bodegas y relaciones operativas para reflejar la operación multiubicación de UP1, UP2 y UltraLag.</p>
                </div>

                <div class="acciones-encabezado-compras">
                    <a href="relaciones.php" class="boton boton-borde">
                        <i class="fa-solid fa-diagram-project"></i>
                        Relaciones de abastecimiento
                    </a>
                    <a href="ubicacion-nueva.php" class="boton boton-azul">
                        <i class="fa-solid fa-plus"></i>
                        Nueva ubicación
                    </a>
                </div>

            </div>

            <div id="mensajeTienda" class="mensaje-producto" aria-live="polite"></div>

            <section class="grilla-estadisticas" aria-label="Resumen de ubicaciones">

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-shop"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Ubicaciones registradas</div>
                            <div class="tarjeta-estadistica-valor" id="totalUbicacionesTienda">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Tiendas y bodegas administradas</div>
                </article>

                <article class="tarjeta-estadistica borde-verde">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-location-dot"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Ubicaciones principales</div>
                            <div class="tarjeta-estadistica-valor" id="totalPrincipalesTienda">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">UP1, UP2 y UltraLag contempladas</div>
                </article>

                <article class="tarjeta-estadistica borde-naranja">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-check"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Ubicaciones activas</div>
                            <div class="tarjeta-estadistica-valor" id="totalActivasTienda">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Operación disponible para abastecimiento</div>
                </article>

                <article class="tarjeta-estadistica borde-morado">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Relaciones operativas</div>
                            <div class="tarjeta-estadistica-valor" id="totalRelacionesTienda">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Puntos de abastecimiento configurados</div>
                </article>

            </section>

            <section class="diseno-tienda">

                <article class="panel panel-completo-tienda">
                    <div class="panel-encabezado">
                        <h2>Ubicaciones registradas</h2>
                    </div>

                    <div class="contenedor-tabla" id="contenedorTablaTiendas">
                        <table class="tabla-datos">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Principal</th>
                                    <th>Abastecida por</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoTablaTiendas"></tbody>
                        </table>
                    </div>
                </article>

            </section>

        </main>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/tienda.js?v=<?= filemtime(__DIR__ . '/../js/tienda.js') ?>"></script>
</body>
</html>