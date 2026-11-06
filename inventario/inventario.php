<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";

/*
|--------------------------------------------------------------------------
| Validar sesión
|--------------------------------------------------------------------------
| Si el usuario no ha iniciado sesión, no puede acceder al inventario.
*/

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}


/*
|--------------------------------------------------------------------------
| Ubicaciones permitidas
|--------------------------------------------------------------------------
| Administrador y Gerente pueden consultar todas.
| Encargado y Dependiente solamente las tiendas asignadas.
*/


/*
|--------------------------------------------------------------------------
| Página actual
|--------------------------------------------------------------------------
| Permite marcar correctamente la opción activa del menú.
*/



/*
|--------------------------------------------------------------------------
| Evitar mostrar información después de cerrar sesión
|--------------------------------------------------------------------------
*/

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");


/*
|--------------------------------------------------------------------------
| Información del usuario para mostrar en pantalla
|--------------------------------------------------------------------------
*/



?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Gestión de inventario del sistema Vending Mini Market">

    <title>Inventario | Vending Mini Market</title>

    <!-- Hoja de estilos principal -->
    <?php vmEstilosGlobales(); ?>

    <!-- Biblioteca de iconos Font Awesome -->
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

        <!-- ==========================================
             BARRA SUPERIOR
        =========================================== -->

        <?php vmBarraSuperior(); ?>

        <!-- ==========================================
             CONTENIDO PRINCIPAL
        =========================================== -->

        <main class="contenido-app">

            <!-- ==========================================
                 ENCABEZADO
            =========================================== -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Inventario</h1>

                    <p>
                        Consulta el stock disponible por tienda
                        y registra movimientos de inventario.
                    </p>

                </div>

                <a
                    href="movimientos.php"
                    class="boton boton-azul">
                    <i class="fa-solid fa-plus"></i>
                    Registrar movimiento
                </a>

            </div>

            <!-- ==========================================
                 RESUMEN DE INVENTARIO
            =========================================== -->

            <section
                class="grilla-estadisticas"
                aria-label="Resumen del inventario">

                <!-- Stock total -->

                <article class="tarjeta-estadistica">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Stock total
                            </div>

                            <div class="tarjeta-estadistica-valor">

                                <span id="totalStockInventario">
                                    0
                                </span>

                                <span class="unidad-tarjeta">
                                    unidades
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Unidades disponibles
                    </div>

                </article>

                <!-- Stock crítico -->

                <article class="tarjeta-estadistica borde-naranja">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Stock crítico
                            </div>

                            <div class="tarjeta-estadistica-valor">

                                <span id="totalStockCriticoInventario">
                                    0
                                </span>

                                <span class="unidad-tarjeta">
                                    productos
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Requieren abastecimiento
                    </div>

                </article>

                <!-- Agotados -->

                <article class="tarjeta-estadistica borde-rojo">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-box-open"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Productos agotados
                            </div>

                            <div class="tarjeta-estadistica-valor">

                                <span id="totalProductosAgotadosInventario">
                                    0
                                </span>

                                <span class="unidad-tarjeta">
                                    productos
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Productos sin existencias
                    </div>

                </article>

                <!-- Movimientos del día -->

                <article class="tarjeta-estadistica borde-morado">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Movimientos del día
                            </div>

                            <div class="tarjeta-estadistica-valor">

                                <span id="totalMovimientosDiaInventario">
                                    0
                                </span>

                                <span class="unidad-tarjeta">
                                    movimientos
                                </span>

                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Entradas, salidas, ajustes y traslados
                    </div>

                </article>

            </section>

            <!-- ==========================================
                 PANEL DE INVENTARIO
            =========================================== -->

            <section class="panel">

                <div class="panel-encabezado">

                    <div>

                        <h2>Inventario por tienda</h2>

                        <p class="descripcion-panel">
                            Consulta las existencias registradas
                            para cada producto y tienda.
                        </p>

                    </div>

                </div>

                <!-- ==========================================
                     FILTROS
                =========================================== -->

                <div class="barra-filtros">

                    <!-- Búsqueda -->

                    <div class="campo-busqueda-filtro">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <label
                            for="buscarInventario"
                            class="texto-solo-lectores">
                            Buscar producto
                        </label>

                        <input
                            type="search"
                            id="buscarInventario"
                            placeholder="Buscar por producto, código o proveedor..."
                            autocomplete="off">

                    </div>

                    <!-- Tienda -->

                    <div class="selector-filtro">

                        <label for="filtroUbicacion">
                            Tienda
                        </label>

                        <select id="filtroUbicacion">

                            <option value="">
                                Todas las tiendas
                            </option>

                            <option value="Ultrapark 1">
                                Ultrapark 1
                            </option>

                            <option value="Ultrapark 2">
                                Ultrapark 2
                            </option>

                            <option value="Ultralag">
                                Ultralag
                            </option>

                        </select>

                    </div>

                    <!-- Categoría -->

                    <div class="selector-filtro">

                        <label for="filtroCategoria">
                            Categoría
                        </label>

                        <select id="filtroCategoria">

                            <option value="">
                                Todas las categorías
                            </option>

                            <option value="Bebidas">
                                Bebidas
                            </option>

                            <option value="Snacks">
                                Snacks
                            </option>

                            <option value="Alimentos">
                                Alimentos
                            </option>

                            <option value="Dulces">
                                Dulces
                            </option>

                            <option value="Lácteos">
                                Lácteos
                            </option>

                            <option value="Otros">
                                Otros
                            </option>

                        </select>

                    </div>

                    <!-- Estado -->

                    <div class="selector-filtro">

                        <label for="filtroEstado">
                            Estado de stock
                        </label>

                        <select id="filtroEstado">

                            <option value="">
                                Todos los estados
                            </option>

                            <option value="normal">
                                Normal
                            </option>

                            <option value="stock-bajo">
                                Stock bajo
                            </option>

                            <option value="critico">
                                Crítico
                            </option>

                            <option value="agotado">
                                Agotado
                            </option>

                        </select>

                    </div>

                    <!-- Limpiar filtros -->

                    <button
                        type="button"
                        id="botonLimpiarFiltrosInventario"
                        class="boton boton-borde">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <!-- Resultado -->

                <p
                    id="resultadoBusquedaInventario"
                    class="resultado-busqueda-productos"
                    aria-live="polite"></p>

                <!-- ==========================================
                     ACCIONES
                =========================================== -->

                <div class="acciones-inventario">

                    <a
                        href="movimientos.php?tipo=entrada"
                        class="boton boton-azul">
                        <i class="fa-solid fa-arrow-down"></i>
                        Entrada
                    </a>

                    <a
                        href="movimientos.php?tipo=salida"
                        class="boton boton-borde">
                        <i class="fa-solid fa-arrow-up"></i>
                        Salida
                    </a>

                    <a
                        href="movimientos.php?tipo=ajuste"
                        class="boton boton-borde">
                        <i class="fa-solid fa-sliders"></i>
                        Ajuste
                    </a>

                    <a
                        href="movimientos.php?tipo=merma"
                        class="boton boton-borde">
                        <i class="fa-solid fa-trash-can"></i>
                        Merma
                    </a>

                    <a
                        href="movimientos.php?tipo=traslado"
                        class="boton boton-borde">
                        <i class="fa-solid fa-right-left"></i>
                        Traslado
                    </a>

                    <a
                        href="stock-minimos.php"
                        class="boton boton-borde">
                        <i class="fa-solid fa-bell"></i>
                        Stock mínimo
                    </a>

                </div>

                <!-- ==========================================
                     TABLA
                =========================================== -->

                <div
                    id="contenedorTablaInventario"
                    class="contenedor-tabla"
                    style="overflow-x: auto;">

                    <table class="tabla-datos" style="min-width: 1200px; width: 100%;">

                        <caption class="texto-solo-lectores">
                            Inventario de productos por tienda
                        </caption>

                        <thead>

                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Código retail</th>
                                <th scope="col">Código de barras</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Stock actual</th>
                                <th scope="col">Stock mínimo</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Último movimiento</th>
                                <th scope="col">Acciones</th>
                            </tr>

                        </thead>

                        <tbody id="tablaInventario">

                            <!--
                                JavaScript agregará aquí los productos
                                registrados en el sistema.
                            -->

                        </tbody>

                    </table>

                </div>

                <!-- ==========================================
                     INVENTARIO VACÍO
                =========================================== -->

                <div
                    id="mensajeSinInventario"
                    class="estado-vacio-productos"
                    hidden>

                    <i class="fa-solid fa-box-open"></i>

                    <h3>
                        No hay productos registrados
                    </h3>

                    <p>
                        Los productos aparecerán en esta sección
                        después de registrarlos.
                    </p>

                    <a
                        href="../productos/producto-nuevo.php"
                        class="boton boton-azul">
                        <i class="fa-solid fa-plus"></i>
                        Registrar producto
                    </a>

                </div>

                <!-- ==========================================
                     SIN RESULTADOS
                =========================================== -->

                <div
                    id="mensajeSinResultadosInventario"
                    class="estado-vacio-productos"
                    hidden>

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <h3>
                        No se encontraron productos
                    </h3>

                    <p>
                        Cambia los filtros o el texto de búsqueda.
                    </p>

                </div>

            </section>

        </main>

    </div>

    <!-- JavaScript general -->
    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/inventario.js"></script>

</body>

</html>