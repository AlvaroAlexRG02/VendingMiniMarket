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
    <meta name="description" content="Administración de categorías de productos de Vending Mini Market">

    <title>Categorías | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css">
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
                       id="buscadorGeneralCategorias"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesCategorias" aria-label="0 notificaciones">0</span>
                </button>

                <div class="perfil-superior">
                    <div class="perfil-superior-texto">
                        <strong id="nombreUsuarioSuperior"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?></strong>
                        <span id="correoUsuarioSuperior"><?= htmlspecialchars($correoUsuario, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

            </div>

        </header>

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

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/categorias.js"></script>

</body>
</html>
