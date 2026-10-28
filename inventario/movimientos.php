<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/permisos.php';


/* ==========================================================
   VALIDAR SESIÓN
========================================================== */

if (!isset($_SESSION['usuario'])) {
    header('Location: ../index/index.html');
    exit;
}


/* ==========================================================
   INFORMACIÓN DEL USUARIO
========================================================== */

$nombreUsuario = trim(
    ($_SESSION['usuario']['nombre'] ?? '') . ' ' .
        ($_SESSION['usuario']['apellido'] ?? '')
);

$correoUsuario =
    $_SESSION['usuario']['correo'] ?? '';


/* ==========================================================
   TIPO DE MOVIMIENTO SOLICITADO
========================================================== */

$tipoSolicitado = strtolower(
    trim(
        (string)($_GET['tipo'] ?? '')
    )
);

$tiposPermitidos = [
    'entrada',
    'salida',
    'ajuste',
    'merma',
    'traslado'
];

if (
    !in_array(
        $tipoSolicitado,
        $tiposPermitidos,
        true
    )
) {
    $tipoSolicitado = '';
}


/* ==========================================================
   EVITAR CACHÉ
========================================================== */

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header('Pragma: no-cache');
header('Expires: 0');

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
        content="Registro y consulta de movimientos de inventario">

    <title>
        Movimientos de inventario | Vending Mini Market
    </title>

    <!-- Hoja de estilos principal -->
    <link
        rel="stylesheet"
        href="../css/estilos.css?v=20261007-1">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>


<body
    class="pagina-app"
    data-tipo-movimiento="<?= htmlspecialchars(
                                $tipoSolicitado,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">


    <!-- ======================================================
         BARRA LATERAL
    ======================================================= -->

    <aside class="barra-lateral">


        <!-- Logo -->

        <div class="logo-sistema">

            <a
                href="../dashboard/dashboard.php"
                aria-label="Ir al Dashboard">

                <img
                    src="../img/logo/logo-vending.jpeg"
                    alt="Logo de Vending Mini Market"
                    class="imagen-logo-sistema">

            </a>

        </div>


        <!-- Navegación -->

        <nav
            class="nav-lateral"
            aria-label="Navegación principal">

            <a href="../dashboard/dashboard.php">

                <i class="fa-solid fa-chart-pie"></i>

                Dashboard

            </a>


            <a href="../usuarios/usuarios.php">

                <i class="fa-solid fa-users"></i>

                Usuarios

            </a>


            <a href="../catalogo/catalogo.php">

                <i class="fa-solid fa-box"></i>

                Catálogo

            </a>


            <a href="../tienda/tienda.php">

                <i class="fa-solid fa-shop"></i>

                Tiendas

            </a>


            <a href="../maquinas/maquinas.php">

                <i class="fa-solid fa-cash-register"></i>

                Máquinas

            </a>


            <a
                href="inventario.php"
                class="activo"
                aria-current="page">

                <i class="fa-solid fa-clipboard-list"></i>

                Inventario

            </a>


            <a href="../compras/compras.php">

                <i class="fa-solid fa-cart-shopping"></i>

                Compras

            </a>


            <a href="../ventas/ventas.php">

                <i class="fa-solid fa-chart-column"></i>

                Ventas

            </a>


            <a href="../alertas/alertas.php">

                <i class="fa-solid fa-bell"></i>

                Alertas

            </a>


            <a href="../reportes/reportes.php">

                <i class="fa-solid fa-chart-line"></i>

                Reportes

            </a>


            <?php if (esAdministradorOGerente()): ?>

                <a href="../bitacora/bitacora.php">

                    <i class="fa-solid fa-clipboard-list"></i>

                    Bitácora

                </a>


                <a href="../respaldo/respaldo.php">

                    <i class="fa-solid fa-database"></i>

                    Respaldos

                </a>

            <?php endif; ?>


            <?php if (esAdministrador()): ?>

                <a href="../incidencias/incidencias.php">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                    Incidencias

                </a>

            <?php endif; ?>

        </nav>


        <!-- Cerrar sesión -->

        <div class="pie-barra-lateral">

            <a
                href="../index/index.html"
                id="botonCerrarSesion">

                <i class="fa-solid fa-right-from-bracket"></i>

                Cerrar sesión

            </a>

        </div>

    </aside>


    <!-- ======================================================
         COLUMNA PRINCIPAL
    ======================================================= -->

    <div class="columna-principal">


        <!-- ==================================================
             BARRA SUPERIOR
        =================================================== -->

        <header class="barra-superior">


            <!-- Menú -->

            <button
                type="button"
                class="boton-menu"
                aria-label="Abrir o cerrar menú lateral">

                <i class="fa-solid fa-bars"></i>

            </button>


            <!-- Buscador general -->

            <div class="buscador-superior">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    id="buscadorGeneralMovimientos"
                    placeholder="Buscar en el sistema..."
                    aria-label="Buscar en el sistema"
                    autocomplete="off">

            </div>


            <!-- Acciones superiores -->

            <div class="acciones-superiores">


                <!-- Notificaciones -->

                <button
                    type="button"
                    class="boton-notificacion"
                    aria-label="Consultar notificaciones">

                    <i class="fa-regular fa-bell"></i>

                    <span
                        class="contador"
                        id="contadorNotificacionesMovimientos"
                        aria-label="0 notificaciones">
                        0
                    </span>

                </button>


                <!-- Perfil -->

                <div class="perfil-superior">

                    <div class="perfil-superior-texto">

                        <strong id="nombreUsuarioSuperior">

                            <?= htmlspecialchars(
                                $nombreUsuario,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </strong>

                        <span id="correoUsuarioSuperior">

                            <?= htmlspecialchars(
                                $correoUsuario,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </div>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>

        </header>


        <!-- ==================================================
             CONTENIDO PRINCIPAL
        =================================================== -->

        <main class="contenido-app">


            <!-- ==================================================
                 ENCABEZADO
            =================================================== -->

            <div class="encabezado-pagina">

                <div>

                    <h1>
                        Movimientos de inventario
                    </h1>

                    <p>
                        Registra y consulta entradas, salidas,
                        ajustes, mermas y traslados de productos.
                    </p>

                </div>


                <a
                    href="inventario.php"
                    class="boton boton-borde">

                    <i class="fa-solid fa-arrow-left"></i>

                    Volver al inventario

                </a>

            </div>


            <!-- ==================================================
                 MENSAJE GENERAL
            =================================================== -->

            <div
                id="mensajeMovimiento"
                class="mensaje-producto"
                aria-live="polite"
                hidden></div>


            <!-- ==================================================
                 RESUMEN DE MOVIMIENTOS
            =================================================== -->

            <section
                class="grilla-estadisticas"
                aria-label="Resumen de movimientos">


                <!-- Entradas -->

                <article class="tarjeta-estadistica">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">

                            <i class="fa-solid fa-arrow-down"></i>

                        </div>


                        <div>

                            <div class="tarjeta-estadistica-titulo">

                                Entradas del día

                            </div>


                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalEntradas">
                                0
                            </div>

                        </div>

                    </div>


                    <div class="tarjeta-estadistica-cambio">

                        Unidades ingresadas

                    </div>

                </article>


                <!-- Salidas -->

                <article class="tarjeta-estadistica borde-verde">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">

                            <i class="fa-solid fa-arrow-up"></i>

                        </div>


                        <div>

                            <div class="tarjeta-estadistica-titulo">

                                Salidas del día

                            </div>


                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalSalidas">
                                0
                            </div>

                        </div>

                    </div>


                    <div class="tarjeta-estadistica-cambio">

                        Unidades retiradas

                    </div>

                </article>


                <!-- Ajustes -->

                <article class="tarjeta-estadistica borde-naranja">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">

                            <i class="fa-solid fa-sliders"></i>

                        </div>


                        <div>

                            <div class="tarjeta-estadistica-titulo">

                                Ajustes realizados

                            </div>


                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalAjustes">
                                0
                            </div>

                        </div>

                    </div>


                    <div class="tarjeta-estadistica-cambio">

                        Movimientos de ajuste

                    </div>

                </article>


                <!-- Mermas -->

                <article class="tarjeta-estadistica borde-rojo">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">

                            <i class="fa-solid fa-trash-can"></i>

                        </div>


                        <div>

                            <div class="tarjeta-estadistica-titulo">

                                Mermas registradas

                            </div>


                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalMermas">
                                0
                            </div>

                        </div>

                    </div>


                    <div class="tarjeta-estadistica-cambio">

                        Productos dañados o vencidos

                    </div>

                </article>

            </section>


            <!-- ==================================================
                 FORMULARIO DE MOVIMIENTO
            =================================================== -->

            <section
                class="panel"
                style="margin-bottom: 20px;">


                <!-- Encabezado formulario -->

                <div class="panel-encabezado">

                    <div>

                        <h2>
                            Registrar movimiento
                        </h2>

                        <p class="descripcion-panel">
                            Complete los datos necesarios para
                            actualizar el inventario.
                        </p>

                    </div>


                    <div class="tarjeta-estadistica-icono">

                        <i class="fa-solid fa-arrow-right-arrow-left"></i>

                    </div>

                </div>


                <!-- ==================================================
                     FORMULARIO
                =================================================== -->

                <form
                    id="formularioMovimientoInventario"
                    autocomplete="off">

                    <div class="cuadricula-formulario">


                        <!-- ==========================================
                             TIPO DE MOVIMIENTO
                        =========================================== -->

                        <div class="grupo-formulario">

                            <label for="tipoMovimiento">

                                Tipo de movimiento

                            </label>


                            <select
                                id="tipoMovimiento"
                                name="tipoMovimiento"
                                required>

                                <option value="" disabled <?= $tipoSolicitado === '' ? 'selected' : '' ?>>
                                    Seleccione un tipo de movimiento
                                </option>

                                <option
                                    value="entrada"
                                    <?= $tipoSolicitado === 'entrada'
                                        ? 'selected'
                                        : '' ?>>
                                    Entrada
                                </option>


                                <option
                                    value="salida"
                                    <?= $tipoSolicitado === 'salida'
                                        ? 'selected'
                                        : '' ?>>
                                    Salida
                                </option>


                                <option
                                    value="ajuste"
                                    <?= $tipoSolicitado === 'ajuste'
                                        ? 'selected'
                                        : '' ?>>
                                    Ajuste
                                </option>


                                <option
                                    value="merma"
                                    <?= $tipoSolicitado === 'merma'
                                        ? 'selected'
                                        : '' ?>>
                                    Merma
                                </option>


                                <option
                                    value="traslado"
                                    <?= $tipoSolicitado === 'traslado'
                                        ? 'selected'
                                        : '' ?>>
                                    Traslado
                                </option>

                            </select>

                        </div>


                        <!-- ==========================================
                             BUSCAR PRODUCTO
                        =========================================== -->

                        <div class="grupo-formulario">

                            <label for="buscarProductoMovimiento">

                                Buscar producto

                            </label>


                            <input
                                type="search"
                                id="buscarProductoMovimiento"
                                placeholder="Nombre, código retail o código de barras..."
                                autocomplete="off"
                                aria-describedby="resultadoBusquedaProductoMovimiento">


                            <small
                                id="resultadoBusquedaProductoMovimiento"
                                class="texto-secundario"
                                aria-live="polite">
                                Escriba para filtrar la lista de productos.
                            </small>

                        </div>


                        <!-- ==========================================
                             PRODUCTO
                        =========================================== -->

                        <div class="grupo-formulario campo-completo">

                            <label for="productoMovimiento">

                                Producto

                            </label>


                            <select
                                id="productoMovimiento"
                                name="productoMovimiento"
                                required>

                                <option value="">

                                    Cargando productos...

                                </option>

                            </select>


                            <small class="texto-secundario">

                                Puede elegir directamente un producto
                                o utilizar el buscador para reducir la lista.

                            </small>

                        </div>


                        <!-- ==========================================
                             ORIGEN DE MERCADERÍA
                        =========================================== -->

                        <div
                            class="grupo-formulario"
                            id="grupoOrigenMercaderia">

                            <label for="origenMercaderia">

                                Tipo de entrada

                            </label>


                            <select
                                id="origenMercaderia"
                                name="origenMercaderia">

                                <option value="">

                                    Seleccione el origen

                                </option>


                                <option value="Proveedor">

                                    Entrada directa de proveedor

                                </option>


                                <option value="Pedido de compra">

                                    Recepción de pedido de compra

                                </option>


                                <option value="Devolución">

                                    Devolución de mercadería

                                </option>

                            </select>

                        </div>


                        <!-- ==========================================
                             PEDIDO DE COMPRA
                        =========================================== -->

                        <div
                            class="grupo-formulario"
                            id="grupoPedidoMovimiento"
                            hidden>

                            <label for="pedidoMovimiento">

                                Pedido de compra

                            </label>


                            <select
                                id="pedidoMovimiento"
                                name="pedidoMovimiento">

                                <option value="">

                                    Cargando pedidos...

                                </option>

                            </select>

                        </div>


                        <!-- ==========================================
                             PROVEEDOR
                        =========================================== -->

                        <div
                            class="grupo-formulario"
                            id="grupoProveedorMovimiento"
                            hidden>

                            <label for="proveedorMovimiento">

                                Proveedor

                            </label>


                            <select
                                id="proveedorMovimiento"
                                name="proveedorMovimiento">

                                <option value="">

                                    Cargando proveedores...

                                </option>

                            </select>

                        </div>


                        <!-- ==========================================
                             UBICACIÓN DE ORIGEN
                        =========================================== -->

                        <div
                            class="grupo-formulario"
                            id="grupoUbicacionOrigen"
                            hidden>

                            <label for="ubicacionOrigen">

                                Ubicación de origen

                            </label>


                            <select
                                id="ubicacionOrigen"
                                name="ubicacionOrigen">

                                <option value="">

                                    Seleccione una ubicación

                                </option>

                            </select>

                        </div>


                        <!-- ==========================================
                             UBICACIÓN DE DESTINO
                        =========================================== -->

                        <div
                            class="grupo-formulario"
                            id="grupoUbicacionDestino">

                            <label for="ubicacionDestino">

                                Ubicación de destino

                            </label>


                            <select
                                id="ubicacionDestino"
                                name="ubicacionDestino"
                                required>

                                <option value="">

                                    Cargando ubicaciones...

                                </option>

                            </select>

                        </div>


                        <!-- ==========================================
                             CANTIDAD
                        =========================================== -->

                        <div class="grupo-formulario">

                            <label for="cantidadMovimiento">

                                Cantidad

                            </label>


                            <input
                                type="number"
                                id="cantidadMovimiento"
                                name="cantidadMovimiento"
                                min="1"
                                step="1"
                                placeholder="Ingrese la cantidad"
                                required>

                        </div>


                        <!-- ==========================================
                             FECHA
                        =========================================== -->

                        <div class="grupo-formulario">

                            <label for="fechaMovimiento">

                                Fecha y hora del movimiento

                            </label>


                            <input
                                type="text"
                                id="fechaMovimiento"
                                name="fechaMovimiento"
                                readonly>


                            <small class="texto-secundario">

                                La fecha y hora se completan automáticamente.

                            </small>

                        </div>


                        <!-- ==========================================
                             DOCUMENTO O REFERENCIA
                        =========================================== -->

                        <div
                            class="grupo-formulario"
                            id="grupoReferenciaMovimiento"
                            hidden>

                            <label for="referenciaMovimiento">

                                Documento o referencia

                            </label>


                            <input
                                type="text"
                                id="referenciaMovimiento"
                                name="referenciaMovimiento"
                                placeholder="Ejemplo: FAC-10235"
                                maxlength="150"
                                autocomplete="off">

                        </div>


                        <!-- ==========================================
                                RESPONSABLE
                        =========================================== -->

                        <div class="grupo-formulario">

                            <label for="responsableMovimiento">
                                Responsable
                            </label>

                            <input
                                type="text"
                                id="responsableMovimiento"
                                value="<?= htmlspecialchars(
                                            $nombreUsuario,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                readonly>

                            <small class="texto-secundario">
                                Responsable del movimiento.
                            </small>

                        </div>


                        <!-- ==========================================
                             OBSERVACIONES
                        =========================================== -->

                        <div
                            class="grupo-formulario campo-completo"
                            id="grupoObservacionesMovimiento"
                            hidden>

                            <label for="observacionesMovimiento">

                                Motivo u observaciones

                            </label>


                            <textarea
                                id="observacionesMovimiento"
                                name="observacionesMovimiento"
                                rows="4"
                                maxlength="500"
                                placeholder="Información adicional sobre el movimiento..."></textarea>

                        </div>

                    </div>


                    <!-- ==============================================
                         TABLA DE PRODUCTOS DEL MOVIMIENTO
                         HU-21: un movimiento, varios productos
                    =============================================== -->
                    <section id="seccionProductosMovimiento" class="panel" style="padding:16px; margin:18px 0;">
                        <div class="panel-encabezado" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                            <div>
                                <h3>Productos del movimiento</h3>
                                <p class="descripcion-panel" id="instruccionTablaMovimiento">
                                    Seleccione un producto y su cantidad, luego agréguelo a la tabla.
                                </p>
                            </div>
                            <button type="button" class="boton boton-borde" id="botonAgregarProductoMovimiento">
                                <i class="fa-solid fa-plus"></i> Agregar producto
                            </button>
                        </div>
                        <div class="contenedor-tabla" style="overflow-x:auto;">
                            <table class="tabla-datos" aria-label="Productos a registrar en el movimiento" style="min-width:740px;width:100%;">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Solicitado</th>
                                        <th>Ya recibido</th>
                                        <th>Pendiente</th>
                                        <th>Recibir ahora</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProductosMovimiento">
                                    <tr><td colspan="6">Aún no hay productos agregados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="texto-secundario" style="margin:10px 0 0;" aria-live="polite">
                            Total de unidades del movimiento: <strong id="totalUnidadesMovimiento">0</strong>
                        </p>
                    </section>

                    <!-- ==============================================
                         RESUMEN DE ENTRADA
                    =============================================== -->

                    <div
                        id="resumenEntradaMovimiento"
                        style="
                            margin-top: 18px;
                            padding: 16px;
                            border: 1px solid #e1e6ef;
                            border-radius: 10px;
                            background: #f8faff;
                        ">

                        <strong>

                            Resumen de la entrada

                        </strong>


                        <p
                            id="textoResumenEntrada"
                            style="
                                margin: 8px 0 0;
                                color: var(--texto-secundario);
                            ">

                            Seleccione producto, ubicación y cantidad.

                        </p>

                    </div>


                    <!-- ==============================================
                         ACCIONES
                    =============================================== -->

                    <div class="acciones-formulario">


                        <button
                            type="reset"
                            id="botonLimpiarMovimiento"
                            class="boton boton-borde">

                            <i class="fa-solid fa-eraser"></i>

                            Limpiar

                        </button>


                        <button
                            type="submit"
                            id="botonRegistrarMovimiento"
                            class="boton boton-azul">

                            <i class="fa-solid fa-floppy-disk"></i>

                            Registrar movimiento

                        </button>

                    </div>

                </form>

            </section>


            <!-- ==================================================
                 HISTORIAL DE MOVIMIENTOS
            =================================================== -->

            <section class="panel">


                <!-- Encabezado -->

                <div class="panel-encabezado">

                    <div>

                        <h2>
                            Historial de movimientos
                        </h2>

                        <p class="descripcion-panel">
                            Consulte los movimientos registrados
                            en el inventario.
                        </p>

                    </div>


                    <button
                        type="button"
                        id="botonExportarMovimientos"
                        class="boton boton-borde">

                        <i class="fa-solid fa-file-export"></i>

                        Exportar

                    </button>

                </div>


                <!-- ==================================================
                     FILTROS
                =================================================== -->

                <div class="barra-filtros">


                    <!-- Buscar -->

                    <div class="campo-busqueda-filtro">

                        <i class="fa-solid fa-magnifying-glass"></i>


                        <label
                            for="buscarMovimiento"
                            class="texto-solo-lectores">

                            Buscar movimiento

                        </label>


                        <input
                            type="search"
                            id="buscarMovimiento"
                            placeholder="Buscar producto, referencia o responsable..."
                            autocomplete="off">

                    </div>


                    <!-- Tipo -->

                    <div class="selector-filtro">

                        <label for="filtroTipoMovimiento">

                            Tipo

                        </label>


                        <select id="filtroTipoMovimiento">

                            <option value="">
                                Todos los tipos
                            </option>

                            <option value="entrada">
                                Entrada
                            </option>

                            <option value="salida">
                                Salida
                            </option>

                            <option value="ajuste">
                                Ajuste
                            </option>

                            <option value="merma">
                                Merma
                            </option>

                            <option value="traslado">
                                Traslado
                            </option>

                        </select>

                    </div>


                    <!-- Tienda -->

                    <div class="selector-filtro">

                        <label for="filtroUbicacionMovimiento">

                            Tienda

                        </label>


                        <select id="filtroUbicacionMovimiento">

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


                    <!-- Fecha -->

                    <div class="selector-filtro">

                        <label for="filtroFecha">
                            Fecha
                        </label>

                        <input
                            type="date"
                            id="filtroFecha">

                    </div>


                    <!-- Limpiar -->

                    <button
                        type="button"
                        class="boton boton-borde"
                        id="botonLimpiarFiltros">

                        <i class="fa-solid fa-rotate-left"></i>

                        Limpiar filtros

                    </button>

                </div>


                <!-- Resultado -->

                <p
                    id="resultadoBusquedaMovimientos"
                    class="resultado-busqueda-productos"
                    aria-live="polite"></p>


                <!-- ==================================================
                     TABLA
                =================================================== -->

                <div
                    id="contenedorTablaMovimientos"
                    class="contenedor-tabla">

                    <table class="tabla-datos">

                        <caption class="texto-solo-lectores">
                            Historial de movimientos de inventario
                        </caption>


                        <thead>

                            <tr>

                                <th scope="col">
                                    Fecha
                                </th>

                                <th scope="col">
                                    Referencia
                                </th>

                                <th scope="col">
                                    Producto
                                </th>

                                <th scope="col">
                                    Tipo
                                </th>

                                <th scope="col">
                                    Origen
                                </th>

                                <th scope="col">
                                    Destino
                                </th>

                                <th scope="col">
                                    Cantidad
                                </th>

                                <th scope="col">
                                    Responsable
                                </th>

                                <th scope="col">
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody id="tablaMovimientos">

                            <!--
                                El historial se conectará a los
                                movimientos registrados en la base.
                            -->

                        </tbody>

                    </table>

                </div>


                <!-- ==================================================
                     SIN MOVIMIENTOS
                =================================================== -->

                <div
                    id="mensajeSinMovimientos"
                    class="estado-vacio-productos"
                    hidden>

                    <i class="fa-solid fa-arrow-right-arrow-left"></i>

                    <h3>
                        No hay movimientos registrados
                    </h3>

                    <p>
                        Los movimientos aparecerán aquí después
                        de registrarlos.
                    </p>

                </div>


                <!-- ==================================================
                     SIN RESULTADOS
                =================================================== -->

                <div
                    id="mensajeSinResultadosMovimientos"
                    class="estado-vacio-productos"
                    hidden>

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <h3>
                        No se encontraron movimientos
                    </h3>

                    <p>
                        Cambia los filtros o el texto de búsqueda.
                    </p>

                </div>


                <!-- ==================================================
                     PAGINACIÓN
                =================================================== -->

                <div class="paginacion">

                    <span id="textoPaginacionMovimientos">
                        No hay movimientos registrados
                    </span>


                    <div class="paginacion-controles">

                        <button
                            type="button"
                            id="botonPaginaAnteriorMovimientos"
                            disabled
                            aria-label="Página anterior">

                            <i class="fa-solid fa-chevron-left"></i>

                        </button>


                        <button
                            type="button"
                            id="paginaActualMovimientos"
                            class="activo"
                            disabled>
                            1
                        </button>


                        <button
                            type="button"
                            id="botonPaginaSiguienteMovimientos"
                            disabled
                            aria-label="Página siguiente">

                            <i class="fa-solid fa-chevron-right"></i>

                        </button>

                    </div>

                </div>

            </section>

        </main>

    </div>


    <!-- ======================================================
         JAVASCRIPT
    ======================================================= -->

    <script src="../js/app.js"></script>

    <script src="../js/movimientos.js"></script>


</body>

</html>