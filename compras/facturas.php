<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$nombreUsuario = trim(
    ($_SESSION["usuario"]["nombre"] ?? "") . " " .
    ($_SESSION["usuario"]["apellido"] ?? "")
);
$correoUsuario = $_SESSION["usuario"]["correo"] ?? "";
$idUsuarioActual = (int)($_SESSION["usuario"]["id_usuario"] ?? 0);
$puedeRevisarFactura = esAdministrador() || esEncargado();
$puedeValidarFactura = esAdministrador();
$puedeAnularFactura = esAdministrador();
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
        content="Registro de facturas de proveedor del sistema Vending Mini Market"
    >

    <title>Facturas | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body class="pagina-app" data-puede-revisar-factura="<?= $puedeRevisarFactura ? '1' : '0' ?>" data-puede-validar-factura="<?= $puedeValidarFactura ? '1' : '0' ?>" data-puede-anular-factura="<?= $puedeAnularFactura ? '1' : '0' ?>" data-puede-exportar-historial="<?= $puedeExportarHistorial ? '1' : '0' ?>" data-id-usuario-actual="<?= $idUsuarioActual ?>">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->

    <aside class="barra-lateral">

        <div class="logo-sistema">

            <a
                href="../dashboard/dashboard.php"
                aria-label="Ir al Dashboard"
            >
                <img
                    src="../img/logo/logo-vending.jpeg"
                    alt="Logo de Vending Mini Market"
                    class="imagen-logo-sistema"
                >
            </a>

        </div>

        <nav
            class="nav-lateral"
            aria-label="Navegación principal"
        >

            <a href="../dashboard/dashboard.php">
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="../productos/productos.php">
                <i class="fa-solid fa-box"></i>
                Productos
            </a>

            <a href="../tienda/tienda.php">
                <i class="fa-solid fa-shop"></i>
                Tienda
            </a>

            <a href="../maquinas/maquinas.php">
                <i class="fa-solid fa-cash-register"></i>
                Máquinas
            </a>

            <a href="../inventario/inventario.php">
                <i class="fa-solid fa-clipboard-list"></i>
                Inventario
            </a>

            <a
                href="compras.php"
                class="activo"
            >
                <i class="fa-solid fa-cart-shopping"></i>
                Compras
            </a>

            <a href="../ventas/ventas.html">
                <i class="fa-solid fa-chart-column"></i>
                Ventas
            </a>

            <a href="../alertas/alertas.html">
                <i class="fa-solid fa-bell"></i>
                Alertas
            </a>

            <a href="../reportes/reportes.html">
                <i class="fa-solid fa-chart-line"></i>
                Reportes
            </a>

            <a href="../usuarios/usuarios.php">
                <i class="fa-solid fa-users"></i>
                Usuarios
            </a>

        </nav>

        <div class="pie-barra-lateral">

            <a
                href="../index/index.html"
                id="botonCerrarSesion"
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </div>

    </aside>

    <!-- ==========================================
         CONTENIDO PRINCIPAL
    =========================================== -->

    <div class="columna-principal">

        <header class="barra-superior">

            <button
                type="button"
                class="boton-menu"
                aria-label="Abrir o cerrar menú lateral"
            >
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="buscador-superior">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    id="buscadorGeneralFacturas"
                    placeholder="Buscar en el sistema..."
                    aria-label="Buscar en el sistema"
                    autocomplete="off"
                >

            </div>

            <div class="acciones-superiores">

                <button
                    type="button"
                    class="boton-notificacion"
                    aria-label="Consultar notificaciones"
                >
                    <i class="fa-regular fa-bell"></i>

                    <span
                        class="contador"
                        id="contadorNotificacionesFacturas"
                    >
                        0
                    </span>

                </button>

                <div class="perfil-superior">

                    <div class="perfil-superior-texto">

                        <strong id="nombreUsuarioSuperior">
                            <?= htmlspecialchars($nombreUsuario) ?>
                        </strong>

                        <span id="correoUsuarioSuperior">
                            <?= htmlspecialchars($correoUsuario) ?>
                        </span>

                    </div>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>

        </header>

        <main class="contenido-app">

            <!-- Encabezado -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Facturas de proveedores</h1>

                    <p>
                        Registra las facturas recibidas y da seguimiento
                        a su validación.
                    </p>

                </div>

                <div class="acciones-encabezado-pedidos">

                    <a
                        href="compras.php"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver a compras
                    </a>

                    <?php if ($puedeExportarHistorial): ?>
                    <button
                        type="button"
                        id="botonExportarHistorial"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-file-export"></i>
                        Exportar
                    </button>
                    <?php endif; ?>

                    <button
                        type="button"
                        id="botonNuevaFactura"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-invoice"></i>
                        Nueva factura
                    </button>

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
                aria-label="Resumen de facturas"
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
                 LISTADO
            =========================================== -->

            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Facturas registradas</h2>
                        <p class="descripcion-panel">
                            Consulta los documentos emitidos por los proveedores.
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

                        <caption class="texto-solo-lectores">Facturas registradas</caption>

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
                                <th scope="col">Movimientos</th>
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
                    <p>Registra la primera factura de un proveedor.</p>
                    <button type="button" id="botonPrimeraFactura" class="boton boton-azul">
                        <i class="fa-solid fa-file-invoice"></i>
                        Registrar factura
                    </button>
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
         MODAL: NUEVA FACTURA
    =========================================== -->

    <div id="modalFactura" class="modal-producto" hidden>

        <div class="modal-producto-fondo" data-cerrar-modal-factura></div>

        <section
            class="modal-producto-contenido modal-factura-contenido"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalFactura"
        >

            <div class="encabezado-modal-venta">
                <div>
                    <h2 id="tituloModalFactura">Nueva factura</h2>
                    <p>Registra los datos del documento recibido del proveedor.</p>
                </div>
                <button type="button" id="botonCerrarModalFactura" class="boton-cerrar-modal" aria-label="Cerrar formulario">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="formularioFactura" enctype="multipart/form-data">

                <!-- Pedido relacionado (primero, opcional) -->

                <div class="grupo-formulario">
                    <label for="pedidoRelacionadoFactura">Pedido relacionado (opcional)</label>
                    <select id="pedidoRelacionadoFactura" name="pedidoRelacionadoFactura">
                        <option value="">Sin pedido relacionado</option>
                    </select>
                </div>

                <div class="cuadricula-formulario">

                    <div class="grupo-formulario">
                        <label for="proveedorFactura">Proveedor</label>
                        <select id="proveedorFactura" name="proveedorFactura" required>
                            <option value="">Seleccione un proveedor</option>
                        </select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="ubicacionFactura">Tienda</label>
                        <select id="ubicacionFactura" name="ubicacionFactura" required>
                            <option value="">Seleccione una tienda</option>
                        </select>
                    </div>

                    <div class="grupo-formulario">
                        <label for="numeroFactura">Número de factura</label>
                        <input
                            type="text"
                            id="numeroFactura"
                            name="numeroFactura"
                            placeholder="Ejemplo: FE-001254"
                            maxlength="50"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <div class="grupo-formulario">
                        <label for="fechaFactura">Fecha de emisión</label>
                        <input type="date" id="fechaFactura" name="fechaFactura" required>
                    </div>

                    <div class="grupo-formulario">
                        <label for="fechaVencimientoFactura">Fecha de vencimiento</label>
                        <input type="date" id="fechaVencimientoFactura" name="fechaVencimientoFactura">
                    </div>

                    <div class="grupo-formulario">
                        <label for="impuestoFactura">Impuesto</label>
                        <select id="impuestoFactura" name="impuestoFactura" required>
                            <option value="">Seleccione un impuesto</option>
                        </select>
                    </div>

                </div>

                <div class="seccion-productos-compra">

                    <div class="encabezado-productos-compra">
                        <div>
                            <h3>Productos facturados</h3>
                            <p>Selecciona primero un proveedor para ver sus productos.</p>
                        </div>
                        <button type="button" id="botonAgregarProductoFactura" class="boton boton-borde" disabled>
                            <i class="fa-solid fa-plus"></i>
                            Agregar producto
                        </button>
                    </div>

                    <div id="listaProductosFactura" class="lista-productos-compra"></div>

                    <div id="mensajeSinProductosFactura" class="mensaje-productos-compra">
                        Todavía no se han agregado productos.
                    </div>

                </div>

                <!-- Archivo -->

                <div class="grupo-formulario">
                    <label for="archivoFactura">Evidencia (PDF)</label>
                    <input type="file" id="archivoFactura" name="evidencia" accept="application/pdf">
                    <small class="texto-ayuda">Solo archivos PDF, máximo 5 MB.</small>
                </div>

                <div class="grupo-formulario">
                    <label for="observacionesFactura">Observaciones</label>
                    <textarea
                        id="observacionesFactura"
                        name="observacionesFactura"
                        rows="3"
                        maxlength="500"
                        placeholder="Detalles adicionales de la factura"
                    ></textarea>
                </div>

                <div class="resumen-compra-formulario">
                    <div>
                        <span>Subtotal</span>
                        <strong id="subtotalFacturaResumen">₡0</strong>
                    </div>
                    <div>
                        <span>Impuesto</span>
                        <strong id="impuestoFacturaResumen">₡0</strong>
                    </div>
                    <div class="total-compra-formulario">
                        <span>Total</span>
                        <strong id="totalFacturaResumen">₡0</strong>
                    </div>
                </div>

                <p id="mensajeFormularioFactura" class="mensaje-login" aria-live="polite"></p>

                <div class="acciones-modal-producto">
                    <button type="button" id="botonCancelarFactura" class="boton boton-borde">Cancelar</button>
                    <button type="submit" class="boton boton-azul">
                        <i class="fa-solid fa-paper-plane"></i>
                        Enviar a validación
                    </button>
                </div>

            </form>

        </section>

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

    <script src="../js/app.js"></script>
    <script src="../js/pedido-api.js"></script>
    <script src="../js/factura-api.js"></script>
    <script src="../js/factura.js"></script>

</body>
</html>
