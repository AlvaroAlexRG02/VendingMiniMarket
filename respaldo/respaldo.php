
<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/permisos.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

if (!esAdministradorOGerente()) {
    http_response_code(403);
    exit("Acceso no autorizado.");
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
$nombreUsuario = trim(
    ($_SESSION["usuario"]["nombre"] ?? "") . " " .
    ($_SESSION["usuario"]["apellido"] ?? "")
);

if ($nombreUsuario === "") {
    $nombreUsuario = "Usuario";
}

$rolUsuario = $_SESSION["usuario"]["nombre_rol"] ?? "Usuario";

=======
>>>>>>> Stashed changes
$nombreUsuario = $_SESSION["usuario"]["nombre"] ?? "";
$apellidoUsuario = $_SESSION["usuario"]["apellido"] ?? "";
$nombreCompleto = trim($nombreUsuario . " " . $apellidoUsuario);

if ($nombreCompleto === "") {
    $nombreCompleto = "Usuario";
}

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
?>

<!DOCTYPE html>
<html lang="es">

<style>
    .pagina-app .columna-principal {
        margin-left: 0px;
        width: calc(100% - 230px);
        min-height: 100vh;
    }

    .pagina-app .barra-superior {
        width: 100%;
    }

    .pagina-app .contenido-app {
        width: 100%;
        box-sizing: border-box;
    }
</style>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestión de respaldos de Vending Mini Market">

    <title>Respaldos | Vending Mini Market</title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
    <style>
        .pagina-app .columna-principal {
            margin-left: 0 !important;
        }

        .pagina-app .contenido-app {
            margin-left: 0 !important;
            padding: 24px !important;
        }

        .encabezado-respaldo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .encabezado-respaldo h1 {
            margin: 0 0 8px;
        }

        .encabezado-respaldo p {
            margin: 0;
            color: #64748b;
        }

        .tarjeta-respaldo {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .encabezado-seccion-respaldo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .encabezado-seccion-respaldo h2 {
            margin: 0;
            font-size: 18px;
        }

        .respaldo-principal {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .dato-respaldo {
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            min-width: 0;
        }

        .dato-respaldo h3 {
            margin: 0 0 12px;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
        }

        .dato-respaldo p {
            margin: 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .tabla-respaldo {
            width: 100%;
            overflow-x: auto;
        }

        .tabla-respaldo table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        .tabla-respaldo th,
        .tabla-respaldo td {
            padding: 13px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .tabla-respaldo th {
            background: #f8fafc;
            color: #334155;
            font-weight: 600;
        }

        .tabla-respaldo tbody tr:hover {
            background: #f8fafc;
        }

        .mensaje-respaldo {
            padding: 22px;
            text-align: center;
            color: #64748b;
        }

        #btnRegistrarRespaldo:disabled {
            opacity: 0.7;
            cursor: wait;
        }

        #mensajeRespaldo {
            color: #b91c1c;
            margin: 0 0 16px;
        }

        @media (max-width: 900px) {
            .respaldo-principal {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .pagina-app .contenido-app {
                padding: 16px !important;
            }

            .encabezado-respaldo {
                align-items: flex-start;
                flex-direction: column;
            }

            .tarjeta-respaldo {
                padding: 16px;
            }
        }
    </style>
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
</head>

<body class="pagina-app">

<<<<<<< Updated upstream

<body class="pagina-app">

    <!-- =====================================================
         BARRA LATERAL
    ====================================================== -->

    <aside class="barra-lateral">

        <div class="logo-sistema">

        <a
            href="../dashboard/dashboard.php"
            aria-label="Ir al Dashboard"
        >

            <img
                src="../img/logo/logo-vending.jpeg"
                alt="Logo de Vending Mini Market"
                class="imagen-logo-sistema"
            >

        </a>

    </div>

        <nav
            class="nav-lateral"
            aria-label="Navegación principal"
        >

            <a
                href="dashboard.php"
                class="activo"
                aria-current="page"
            >
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="../productos/productos.html">
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

            <?php if (esAdministradorOGerente()): ?>

                <a href="../bitacora/bitacora.php">

                    <i class="fa-solid fa-clipboard-list"></i>

                    Bitácora

                </a>

            <?php endif; ?>
=======
<<<<<<< HEAD
    <!-- BARRA LATERAL -->
    <aside class="barra-lateral">

       <div class="logo-sistema">
=======
>>>>>>> Stashed changes

<body class="pagina-app">

<<<<<<< Updated upstream
            <?php if (esAdministradorOGerente()): ?>

                <a href="../respaldo/respaldo.php">
                    <i class="fa-solid fa-database"></i>
                    Respaldos
                </a>

            <?php endif; ?>

=======
    <!-- =====================================================
         BARRA LATERAL
    ====================================================== -->

    <aside class="barra-lateral">

        <div class="logo-sistema">

        <a
            href="../dashboard/dashboard.php"
            aria-label="Ir al Dashboard"
        >

            <img
                src="../img/logo/logo-vending.jpeg"
                alt="Logo de Vending Mini Market"
                class="imagen-logo-sistema"
            >

        </a>

    </div>

        <nav
            class="nav-lateral"
            aria-label="Navegación principal"
        >

            <a
                href="dashboard.php"
                class="activo"
                aria-current="page"
            >
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="../productos/productos.html">
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

            <?php if (esAdministradorOGerente()): ?>

                <a href="../bitacora/bitacora.php">

                    <i class="fa-solid fa-clipboard-list"></i>

                    Bitácora

                </a>

            <?php endif; ?>
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef

        <a
            href="../dashboard/dashboard.php"
            aria-label="Ir al Dashboard"
        >

<<<<<<< HEAD
            <img
                src="../img/logo/logo-vending.jpeg"
                alt="Logo de Vending Mini Market"
                class="imagen-logo-sistema"
            >

        </a>

    </div>

        <nav
        class="nav-lateral"
        aria-label="Navegación principal"
    >

        <a href="../dashboard/dashboard.php">
            <i class="fa-solid fa-chart-pie"></i>
            Dashboard
        </a>

        <a href="../productos/productos.html">
            <i class="fa-solid fa-box"></i>
            Productos
        </a>

        <a href="../inventario/inventario.html">
            <i class="fa-solid fa-clipboard-list"></i>
            Inventario
        </a>

        <a href="../compras/compras.html">
            <i class="fa-solid fa-cart-shopping"></i>
            Compras
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

        <a
            href="incidencias.php"
            class="activo"
            aria-current="page"
        >
            <i class="fa-solid fa-triangle-exclamation"></i>
            Incidencias
        </a>

    </nav>
=======
            <?php if (esAdministradorOGerente()): ?>

                <a href="../respaldo/respaldo.php">
                    <i class="fa-solid fa-database"></i>
                    Respaldos
                </a>

            <?php endif; ?>

>>>>>>> Stashed changes
           <?php if (esAdministrador()): ?>

                <a href="../incidencias/incidencias.php">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Incidencias
                </a>

            <?php endif; ?>

        </nav>

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes

        <div class="pie-barra-lateral">

            <a href="../perfil/perfil.php">
                <i class="fa-solid fa-user"></i>
                Mi perfil
            </a>

            <a href="../index/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </div>

    </aside>

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
    <!-- COLUMNA PRINCIPAL -->
    <div class="columna-principal">

=======
>>>>>>> Stashed changes

    <!-- =====================================================
         COLUMNA PRINCIPAL
    ====================================================== -->

    <div class="columna-principal">

        <!-- =================================================
             BARRA SUPERIOR
        ================================================== -->

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
        <header class="barra-superior">

            <button
                type="button"
                class="boton-menu"
                id="botonMenu"
<<<<<<< Updated upstream
                aria-label="Abrir menú"
=======
<<<<<<< HEAD
                aria-label="Abrir o cerrar el menú lateral"
=======
                aria-label="Abrir menú"
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
            >
                <i class="fa-solid fa-bars"></i>
            </button>

<<<<<<< Updated upstream

            <div class="buscador-superior">

=======
<<<<<<< HEAD
            <div class="buscador-superior">
=======

            <div class="buscador-superior">

>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    placeholder="Buscar..."
                    aria-label="Buscar"
                >
<<<<<<< Updated upstream
=======
<<<<<<< HEAD
            </div>

=======
>>>>>>> Stashed changes

            </div>


<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
            <div class="acciones-superiores">

                <button
                    type="button"
                    class="boton-notificacion"
                    aria-label="Notificaciones"
                >
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador">0</span>
                </button>

<<<<<<< Updated upstream

=======
<<<<<<< HEAD
=======

>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                <div class="perfil-superior">

                    <div class="perfil-superior-icono">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div class="perfil-superior-texto">
<<<<<<< Updated upstream
=======
<<<<<<< HEAD
                        <strong><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span><?= htmlspecialchars($rolUsuario, ENT_QUOTES, 'UTF-8') ?></span>
=======
>>>>>>> Stashed changes

                        <strong>
                            <?= htmlspecialchars($nombreCompleto) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($_SESSION["usuario"]["nombre_rol"] ?? "Usuario") ?>
                        </span>

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                    </div>

                </div>

            </div>

        </header>

        <main class="contenido-app">

<<<<<<< Updated upstream
        <!-- =================================================
             CONTENIDO
        ================================================== -->

=======
<<<<<<< HEAD
            <!-- ENCABEZADO -->
            <section class="encabezado-respaldo">

                <div>
=======
        <!-- =================================================
             CONTENIDO
        ================================================== -->

>>>>>>> Stashed changes
        <section class="contenido-app">


            <!-- ENCABEZADO -->

            <div class="encabezado-pagina">

                <div>

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                    <h1>
                        <i class="fa-solid fa-database"></i>
                        Gestión de respaldos
                    </h1>

                    <p>
<<<<<<< Updated upstream
                        Generación y seguimiento de los respaldos del sistema.
=======
<<<<<<< HEAD
                        Consulta el historial y genera respaldos de la base de datos.
=======
                        Generación y seguimiento de los respaldos del sistema.
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                    </p>
                </div>

                <button
                    type="button"
                    id="btnRegistrarRespaldo"
                    class="boton boton-azul"
                >
                    <i class="fa-solid fa-database"></i>
                    Generar respaldo
                </button>

            </section>

            <p id="mensajeRespaldo" role="status" aria-live="polite"></p>

            <!-- ÚLTIMO RESPALDO -->
            <section class="tarjeta-respaldo">

                <div class="encabezado-seccion-respaldo">
                    <h2>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Último respaldo
                    </h2>
                </div>

                <div class="respaldo-principal">

                    <div class="dato-respaldo">
                        <h3>Fecha y hora</h3>
                        <p id="fechaUltimoRespaldo">Cargando...</p>
                    </div>

                    <div class="dato-respaldo">
                        <h3>Estado</h3>
                        <p id="estadoUltimoRespaldo">Cargando...</p>
                    </div>

                    <div class="dato-respaldo">
                        <h3>Tipo</h3>
                        <p id="tipoUltimoRespaldo">Cargando...</p>
                    </div>

                </div>

            </section>

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
            <!-- HISTORIAL -->
            <section class="tarjeta-respaldo">

                <div class="encabezado-seccion-respaldo">
                    <h2>
                        <i class="fa-solid fa-list"></i>
                        Historial de respaldos
                    </h2>
                </div>

                <div class="tabla-respaldo">

                    <table>

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Cobertura</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>

                        <tbody id="tablaRespaldos">
                            <tr>
                                <td colspan="5" class="mensaje-respaldo">
                                    Cargando respaldos...
                                </td>
                            </tr>
=======
>>>>>>> Stashed changes
                <button
                    type="button"
                    id="btnRegistrarRespaldo"
                    class="boton boton-azul"
                >
                    <i class="fa-solid fa-database"></i>
                    Generar respaldo
                </button>

            </div>


            <!-- =================================================
                 ESTADÍSTICAS
            ================================================== -->

            <div class="grilla-estadisticas">


                <div class="tarjeta-estadistica borde-azul">

                    <div class="icono-estadistica">
                        <i class="fa-solid fa-database"></i>
                    </div>

                    <div>

                        <span>
                            Último respaldo
                        </span>

                        <strong id="fechaUltimoRespaldo">
                            Cargando...
                        </strong>

                    </div>

                </div>


                <div class="tarjeta-estadistica borde-verde">

                    <div class="icono-estadistica">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>

                        <span>
                            Estado
                        </span>

                        <strong id="estadoUltimoRespaldo">
                            Cargando...
                        </strong>

                    </div>

                </div>


                <div class="tarjeta-estadistica borde-morado">

                    <div class="icono-estadistica">
                        <i class="fa-solid fa-file-zipper"></i>
                    </div>

                    <div>

                        <span>
                            Tipo
                        </span>

                        <strong id="tipoUltimoRespaldo">
                            Cargando...
                        </strong>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 HISTORIAL
            ================================================== -->

            <section class="panel">

                <div class="panel-encabezado">

                    <div>

                        <h2>
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            Historial de respaldos
                        </h2>

                        <p class="descripcion-panel">
                            Consulta los respaldos generados y su información.
                        </p>

                    </div>

                </div>


                <div class="contenedor-tabla">

                    <table class="tabla-datos">

                        <thead>

                            <tr>

                                <th>Fecha</th>

                                <th>Tipo</th>

                                <th>Estado</th>

                                <th>Cobertura</th>

                                <th>Usuario</th>

                            </tr>

                        </thead>


                        <tbody id="tablaRespaldos">

                            <tr>

                                <td
                                    colspan="5"
                                    class="estado-vacio-productos"
                                >
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                    Cargando respaldos...
                                </td>

                            </tr>

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                        </tbody>

                    </table>

                </div>

            </section>

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
        </main>

    </div>
=======
>>>>>>> Stashed changes

        </section>

     </div>

</body>
<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes

    <script src="../js/app.js"></script>

<<<<<<< Updated upstream
<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="../js/app.js"></script>

<script>

const fechaUltimoRespaldo =
    document.getElementById("fechaUltimoRespaldo");

=======
<<<<<<< HEAD
    <script>
        const fechaUltimoRespaldo =
            document.getElementById("fechaUltimoRespaldo");

        const estadoUltimoRespaldo =
            document.getElementById("estadoUltimoRespaldo");

        const tipoUltimoRespaldo =
            document.getElementById("tipoUltimoRespaldo");

        const tablaRespaldos =
            document.getElementById("tablaRespaldos");
=======
<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="../js/app.js"></script>

<script>

const fechaUltimoRespaldo =
    document.getElementById("fechaUltimoRespaldo");

>>>>>>> Stashed changes
const estadoUltimoRespaldo =
    document.getElementById("estadoUltimoRespaldo");

const tipoUltimoRespaldo =
    document.getElementById("tipoUltimoRespaldo");

const tablaRespaldos =
    document.getElementById("tablaRespaldos");


function escaparHTML(valor) {

    if (
        valor === null ||
        valor === undefined
    ) {
        return "";
    }

    return String(valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


function formatearFecha(fecha) {

    if (!fecha) {
        return "-";
    }

    const fechaObjeto = new Date(fecha);

    if (
        Number.isNaN(
            fechaObjeto.getTime()
        )
    ) {
        return fecha;
    }

    return fechaObjeto.toLocaleString("es-CR");
}


function obtenerClaseEstado(estado) {

    const estadoNormalizado =
        String(estado || "")
            .toUpperCase();

    if (
        estadoNormalizado.includes("EXITOS") ||
        estadoNormalizado.includes("COMPLET")
    ) {
        return "estado-exito";
    }

    if (
        estadoNormalizado.includes("ERROR") ||
        estadoNormalizado.includes("FALL")
    ) {
        return "estado-error";
    }

    if (
        estadoNormalizado.includes("PROCES")
    ) {
        return "estado-proceso";
    }

    return "estado-neutral";
}


function formatearEstado(estado) {

    const valor =
        String(estado || "-");

    const clase =
        obtenerClaseEstado(valor);

    return `
        <span class="estado-badge ${clase}">
            ${escaparHTML(valor)}
        </span>
    `;
}


async function cargarRespaldos() {

    try {

        const respuesta =
            await fetch(
                "../api/respaldo/listar.php"
            );
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef

        const mensajeRespaldo =
            document.getElementById("mensajeRespaldo");

<<<<<<< Updated upstream
        const resultado =
            await respuesta.json();

=======
        const botonRegistrarRespaldo =
            document.getElementById("btnRegistrarRespaldo");

<<<<<<< HEAD
        function escaparHTML(valor) {
            if (valor === null || valor === undefined) {
                return "";
            }

            return String(valor)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatearFecha(fecha) {
            if (!fecha) {
                return "-";
            }

            const fechaObjeto = new Date(fecha);

            if (Number.isNaN(fechaObjeto.getTime())) {
                return fecha;
            }

            return fechaObjeto.toLocaleString("es-CR");
        }

        async function cargarRespaldos() {
            try {
                const respuesta = await fetch(
                    "../api/respaldo/listar.php"
                );

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.success) {
                    throw new Error(
                        resultado.message ||
                        "No fue posible consultar los respaldos."
                    );
                }
=======
        const resultado =
            await respuesta.json();

>>>>>>> Stashed changes

        if (
            !respuesta.ok ||
            !resultado.success
        ) {

            throw new Error(
                resultado.message ||
                "No fue posible consultar los respaldos."
            );

        }


        const respaldos =
            resultado.data || [];


        if (respaldos.length === 0) {

            fechaUltimoRespaldo.textContent =
                "Sin registros";

            estadoUltimoRespaldo.textContent =
                "Sin registros";

            tipoUltimoRespaldo.textContent =
                "Sin registros";


            tablaRespaldos.innerHTML = `

                <tr>

                    <td
                        colspan="5"
                        class="estado-vacio-productos"
                    >
<<<<<<< Updated upstream

                        <i class="fa-solid fa-database"></i>

                        <span>
                            No hay respaldos registrados.
                        </span>

                    </td>

                </tr>

            `;

            return;
        }
=======
>>>>>>> Stashed changes

                        <i class="fa-solid fa-database"></i>

<<<<<<< Updated upstream
        const ultimo =
            respaldos[0];
=======
                        <span>
                            No hay respaldos registrados.
                        </span>
>>>>>>> Stashed changes

                    </td>

<<<<<<< Updated upstream
        fechaUltimoRespaldo.textContent =
            formatearFecha(
                ultimo.fecha_inicio
            );


=======
                </tr>

            `;

            return;
        }
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef

                const respaldos = resultado.data || [];

<<<<<<< HEAD
                if (respaldos.length === 0) {
                    fechaUltimoRespaldo.textContent = "Sin registros";
                    estadoUltimoRespaldo.textContent = "Sin registros";
                    tipoUltimoRespaldo.textContent = "Sin registros";
=======
        const ultimo =
            respaldos[0];


        fechaUltimoRespaldo.textContent =
            formatearFecha(
                ultimo.fecha_inicio
            );

>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef

>>>>>>> Stashed changes
        estadoUltimoRespaldo.innerHTML =
            formatearEstado(
                ultimo.estado
            );


        tipoUltimoRespaldo.textContent =
            ultimo.tipo || "-";


        tablaRespaldos.innerHTML =
            respaldos.map(
                function (respaldo) {

                    return `

                        <tr>
<<<<<<< Updated upstream
=======
<<<<<<< HEAD
                            <td colspan="5" class="mensaje-respaldo">
                                No hay respaldos registrados.
=======
>>>>>>> Stashed changes

                            <td>
                                ${escaparHTML(
                                    formatearFecha(
                                        respaldo.fecha_inicio
                                    )
                                )}
<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
                            </td>


                            <td>
                                <span class="etiqueta-tabla">
                                    ${escaparHTML(
                                        respaldo.tipo || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                ${formatearEstado(
                                    respaldo.estado
                                )}
                            </td>


                            <td>
                                ${escaparHTML(
                                    respaldo.cobertura || "-"
                                )}
                            </td>


                            <td>
                                <strong>
                                    ${escaparHTML(
                                        respaldo.usuario || "-"
                                    )}
                                </strong>
                            </td>

                        </tr>

                    `;

<<<<<<< Updated upstream
                }
            ).join("");


    } catch (error) {

        console.error(error);


        fechaUltimoRespaldo.textContent =
            "No disponible";

        estadoUltimoRespaldo.textContent =
            "No disponible";

        tipoUltimoRespaldo.textContent =
            "No disponible";


        tablaRespaldos.innerHTML = `

            <tr>

                <td
                    colspan="5"
                    class="estado-vacio-productos"
                >

                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <span>
                        No fue posible cargar los respaldos.
                    </span>

                </td>

            </tr>

        `;

    }

}
=======
<<<<<<< HEAD
                    return;
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
                }
            ).join("");

<<<<<<< HEAD
                const ultimo = respaldos[0];

                fechaUltimoRespaldo.textContent =
                    formatearFecha(ultimo.fecha_inicio);
=======

    } catch (error) {

        console.error(error);


        fechaUltimoRespaldo.textContent =
            "No disponible";
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef

        estadoUltimoRespaldo.textContent =
            "No disponible";

        tipoUltimoRespaldo.textContent =
            "No disponible";
>>>>>>> Stashed changes

<<<<<<< HEAD
                tablaRespaldos.innerHTML = respaldos.map(
                    function(respaldo) {
                        return `
                            <tr>
                                <td>
                                    ${escaparHTML(
                                        formatearFecha(respaldo.fecha_inicio)
                                    )}
                                </td>
                                <td>${escaparHTML(respaldo.tipo || "-")}</td>
                                <td>${escaparHTML(respaldo.estado || "-")}</td>
                                <td>${escaparHTML(respaldo.cobertura || "-")}</td>
                                <td>${escaparHTML(respaldo.usuario || "-")}</td>
                            </tr>
                        `;
                    }
                ).join("");

<<<<<<< Updated upstream
cargarRespaldos();


=======
            } catch (error) {
                console.error("Error consultando respaldos:", error);

                fechaUltimoRespaldo.textContent = "No disponible";
                estadoUltimoRespaldo.textContent = "No disponible";
                tipoUltimoRespaldo.textContent = "No disponible";

                tablaRespaldos.innerHTML = `
                    <tr>
                        <td colspan="5" class="mensaje-respaldo">
                            No fue posible cargar los respaldos.
                        </td>
                    </tr>
                `;

                mensajeRespaldo.textContent =
                    error.message || "Error al consultar los respaldos.";
            }
        }

        botonRegistrarRespaldo.addEventListener(
            "click",
            async function() {
                const boton = this;

                mensajeRespaldo.textContent = "";

                try {
                    boton.disabled = true;
                    boton.innerHTML = `
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Generando...
                    `;

                    const respuesta = await fetch(
=======

        tablaRespaldos.innerHTML = `

            <tr>

                <td
                    colspan="5"
                    class="estado-vacio-productos"
                >

                    <i class="fa-solid fa-triangle-exclamation"></i>

                    <span>
                        No fue posible cargar los respaldos.
                    </span>

                </td>

            </tr>

        `;

    }

}


cargarRespaldos();


>>>>>>> Stashed changes
document
    .getElementById("btnRegistrarRespaldo")
    .addEventListener(
        "click",
        async function () {

            const boton = this;


            try {

                boton.disabled = true;

                boton.innerHTML = `
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    Generando...
                `;


                const respuesta =
                    await fetch(
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
                        "../api/respaldo/registrar.php",
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            }
                        }
                    );

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
                    const resultado = await respuesta.json();

                    if (!respuesta.ok || !resultado.success) {
                        throw new Error(
                            resultado.message ||
                            "No fue posible generar el respaldo."
                        );
                    }
=======
>>>>>>> Stashed changes

                const resultado =
                    await respuesta.json();


                if (
                    !respuesta.ok ||
                    !resultado.success
                ) {
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef

                    alert(
                        "Respaldo generado correctamente.\n\n" +
                        "Archivo: " + resultado.archivo + "\n" +
                        "Tamaño: " + resultado.tamano + " bytes"
                    );

                    await cargarRespaldos();

                } catch (error) {
                    console.error("Error generando respaldo:", error);

                    alert(
                        error.message ||
                        "Ocurrió un error al generar el respaldo."
                    );

                } finally {
                    boton.disabled = false;
                    boton.innerHTML = `
                        <i class="fa-solid fa-database"></i>
                        Generar respaldo
                    `;
                }
<<<<<<< HEAD
=======



                alert(
                    "Respaldo generado correctamente.\n\n" +
                    "Archivo: " +
                    resultado.archivo +
                    "\n" +
                    "Tamaño: " +
                    resultado.tamano +
                    " bytes"
                );


                await cargarRespaldos();


            } catch (error) {

                console.error(error);

                alert(
                    error.message ||
                    "Ocurrió un error al generar el respaldo."
                );


            } finally {

                boton.disabled = false;

                boton.innerHTML = `
                    <i class="fa-solid fa-database"></i>
                    Generar respaldo
                `;

<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes
            }
        );

<<<<<<< Updated upstream
=======
<<<<<<< HEAD
        cargarRespaldos();
    </script>
=======
>>>>>>> Stashed changes
        }
    );

</script>
<<<<<<< Updated upstream
=======
>>>>>>> e3f3c22ec65a82610ae5ceb6b991bd6bd0f5cfef
>>>>>>> Stashed changes

</body>
</html>
