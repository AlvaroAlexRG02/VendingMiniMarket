<?php

/*
|--------------------------------------------------------------------------
| Layout compartido de Vending Mini Market
|--------------------------------------------------------------------------
|
| Única fuente del armazón visual de las pantallas internas:
|
|   vmEstilosGlobales()  -> <link> de CSS global (con cache-busting) y Font Awesome
|   vmBarraLateral()     -> <aside> con el menú principal filtrado por rol
|   vmBarraSuperior()    -> <header> con buscador, notificaciones y perfil
|
| Las páginas ya cargaron session.php y permisos.php (database.php + $pdo).
| Cada módulo conserva su contenido, sus scripts y sus propios permisos.
|
| El menú también existe en js/app.js (inicializarNavegacionLateral) solo
| para las pantallas estáticas .html, que no pueden usar PHP. Si se agrega o
| cambia una opción aquí, replicarla allí.
*/

require_once __DIR__ . "/ubicaciones.php";

const VM_FONT_AWESOME = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css";

/**
 * Opciones del menú principal.
 *
 * modulo: carpeta del módulo; la opción se marca activa para cualquier
 *         pantalla de esa carpeta (facturas.php también activa "Pedidos").
 * roles:  ids de rol con acceso (1 Administrador, 2 Gerente General,
 *         3 Encargado, 4 Dependiente). null = todos los roles.
 */
function vmMenuPrincipal(): array
{
    return [
        ["nombre" => "Dashboard",   "ruta" => "dashboard/dashboard.php",   "icono" => "fa-chart-pie",             "modulo" => "dashboard",   "roles" => null],
        ["nombre" => "Usuarios",    "ruta" => "usuarios/usuarios.php",     "icono" => "fa-users",                 "modulo" => "usuarios",    "roles" => [1, 2]],
        ["nombre" => "Productos",   "ruta" => "productos/productos.php",   "icono" => "fa-box",                   "modulo" => "productos",   "roles" => null],
        ["nombre" => "Tiendas",     "ruta" => "tienda/tienda.php",         "icono" => "fa-shop",                  "modulo" => "tienda",      "roles" => null],
        ["nombre" => "Máquinas",    "ruta" => "maquinas/maquinas.php",     "icono" => "fa-cash-register",         "modulo" => "maquinas",    "roles" => null],
        ["nombre" => "Inventario",  "ruta" => "inventario/inventario.php", "icono" => "fa-clipboard-list",        "modulo" => "inventario",  "roles" => null],
        ["nombre" => "Pedidos",     "ruta" => "compras/compras.php",       "icono" => "fa-cart-shopping",         "modulo" => "compras",     "roles" => null],
        ["nombre" => "Ventas",      "ruta" => "ventas/ventas.html",        "icono" => "fa-chart-column",          "modulo" => "ventas",      "roles" => null],
        ["nombre" => "Alertas",     "ruta" => "alertas/alertas.html",      "icono" => "fa-bell",                  "modulo" => "alertas",     "roles" => null],
        ["nombre" => "Reportes",    "ruta" => "reportes/reportes.php",     "icono" => "fa-chart-line",            "modulo" => "reportes",    "roles" => [1, 2, 3]],
        ["nombre" => "Bitácora",    "ruta" => "bitacora/bitacora.php",     "icono" => "fa-clipboard-list",        "modulo" => "bitacora",    "roles" => [1, 2]],
        ["nombre" => "Respaldos",   "ruta" => "respaldo/respaldo.php",     "icono" => "fa-database",              "modulo" => "respaldo",    "roles" => [1, 2]],
        ["nombre" => "Incidencias", "ruta" => "incidencias/incidencias.php", "icono" => "fa-triangle-exclamation", "modulo" => "incidencias", "roles" => [1]],
    ];
}

function vmEscapar(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
}

/** Carpeta del módulo de la pantalla actual (ej. "compras" para compras/facturas.php). */
function vmModuloActual(): string
{
    $partes = array_values(array_filter(explode("/", str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "")))));

    return (string)end($partes);
}

/** CSS global con cache-busting + Font Awesome. $extras: rutas relativas a /css (ej. "catalogo.css"). */
function vmEstilosGlobales(array $extras = []): void
{
    $archivos = array_merge(["estilos.css"], $extras);

    foreach ($archivos as $archivo) {
        $ruta   = __DIR__ . "/../css/" . $archivo;
        $version = is_file($ruta) ? filemtime($ruta) : 0;
        ?>
    <link rel="stylesheet" href="../css/<?= vmEscapar($archivo) ?>?v=<?= $version ?>">
        <?php
    }
    ?>
    <link rel="stylesheet" href="<?= VM_FONT_AWESOME ?>">
    <?php
}

function vmBarraLateral(): void
{
    $modulo = vmModuloActual();
    ?>
    <aside class="barra-lateral">

        <div class="logo-sistema">
            <a href="../dashboard/dashboard.php" aria-label="Ir al Dashboard">
                <img src="../img/logo/logo-vending.jpeg"
                     alt="Logo de Vending Mini Market"
                     class="imagen-logo-sistema">
            </a>
        </div>

        <nav class="nav-lateral" aria-label="Navegación principal" data-menu-servidor>
            <?php foreach (vmMenuPrincipal() as $opcion): ?>
                <?php
                if ($opcion["roles"] !== null && !usuarioTieneRol($opcion["roles"])) {
                    continue;
                }
                $activa = $opcion["modulo"] === $modulo;
                ?>
                <a href="../<?= vmEscapar($opcion["ruta"]) ?>"
                   <?= $activa ? 'class="activo" aria-current="page"' : "" ?>>
                    <i class="fa-solid <?= vmEscapar($opcion["icono"]) ?>"></i>
                    <?= vmEscapar($opcion["nombre"]) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="pie-barra-lateral">
            <a href="../index/index.html" id="botonCerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>
        </div>

    </aside>
    <?php
}

function vmBarraSuperior(): void
{
    global $pdo;

    $usuario = $_SESSION["usuario"] ?? [];
    $nombre  = trim(($usuario["nombre"] ?? "") . " " . ($usuario["apellido"] ?? ""));
    $correo  = (string)($usuario["correo"] ?? "");
    $ubicacion = ($pdo instanceof PDO) ? resumenUbicacionUsuario($pdo)["texto"] : "";
    ?>
        <header class="barra-superior">

            <button type="button" class="boton-menu" aria-label="Abrir o cerrar menú lateral">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="buscador-superior">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador">0</span>
                </button>

                <div class="perfil-superior">
                    <div class="perfil-superior-texto">
                        <strong id="nombreUsuarioSuperior"><?= vmEscapar($nombre) ?></strong>
                        <span id="correoUsuarioSuperior"><?= vmEscapar($correo) ?></span>
                        <?php if ($ubicacion !== ""): ?>
                            <span id="ubicacionUsuarioSuperior">Ubicación: <?= vmEscapar($ubicacion) ?></span>
                        <?php endif; ?>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

            </div>

        </header>
    <?php
}
