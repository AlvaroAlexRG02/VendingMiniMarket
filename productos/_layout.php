<?php

/*
 * Estructura común del módulo de productos (precios.php e importar.php).
 * Usa el layout global (config/layout.php): barra lateral y superior compartidas.
 * Las páginas que lo usan ya cargaron session.php y permisos.php.
 */

require_once __DIR__ . "/../config/layout.php";

/** HU-14: administrador o Soporte TI autorizado. */
function catalogoPuedeImportar(): bool
{
    $rol = mb_strtolower(trim((string)($_SESSION["usuario"]["rol"] ?? "")), "UTF-8");

    return in_array($rol, ["administrador", "admin", "soporte ti", "soporte_ti", "soporte-ti", "soporte"], true);
}

function catalogoLayoutInicio(string $tituloPestana, string $titulo, string $subtitulo): void
{
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPestana, ENT_QUOTES, "UTF-8") ?> | Vending Mini Market</title>

    <?php vmEstilosGlobales(["catalogo.css"]); ?>
</head>

<body class="pagina-app">

    <?php vmBarraLateral(); ?>

    <div class="columna-principal">

        <?php vmBarraSuperior(); ?>

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

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <?php foreach ($scripts as $script): ?>
    <script src="<?= htmlspecialchars($script, ENT_QUOTES, "UTF-8") ?>"></script>
    <?php endforeach; ?>

</body>
</html>
    <?php
}
