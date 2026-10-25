<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/_layout.php";

/*
 * Sin sesión iniciada no se puede entrar a la página,
 * ni escribiendo el enlace directamente en el navegador.
 */
if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

/*
 * Solo Administrador o Soporte TI autorizado (HU-14).
 */
if (!catalogoPuedeImportar()) {
    http_response_code(403);
    echo "Acceso no autorizado.";
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

catalogoLayoutInicio(
    "Importar catálogo",
    "Importar catálogo",
    "Carga inicial de categorías, proveedores, productos y precios desde un archivo CSV o Excel (.xlsx)."
);
?>

            <style>
                .resumen-importacion { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin: 14px 0; }
                .resumen-importacion div { padding: 12px; border-radius: 8px; background: rgba(23, 78, 166, .08); }
                .resumen-importacion strong { display: block; font-size: 1.4rem; }
                .texto-ayuda { margin: 8px 0; opacity: .8; }
                .opcion-validar { display: flex; align-items: center; gap: 8px; margin: 12px 0; font-weight: 500; }
            </style>

            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Subir archivo</h2>
                        <p class="descripcion-panel">
                            Máximo 10 MB. La primera fila debe tener los encabezados. Acepta CSV (separado por coma, punto y coma o tabulador)
                            y XLSX (se lee la primera hoja).
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-file-import"></i>
                    </div>
                </div>

                <form id="formularioImportar">

                    <div class="cuadricula-formulario">
                        <div class="grupo-formulario campo-completo">
                            <label for="archivoImportar">Archivo CSV o Excel (.xlsx) *</label>
                            <input type="file" id="archivoImportar" accept=".csv,.xlsx" required>
                        </div>
                    </div>

                    <label class="opcion-validar">
                        <input type="checkbox" id="soloValidar" checked>
                        Solo validar (revisar errores sin guardar nada)
                    </label>

                    <div class="acciones-formulario">
                        <a href="../api/catalogo/importar.php?accion=plantilla" class="boton boton-borde">
                            <i class="fa-solid fa-download"></i>
                            Descargar plantilla CSV
                        </a>

                        <button type="submit" id="botonImportar" class="boton boton-azul">
                            <i class="fa-solid fa-file-import"></i>
                            Validar archivo
                        </button>
                    </div>

                </form>

            </section>

            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Formato del archivo</h2>
                        <p class="descripcion-panel">
                            La columna <strong>tipo</strong> indica qué es cada fila. Se procesan en este orden:
                            categorías, proveedores, productos, precios e inventario, sin importar su posición en el archivo.
                        </p>
                    </div>
                </div>

                <div class="contenedor-tabla">
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Columnas por tipo de fila</caption>
                        <thead>
                            <tr>
                                <th scope="col">tipo</th>
                                <th scope="col">Columnas</th>
                                <th scope="col">Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>categoria</td>
                                <td>nombre*, descripcion</td>
                                <td>Si ya existe se omite.</td>
                            </tr>
                            <tr>
                                <td>proveedor</td>
                                <td>nombre*, contacto, telefono, correo, direccion</td>
                                <td>Si ya existe se omite.</td>
                            </tr>
                            <tr>
                                <td>producto</td>
                                <td>nombre*, categoria*, proveedor, costo, impuesto, codigo_interno, codigo_barras, descripcion, estado</td>
                                <td>
                                    La categoría y el proveedor se crean si no existen. El impuesto es el nombre o el porcentaje
                                    (por defecto 13 %). Sin código interno el sistema asigna el siguiente código CR disponible. El costo solo se guarda si hay proveedor.
                                    Se rechaza si el código interno, el código de barras o un nombre equivalente ya existen.
                                </td>
                            </tr>
                            <tr>
                                <td>precio</td>
                                <td>codigo_interno* (o codigo_barras), tienda_id*, costo*, margen*</td>
                                <td>
                                    El margen es el nombre o el porcentaje de uno activo. Usa el IVA del producto, guarda el precio anterior
                                    en el historial y deja solo un precio activo por producto y tienda.
                                </td>
                            </tr>
                            <tr>
                                <td>inventario</td>
                                <td>codigo_interno* (o codigo_barras), tienda_id*, cantidad*, ubicacion, stock_minimo</td>
                                <td>
                                    Carga la existencia inicial y deja una entrada confirmada en los movimientos. La ubicación solo es
                                    obligatoria si la tienda tiene varias. Si el producto ya tiene existencia en esa ubicación, se rechaza.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="texto-ayuda">
                    * obligatorio. Los montos aceptan 1500, 1500.50, 1500,50 o 1.500,50.
                </p>

            </section>

            <section class="panel" id="panelResultado" hidden>

                <div class="panel-encabezado">
                    <div>
                        <h2 id="tituloResultado">Resultado</h2>
                        <p class="descripcion-panel" id="textoResultado"></p>
                    </div>
                </div>

                <div id="resumenImportacion" class="resumen-importacion"></div>

                <div id="contenedorErrores" class="contenedor-tabla" hidden>
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Filas rechazadas</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fila del archivo</th>
                                <th scope="col">Motivo</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoErrores"></tbody>
                    </table>
                </div>

                <p id="notaErrores" class="texto-ayuda" hidden></p>

            </section>

            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Importaciones recientes</h2>
                        <p class="descripcion-panel">Se guardan solo las importaciones reales (no las validaciones).</p>
                    </div>
                </div>

                <div class="contenedor-tabla">
                    <table class="tabla-datos tabla-compacta">
                        <caption class="texto-solo-lectores">Importaciones recientes</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Archivo</th>
                                <th scope="col">Usuario</th>
                                <th scope="col">Procesadas</th>
                                <th scope="col">Importadas</th>
                                <th scope="col">Rechazadas</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoHistorialImportaciones"></tbody>
                    </table>
                </div>

                <p id="sinImportaciones" class="texto-ayuda" hidden>Todavía no se ha importado ningún archivo.</p>

            </section>

<?php
catalogoLayoutFin(["../js/importar-catalogo.js"]);
