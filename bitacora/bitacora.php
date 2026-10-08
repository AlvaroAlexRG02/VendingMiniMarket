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

$nombreCompleto = trim(
    $nombreUsuario . " " . $apellidoUsuario
);

if ($nombreCompleto === "") {
    $nombreCompleto = "Usuario";
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Bitácora de auditoría | Vending Mini Market"
    >

    <title>Bitácora | Vending Mini Market</title>

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

                            <?= htmlspecialchars(
                                $_SESSION["usuario"]["nombre_rol"]
                                ?? "Usuario"
                            ) ?>

                        </span>

                    </div>

                </div>

            </div>

        </header>


        <!-- =================================================
             CONTENIDO
        ================================================== -->

        <section class="contenido-app">


            <!-- =================================================
                 ENCABEZADO DE PÁGINA
            ================================================== -->

            <div class="encabezado-pagina">

                <div>

                    <h1>

                        <i class="fa-solid fa-clipboard-list"></i>

                        Bitácora de auditoría

                    </h1>


                    <p>

                        Consulta de acciones y eventos registrados
                        en el sistema.

                    </p>

                </div>

            </div>


            <!-- =================================================
                 FILTROS
            ================================================== -->

            <section class="panel">


                <div class="panel-encabezado">

                    <div>

                        <h2>

                            <i class="fa-solid fa-filter"></i>

                            Filtros de consulta

                        </h2>


                        <p class="descripcion-panel">

                            Utiliza los filtros para consultar
                            los registros de auditoría.

                        </p>

                    </div>

                </div>


                <form
                    id="formularioFiltros"
                    class="barra-filtros"
                >


                    <div class="grupo-formulario">

                        <label for="usuario">

                            Usuario

                        </label>


                        <select
                            id="usuario"
                            class="selector-filtro"
                        >

                            <option value="">

                                Todos los usuarios

                            </option>

                        </select>

                    </div>


                    <div class="grupo-formulario">

                        <label for="fechaInicio">

                            Fecha inicial

                        </label>


                        <input
                            type="date"
                            id="fechaInicio"
                            class="selector-filtro"
                        >

                    </div>


                    <div class="grupo-formulario">

                        <label for="fechaFin">

                            Fecha final

                        </label>


                        <input
                            type="date"
                            id="fechaFin"
                            class="selector-filtro"
                        >

                    </div>


                    <div class="grupo-formulario">

                        <label for="entidad">

                            Módulo

                        </label>


                        <select
                            id="entidad"
                            class="selector-filtro"
                        >

                            <option value="">

                                Todos los módulos

                            </option>

                        </select>

                    </div>


                    <div class="grupo-formulario">

                        <label for="accion">

                            Acción

                        </label>


                        <select
                            id="accion"
                            class="selector-filtro"
                        >

                            <option value="">

                                Todas las acciones

                            </option>

                        </select>

                    </div>


                    <div class="grupo-formulario">

                        <label for="resultado">

                            Resultado

                        </label>


                        <select
                            id="resultado"
                            class="selector-filtro"
                        >

                            <option value="">

                                Todos los resultados

                            </option>


                            <option value="EXITOSO">

                                Exitoso

                            </option>


                            <option value="ERROR">

                                Error

                            </option>

                        </select>

                    </div>


                    <div class="acciones-modal-producto">

                        <button
                            type="submit"
                            class="boton boton-azul"
                        >

                            <i class="fa-solid fa-magnifying-glass"></i>

                            Buscar

                        </button>


                        <button
                            type="button"
                            id="botonLimpiar"
                            class="boton boton-borde"
                        >

                            <i class="fa-solid fa-rotate-left"></i>

                            Limpiar

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 MENSAJE
            ================================================== -->

            <p
                id="mensajeBitacora"
                aria-live="polite"
            ></p>


            <!-- =================================================
                 REGISTROS
            ================================================== -->

            <section class="panel">


                <div class="panel-encabezado">

                    <div>

                        <h2>

                            <i class="fa-solid fa-clock-rotate-left"></i>

                            Registros de auditoría

                        </h2>


                        <p class="descripcion-panel">

                            Historial de acciones realizadas
                            dentro del sistema.

                        </p>

                    </div>

                </div>


                <div class="contenedor-tabla">


                    <table class="tabla-datos">


                        <thead>

                            <tr>

                                <th>

                                    Fecha

                                </th>


                                <th>

                                    Usuario

                                </th>


                                <th>

                                    Tienda

                                </th>


                                <th>

                                    Módulo

                                </th>


                                <th>

                                    Acción

                                </th>


                                <th>

                                    Resultado

                                </th>


                                <th>

                                    IP

                                </th>

                            </tr>

                        </thead>


                        <tbody id="tablaBitacora">


                            <tr>

                                <td
                                    colspan="7"
                                    class="estado-vacio-productos"
                                >

                                    <i class="fa-solid fa-spinner fa-spin"></i>

                                    Cargando registros...

                                </td>

                            </tr>


                        </tbody>


                    </table>

                </div>

            </section>


        </section>

    </div>


    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script src="../js/app.js"></script>


    <script>

        const formularioFiltros =
            document.getElementById("formularioFiltros");

        const botonLimpiar =
            document.getElementById("botonLimpiar");

        const tablaBitacora =
            document.getElementById("tablaBitacora");

        const mensajeBitacora =
            document.getElementById("mensajeBitacora");


        /*
         * Formatea la fecha recibida
         * desde PostgreSQL.
         */

        function formatearFecha(fecha) {

            if (!fecha) {

                return "-";

            }


            const fechaObjeto =
                new Date(fecha);


            if (
                Number.isNaN(
                    fechaObjeto.getTime()
                )
            ) {

                return fecha;

            }


            return fechaObjeto.toLocaleString("es-CR");

        }


        /*
         * Escapa contenido HTML para evitar
         * insertar directamente datos provenientes
         * de la base de datos.
         */

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


        /*
         * Carga las opciones de los filtros.
         */

        async function cargarOpcionesFiltros() {

            try {

                const respuesta =
                    await fetch(
                        "../api/bitacora/opciones.php"
                    );


                const datos =
                    await respuesta.json();


                if (
                    !respuesta.ok ||
                    !datos.success
                ) {

                    console.error(
                        datos.message ||
                        "No fue posible cargar las opciones."
                    );

                    return;

                }


                /*
                 * Usuarios
                 */

                const selectUsuario =
                    document.getElementById("usuario");


                datos.usuarios.forEach(
                    function (usuario) {

                        const opcion =
                            document.createElement("option");


                        opcion.value =
                            usuario.id_usuario;


                        opcion.textContent =
                            usuario.usuario;


                        selectUsuario.appendChild(
                            opcion
                        );

                    }
                );


                /*
                 * Entidades / módulos
                 */

                const selectEntidad =
                    document.getElementById("entidad");


                datos.entidades.forEach(
                    function (entidad) {

                        const opcion =
                            document.createElement("option");


                        opcion.value =
                            entidad.entidad;


                        opcion.textContent =
                            entidad.entidad;


                        selectEntidad.appendChild(
                            opcion
                        );

                    }
                );


                /*
                 * Acciones
                 */

                const selectAccion =
                    document.getElementById("accion");


                datos.acciones.forEach(
                    function (accion) {

                        const opcion =
                            document.createElement("option");


                        opcion.value =
                            accion.accion;


                        opcion.textContent =
                            accion.accion;


                        selectAccion.appendChild(
                            opcion
                        );

                    }
                );


            } catch (error) {

                console.error(
                    "Error cargando opciones:",
                    error
                );

            }

        }


        /*
         * Carga los registros de la bitácora.
         */

        async function cargarBitacora() {


            tablaBitacora.innerHTML = `

                <tr>

                    <td
                        colspan="7"
                        class="estado-vacio-productos"
                    >

                        <i class="fa-solid fa-spinner fa-spin"></i>

                        Cargando registros...

                    </td>

                </tr>

            `;


            mensajeBitacora.textContent = "";


            const parametros =
                new URLSearchParams();


            const usuario =
                document.getElementById("usuario").value;


            const fechaInicio =
                document.getElementById("fechaInicio").value;


            const fechaFin =
                document.getElementById("fechaFin").value;


            const entidad =
                document.getElementById("entidad").value;


            const accion =
                document.getElementById("accion").value;


            const resultado =
                document.getElementById("resultado").value;


            if (usuario) {

                parametros.append(
                    "usuario",
                    usuario
                );

            }


            if (fechaInicio) {

                parametros.append(
                    "fecha_inicio",
                    fechaInicio
                );

            }


            if (fechaFin) {

                parametros.append(
                    "fecha_fin",
                    fechaFin
                );

            }


            if (entidad) {

                parametros.append(
                    "entidad",
                    entidad
                );

            }


            if (accion) {

                parametros.append(
                    "accion",
                    accion
                );

            }


            if (resultado) {

                parametros.append(
                    "resultado",
                    resultado
                );

            }


            try {

                const respuesta =
                    await fetch(
                        "../api/bitacora/listar.php?" +
                        parametros.toString()
                    );


                const datos =
                    await respuesta.json();


                if (
                    !respuesta.ok ||
                    !datos.success
                ) {

                    tablaBitacora.innerHTML = `

                        <tr>

                            <td
                                colspan="7"
                                class="estado-vacio-productos"
                            >

                                No fue posible cargar
                                la bitácora.

                            </td>

                        </tr>

                    `;


                    mensajeBitacora.textContent =
                        datos.message ||
                        "No fue posible consultar los registros.";


                    return;

                }


                const registros =
                    datos.data || [];


                if (registros.length === 0) {

                    tablaBitacora.innerHTML = `

                        <tr>

                            <td
                                colspan="7"
                                class="estado-vacio-productos"
                            >

                                <i class="fa-solid fa-clipboard-list"></i>

                                No se encontraron registros.

                            </td>

                        </tr>

                    `;


                    return;

                }


                tablaBitacora.innerHTML =
                    registros.map(
                        function (registro) {

                            return `

                                <tr>

                                    <td>

                                        ${escaparHTML(
                                            formatearFecha(
                                                registro.fecha
                                            )
                                        )}

                                    </td>


                                    <td>

                                        <strong>

                                            ${escaparHTML(
                                                registro.usuario || "-"
                                            )}

                                        </strong>

                                    </td>


                                    <td>

                                        ${escaparHTML(
                                            registro.tienda || "-"
                                        )}

                                    </td>


                                    <td>

                                        <span class="etiqueta-tabla">

                                            ${escaparHTML(
                                                registro.entidad || "-"
                                            )}

                                        </span>

                                    </td>


                                    <td>

                                        ${escaparHTML(
                                            registro.accion || "-"
                                        )}

                                    </td>


                                    <td>

                                        <span class="estado-badge">

                                            ${escaparHTML(
                                                registro.resultado || "-"
                                            )}

                                        </span>

                                    </td>


                                    <td>

                                        ${escaparHTML(
                                            registro.ip_origen || "-"
                                        )}

                                    </td>

                                </tr>

                            `;

                        }
                    ).join("");


            } catch (error) {

                console.error(error);


                tablaBitacora.innerHTML = `

                    <tr>

                        <td
                            colspan="7"
                            class="estado-vacio-productos"
                        >

                            <i class="fa-solid fa-triangle-exclamation"></i>

                            No fue posible comunicarse
                            con el servidor.

                        </td>

                    </tr>

                `;

            }

        }


        /*
         * Buscar registros.
         */

        formularioFiltros.addEventListener(
            "submit",
            function (evento) {

                evento.preventDefault();

                cargarBitacora();

            }
        );


        /*
         * Limpiar filtros.
         */

        botonLimpiar.addEventListener(
            "click",
            function () {

                formularioFiltros.reset();

                cargarBitacora();

            }
        );


        /*
         * Cargar información al abrir la página.
         */

        cargarOpcionesFiltros();

        cargarBitacora();

    </script>


</body>

</html>s