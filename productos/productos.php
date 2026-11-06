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
    <meta name="description" content="Consulta y administración del catálogo de productos de Vending Mini Market">

    <title>Productos | Vending Mini Market</title>

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
                    <h1>Productos</h1>
                    <p>Consulta y administra los productos registrados en el sistema.</p>
                </div>

                <div class="acciones-encabezado-catalogo">

                    <button type="button" id="botonExportarCatalogo" class="boton boton-borde">
                        <i class="fa-solid fa-file-csv"></i>
                        Exportar CSV
                    </button>

                    <!-- NUEVO: precios e importación -->
                    <?php if (esAdministrador()): ?>
                    <a href="precios.php" class="boton boton-borde">
                        <i class="fa-solid fa-calculator"></i>
                        Precios
                    </a>

                    <a href="importar.php" class="boton boton-borde">
                        <i class="fa-solid fa-file-import"></i>
                        Importar catálogo
                    </a>
                    <?php endif; ?>
                    
                    <?php if (esAdministrador()): ?>
                    <a href="categorias.php" class="boton boton-borde">
                        <i class="fa-solid fa-tags"></i>
                        Categorías
                    </a>
                    <?php endif; ?>

                    <?php if (esAdministrador()): ?>
                    <a href="proveedores.php" class="boton boton-borde">
                        <i class="fa-solid fa-truck-field"></i>
                        Proveedores
                    </a>
                    <?php endif; ?>

                    <?php if (esAdministrador()): ?>
                    <a href="producto-nuevo.php" class="boton boton-azul">
                        <i class="fa-solid fa-plus"></i>
                        Nuevo producto
                    </a>
                    <?php endif; ?>

                </div>

            </div>

            <!-- MENSAJES -->
            <div id="mensajeProducto" class="mensaje-producto" aria-live="polite"></div>

            <!-- RESUMEN -->
            <section class="resumen-productos" aria-label="Resumen de productos">

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-box"></i></div>
                    <div>
                        <span>Total de productos</span>
                        <strong id="totalProductos">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <span>Productos activos</span>
                        <strong id="productosActivos">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div>
                        <span>Productos inactivos</span>
                        <strong id="productosInactivos">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-tags"></i></div>
                    <div>
                        <span>Categorías activas</span>
                        <strong id="categoriasActivas">0</strong>
                    </div>
                </article>

            </section>

            <!-- CATÁLOGO -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Catálogo de productos</h2>
                        <p class="descripcion-panel">
                            Busca por nombre, código interno o código de barras.
                            La exportación incluye todos los productos que coinciden con los filtros.
                        </p>
                    </div>
                </div>

                <!-- FILTROS -->
                <div class="barra-filtros productos-filtros">

                    <div class="campo-busqueda-productos">
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <label for="buscarProducto" class="texto-solo-lectores">Buscar productos</label>

                        <input type="search"
                               id="buscarProducto"
                               placeholder="Buscar por nombre, código interno o código de barras..."
                               autocomplete="off">
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroCategoriaProducto">Categoría</label>
                        <select id="filtroCategoriaProducto">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroEstadoProducto">Estado</label>
                        <select id="filtroEstadoProducto">
                            <option value="">Todos los estados</option>
                            <option value="activos">Activos</option>
                            <option value="inactivos">Inactivos</option>
                        </select>
                    </div>

                    <button type="button" id="botonLimpiarFiltrosProductos" class="boton boton-borde">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <p id="resultadoBusquedaProductos"
                   class="resultado-busqueda-productos"
                   aria-live="polite"></p>

                <!-- TABLA -->
                <div id="contenedorTablaProductos" class="contenedor-tabla">

                    <table class="tabla-datos">

                        <caption class="texto-solo-lectores">Catálogo de productos registrados</caption>

                        <thead>
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Código interno / barras</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Impuesto</th>
                                <th scope="col">Proveedores</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="cuerpoTablaProductos"></tbody>

                    </table>

                </div>

                <!-- SIN PRODUCTOS -->
                <div id="mensajeSinProductos" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No hay productos registrados</h3>
                    <p>Registra el primer producto para que aparezca en el catálogo.</p>
                    <?php if (esAdministrador()): ?>
                    <a href="producto-nuevo.php" class="boton boton-azul">
                        <i class="fa-solid fa-plus"></i>
                        Registrar producto
                    </a>
                    <?php endif; ?>
                </div>

                <!-- SIN RESULTADOS -->
                <div id="mensajeSinResultadosProductos" class="estado-vacio-productos" hidden>
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
    <script src="../js/productos.js"></script>

</body>
</html>
