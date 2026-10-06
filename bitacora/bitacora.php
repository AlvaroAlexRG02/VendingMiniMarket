<?php

require_once __DIR__ . "/../config/session.php";
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
        href="../css/estilos.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body class="pagina-bitacora">

    <div class="contenedor-bitacora">


        <!-- =========================
             ENCABEZADO
        ========================== -->

        <header class="encabezado-bitacora">

            <div>

                <h1>
                    <i class="fa-solid fa-clipboard-list"></i>
                    Bitácora de auditoría
                </h1>

                <p>
                    Consulta de acciones y eventos registrados en el sistema.
                </p>

            </div>

        </header>


        <!-- =========================
             FILTROS
        ========================== -->

        <section class="tarjeta-bitacora">

            <div class="encabezado-seccion">

                <h2>
                    <i class="fa-solid fa-filter"></i>
                    Filtros de consulta
                </h2>

            </div>


            <form
                id="formularioFiltros"
                class="filtros-bitacora"
            >

                <div class="filtro-bitacora">

                    <label for="usuario">
                        Usuario
                    </label>

                    <select id="usuario">

                        <option value="">
                            Todos los usuarios
                        </option>

                    </select>

                </div>


                <div class="filtro-bitacora">

                    <label for="fechaInicio">
                        Fecha inicial
                    </label>

                    <input
                        type="date"
                        id="fechaInicio"
                    >

                </div>


                <div class="filtro-bitacora">

                    <label for="fechaFin">
                        Fecha final
                    </label>

                    <input
                        type="date"
                        id="fechaFin"
                    >

                </div>


                <div class="filtro-bitacora">

                    <label for="entidad">
                        Módulo
                    </label>

                    <select id="entidad">

                        <option value="">
                            Todos los módulos
                        </option>

                    </select>

                </div>


                <div class="filtro-bitacora">

                    <label for="accion">
                        Acción
                    </label>

                    <select id="accion">

                        <option value="">
                            Todas las acciones
                        </option>

                    </select>

                </div>


                <div class="filtro-bitacora">

                    <label for="resultado">
                        Resultado
                    </label>

                    <select id="resultado">

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


                <div class="acciones-bitacora">

                    <button
                        type="submit"
                        class="boton boton-primario"
                    >
                        <i class="fa-solid fa-magnifying-glass"></i>
                        Buscar
                    </button>

                    <button
                        type="button"
                        id="botonLimpiar"
                        class="boton boton-secundario"
                    >
                        <i class="fa-solid fa-rotate-left"></i>
                        Limpiar
                    </button>

                </div>

            </form>

        </section>


        <!-- =========================
             MENSAJE
        ========================== -->

        <p
            id="mensajeBitacora"
            aria-live="polite"
        ></p>


        <!-- =========================
             TABLA
        ========================== -->

        <section class="tarjeta">

            <div class="encabezado-seccion">

                <h2>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Registros de auditoría
                </h2>

            </div>


            <div class="tabla-bitacora-contenedor">

                <table>

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

                            <td colspan="7">
                                Cargando registros...
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>


    </div>


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

            const fechaObjeto = new Date(fecha);

            if (Number.isNaN(fechaObjeto.getTime())) {
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

        /*
        * Carga las opciones de los filtros.
        */
        async function cargarOpcionesFiltros() {

            try {

                const respuesta = await fetch(
                    "../api/bitacora/opciones.php"
                );

                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.success) {

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

                datos.usuarios.forEach(function (usuario) {

                    const opcion =
                        document.createElement("option");

                    opcion.value =
                        usuario.id_usuario;

                    opcion.textContent =
                        usuario.usuario;

                    selectUsuario.appendChild(opcion);

                });


                /*
                * Entidades / módulos
                */
                const selectEntidad =
                    document.getElementById("entidad");

                datos.entidades.forEach(function (entidad) {

                    const opcion =
                        document.createElement("option");

                    opcion.value =
                        entidad.entidad;

                    opcion.textContent =
                        entidad.entidad;

                    selectEntidad.appendChild(opcion);

                });


                /*
                * Acciones
                */
                const selectAccion =
                    document.getElementById("accion");

                datos.acciones.forEach(function (accion) {

                    const opcion =
                        document.createElement("option");

                    opcion.value =
                        accion.accion;

                    opcion.textContent =
                        accion.accion;

                    selectAccion.appendChild(opcion);

                });

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
                    <td colspan="7">
                        Cargando registros...
                    </td>
                </tr>
            `;

            mensajeBitacora.textContent = "";


            const parametros = new URLSearchParams();


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
                parametros.append("usuario", usuario);
            }

            if (fechaInicio) {
                parametros.append("fecha_inicio", fechaInicio);
            }

            if (fechaFin) {
                parametros.append("fecha_fin", fechaFin);
            }

            if (entidad) {
                parametros.append("entidad", entidad);
            }

            if (accion) {
                parametros.append("accion", accion);
            }

            if (resultado) {
                parametros.append("resultado", resultado);
            }


            try {

                const respuesta = await fetch(
                    "../api/bitacora/listar.php?" +
                    parametros.toString()
                );


                const datos = await respuesta.json();


                if (!respuesta.ok || !datos.success) {

                    tablaBitacora.innerHTML = `
                        <tr>
                            <td colspan="7">
                                No fue posible cargar la bitácora.
                            </td>
                        </tr>
                    `;

                    mensajeBitacora.textContent =
                        datos.message ||
                        "No fue posible consultar los registros.";

                    return;
                }


                const registros = datos.data || [];


                if (registros.length === 0) {

                    tablaBitacora.innerHTML = `
                        <tr>
                            <td colspan="7">
                                No se encontraron registros.
                            </td>
                        </tr>
                    `;

                    return;
                }


                tablaBitacora.innerHTML =
                    registros.map(function (registro) {

                        return `
                            <tr>

                                <td>
                                    ${escaparHTML(
                                        formatearFecha(registro.fecha)
                                    )}
                                </td>

                                <td>
                                    ${escaparHTML(
                                        registro.usuario || "-"
                                    )}
                                </td>

                                <td>
                                    ${escaparHTML(
                                        registro.tienda || "-"
                                    )}
                                </td>

                                <td>
                                    ${escaparHTML(
                                        registro.entidad || "-"
                                    )}
                                </td>

                                <td>
                                    ${escaparHTML(
                                        registro.accion || "-"
                                    )}
                                </td>

                                <td>
                                    ${escaparHTML(
                                        registro.resultado || "-"
                                    )}
                                </td>

                                <td>
                                    ${escaparHTML(
                                        registro.ip_origen || "-"
                                    )}
                                </td>

                            </tr>
                        `;

                    }).join("");


            } catch (error) {

                console.error(error);

                tablaBitacora.innerHTML = `
                    <tr>
                        <td colspan="7">
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

</html>