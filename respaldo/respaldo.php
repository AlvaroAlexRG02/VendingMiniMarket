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

    <style>

        body.pagina-respaldo {
            padding: 35px 45px;
            box-sizing: border-box;
        }

        .contenedor-respaldo {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        .encabezado-respaldo {
            margin-bottom: 30px;
        }

        .encabezado-respaldo h1 {
            margin-bottom: 8px;
        }

        .tarjeta-respaldo {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-sizing: border-box;
        }

        .respaldo-principal {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .dato-respaldo {
            padding: 20px;
            border: 1px solid #e1e5eb;
            border-radius: 10px;
        }

        .dato-respaldo h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .dato-respaldo p {
            margin: 0;
        }

        .tabla-respaldo {
            width: 100%;
            overflow-x: auto;
        }

        .tabla-respaldo table {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-respaldo th,
        .tabla-respaldo td {
            padding: 12px 14px;
            text-align: left;
            white-space: nowrap;
        }

        .tabla-respaldo th {
            font-weight: 700;
        }

        .mensaje-respaldo {
            padding: 20px;
            text-align: center;
        }

        @media (max-width: 900px) {

            body.pagina-respaldo {
                padding: 25px 20px;
            }

            .respaldo-principal {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body class="pagina-respaldo">

    <main class="contenedor-respaldo">


        <!-- ==========================================
             ENCABEZADO
        =========================================== -->

        <header class="encabezado-respaldo">

            <h1>
                <i class="fa-solid fa-database"></i>
                Gestión de respaldos
            </h1>

          
            <p>
                Verificación y seguimiento de los respaldos del sistema.
            </p>

            <button
                type="button"
                id="btnRegistrarRespaldo"
                class="btn btn-primary"
            >
                Generar Respaldo
            </button>

        </header>


        <!-- ==========================================
             ÚLTIMO RESPALDO
        =========================================== -->

        <section class="tarjeta-respaldo">

            <h2>
                <i class="fa-solid fa-clock-rotate-left"></i>
                Último respaldo
            </h2>


            <div
                id="ultimoRespaldo"
                class="respaldo-principal"
            >

                <div class="dato-respaldo">

                    <h3>
                        Fecha
                    </h3>

                    <p id="fechaUltimoRespaldo">
                        Cargando...
                    </p>

                </div>


                <div class="dato-respaldo">

                    <h3>
                        Estado
                    </h3>

                    <p id="estadoUltimoRespaldo">
                        Cargando...
                    </p>

                </div>


                <div class="dato-respaldo">

                    <h3>
                        Tipo
                    </h3>

                    <p id="tipoUltimoRespaldo">
                        Cargando...
                    </p>

                </div>

            </div>

        </section>


        <!-- ==========================================
             HISTORIAL
        =========================================== -->

        <section class="tarjeta-respaldo">

            <h2>
                <i class="fa-solid fa-list"></i>
                Historial de respaldos
            </h2>


            <div class="tabla-respaldo">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Tipo
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Cobertura
                            </th>

                            <th>
                                Usuario
                            </th>

                        </tr>

                    </thead>


                    <tbody id="tablaRespaldos">

                        <tr>

                            <td
                                colspan="5"
                                class="mensaje-respaldo"
                            >
                                Cargando respaldos...

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>


    </main>


    <script>

        const fechaUltimoRespaldo =
            document.getElementById(
                "fechaUltimoRespaldo"
            );

        const estadoUltimoRespaldo =
            document.getElementById(
                "estadoUltimoRespaldo"
            );

        const tipoUltimoRespaldo =
            document.getElementById(
                "tipoUltimoRespaldo"
            );

        const tablaRespaldos =
            document.getElementById(
                "tablaRespaldos"
            );


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

            const fechaObjeto =
                new Date(fecha);

            if (
                Number.isNaN(
                    fechaObjeto.getTime()
                )
            ) {
                return fecha;
            }

            return fechaObjeto.toLocaleString(
                "es-CR"
            );

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


                /*
                 * No existen respaldos registrados.
                 */
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
                                class="mensaje-respaldo"
                            >
                                No hay respaldos registrados.
                            </td>
                        </tr>
                    `;

                    return;

                }


                /*
                 * El primer registro es el más reciente.
                 */
                const ultimo =
                    respaldos[0];


                fechaUltimoRespaldo.textContent =
                    formatearFecha(
                        ultimo.fecha_inicio
                    );

                estadoUltimoRespaldo.textContent =
                    ultimo.estado || "-";

                tipoUltimoRespaldo.textContent =
                    ultimo.tipo || "-";


                /*
                 * Historial.
                 */
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
                                        ${escaparHTML(
                                            respaldo.tipo || "-"
                                        )}
                                    </td>

                                    <td>
                                        ${escaparHTML(
                                            respaldo.estado || "-"
                                        )}
                                    </td>

                                    <td>
                                        ${escaparHTML(
                                            respaldo.cobertura || "-"
                                        )}
                                    </td>

                                    <td>
                                        ${escaparHTML(
                                            respaldo.usuario || "-"
                                        )}
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
                            class="mensaje-respaldo"
                        >
                            No fue posible cargar los respaldos.
                        </td>
                    </tr>
                `;

            }

        }


        cargarRespaldos();

            document
        .getElementById("btnRegistrarRespaldo")
        .addEventListener("click", async function () {

            const boton = this;

            try {

                boton.disabled = true;
                boton.textContent = "Generando respaldo...";

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

                cargarRespaldos();

            } catch (error) {

                console.error(error);

                alert(
                    error.message ||
                    "Ocurrió un error al generar el respaldo."
                );

            } finally {

                boton.disabled = false;
                boton.textContent = "Generar respaldo";
            }

        });

    </script>

</body>

</html>