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
    <meta name="description" content="Registro de productos del sistema Vending Mini Market">

    <title>Nuevo producto | Vending Mini Market</title>

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
                    <h1 id="tituloPagina">Nuevo producto</h1>
                    <p id="textoPagina">
                        Registra un producto para utilizarlo en productos, inventario y reportes.
                    </p>
                </div>

                <a href="productos.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a productos
                </a>

            </div>

            <div id="mensajeProducto" class="mensaje-producto" aria-live="polite"></div>

            <section class="panel">

                <div class="panel-encabezado">

                    <div>
                        <h2>Información del producto</h2>
                        <p class="descripcion-panel">
                            Complete los campos necesarios. Los marcados con * son obligatorios.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                </div>

                <form id="formularioProducto">

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="nombreProducto">Nombre del producto *</label>
                            <input type="text" id="nombreProducto" maxlength="255"
                                   placeholder="Ejemplo: Coca-Cola 600 ml"
                                   autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="codigoInternoProducto">Código interno</label>
                               <input type="text" id="codigoInternoProducto" maxlength="100"
                                    placeholder="Cargando siguiente código..."
                                    autocomplete="off" readonly>
                                <small id="ayudaCodigoInterno">Se asigna automáticamente (CR600, CR601...).</small>
                        </div>

                        <div class="grupo-formulario">
                            <label for="codigoBarrasProducto">Código de barras</label>
                            <input type="text" id="codigoBarrasProducto" maxlength="100"
                                   inputmode="numeric" autocomplete="off">
                        </div>

                        <div class="grupo-formulario">
                            <label for="categoriaProducto">Categoría *</label>
                            <select id="categoriaProducto" required>
                                <option value="">Seleccione una categoría</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="impuestoProducto">Impuesto *</label>
                            <select id="impuestoProducto" required>
                                <option value="">Seleccione un impuesto</option>
                            </select>
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="descripcionProducto">Descripción</label>
                            <textarea id="descripcionProducto" rows="3" maxlength="500"
                                      placeholder="Descripción u observaciones del producto..."></textarea>
                        </div>

                        <div class="grupo-formulario campo-completo">

                            <label>Proveedores</label>

                            <div class="seccion-proveedores-producto">

                                <div id="listaProveedoresProducto"></div>

                                <p id="mensajeSinProveedoresProducto" class="texto-ayuda">
                                    Este producto no tiene proveedores. Es opcional; puede agregar uno o varios,
                                    cada uno con su costo.
                                </p>

                                <button type="button" id="botonAgregarProveedorProducto" class="boton boton-borde">
                                    <i class="fa-solid fa-plus"></i>
                                    Agregar proveedor
                                </button>

                            </div>

                        </div>

                    </div>

                    <div class="acciones-formulario">

                        <a href="productos.php" class="boton boton-borde">Cancelar</a>

                        <button type="submit" id="botonGuardarProducto" class="boton boton-azul">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar producto
                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/producto-form.js"></script>

</body>
</html>
