<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

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
    <meta name="description" content="Consulta y administración del catálogo de productos de Vending Mini Market">

    <title>Productos | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">
    <link rel="stylesheet" href="../css/catalogo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="pagina-app">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->
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

        </nav>

        <div class="pie-barra-lateral">
            <a href="../index/index.html" id="botonCerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>
        </div>

    </aside>

    <!-- ==========================================
         COLUMNA PRINCIPAL
    =========================================== -->
    <div class="columna-principal">

        <header class="barra-superior">

            <button type="button" class="boton-menu" aria-label="Abrir o cerrar menú lateral">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="buscador-superior">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search"
                       id="buscadorGeneralProductos"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesProductos" aria-label="0 notificaciones">0</span>
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

                <!-- PAGINACIÓN -->
                <div id="paginacionProductos" class="paginacion" hidden>
                    <span id="textoPaginacionProductos"></span>

                    <div class="paginacion-controles">
                        <button type="button" id="botonPaginaAnterior" aria-label="Página anterior">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button type="button" id="botonPaginaSiguiente" aria-label="Página siguiente">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
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

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/productos.js"></script>

</body>
</html>
