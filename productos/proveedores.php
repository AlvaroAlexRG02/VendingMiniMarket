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
    <meta name="description" content="Administración de proveedores de Vending Mini Market">

    <title>Proveedores | Vending Mini Market</title>

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
                    <h1>Proveedores</h1>
                    <p>Registra y mantiene los proveedores que se relacionan con productos, pedidos, facturas y compras.</p>
                </div>

                <a href="productos.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a productos
                </a>

            </div>

            <div id="mensajeProducto" class="mensaje-producto" aria-live="polite"></div>

            <!-- FORMULARIO -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2 id="tituloFormularioProveedor">Nuevo proveedor</h2>
                        <p class="descripcion-panel">El nombre no se puede repetir.</p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-truck-field"></i>
                    </div>
                </div>

                <form id="formularioProveedor">

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="nombreProveedor">Nombre *</label>
                            <input type="text" id="nombreProveedor" maxlength="255"
                                   autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="contactoProveedor">Persona de contacto</label>
                            <input type="text" id="contactoProveedor" maxlength="255"
                                   autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="telefonoProveedor">Teléfono</label>
                            <input type="tel" id="telefonoProveedor" maxlength="50"
                                   placeholder="Ejemplo: 2222-3333" autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="correoProveedor">Correo electrónico</label>
                            <input type="email" id="correoProveedor" maxlength="255"
                                   placeholder="proveedor@ejemplo.com" autocomplete="off">
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="direccionProveedor">Dirección</label>
                            <input type="text" id="direccionProveedor" maxlength="500"
                                   autocomplete="off">
                        </div>

                    </div>

                    <div class="acciones-formulario">

                        <button type="button" id="botonCancelarEdicion" class="boton boton-borde" hidden>
                            Cancelar edición
                        </button>

                        <button type="submit" id="botonGuardarProveedor" class="boton boton-azul">
                            <i class="fa-solid fa-plus"></i>
                            Registrar proveedor
                        </button>

                    </div>

                </form>

            </section>

            <!-- LISTA -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Proveedores registrados</h2>
                        <p class="descripcion-panel">
                            Un proveedor desactivado no se puede elegir en productos nuevos,
                            pero los productos que ya lo usan lo conservan.
                        </p>
                    </div>
                </div>

                <div id="contenedorTablaProveedores" class="contenedor-tabla">

                    <table class="tabla-datos tabla-compacta">

                        <caption class="texto-solo-lectores">Proveedores registrados</caption>

                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Contacto</th>
                                <th scope="col">Teléfono</th>
                                <th scope="col">Correo</th>
                                <th scope="col">Dirección</th>
                                <th scope="col">Productos</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="cuerpoTablaProveedores"></tbody>

                    </table>

                </div>

                <div id="mensajeSinProveedores" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-truck-field"></i>
                    <h3>No hay proveedores registrados</h3>
                    <p>Registra el primer proveedor con el formulario de arriba.</p>
                </div>

            </section>

        </main>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/proveedores.js"></script>

</body>
</html>
