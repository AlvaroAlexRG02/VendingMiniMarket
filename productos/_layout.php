<?php

/*
 * Estructura común (barra lateral + barra superior) de precios.php e importar.php.
 * Mismo diseño que productos.php, categorias.php y proveedores.php.
 * Las páginas que lo usan ya cargaron session.php y permisos.php.
 */

/** HU-14: administrador o Soporte TI autorizado. */
function catalogoPuedeImportar(): bool
{
    $rol = mb_strtolower(trim((string)($_SESSION["usuario"]["rol"] ?? "")), "UTF-8");

    return in_array($rol, ["administrador", "admin", "soporte ti", "soporte_ti", "soporte-ti", "soporte"], true);
}

function catalogoLayoutInicio(string $tituloPestana, string $titulo, string $subtitulo): void
{
    global $pdo;

    $nombreUsuario = trim(
        ($_SESSION["usuario"]["nombre"] ?? "") . " " .
        ($_SESSION["usuario"]["apellido"] ?? "")
    );
    $correoUsuario = $_SESSION["usuario"]["correo"] ?? "";
    $ubicacion     = resumenUbicacionUsuario($pdo)["texto"];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPestana, ENT_QUOTES, "UTF-8") ?> | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">
    <link rel="stylesheet" href="../css/catalogo.css">
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

        <nav class="nav-lateral" aria-label="Navegación principal">

            <a href="../dashboard/dashboard.php">
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="productos.php" class="activo" aria-current="page">
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

            <a href="../inventario/inventario.php">
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
                       id="buscadorGeneralCatalogo"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesCatalogo" aria-label="0 notificaciones">0</span>
                </button>

                <div class="perfil-superior">
                    <div class="perfil-superior-texto">
                        <strong id="nombreUsuarioSuperior"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?></strong>
                        <span id="correoUsuarioSuperior"><?= htmlspecialchars($correoUsuario, ENT_QUOTES, "UTF-8") ?></span>
                        <span id="ubicacionUsuarioSuperior">Ubicación: <?= htmlspecialchars($ubicacion, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

            </div>

        </header>

        <main class="contenido-app">

            <div class="encabezado-pagina">

                <div>
                    <h1><?= htmlspecialchars($titulo, ENT_QUOTES, "UTF-8") ?></h1>
                    <p><?= htmlspecialchars($subtitulo, ENT_QUOTES, "UTF-8") ?></p>
                </div>

                <a href="productos.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a productos
                </a>

            </div>

            <div id="mensajeProducto" class="mensaje-producto" aria-live="polite"></div>

    <?php
}

function catalogoLayoutFin(array $scripts): void
{
    ?>
        </main>

    </div>

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script, ENT_QUOTES, "UTF-8") ?>"></script>
    <?php endforeach; ?>

</body>
</html>
    <?php
}
