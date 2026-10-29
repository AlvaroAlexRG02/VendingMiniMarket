<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";


if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

$paginaActual = basename($_SERVER['PHP_SELF']);

/*
 * Ubicación asignada al usuario (HU-19), leída de la base en cada carga.
 */
$ubicacionUsuario = resumenUbicacionUsuario($pdo);

/*
 * Evitar que el navegador muestre el dashboard
 * después de cerrar sesión utilizando la caché.
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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
        content="Reportes del sistema Vending Mini Market"
    >

    <title>Reportes | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css?v=20261007-1">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body class="pagina-app">

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
                    id="buscadorGeneralReportes"
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
                        id="contadorNotificacionesReportes"
                        aria-label="0 notificaciones"
                    >
                        0
                    </span>
                </button>

                <div class="perfil-superior">

                    <div class="perfil-superior-texto">

                        <strong id="nombreUsuarioSuperior">
                            Administrador
                        </strong>

                        <span id="correoUsuarioSuperior">
                            admin@vending.com
                        </span>

                    </div>

                    <i class="fa-solid fa-chevron-down"></i>

                </div>

            </div>

        </header>

        <!-- ==========================================
             CONTENIDO
        =========================================== -->

        <main class="contenido-app">

            <!-- Encabezado -->

            <div class="encabezado-pagina">

                <div>

                    <h1>Reportes</h1>

                    <p>
                        Consulta información de ventas, inventario,
                        movimientos, compras y cierres de caja.
                    </p>

                </div>

                <div class="acciones-encabezado-reportes">

                    <button
                        type="button"
                        id="botonImprimirReporte"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-print"></i>
                        Imprimir
                    </button>

                    <button
                        type="button"
                        id="botonExportarReporte"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-file-export"></i>
                        Exportar reporte
                    </button>

                </div>

            </div>

            <!-- Mensaje -->

            <div
                id="mensajeReportes"
                class="mensaje-producto"
                aria-live="polite"
                hidden
            ></div>

            <!-- ==========================================
                 FILTROS DEL REPORTE
            =========================================== -->

            <section class="panel panel-filtros-reportes">

                <div class="panel-encabezado">

                    <div>

                        <h2>Configurar reporte</h2>

                        <p class="descripcion-panel">
                            Selecciona el tipo de información y el periodo.
                        </p>

                    </div>

                </div>

                <form id="formularioReporte">

                    <div class="cuadricula-filtros-reportes">

                        <!-- Tipo -->

                        <div class="grupo-formulario">

                            <label for="tipoReporte">
                                Tipo de reporte
                            </label>

                            <select
                                id="tipoReporte"
                                name="tipoReporte"
                                required
                            >

                                <option value="Ventas">
                                    Ventas
                                </option>

                                <option value="Inventario">
                                    Inventario
                                </option>

                                <option value="Movimientos">
                                    Movimientos
                                </option>

                                <option value="Compras">
                                    Compras
                                </option>

                                <option value="Cierres de caja">
                                    Cierres de caja
                                </option>

                                <option value="Productos">
                                    Productos
                                </option>

                            </select>

                        </div>

                        <!-- Tienda -->

                        <div class="grupo-formulario">

                            <label for="tiendaReporte">
                                Tienda
                            </label>

                            <select
                                id="tiendaReporte"
                                name="tiendaReporte"
                            >

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

                        <!-- Fecha inicial -->

                        <div class="grupo-formulario">

                            <label for="fechaInicioReporte">
                                Fecha inicial
                            </label>

                            <input
                                type="date"
                                id="fechaInicioReporte"
                                name="fechaInicioReporte"
                                required
                            >

                        </div>

                        <!-- Fecha final -->

                        <div class="grupo-formulario">

                            <label for="fechaFinReporte">
                                Fecha final
                            </label>

                            <input
                                type="date"
                                id="fechaFinReporte"
                                name="fechaFinReporte"
                                required
                            >

                        </div>

                    </div>

                    <div class="acciones-formulario-reporte">

                        <button
                            type="button"
                            id="botonLimpiarReporte"
                            class="boton boton-borde"
                        >
                            <i class="fa-solid fa-eraser"></i>
                            Limpiar
                        </button>

                        <button
                            type="submit"
                            class="boton boton-azul"
                        >
                            <i class="fa-solid fa-chart-column"></i>
                            Generar reporte
                        </button>

                    </div>

                </form>

            </section>

            <?php if (esAdministrador()): ?>
                <section class="panel" aria-labelledby="tituloResumenInventario">
                    <div class="panel-encabezado">
                        <div>
                            <h2 id="tituloResumenInventario">Resumen periódico del inventario</h2>
                            <p class="descripcion-panel">
                                Configura el envío diario o semanal de existencias críticas, alertas pendientes
                                y movimientos confirmados del periodo.
                            </p>
                        </div>
                    </div>

                    <form id="formularioResumenInventario">
                        <div class="configuracion-resumen-grid">
                            <div class="grupo-formulario">
                                <label for="correoResumenInventario">Correo de destino</label>
                                <input
                                    type="email"
                                    id="correoResumenInventario"
                                    maxlength="254"
                                    autocomplete="email"
                                    required
                                >
                            </div>

                            <div class="grupo-formulario">
                                <label for="frecuenciaResumenInventario">Frecuencia</label>
                                <select id="frecuenciaResumenInventario" required>
                                    <option value="DIARIA">Diaria</option>
                                    <option value="SEMANAL">Semanal</option>
                                </select>
                            </div>

                            <div class="grupo-formulario" id="grupoDiaResumenInventario" hidden>
                                <label for="diaResumenInventario">Día de envío</label>
                                <select id="diaResumenInventario">
                                    <option value="1">Lunes</option>
                                    <option value="2">Martes</option>
                                    <option value="3">Miércoles</option>
                                    <option value="4">Jueves</option>
                                    <option value="5">Viernes</option>
                                    <option value="6">Sábado</option>
                                    <option value="7">Domingo</option>
                                </select>
                            </div>

                            <div class="grupo-formulario">
                                <label for="horaResumenInventario">Hora de envío</label>
                                <input type="time" id="horaResumenInventario" value="07:00" required>
                            </div>
                        </div>

                        <label class="opcion-resumen-inventario" for="habilitadoResumenInventario">
                            <input type="checkbox" id="habilitadoResumenInventario">
                            Activar el envío automático
                        </label>

                        <p class="descripcion-panel">
                            La tarea del servidor debe ejecutar
                            <code>scripts/ejecutar_resumen_inventario.php</code> cada 5 minutos.
                            El sistema enviará el resumen cuando llegue la próxima ejecución programada.
                            Los horarios se interpretan en la zona horaria de Costa Rica.
                        </p>

                        <div
                            id="mensajeResumenInventario"
                            class="mensaje-producto"
                            aria-live="polite"
                            hidden
                        ></div>

                        <div class="acciones-formulario-reporte">
                            <button type="submit" class="boton boton-azul">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Guardar configuración
                            </button>
                        </div>
                    </form>

                    <div class="resumen-programacion-inventario">
                        <strong>Próximo envío:</strong>
                        <span id="proximaEjecucionResumenInventario">Sin programar</span>
                    </div>

                    <h3>Historial de envíos</h3>
                    <div class="contenedor-tabla">
                        <table class="tabla-datos">
                            <caption class="texto-solo-lectores">Historial de ejecuciones del resumen periódico</caption>
                            <thead>
                                <tr>
                                    <th>Fecha de ejecución</th>
                                    <th>Periodo</th>
                                    <th>Responsable</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody id="historialResumenInventario">
                                <tr><td colspan="6">Cargando historial…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (esAdministradorOGerente()): ?>
                <section class="panel panel-conciliacion" aria-labelledby="tituloConciliacion">
                    <div class="panel-encabezado">
                        <div>
                            <h2 id="tituloConciliacion">Conciliación de ventas e inventario</h2>
                            <p class="descripcion-panel">
                                Compara las ventas manuales con sus salidas de inventario y los saldos actuales.
                                Las importaciones Nayax aún no se incluyen porque sus filas no se almacenan en el sistema.
                            </p>
                        </div>
                    </div>

                    <form id="formularioConciliacion">
                        <div class="cuadricula-filtros-reportes">
                            <div class="grupo-formulario">
                                <label for="tiendaConciliacion">Tienda</label>
                                <select id="tiendaConciliacion" name="id_tienda">
                                    <option value="">Todas las tiendas</option>
                                </select>
                            </div>
                            <div class="grupo-formulario">
                                <label for="fechaInicioConciliacion">Fecha inicial</label>
                                <input type="date" id="fechaInicioConciliacion" required>
                            </div>
                            <div class="grupo-formulario">
                                <label for="fechaFinConciliacion">Fecha final</label>
                                <input type="date" id="fechaFinConciliacion" required>
                            </div>
                            <div class="grupo-formulario grupo-boton-conciliacion">
                                <label>&nbsp;</label>
                                <button type="submit" class="boton boton-azul">
                                    <i class="fa-solid fa-scale-balanced"></i>
                                    Conciliar periodo
                                </button>
                            </div>
                        </div>
                    </form>

                    <p id="mensajeConciliacion" class="mensaje-producto" aria-live="polite" hidden></p>

                    <div id="resumenConciliacion" class="resumen-conciliacion" hidden>
                        <span id="periodoConciliacion"></span>
                        <span id="cantidadDiferenciasConciliacion"></span>
                        <span id="utilidadConciliacion"></span>
                        <button type="button" id="botonExportarConciliacion" class="boton boton-borde">
                            <i class="fa-solid fa-file-export"></i>
                            Exportar resultados
                        </button>
                    </div>

                    <div id="contenedorConciliacion" class="contenedor-tabla" hidden>
                        <table class="tabla-datos tabla-conciliacion">
                            <caption class="texto-solo-lectores">Diferencias de ventas e inventario</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Producto</th>
                                    <th scope="col">Tienda / ubicación</th>
                                    <th scope="col">Vendido</th>
                                    <th scope="col">Salida vinculada</th>
                                    <th scope="col">Diferencia venta / salida</th>
                                    <th scope="col">Existencia actual</th>
                                    <th scope="col">Último saldo de movimiento</th>
                                    <th scope="col">Diferencia de existencia</th>
                                    <th scope="col">Utilidad estimada</th>
                                    <th scope="col">Observación y ajuste autorizado</th>
                                </tr>
                            </thead>
                            <tbody id="cuerpoConciliacion"></tbody>
                        </table>
                    </div>

                    <div id="mensajeSinDatosConciliacion" class="estado-vacio-productos" hidden>
                        <i class="fa-solid fa-circle-check"></i>
                        <h3>Sin ventas manuales para conciliar</h3>
                        <p>No se encontraron ventas en las fechas seleccionadas.</p>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ==========================================
                 RESUMEN
            =========================================== -->

            <section
                class="grilla-estadisticas"
                aria-label="Resumen del reporte"
            >

                <article class="tarjeta-estadistica">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-coins"></i>
                        </div>

                        <div>

                            <div
                                class="tarjeta-estadistica-titulo"
                                id="tituloIndicadorReporte1"
                            >
                                Total registrado
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="valorIndicadorReporte1"
                            >
                                ₡0
                            </div>

                        </div>

                    </div>

                    <div
                        class="tarjeta-estadistica-cambio"
                        id="detalleIndicadorReporte1"
                    >
                        Según el periodo seleccionado
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-verde">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-list-ol"></i>
                        </div>

                        <div>

                            <div
                                class="tarjeta-estadistica-titulo"
                                id="tituloIndicadorReporte2"
                            >
                                Registros
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="valorIndicadorReporte2"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div
                        class="tarjeta-estadistica-cambio"
                        id="detalleIndicadorReporte2"
                    >
                        Cantidad de resultados
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-naranja">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-arrow-trend-up"></i>
                        </div>

                        <div>

                            <div
                                class="tarjeta-estadistica-titulo"
                                id="tituloIndicadorReporte3"
                            >
                                Mayor valor
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="valorIndicadorReporte3"
                            >
                                ₡0
                            </div>

                        </div>

                    </div>

                    <div
                        class="tarjeta-estadistica-cambio"
                        id="detalleIndicadorReporte3"
                    >
                        Registro más alto
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-morado">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-calculator"></i>
                        </div>

                        <div>

                            <div
                                class="tarjeta-estadistica-titulo"
                                id="tituloIndicadorReporte4"
                            >
                                Promedio
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="valorIndicadorReporte4"
                            >
                                ₡0
                            </div>

                        </div>

                    </div>

                    <div
                        class="tarjeta-estadistica-cambio"
                        id="detalleIndicadorReporte4"
                    >
                        Promedio del periodo
                    </div>

                </article>

            </section>

            <!-- ==========================================
                 RESULTADOS
            =========================================== -->

            <section class="panel">

                <div class="panel-encabezado">

                    <div>

                        <h2 id="tituloResultadosReporte">
                            Resultados del reporte
                        </h2>

                        <p
                            class="descripcion-panel"
                            id="descripcionResultadosReporte"
                        >
                            Genera un reporte para consultar la información.
                        </p>

                    </div>

                </div>

                <!-- Resumen del periodo -->

                <div
                    id="resumenPeriodoReporte"
                    class="resumen-periodo-reporte"
                    hidden
                >

                    <div>

                        <span>Tipo</span>

                        <strong id="resumenTipoReporte">
                            Ventas
                        </strong>

                    </div>

                    <div>

                        <span>Tienda</span>

                        <strong id="resumenTiendaReporte">
                            Todas las tiendas
                        </strong>

                    </div>

                    <div>

                        <span>Periodo</span>

                        <strong id="resumenFechasReporte">
                            Sin seleccionar
                        </strong>

                    </div>

                    <div>

                        <span>Generado</span>

                        <strong id="fechaGeneracionReporte">
                            -
                        </strong>

                    </div>

                </div>

                <!-- Tabla -->

                <div
                    id="contenedorTablaReporte"
                    class="contenedor-tabla"
                    hidden
                >

                    <table class="tabla-datos tabla-reportes">

                        <caption class="texto-solo-lectores">
                            Resultados generados por el reporte
                        </caption>

                        <thead id="encabezadoTablaReporte">
                            <!-- JavaScript genera las columnas -->
                        </thead>

                        <tbody id="cuerpoTablaReporte">
                            <!-- JavaScript genera los resultados -->
                        </tbody>

                    </table>

                </div>

                <!-- Estado inicial -->

                <div
                    id="mensajeReporteInicial"
                    class="estado-vacio-productos"
                >

                    <i class="fa-solid fa-chart-column"></i>

                    <h3>
                        Configura el reporte
                    </h3>

                    <p>
                        Selecciona el tipo, la tienda y el periodo
                        para mostrar la información.
                    </p>

                </div>

                <!-- Sin datos -->

                <div
                    id="mensajeSinDatosReporte"
                    class="estado-vacio-productos"
                    hidden
                >

                    <i class="fa-solid fa-folder-open"></i>

                    <h3>
                        No hay datos para mostrar
                    </h3>

                    <p>
                        No se encontraron registros dentro
                        del periodo seleccionado.
                    </p>

                </div>

            </section>

        </main>

    </div>

    <script src="../js/app.js"></script>

</body>
</html>