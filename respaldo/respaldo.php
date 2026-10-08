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
    echo "Acceso no autorizado.";
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$nombreUsuario = $_SESSION["usuario"]["nombre"] ?? "";
$apellidoUsuario = $_SESSION["usuario"]["apellido"] ?? "";
$nombreCompleto = trim($nombreUsuario . " " . $apellidoUsuario);

if ($nombreCompleto === "") {
    $nombreCompleto = "Usuario";
}

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Gestión de respaldos | Vending Mini Market"
    >

    <title>Respaldos | Vending Mini Market</title>

    <link
        rel="stylesheet"
        href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>



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


            <?php if (esAdministradorOGerente()): ?>

                <a href="../respaldo/respaldo.php">
                    <i class="fa-solid fa-database"></i>
                    Respaldos
                </a>

            <?php endif; ?>

           <?php if (esAdministrador()): ?>

                <a href="../incidencias/incidencias.php">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Incidencias
                </a>

            <?php endif; ?>

        </nav>


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


    <!-- =====================================================
         COLUMNA PRINCIPAL
    ====================================================== -->

    <div class="columna-principal">

        <!-- =================================================
             BARRA SUPERIOR
        ================================================== -->

        <header class="barra-superior">

            <button
                type="button"
                class="boton-menu"
                id="botonMenu"
                aria-label="Abrir menú"
            >
                <i class="fa-solid fa-bars"></i>
            </button>


            <div class="buscador-superior">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    placeholder="Buscar..."
                    aria-label="Buscar"
                >

            </div>


            <div class="acciones-superiores">

                <button
                    type="button"
                    class="boton-notificacion"
                    aria-label="Notificaciones"
                >
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador">0</span>
                </button>


                <div class="perfil-superior">

                    <div class="perfil-superior-icono">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div class="perfil-superior-texto">

                        <strong>
                            <?= htmlspecialchars($nombreCompleto) ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars($_SESSION["usuario"]["nombre_rol"] ?? "Usuario") ?>
                        </span>

                    </div>

                </div>

            </div>

        </header>


        <!-- =================================================
             CONTENIDO
        ================================================== -->

        <section class="contenido-app">


            <!-- ENCABEZADO -->

            <div class="encabezado-pagina">

                <div>

                    <h1>
                        <i class="fa-solid fa-database"></i>
                        Gestión de respaldos
                    </h1>

                    <p>
                        Generación y seguimiento de los respaldos del sistema.
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

                        </tbody>

                    </table>

                </div>

            </section>


        </section>

     </div>

</body>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="../js/app.js"></script>

<script>

const fechaUltimoRespaldo =
    document.getElementById("fechaUltimoRespaldo");

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


        const resultado =
            await respuesta.json();


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

                        <i class="fa-solid fa-database"></i>

                        <span>
                            No hay respaldos registrados.
                        </span>

                    </td>

                </tr>

            `;

            return;
        }


        const ultimo =
            respaldos[0];


        fechaUltimoRespaldo.textContent =
            formatearFecha(
                ultimo.fecha_inicio
            );


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

                            <td>
                                ${escaparHTML(
                                    formatearFecha(
                                        respaldo.fecha_inicio
                                    )
                                )}
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


cargarRespaldos();


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
                        "../api/respaldo/registrar.php",
                        {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            }
                        }
                    );


                const resultado =
                    await respuesta.json();


                if (
                    !respuesta.ok ||
                    !resultado.success
                ) {

                    throw new Error(
                        resultado.message ||
                        "No fue posible generar el respaldo."
                    );

                }


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

            }

        }
    );

</script>

</body>

</html>