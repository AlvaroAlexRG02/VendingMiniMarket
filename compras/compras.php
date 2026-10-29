<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

$paginaActual = basename($_SERVER["PHP_SELF"]);

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
        content="Historial de compras y facturas del sistema Vending Mini Market"
    >

    <title>Historial de compras | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=20261007-1">

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

    <!-- Logo del sistema -->
    <div class="logo-sistema">
        <a
            href="/VendingMiniMarket/dashboard/dashboard.php"
            aria-label="Ir al Dashboard"
        >
            <img
                src="/VendingMiniMarket/img/logo/logo-vending.jpeg"
                alt="Logo de Vending Mini Market"
                class="imagen-logo-sistema"
            >
        </a>
    </div>

    <!-- Navegación principal -->
    <nav class="nav-lateral" aria-label="Navegación principal">

        <a href="/VendingMiniMarket/dashboard/dashboard.php"
           class="<?= $paginaActual === 'dashboard.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'dashboard.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-chart-pie"></i>
            Dashboard
        </a>

        <a href="/VendingMiniMarket/usuarios/usuarios.php"
           class="<?= $paginaActual === 'usuarios.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'usuarios.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-users"></i>
            Usuarios
        </a>

        <a href="/VendingMiniMarket/catalogo/catalogo.php"
           class="<?= $paginaActual === 'catalogo.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'catalogo.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-box"></i>
            Catálogo
        </a>

        <a href="/VendingMiniMarket/tienda/tienda.php"
           class="<?= $paginaActual === 'tienda.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'tienda.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-shop"></i>
            Tiendas
        </a>

        <a href="/VendingMiniMarket/maquinas/maquinas.php"
           class="<?= $paginaActual === 'maquinas.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'maquinas.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-cash-register"></i>
            Máquinas
        </a>

        <a href="/VendingMiniMarket/inventario/inventario.php"
           class="<?= $paginaActual === 'inventario.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'inventario.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-clipboard-list"></i>
            Inventario
        </a>

        <a href="/VendingMiniMarket/compras/compras.php"
           class="<?= $paginaActual === 'compras.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'compras.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-cart-shopping"></i>
            Pedidos
        </a>

        <a href="/VendingMiniMarket/ventas/ventas.html"
           class="<?= $paginaActual === 'ventas.html' ? 'activo' : '' ?>"
           <?= $paginaActual === 'ventas.html' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-chart-column"></i>
            Ventas
        </a>

        <a href="/VendingMiniMarket/reportes/reportes.php"
           class="<?= $paginaActual === 'reportes.php' ? 'activo' : '' ?>"
           <?= $paginaActual === 'reportes.php' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-chart-line"></i>
            Reportes
        </a>

        <?php if (esAdministradorOGerente()): ?>

            <a href="/VendingMiniMarket/bitacora/bitacora.php"
               class="<?= $paginaActual === 'bitacora.php' ? 'activo' : '' ?>"
               <?= $paginaActual === 'bitacora.php' ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-clipboard-list"></i>
                Bitácora
            </a>

            <a href="/VendingMiniMarket/respaldo/respaldo.php"
               class="<?= $paginaActual === 'respaldo.php' ? 'activo' : '' ?>"
               <?= $paginaActual === 'respaldo.php' ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-database"></i>
                Respaldos
            </a>

        <?php endif; ?>

        <?php if (esAdministrador()): ?>

            <a href="/VendingMiniMarket/incidencias/incidencias.php"
               class="<?= $paginaActual === 'incidencias.php' ? 'activo' : '' ?>"
               <?= $paginaActual === 'incidencias.php' ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-triangle-exclamation"></i>
                Incidencias
            </a>

        <?php endif; ?>

    </nav>

    <!-- Cerrar sesión -->
    <div class="pie-barra-lateral">

        <a href="/VendingMiniMarket/index/index.html"
           id="botonCerrarSesion">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar sesión
        </a>

    </div>

</aside>
    <!-- ==========================================
         COLUMNA PRINCIPAL
    =========================================== -->

    <div class="columna-principal">

        <!-- ==========================================
             BARRA SUPERIOR
        =========================================== -->

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
                    id="buscadorGeneralCompras"
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
                        id="contadorNotificacionesCompras"
                        aria-label="0 notificaciones"
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
        <!-- ==========================================
             CONTENIDO PRINCIPAL
        =========================================== -->

        <main class="contenido-app">

            <!-- Encabezado -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Historial de compras y facturas</h1>

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
                        Nuevo pedido
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

                    <a
                        href="facturas.php"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-invoice"></i>
                        Nueva factura
                    </a>

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

    <script src="../js/app.js"></script>
    <script src="../js/pedido-api.js"></script>
    <script src="../js/factura-api.js"></script>
    <script src="../js/factura.js"></script>

</body>
</html>
