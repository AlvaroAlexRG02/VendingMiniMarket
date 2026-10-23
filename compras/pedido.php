<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

/*
 * Sin sesión iniciada no se puede entrar a la página,
 * ni escribiendo el enlace directamente en el navegador.
 */
if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

/*
 * Evitar que el navegador muestre la página después de
 * cerrar sesión utilizando la caché.
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$nombreUsuario = trim(
    ($_SESSION["usuario"]["nombre"] ?? "") . " " .
    ($_SESSION["usuario"]["apellido"] ?? "")
);
$correoUsuario = $_SESSION["usuario"]["correo"] ?? "";
$puedeActualizarEstado = esAdministrador() || esEncargado();

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
        content="Creación y gestión de pedidos del sistema Vending Mini Market"
    >

    <title>Pedidos | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body class="pagina-app" data-puede-actualizar-estado="<?= $puedeActualizarEstado ? '1' : '0' ?>">

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

            <a href="../productos/productos.html">
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

            <a href="../inventario/inventario.html">
                <i class="fa-solid fa-clipboard-list"></i>
                Inventario
            </a>

            <a
                href="compras.html"
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
                    id="buscadorGeneralPedidos"
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
                        id="contadorNotificacionesPedidos"
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

                    <h1>Pedidos</h1>

                    <p>
                        Crea solicitudes de productos para enviarlas
                        a los proveedores.
                    </p>

                </div>

                <div class="acciones-encabezado-pedidos">

                    <a
                        href="compras.html"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver a compras
                    </a>

                    <button
                        type="button"
                        id="botonNuevoPedido"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-circle-plus"></i>
                        Nuevo pedido
                    </button>

                </div>

            </div>

            <div
                id="mensajePedidos"
                class="mensaje-producto"
                aria-live="polite"
                hidden
            ></div>

            <!-- ==========================================
                 RESUMEN
            =========================================== -->

            <section
                class="grilla-estadisticas"
                aria-label="Resumen de pedidos"
            >

                <article class="tarjeta-estadistica">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Total de pedidos
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalPedidos"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Solicitudes registradas
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-naranja">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-clock"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Pendientes
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="pedidosPendientes"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Esperando envío
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-morado">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Requieren aprobación
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="pedidosRequierenAprobacion"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Revisar antes de enviar
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-verde">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Productos solicitados
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalProductosPedidos"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Unidades solicitadas
                    </div>

                </article>

            </section>

            <!-- ==========================================
                 LISTADO
            =========================================== -->

            <section class="panel">

                <div class="panel-encabezado">

                    <div>

                        <h2>Pedidos registrados</h2>

                        <p class="descripcion-panel">
                            Consulta las solicitudes enviadas
                            y su estado actual.
                        </p>

                    </div>

                </div>

                <div class="barra-filtros">

                    <div class="campo-busqueda-filtro">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <label
                            for="buscarPedido"
                            class="texto-solo-lectores"
                        >
                            Buscar pedido
                        </label>

                        <input
                            type="search"
                            id="buscarPedido"
                            placeholder="Buscar por proveedor o tienda..."
                            autocomplete="off"
                        >

                    </div>

                    <div class="selector-filtro">

                        <label for="filtroProveedorPedido">
                            Proveedor
                        </label>

                        <select id="filtroProveedorPedido">
                            <option value="">Todos los proveedores</option>
                        </select>

                    </div>

                    <div class="selector-filtro">

                        <label for="filtroTiendaPedido">
                            Tienda
                        </label>

                        <select id="filtroTiendaPedido">
                            <option value="">Todas las tiendas</option>
                        </select>

                    </div>

                    <div class="selector-filtro">

                        <label for="filtroEstadoPedido">
                            Estado
                        </label>

                        <select id="filtroEstadoPedido">

                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="EN_CAMINO">En camino</option>
                            <option value="PARCIAL">Parcial</option>
                            <option value="RECIBIDO">Recibido</option>
                            <option value="CANCELADO">Cancelado</option>

                        </select>

                    </div>

                    <div class="selector-filtro">

                        <label for="filtroFechaPedido">
                            Fecha
                        </label>

                        <input
                            type="date"
                            id="filtroFechaPedido"
                        >

                    </div>

                    <button
                        type="button"
                        id="botonLimpiarFiltrosPedidos"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <p
                    id="resultadoBusquedaPedidos"
                    class="resultado-busqueda-productos"
                    aria-live="polite"
                ></p>

                <div
                    id="contenedorTablaPedidos"
                    class="contenedor-tabla"
                    hidden
                >

                    <table class="tabla-datos tabla-pedidos">

                        <caption class="texto-solo-lectores">
                            Pedidos registrados
                        </caption>

                        <thead>

                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Proveedor</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Solicitado por</th>
                                <th scope="col">Productos</th>
                                <th scope="col">Total estimado</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>

                        </thead>

                        <tbody id="cuerpoTablaPedidos">
                            <!-- JavaScript agregará los pedidos -->
                        </tbody>

                    </table>

                </div>

                <div
                    id="mensajeSinPedidos"
                    class="estado-vacio-productos"
                    hidden
                >

                    <i class="fa-solid fa-file-circle-xmark"></i>

                    <h3>
                        No hay pedidos registrados
                    </h3>

                    <p>
                        Crea un pedido para solicitar productos
                        a un proveedor.
                    </p>

                    <button
                        type="button"
                        id="botonPrimerPedido"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-circle-plus"></i>
                        Crear pedido
                    </button>

                </div>

                <div
                    id="mensajeSinResultadosPedidos"
                    class="estado-vacio-productos"
                    hidden
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <h3>
                        No se encontraron pedidos
                    </h3>

                    <p>
                        Cambia los filtros o el texto de búsqueda.
                    </p>

                </div>

            </section>

        </main>

    </div>

    <!-- ==========================================
         MODAL: NUEVO PEDIDO
    =========================================== -->

    <div
        id="modalPedido"
        class="modal-producto"
        hidden
    >

        <div
            class="modal-producto-fondo"
            data-cerrar-modal-pedido
        ></div>

        <section
            class="modal-producto-contenido modal-pedido-contenido"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalPedido"
        >

            <div class="encabezado-modal-venta">

                <div>

                    <h2 id="tituloModalPedido">
                        Nuevo pedido
                    </h2>

                    <p>
                        Registra una solicitud de productos
                        para un proveedor.
                    </p>

                </div>

                <button
                    type="button"
                    id="botonCerrarModalPedido"
                    class="boton-cerrar-modal"
                    aria-label="Cerrar formulario"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <form id="formularioPedido">

                <div class="cuadricula-formulario">

                    <div class="grupo-formulario">

                        <label for="fechaPedido">
                            Fecha del pedido
                        </label>

                        <input
                            type="text"
                            id="fechaPedido"
                            readonly
                        >

                    </div>

                    <div class="grupo-formulario">

                        <label for="proveedorPedido">
                            Proveedor
                        </label>

                        <select
                            id="proveedorPedido"
                            name="proveedorPedido"
                            required
                        >
                            <option value="">Seleccione un proveedor</option>
                        </select>

                    </div>

                    <div class="grupo-formulario">

                        <label for="ubicacionPedido">
                            Tienda solicitante
                        </label>

                        <select
                            id="ubicacionPedido"
                            name="ubicacionPedido"
                            required
                        >
                            <option value="">Seleccione una tienda</option>
                        </select>

                    </div>

                    <div class="grupo-formulario">

                        <label for="responsablePedido">
                            Responsable
                        </label>

                        <input
                            type="text"
                            id="responsablePedido"
                            value="<?= htmlspecialchars($nombreUsuario) ?>"
                            readonly
                        >

                    </div>

                </div>

                <div class="seccion-productos-compra">

                    <div class="encabezado-productos-compra">

                        <div>

                            <h3>Productos solicitados</h3>

                            <p>
                                Selecciona primero un proveedor
                                para ver sus productos.
                            </p>

                        </div>

                        <button
                            type="button"
                            id="botonAgregarProductoPedido"
                            class="boton boton-borde"
                            disabled
                        >
                            <i class="fa-solid fa-plus"></i>
                            Agregar producto
                        </button>

                    </div>

                    <div
                        id="listaProductosPedido"
                        class="lista-productos-compra"
                    >
                        <!-- JavaScript agregará las filas -->
                    </div>

                    <div
                        id="mensajeSinProductosPedido"
                        class="mensaje-productos-compra"
                    >
                        Todavía no se han agregado productos.
                    </div>

                </div>

                <div class="grupo-formulario">

                    <label for="observacionesPedido">
                        Observaciones
                    </label>

                    <textarea
                        id="observacionesPedido"
                        name="observacionesPedido"
                        rows="3"
                        maxlength="500"
                        placeholder="Indica detalles adicionales del pedido"
                    ></textarea>

                </div>

                <div class="resumen-compra-formulario">

                    <div>

                        <span>Total de unidades</span>

                        <strong id="cantidadTotalPedido">
                            0
                        </strong>

                    </div>

                    <div class="total-compra-formulario">

                        <span>Total estimado</span>

                        <strong id="totalPedido">
                            ₡0
                        </strong>

                    </div>

                </div>

                <p
                    id="mensajeFormularioPedido"
                    class="mensaje-login"
                    aria-live="polite"
                ></p>

                <div class="acciones-modal-producto">

                    <button
                        type="button"
                        id="botonCancelarPedido"
                        class="boton boton-borde"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar pedido
                    </button>

                </div>

            </form>

        </section>

    </div>

    <script src="../js/app.js"></script>
    <script src="../js/pedido-api.js"></script>
    <script src="../js/pedido.js"></script>

</body>
</html>
