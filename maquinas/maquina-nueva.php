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
    <meta name="description" content="Registro de máquinas expendedoras de Vending Mini Market">

    <title>Nueva máquina | Vending Mini Market</title>

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
                       id="buscadorGeneralMaquinaNueva"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesMaquinaNueva" aria-label="0 notificaciones">0</span>
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
                    <h1 id="tituloPagina">Nueva máquina</h1>
                    <p id="textoPagina">
                        Registra una máquina expendedora con su identificador, ubicación y estado.
                    </p>
                </div>

                <a href="maquinas.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a máquinas
                </a>

            </div>

            <div id="mensajeMaquina" class="mensaje-producto" aria-live="polite"></div>

            <section class="panel">

                <div class="panel-encabezado">

                    <div>
                        <h2>Información de la máquina</h2>
                        <p class="descripcion-panel">
                            Complete los campos necesarios. Los marcados con * son obligatorios.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>

                </div>

                <form id="formularioMaquina" novalidate>

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="codigoMaquina">Código (identificador único) *</label>
                            <input type="text" id="codigoMaquina" maxlength="100"
                                   placeholder="Ejemplo: VMM UL 001"
                                   autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="nombreMaquina">Nombre *</label>
                            <input type="text" id="nombreMaquina" maxlength="150"
                                   placeholder="Ejemplo: Máquina de bebidas entrada"
                                   autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="tiendaMaquina">Ubicación *</label>
                            <select id="tiendaMaquina" required>
                                <option value="">Seleccione una ubicación</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="ubicacionMaquina">Punto operativo</label>
                            <input type="text" id="ubicacionMaquina" maxlength="200"
                                   placeholder="Ejemplo: Entrada principal"
                                   autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="modeloMaquina">Modelo</label>
                            <input type="text" id="modeloMaquina" maxlength="100"
                                   autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="tipoMaquina">Tipo</label>
                            <input type="text" id="tipoMaquina" maxlength="100"
                                   placeholder="Ejemplo: Bebidas"
                                   autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="estadoMaquina">Estado *</label>
                            <select id="estadoMaquina" required>
                                <option value="true">Activa</option>
                                <option value="false">Inactiva</option>
                            </select>
                            <small id="ayudaEstadoMaquina" class="texto-ayuda" hidden>
                                El estado se cambia desde el listado de máquinas.
                            </small>
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="observacionesMaquina">Observaciones</label>
                            <textarea id="observacionesMaquina" rows="3" maxlength="500"
                                      placeholder="Observaciones de la máquina..."></textarea>
                        </div>

                    </div>

                    <div class="acciones-formulario">

                        <a href="maquinas.php" class="boton boton-borde">Cancelar</a>

                        <button type="submit" id="botonGuardarMaquina" class="boton boton-azul">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar máquina
                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js"></script>
    <script src="../js/maquina-form.js"></script>

</body>
</html>
