<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

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

$nombreUsuario = trim(
    ($_SESSION["usuario"]["nombre"] ?? "") . " " .
    ($_SESSION["usuario"]["apellido"] ?? "")
);
$correoUsuario = $_SESSION["usuario"]["correo"] ?? "";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Administración de tiendas y bodegas de Vending Mini Market">
    <title>Tienda | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">
    <link rel="stylesheet" href="../css/catalogo.css?v=<?= filemtime(__DIR__ . '/../css/catalogo.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="pagina-app">

    <aside class="barra-lateral">

        <div class="logo-sistema">
            <a href="../dashboard/dashboard.php" aria-label="Ir al Dashboard">
                <img src="../img/logo/logo-vending.jpeg"
                     alt="Logo de Vending Mini Market"
                     class="imagen-logo-sistema">
            </a>
        </div>

        <nav
            class="nav-lateral"
            aria-label="Navegación principal"
        >

            <a
                href="dashboard.php"
                class="activo"
                aria-current="page"
            >
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="../productos/productos.html">
                <i class="fa-solid fa-box"></i>
                Productos
            </a>

            <a href="../tienda/tienda.php">
                <i class="fa-solid fa-shop"></i>
                Tienda
            </a>

            <a href="../maquinas/maquinas.php">
                <i class="fa-solid fa-cash-register"></i>
                Máquinas
            </a>

            <a href="../inventario/inventario.html">
                <i class="fa-solid fa-clipboard-list"></i>
                Inventario
            </a>

            <a href="../ventas/ventas.html">
                <i class="fa-solid fa-chart-column"></i>
                Ventas
            </a>

            <a href="../alertas/alertas.html">
                <i class="fa-solid fa-bell"></i>
                Alertas
            </a>

            <a href="../reportes/reportes.html">
                <i class="fa-solid fa-chart-line"></i>
                Reportes
            </a>

            <a href="../usuarios/usuarios.php">
                <i class="fa-solid fa-users"></i>
                Usuarios
            </a>

            <?php if (esAdministradorOGerente()): ?>

                <a href="../bitacora/bitacora.php">

                    <i class="fa-solid fa-clipboard-list"></i>

                    Bitácora

                </a>

            <?php endif; ?>


            <?php if (esAdministradorOGerente()): ?>

                <a href="../respaldo/respaldo.php">
                    <i class="fa-solid fa-database"></i>
                    Respaldos
                </a>

            <?php endif; ?>

           <?php if (esAdministrador()): ?>

                <a href="../incidencias/incidencias.php">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Incidencias
                </a>

            <?php endif; ?>

        </nav>

        <div class="pie-barra-lateral">
            <a href="../index/index.html" id="botonCerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>
        </div>

    </aside>

    <div class="columna-principal">

        <header class="barra-superior">

            <button type="button" class="boton-menu" aria-label="Abrir o cerrar menú lateral">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="buscador-superior">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search"
                       id="buscadorGeneralTienda"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesTienda" aria-label="0 notificaciones">0</span>
                </button>

                <div class="perfil-superior">
                    <div class="perfil-superior-texto">
                        <strong id="nombreUsuarioSuperior"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?></strong>
                        <span id="correoUsuarioSuperior"><?= htmlspecialchars($correoUsuario, ENT_QUOTES, "UTF-8") ?></span>
                        <span id="ubicacionUsuarioSuperior">Ubicación: <?= htmlspecialchars(resumenUbicacionUsuario($pdo)["texto"], ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

            </div>

        </header>

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

                    <div class="contenedor-tabla">
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

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/tienda.js?v=<?= filemtime(__DIR__ . '/../js/tienda.js') ?>"></script>
</body>
</html>