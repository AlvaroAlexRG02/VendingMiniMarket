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
 * Esta página es para el Administrador y el Gerente General.
 */
if (!esAdministradorOGerente()) {
    header("Location: ../dashboard/dashboard.php");
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
    <meta name="description" content="Registro de ubicaciones de Vending Mini Market">

    <title>Nueva ubicación | Vending Mini Market</title>

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

            <a href="../productos/productos.php">
                <i class="fa-solid fa-box"></i>
                Productos
            </a>

            <a href="tienda.php" class="activo" aria-current="page">
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

            <a href="../bitacora/bitacora.php">
                <i class="fa-solid fa-clipboard-list"></i>
                Bitácora
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
                       id="buscadorGeneralUbicacionNueva"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesUbicacionNueva" aria-label="0 notificaciones">0</span>
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
                    <h1 id="tituloPagina">Nueva ubicación</h1>
                    <p id="textoPagina">
                        Registra una tienda o una bodega con su tipo, estado y, si aplica, la ubicación que la abastece.
                    </p>
                </div>

                <a href="tienda.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a tienda
                </a>

            </div>

            <div id="mensajeUbicacion" class="mensaje-producto" aria-live="polite"></div>

            <section class="panel">

                <div class="panel-encabezado">

                    <div>
                        <h2>Información de la ubicación</h2>
                        <p class="descripcion-panel">
                            Complete los campos necesarios. Los marcados con * son obligatorios.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-shop"></i>
                    </div>

                </div>

                <form id="formularioUbicacion" novalidate>

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="nombreUbicacion">Nombre *</label>
                            <input type="text" id="nombreUbicacion" maxlength="100"
                                   placeholder="Ejemplo: Bodega Central"
                                   autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="tipoUbicacion">Tipo *</label>
                            <select id="tipoUbicacion" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="Tienda">Tienda</option>
                                <option value="Bodega">Bodega</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="estadoUbicacion">Estado *</label>
                            <select id="estadoUbicacion" required>
                                <option value="true">Activa</option>
                                <option value="false">Inactiva</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="principalUbicacion">Ubicación principal</label>
                            <select id="principalUbicacion">
                                <option value="false">No</option>
                                <option value="true">Sí</option>
                            </select>
                        </div>

                        <div class="grupo-formulario" id="grupoAbastecidaPorUbicacion">
                            <label for="abastecidaPorUbicacion">Abastecida por</label>
                            <select id="abastecidaPorUbicacion">
                                <option value="">Sin relación operativa</option>
                            </select>
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="observacionesUbicacion">Observaciones</label>
                            <textarea id="observacionesUbicacion" rows="3" maxlength="500"
                                      placeholder="Detalle operativo opcional..."></textarea>
                        </div>

                    </div>

                    <div class="acciones-formulario">

                        <a href="tienda.php" class="boton boton-borde">Cancelar</a>

                        <button type="submit" id="botonGuardarUbicacion" class="boton boton-azul">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar ubicación
                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/ubicacion-form.js?v=<?= filemtime(__DIR__ . '/../js/ubicacion-form.js') ?>"></script>

</body>
</html>
