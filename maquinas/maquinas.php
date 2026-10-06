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
    <meta name="description" content="Consulta de máquinas expendedoras de Vending Mini Market">

    <title>Máquinas | Vending Mini Market</title>

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

            <a href="../productos/productos.php">
                <i class="fa-solid fa-box"></i>
                Productos
            </a>

            <a href="../tienda/tienda.php">
                <i class="fa-solid fa-shop"></i>
                Tienda
            </a>

            <a href="maquinas.php" class="activo" aria-current="page">
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
                       id="buscadorGeneralMaquinas"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesMaquinas" aria-label="0 notificaciones">0</span>
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

            <!-- ENCABEZADO -->
            <div class="encabezado-pagina">

                <div>
                    <h1>Máquinas</h1>
                    <p>Consulta las máquinas expendedoras y la ubicación a la que pertenece cada una.</p>
                </div>

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
                <div id="contenedorTablaMaquinas" class="contenedor-tabla">

                    <table class="tabla-datos">

                        <caption class="texto-solo-lectores">Máquinas expendedoras registradas</caption>

                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Ubicación</th>
                                <th scope="col">Modelo / tipo</th>
                                <th scope="col">Estado</th>
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

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas.js"></script>
</body>
</html>
