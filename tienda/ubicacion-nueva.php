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


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro de ubicaciones de Vending Mini Market">

    <title>Nueva ubicación | Vending Mini Market</title>

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

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/ubicacion-form.js?v=<?= filemtime(__DIR__ . '/../js/ubicacion-form.js') ?>"></script>

</body>
</html>
