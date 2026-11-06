<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$idUsuarioActual = (int)($_SESSION["usuario"]["id_usuario"] ?? 0);
$puedeRevisarFactura = esAdministrador() || esGerenteGeneral() || esEncargado();
$puedeValidarFactura = esAdministrador() || esGerenteGeneral();
$puedeAnularFactura = esAdministrador() || esGerenteGeneral();
$puedeExportarHistorial = esAdministrador() || esGerenteGeneral();

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
        content="Historial de pedidos y facturas del sistema Vending Mini Market"
    >

    <title>Historial de pedidos y facturas | Vending Mini Market</title>

    <?php vmEstilosGlobales(); ?>

</head>

<body class="pagina-app" data-puede-revisar-factura="<?= $puedeRevisarFactura ? '1' : '0' ?>" data-puede-validar-factura="<?= $puedeValidarFactura ? '1' : '0' ?>" data-puede-anular-factura="<?= $puedeAnularFactura ? '1' : '0' ?>" data-puede-exportar-historial="<?= $puedeExportarHistorial ? '1' : '0' ?>" data-id-usuario-actual="<?= $idUsuarioActual ?>">

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

            <!-- Encabezado -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Historial de pedidos y facturas</h1>

                    <p>
                        Consulta pedidos, recepciones y facturas de
                        proveedores para dar seguimiento contable y operativo.
                    </p>

                </div>

                <div class="acciones-encabezado-pedidos">

                    <a
                        href="pedido.php"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-file-circle-plus"></i>
                        Sección de Pedidos
                    </a>

                    <a
                        href="facturas.php"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-file-invoice"></i>
                        Sección de Facturas
                    </a>

                    <?php if ($puedeExportarHistorial): ?>
                    <button
                        type="button"
                        id="botonExportarHistorial"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-export"></i>
                        Exportar
                    </button>
                    <?php endif; ?>

                </div>

            </div>

            <div
                id="mensajeFacturas"
                class="mensaje-producto"
                aria-live="polite"
                hidden
            ></div>

            <!-- ==========================================
                 RESUMEN
            =========================================== -->

            <section
                class="grilla-estadisticas"
                aria-label="Resumen del historial"
            >

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Total de facturas</div>
                            <div class="tarjeta-estadistica-valor" id="totalFacturas">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Documentos registrados</div>
                </article>

                <article class="tarjeta-estadistica borde-naranja">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Pendientes</div>
                            <div class="tarjeta-estadistica-valor" id="facturasPendientes">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Esperan validación</div>
                </article>

                <article class="tarjeta-estadistica borde-morado">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">En revisión</div>
                            <div class="tarjeta-estadistica-valor" id="facturasEnRevision">0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Esperan al Administrador</div>
                </article>

                <article class="tarjeta-estadistica borde-verde">
                    <div class="tarjeta-estadistica-encabezado">
                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <div>
                            <div class="tarjeta-estadistica-titulo">Monto facturado</div>
                            <div class="tarjeta-estadistica-valor" id="montoTotalFacturas">₡0</div>
                        </div>
                    </div>
                    <div class="tarjeta-estadistica-cambio">Total acumulado</div>
                </article>

            </section>

            <!-- ==========================================
                 LISTADO (HU-36)
            =========================================== -->

            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Historial de facturas</h2>
                        <p class="descripcion-panel">
                            Filtra por proveedor, tienda, estado o fecha para
                            dar seguimiento contable y operativo.
                        </p>
                    </div>
                </div>

                <div class="barra-filtros">

                    <div class="campo-busqueda-filtro">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <label for="buscarFactura" class="texto-solo-lectores">Buscar factura</label>
                        <input
                            type="search"
                            id="buscarFactura"
                            placeholder="Buscar por número o proveedor..."
                            autocomplete="off"
                        >
                    </div>

                    <div class="selector-filtro">
                        <label for="filtroProveedorFactura">Proveedor</label>
                        <select id="filtroProveedorFactura">
                            <option value="">Todos los proveedores</option>
                        </select>
                    </div>

                    <div class="selector-filtro">
                        <label for="filtroTiendaFactura">Tienda</label>
                        <select id="filtroTiendaFactura">
                            <option value="">Todas las tiendas</option>
                        </select>
                    </div>

                    <div class="selector-filtro">
                        <label for="filtroEstadoFactura">Estado</label>
                        <select id="filtroEstadoFactura">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="EN_REVISION">En revisión</option>
                            <option value="APROBADA">Aprobada</option>
                            <option value="RECHAZADA">Rechazada</option>
                            <option value="ANULADA">Anulada</option>
                        </select>
                    </div>

                    <div class="selector-filtro">
                        <label for="filtroFechaFactura">Fecha</label>
                        <input type="date" id="filtroFechaFactura">
                    </div>

                    <button type="button" id="botonLimpiarFiltrosFacturas" class="boton boton-borde">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <p id="resultadoBusquedaFacturas" class="resultado-busqueda-productos" aria-live="polite"></p>

                <div id="contenedorTablaFacturas" class="contenedor-tabla" hidden>

                    <table class="tabla-datos">

                        <caption class="texto-solo-lectores">Historial de facturas</caption>

                        <thead>
                            <tr>
                                <th scope="col">Factura</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Vencimiento</th>
                                <th scope="col">Proveedor</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Subtotal</th>
                                <th scope="col">Impuesto</th>
                                <th scope="col">Total</th>
                                <th scope="col">Pedido</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="cuerpoTablaFacturas"></tbody>

                    </table>

                </div>

                <div id="mensajeSinFacturas" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-file-circle-xmark"></i>
                    <h3>No hay facturas registradas</h3>
                    <p>Registra una factura desde la sección de Facturas.</p>
                    <a href="facturas.php" id="botonPrimeraFactura" class="boton boton-azul">
                        <i class="fa-solid fa-file-invoice"></i>
                        Registrar factura
                    </a>
                </div>

                <div id="mensajeSinResultadosFacturas" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3>No se encontraron facturas</h3>
                    <p>Cambia los filtros o el texto de búsqueda.</p>
                </div>

            </section>

        </main>

    </div>

    <!-- ==========================================
         MODAL: VER / EDITAR FACTURA
    =========================================== -->

    <div id="modalDetalleFactura" class="modal-producto" hidden>

        <div class="modal-producto-fondo" data-cerrar-modal-detalle-factura></div>

        <section
            class="modal-producto-contenido modal-factura-contenido"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalDetalleFactura"
        >

            <div class="encabezado-modal-venta">
                <div>
                    <h2 id="tituloModalDetalleFactura">Factura</h2>
                    <p>Consulta o actualiza los datos del documento registrado.</p>
                </div>
                <button type="button" id="botonCerrarModalDetalleFactura" class="boton-cerrar-modal" aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <input type="hidden" id="idFacturaDetalle">

            <div class="grupo-formulario">
                <label>Estado</label>
                <div>
                    <span id="badgeEstadoDetalleFactura" class="badge badge-naranja">Pendiente</span>
                </div>
            </div>

            <div class="grupo-formulario">
                <label for="pedidoRelacionadoDetalle">Pedido relacionado</label>
                <input type="text" id="pedidoRelacionadoDetalle" disabled>
            </div>

            <div class="cuadricula-formulario">

                <div class="grupo-formulario">
                    <label for="proveedorDetalle">Proveedor</label>
                    <input type="text" id="proveedorDetalle" disabled>
                </div>

                <div class="grupo-formulario">
                    <label for="ubicacionDetalle">Tienda</label>
                    <input type="text" id="ubicacionDetalle" disabled>
                </div>

                <div class="grupo-formulario">
                    <label for="numeroFacturaDetalle">Número de factura</label>
                    <input type="text" id="numeroFacturaDetalle" maxlength="50" autocomplete="off">
                </div>

                <div class="grupo-formulario">
                    <label for="fechaFacturaDetalle">Fecha de emisión</label>
                    <input type="date" id="fechaFacturaDetalle">
                </div>

                <div class="grupo-formulario">
                    <label for="fechaVencimientoFacturaDetalle">Fecha de vencimiento</label>
                    <input type="date" id="fechaVencimientoFacturaDetalle">
                </div>

                <div class="grupo-formulario">
                    <label for="impuestoFacturaDetalle">Impuesto</label>
                    <select id="impuestoFacturaDetalle">
                        <option value="">Seleccione un impuesto</option>
                    </select>
                </div>

            </div>

            <div class="seccion-productos-compra">

                <div class="encabezado-productos-compra">
                    <div>
                        <h3>Productos facturados</h3>
                    </div>
                    <button type="button" id="botonAgregarProductoDetalle" class="boton boton-borde">
                        <i class="fa-solid fa-plus"></i>
                        Agregar producto
                    </button>
                </div>

                <div id="listaProductosDetalle" class="lista-productos-compra"></div>

            </div>

            <div class="grupo-formulario">
                <label for="archivoFacturaDetalle">Evidencia (PDF)</label>
                <p id="evidenciaActualDetalle" class="texto-ayuda"></p>
                <input type="file" id="archivoFacturaDetalle" accept="application/pdf">
                <small class="texto-ayuda">Sube un archivo solo si deseas reemplazar el actual (máx. 5 MB).</small>
            </div>

            <div class="grupo-formulario">
                <label for="observacionesFacturaDetalle">Observaciones</label>
                <textarea id="observacionesFacturaDetalle" rows="3" maxlength="500"></textarea>
            </div>

            <div class="grupo-formulario" id="grupoMotivoRechazoFactura" hidden>
                <label for="motivoRechazoFactura">Motivo de rechazo (obligatorio para rechazar)</label>
                <textarea id="motivoRechazoFactura" rows="2" maxlength="300"></textarea>
            </div>

            <div class="grupo-formulario" id="grupoMotivoAnulacionFactura" hidden>
                <label for="motivoAnulacionFactura">Motivo de anulación (obligatorio, cancelación definitiva)</label>
                <textarea id="motivoAnulacionFactura" rows="2" maxlength="300"></textarea>
            </div>

            <div class="resumen-compra-formulario">
                <div>
                    <span>Subtotal</span>
                    <strong id="subtotalDetalleResumen">₡0</strong>
                </div>
                <div>
                    <span>Impuesto</span>
                    <strong id="impuestoDetalleResumen">₡0</strong>
                </div>
                <div class="total-compra-formulario">
                    <span>Total</span>
                    <strong id="totalDetalleResumen">₡0</strong>
                </div>
            </div>

            <p id="mensajeDetalleFactura" class="mensaje-login" aria-live="polite"></p>

            <div class="acciones-modal-producto">
                <button type="button" id="botonCerrarDetalleFactura" class="boton boton-borde">Cerrar</button>
                <button type="button" id="botonGuardarDetalleFactura" class="boton boton-azul" hidden>
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                </button>
                <button type="button" id="botonEnviarRevisionDetalle" class="boton boton-azul" hidden>
                    <i class="fa-solid fa-check-double"></i>
                    Validar factura
                </button>
                <button type="button" id="botonRechazarFacturaDetalle" class="boton boton-peligro" hidden>
                    <i class="fa-solid fa-xmark"></i>
                    Rechazar
                </button>
                <button type="button" id="botonAnularFacturaDetalle" class="boton boton-peligro" hidden>
                    <i class="fa-solid fa-ban"></i>
                    Anular
                </button>
                <button type="button" id="botonAprobarFacturaDetalle" class="boton boton-azul" hidden>
                    <i class="fa-solid fa-check"></i>
                    Aprobar
                </button>
            </div>

        </section>

    </div>

    <!-- ==========================================
         MODAL: EXPORTAR HISTORIAL (PDF)
    =========================================== -->

    <div id="modalExportarHistorial" class="modal-producto" hidden>

        <div class="modal-producto-fondo" data-cerrar-modal-exportar-historial></div>

        <section
            class="modal-producto-contenido"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalExportarHistorial"
        >

            <div class="modal-producto-icono">
                <i class="fa-solid fa-file-pdf"></i>
            </div>

            <h2 id="tituloModalExportarHistorial">Exportar historial</h2>

            <p>
                Elige el mes que quieres incluir en el reporte.
                Se respetan también los filtros de proveedor, tienda
                y estado que tengas activos en la tabla.
            </p>

            <div class="cuadricula-formulario">

                <div class="grupo-formulario">
                    <label for="selectMesExportarHistorial">Mes</label>
                    <select id="selectMesExportarHistorial">
                        <option value="01">Enero</option>
                        <option value="02">Febrero</option>
                        <option value="03">Marzo</option>
                        <option value="04">Abril</option>
                        <option value="05">Mayo</option>
                        <option value="06">Junio</option>
                        <option value="07">Julio</option>
                        <option value="08">Agosto</option>
                        <option value="09">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                </div>

                <div class="grupo-formulario">
                    <label for="selectAnioExportarHistorial">Año</label>
                    <select id="selectAnioExportarHistorial"></select>
                </div>

            </div>

            <div class="acciones-modal-producto">

                <button
                    type="button"
                    id="botonCancelarExportarHistorial"
                    class="boton boton-borde"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="botonGenerarExportarHistorial"
                    class="boton boton-azul"
                >
                    <i class="fa-solid fa-file-pdf"></i>
                    Generar PDF
                </button>

            </div>

        </section>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/pedido-api.js"></script>
    <script src="../js/factura-api.js"></script>
    <script src="../js/factura.js"></script>

</body>
</html>
