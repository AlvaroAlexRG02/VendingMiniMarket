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
 * Esta página es solo para el Administrador.
 */
if (!esAdministrador()) {
    http_response_code(403);
    echo "Acceso no autorizado.";
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
    <meta name="description" content="Administración de categorías de productos de Vending Mini Market">

    <title>Categorías | Vending Mini Market</title>

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

            <div class="encabezado-pagina">

                <div>
                    <h1>Categorías</h1>
                    <p>Organiza el catálogo para facilitar la búsqueda y los reportes.</p>
                </div>

                <a href="productos.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a productos
                </a>

            </div>

            <div id="mensajeProducto" class="mensaje-producto" aria-live="polite"></div>

            <!-- FORMULARIO -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2 id="tituloFormularioCategoria">Nueva categoría</h2>
                        <p class="descripcion-panel">El nombre no se puede repetir.</p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-tags"></i>
                    </div>
                </div>

                <form id="formularioCategoria">

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="nombreCategoria">Nombre *</label>
                            <input type="text" id="nombreCategoria" maxlength="255"
                                   placeholder="Ejemplo: Bebidas" autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="descripcionCategoria">Descripción</label>
                            <input type="text" id="descripcionCategoria" maxlength="500"
                                   placeholder="Opcional" autocomplete="off">
                        </div>

                    </div>

                    <div class="acciones-formulario">

                        <button type="button" id="botonCancelarEdicion" class="boton boton-borde" hidden>
                            Cancelar edición
                        </button>

                        <button type="submit" id="botonGuardarCategoria" class="boton boton-azul">
                            <i class="fa-solid fa-plus"></i>
                            Crear categoría
                        </button>

                    </div>

                </form>

            </section>

            <!-- LISTA -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Categorías registradas</h2>
                        <p class="descripcion-panel">
                            Una categoría desactivada no se puede elegir en productos nuevos,
                            pero los productos que ya la usan la conservan.
                        </p>
                    </div>
                </div>

                <div id="contenedorTablaCategorias" class="contenedor-tabla">

                    <table class="tabla-datos tabla-compacta">

                        <caption class="texto-solo-lectores">Categorías de productos</caption>

                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Productos</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="cuerpoTablaCategorias"></tbody>

                    </table>

                </div>

                <div id="mensajeSinCategorias" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-tags"></i>
                    <h3>No hay categorías registradas</h3>
                    <p>Crea la primera categoría con el formulario de arriba.</p>
                </div>

            </section>

        </main>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/categorias.js"></script>

</body>
</html>
