<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

/*
 * Sin sesión iniciada no se puede entrar a la página,
 * ni escribiendo el enlace directamente en el navegador.
 * Cualquier usuario con sesión puede consultar y simular;
 * configurar y aplicar precios es solo del Administrador
 * (lo valida la API y se ocultan los controles con data-solo-admin).
 */
if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . "/_layout.php";

catalogoLayoutInicio(
    "Precios por tienda",
    "Precios por tienda",
    "Configura IVA y márgenes, calcula y simula precios de venta y consulta su historial."
);
?>

            <style>
                .resultado-calculo { white-space: pre-line; margin-top: 12px; padding: 12px 14px;
                    border-radius: 8px; background: rgba(23, 78, 166, .08); font-weight: 600; }
                .lista-checks-margenes { display: flex; flex-wrap: wrap; gap: 8px 18px; margin: 6px 0 12px; }
                .lista-checks-margenes label { display: inline-flex; align-items: center; gap: 6px; font-weight: 500; }
                .texto-ayuda { margin: 8px 0; opacity: .8; }
                .subtitulo-panel { margin: 18px 0 6px; font-size: 1rem; }
                .celda-numero { text-align: right; white-space: nowrap; }
                .fila-acciones { display: flex; flex-wrap: wrap; gap: 8px; }
            </style>

            <!-- ======================================================
                 CALCULAR, SIMULAR Y APLICAR (HU-38, HU-39, HU-40)
            ======================================================= -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Calcular, simular y aplicar precio</h2>
                        <p class="descripcion-panel">
                            Costo con IVA = costo × (1 + IVA %). Precio de venta = costo con IVA × (1 + margen %), redondeado siempre hacia arriba al siguiente múltiplo de ₡25.
                            Calcular y simular no cambian el precio activo; cada tienda puede tener un precio distinto para el mismo producto.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                </div>

                <div class="cuadricula-formulario">

                    <div class="grupo-formulario">
                        <label for="selProducto">Producto *</label>
                        <select id="selProducto"></select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="selTienda">Tienda *</label>
                        <select id="selTienda"></select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="selMargen">Margen (para calcular y aplicar) *</label>
                        <select id="selMargen"></select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="inputCosto">Costo (₡) *</label>
                        <input type="number" id="inputCosto" min="0" step="0.01" placeholder="Ejemplo: 650">
                    </div>

                </div>

                <p id="infoContexto" class="texto-ayuda"></p>

                <div class="acciones-formulario">
                    <button type="button" id="botonHistorialSeleccion" class="boton boton-borde">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Ver historial de la selección
                    </button>

                    <button type="button" id="botonCalcular" class="boton boton-borde">
                        <i class="fa-solid fa-calculator"></i>
                        Calcular
                    </button>

                    <button type="button" id="botonAplicar" class="boton boton-azul" data-solo-admin>
                        <i class="fa-solid fa-check"></i>
                        Aplicar precio activo
                    </button>
                </div>

                <div id="resultadoCalculo" class="resultado-calculo" hidden></div>

                <h3 class="subtitulo-panel">Simular escenarios de margen</h3>
                <p class="texto-ayuda">
                    Marque los márgenes a comparar. Si deja el costo vacío se usa el costo del precio activo.
                    Las simulaciones quedan guardadas y no afectan el precio activo hasta que un administrador aplique una.
                </p>

                <div id="listaMargenesSimulacion" class="lista-checks-margenes"></div>

                <div class="acciones-formulario">
                    <button type="button" id="botonSimular" class="boton boton-borde">
                        <i class="fa-solid fa-flask"></i>
                        Simular escenarios
                    </button>
                </div>

                <div id="contenedorEscenarios" class="contenedor-tabla" hidden>
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Escenarios simulados</caption>
                        <thead>
                            <tr>
                                <th scope="col">Margen</th>
                                <th scope="col">IVA</th>
                                <th scope="col">Costo con IVA</th>
                                <th scope="col">Precio de venta</th>
                                <th scope="col">Ganancia unitaria</th>
                                <th scope="col">Diferencia vs. precio activo</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoEscenarios"></tbody>
                    </table>
                </div>

            </section>

            <!-- ======================================================
                 MÁRGENES (HU-37)
            ======================================================= -->
            <section class="panel" data-solo-admin>

                <div class="panel-encabezado">
                    <div>
                        <h2 id="tituloFormularioMargen">Márgenes de ganancia</h2>
                        <p class="descripcion-panel">
                            Un margen es el recargo sobre el costo (40 % = costo × 1.40). El nombre no se puede repetir.
                            Un margen desactivado no se puede elegir en cálculos nuevos, pero los precios que ya lo usan se conservan.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                </div>

                <form id="formularioMargen">

                    <input type="hidden" id="margenId">

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="margenNombre">Nombre *</label>
                            <input type="text" id="margenNombre" maxlength="100" autocomplete="off" required>
                        </div>

                        <div class="grupo-formulario">
                            <label for="margenPorcentaje">Porcentaje (%) *</label>
                            <input type="number" id="margenPorcentaje" min="0" max="999.99" step="0.01" required>
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="margenDescripcion">Descripción</label>
                            <input type="text" id="margenDescripcion" maxlength="250" autocomplete="off">
                        </div>

                    </div>

                    <div class="acciones-formulario">
                        <button type="button" id="botonCancelarMargen" class="boton boton-borde" hidden>
                            Cancelar edición
                        </button>

                        <button type="submit" id="botonGuardarMargen" class="boton boton-azul">
                            <i class="fa-solid fa-plus"></i>
                            Crear margen
                        </button>
                    </div>

                </form>

                <div class="contenedor-tabla">
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Márgenes registrados</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Porcentaje</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Precios activos</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoMargenes"></tbody>
                    </table>
                </div>

            </section>

            <!-- ======================================================
                 IVA POR PRODUCTO (HU-37)
            ======================================================= -->
            <section class="panel" data-solo-admin>

                <div class="panel-encabezado">
                    <div>
                        <h2>IVA por producto</h2>
                        <p class="descripcion-panel">
                            El IVA del producto se usa en los cálculos nuevos. Los precios activos no cambian solos:
                            use «Recalcular» en la lista de precios para aplicar el IVA nuevo.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>

                <form id="formularioIva">

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="selProductoIva">Producto *</label>
                            <select id="selProductoIva" required></select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="selImpuestoIva">Impuesto / IVA *</label>
                            <select id="selImpuestoIva" required></select>
                        </div>

                    </div>

                    <div class="acciones-formulario">
                        <button type="submit" class="boton boton-azul">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar IVA del producto
                        </button>
                    </div>

                </form>

            </section>

            <!-- ======================================================
                 MARGEN POR TIENDA (HU-37)
            ======================================================= -->
            <section class="panel" data-solo-admin>

                <div class="panel-encabezado">
                    <div>
                        <h2>Margen por tienda</h2>
                        <p class="descripcion-panel">
                            Recalcula con otro margen todos los precios activos de una tienda (o solo de una categoría),
                            conservando el costo de cada producto. Use primero la vista previa.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-store"></i>
                    </div>
                </div>

                <div class="cuadricula-formulario">

                    <div class="grupo-formulario">
                        <label for="masTienda">Tienda *</label>
                        <select id="masTienda"></select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="masMargen">Margen *</label>
                        <select id="masMargen"></select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="masCategoria">Categoría (opcional)</label>
                        <select id="masCategoria"></select>
                    </div>

                </div>

                <div class="acciones-formulario">
                    <button type="button" id="botonVistaPrevia" class="boton boton-borde">
                        <i class="fa-solid fa-eye"></i>
                        Vista previa
                    </button>

                    <button type="button" id="botonAplicarMasivo" class="boton boton-azul">
                        <i class="fa-solid fa-check"></i>
                        Aplicar a la tienda
                    </button>
                </div>

                <p id="resumenMasivo" class="texto-ayuda" aria-live="polite"></p>

                <div id="contenedorMasivo" class="contenedor-tabla" hidden>
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Vista previa del cambio de margen</caption>
                        <thead>
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Costo</th>
                                <th scope="col">Precio actual</th>
                                <th scope="col">Precio nuevo</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoMasivo"></tbody>
                    </table>
                </div>

            </section>

            <!-- ======================================================
                 PRECIOS ACTIVOS (HU-39)
            ======================================================= -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Precios activos por tienda</h2>
                        <p class="descripcion-panel">
                            Un producto puede tener un precio distinto en cada tienda. El IVA mostrado es el actual del producto.
                        </p>
                    </div>
                </div>

                <div class="barra-filtros productos-filtros">

                    <div class="campo-filtro-producto">
                        <label for="filtroTienda">Tienda</label>
                        <select id="filtroTienda"></select>
                    </div>

                    <div class="campo-busqueda-productos">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <label for="filtroBusqueda" class="texto-solo-lectores">Buscar precios</label>
                        <input type="search" id="filtroBusqueda"
                               placeholder="Buscar por nombre o código interno..." autocomplete="off">
                    </div>

                </div>

                <p id="resumenPrecios" class="resultado-busqueda-productos" aria-live="polite"></p>

                <div class="contenedor-tabla">
                    <table class="tabla-datos">
                        <caption class="texto-solo-lectores">Precios activos por tienda</caption>
                        <thead>
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Costo</th>
                                <th scope="col">Costo con IVA</th>
                                <th scope="col">Precio de venta</th>
                                <th scope="col">IVA</th>
                                <th scope="col">Margen</th>
                                <th scope="col">Desde</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoPrecios"></tbody>
                    </table>
                </div>

            </section>

            <!-- ======================================================
                 HISTORIAL Y SIMULACIONES (HU-40)
            ======================================================= -->
            <section class="panel" id="panelHistorial">

                <div class="panel-encabezado">
                    <div>
                        <h2 id="tituloHistorial">Historial de precios</h2>
                        <p class="descripcion-panel">
                            Elija un producto (y tienda) arriba, o use el botón de reloj en la lista de precios.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                </div>

                <div class="contenedor-tabla">
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Historial de cambios de precio</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Costo anterior → nuevo</th>
                                <th scope="col">Precio anterior → nuevo</th>
                                <th scope="col">Margen</th>
                                <th scope="col">IVA</th>
                                <th scope="col">Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoHistorial"></tbody>
                    </table>
                </div>

                <h3 class="subtitulo-panel">Simulaciones guardadas</h3>

                <div class="contenedor-tabla">
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Simulaciones guardadas</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Margen</th>
                                <th scope="col">Costo</th>
                                <th scope="col">Precio de venta</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoSimulaciones"></tbody>
                    </table>
                </div>

            </section>

<?php
catalogoLayoutFin(["../js/precios.js"]);
