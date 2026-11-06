<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";

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


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de máquinas expendedoras de Vending Mini Market">

    <title>Nueva máquina | Vending Mini Market</title>

    <?php vmEstilosGlobales(["catalogo.css"]); ?>
</head>

<body class="pagina-app">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->
    <?php vmBarraLateral(); ?>

    <!-- ==========================================
         COLUMNA PRINCIPAL
    =========================================== -->
    <div class="columna-principal">

        <?php vmBarraSuperior(); ?>

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
                            <label for="modeloMaquina">Modelo / Número de serie</label>
                            <input type="text" id="modeloMaquina" maxlength="100"
                                   autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="tipoMaquina">Tipo</label>
                            <input type="text" id="tipoMaquina" maxlength="100"
                                   placeholder="Ejemplo: Bebidas"
                                   autocomplete="off">
                        </div>

                        <div class="grupo-formulario" id="grupoEstadoMaquina" hidden>
                            <label for="estadoMaquina">Estado *</label>
                            <select id="estadoMaquina" required>
                                <option value="true">Activa</option>
                                <option value="false">Inactiva</option>
                            </select>
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

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/maquina-form.js?v=<?= filemtime(__DIR__ . '/../js/maquina-form.js') ?>"></script>

</body>
</html>
