<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

if (!esAdministrador()) {
    http_response_code(403);
    die("Acceso no autorizado.");
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$usuario = $_SESSION["usuario"];

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
        content="Gestión de incidencias técnicas del sistema Vending Mini Market"
    >

    <title>Incidencias | Vending Mini Market</title>

    <link
        rel="stylesheet"
        href="../css/estilos.css"
    >

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

        <a href="../inventario/inventario.html">
            <i class="fa-solid fa-clipboard-list"></i>
            Inventario
        </a>

        <a href="../compras/compras.html">
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

        <a
            href="incidencias.php"
            class="activo"
            aria-current="page"
        >
            <i class="fa-solid fa-triangle-exclamation"></i>
            Incidencias
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
                id="buscadorGeneralIncidencias"
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
                    aria-label="0 notificaciones"
                >
                    0
                </span>

            </button>


            <div class="perfil-superior">

                <div class="perfil-superior-texto">

                    <strong>
                        <?= htmlspecialchars(
                            $usuario["nombre"] . " " .
                            ($usuario["apellido"] ?? "")
                        ) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars(
                            $usuario["correo"]
                        ) ?>
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


        <!-- ENCABEZADO -->

        <div class="encabezado-pagina">

            <div>

                <h1>Incidencias</h1>

                <p>
                    Registra y da seguimiento a las incidencias
                    técnicas del sistema.
                </p>

            </div>

            <button
                type="button"
                id="botonNuevaIncidencia"
                class="boton boton-azul"
            >

                <i class="fa-solid fa-plus"></i>

                Nueva incidencia

            </button>

        </div>


        <!-- MENSAJE -->

        <div
            id="mensajeIncidencias"
            class="mensaje-producto"
            aria-live="polite"
            hidden
        ></div>


        <!-- ==========================================
             ESTADÍSTICAS
        =========================================== -->

        <section
            class="grilla-estadisticas"
            aria-label="Resumen de incidencias"
        >

            <article class="tarjeta-estadistica">

                <div class="tarjeta-estadistica-encabezado">

                    <div class="tarjeta-estadistica-icono">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <div>

                        <div class="tarjeta-estadistica-titulo">
                            Total de incidencias
                        </div>

                        <div
                            class="tarjeta-estadistica-valor"
                            id="totalIncidencias"
                        >
                            0
                        </div>

                    </div>

                </div>

                <div class="tarjeta-estadistica-cambio">
                    Incidencias registradas
                </div>

            </article>


            <article class="tarjeta-estadistica borde-naranja">

                <div class="tarjeta-estadistica-encabezado">

                    <div class="tarjeta-estadistica-icono">

                        <i class="fa-solid fa-folder-open"></i>

                    </div>

                    <div>

                        <div class="tarjeta-estadistica-titulo">
                            Abiertas
                        </div>

                        <div
                            class="tarjeta-estadistica-valor"
                            id="incidenciasAbiertas"
                        >
                            0
                        </div>

                    </div>

                </div>

                <div class="tarjeta-estadistica-cambio">
                    Pendientes de atención
                </div>

            </article>


            <article class="tarjeta-estadistica borde-morado">

                <div class="tarjeta-estadistica-encabezado">

                    <div class="tarjeta-estadistica-icono">

                        <i class="fa-solid fa-spinner"></i>

                    </div>

                    <div>

                        <div class="tarjeta-estadistica-titulo">
                            En proceso
                        </div>

                        <div
                            class="tarjeta-estadistica-valor"
                            id="incidenciasEnProceso"
                        >
                            0
                        </div>

                    </div>

                </div>

                <div class="tarjeta-estadistica-cambio">
                    En seguimiento
                </div>

            </article>


            <article class="tarjeta-estadistica borde-rojo">

                <div class="tarjeta-estadistica-encabezado">

                    <div class="tarjeta-estadistica-icono">

                        <i class="fa-solid fa-circle-exclamation"></i>

                    </div>

                    <div>

                        <div class="tarjeta-estadistica-titulo">
                            Críticas
                        </div>

                        <div
                            class="tarjeta-estadistica-valor"
                            id="incidenciasCriticas"
                        >
                            0
                        </div>

                    </div>

                </div>

                <div class="tarjeta-estadistica-cambio">
                    Prioridad crítica
                </div>

            </article>

        </section>


        <!-- ==========================================
             LISTA DE INCIDENCIAS
        =========================================== -->

        <section class="panel">

            <div class="panel-encabezado">

                <div>

                    <h2>Incidencias registradas</h2>

                    <p class="descripcion-panel">
                        Consulta el estado y seguimiento de las incidencias.
                    </p>

                </div>

            </div>


            <!-- FILTROS -->

            <div class="barra-filtros">

                <div class="campo-busqueda-filtro">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <label
                        for="buscarIncidencia"
                        class="texto-solo-lectores"
                    >
                        Buscar incidencia
                    </label>

                    <input
                        type="search"
                        id="buscarIncidencia"
                        placeholder="Buscar por descripción o módulo..."
                        autocomplete="off"
                    >

                </div>


                <div class="selector-filtro">

                    <label for="filtroEstado">
                        Estado
                    </label>

                    <select id="filtroEstado">

                        <option value="">
                            Todos los estados
                        </option>

                        <option value="ABIERTA">
                            Abierta
                        </option>

                        <option value="EN_PROCESO">
                            En proceso
                        </option>

                        <option value="RESUELTA">
                            Resuelta
                        </option>

                        <option value="CERRADA">
                            Cerrada
                        </option>

                    </select>

                </div>


                <div class="selector-filtro">

                    <label for="filtroPrioridad">
                        Prioridad
                    </label>

                    <select id="filtroPrioridad">

                        <option value="">
                            Todas las prioridades
                        </option>

                        <option value="BAJA">
                            Baja
                        </option>

                        <option value="MEDIA">
                            Media
                        </option>

                        <option value="ALTA">
                            Alta
                        </option>

                        <option value="CRITICA">
                            Crítica
                        </option>

                    </select>

                </div>


                <button
                    type="button"
                    id="botonLimpiarFiltros"
                    class="boton boton-borde"
                >

                    <i class="fa-solid fa-filter-circle-xmark"></i>

                    Limpiar filtros

                </button>

            </div>


            <p
                id="resultadoBusquedaIncidencias"
                class="resultado-busqueda-productos"
                aria-live="polite"
            ></p>


            <!-- TABLA -->

            <div
                id="contenedorTablaIncidencias"
                class="contenedor-tabla"
            >

                <table class="tabla-datos tabla-incidencias">

                    <caption class="texto-solo-lectores">
                        Incidencias registradas en el sistema
                    </caption>

                    <thead>

                        <tr>

                            <th scope="col">
                                ID
                            </th>

                            <th scope="col">
                                Descripción
                            </th>

                            <th scope="col">
                                Módulo
                            </th>

                            <th scope="col">
                                Prioridad
                            </th>

                            <th scope="col">
                                Estado
                            </th>

                            <th scope="col">
                                Registrado por
                            </th>

                            <th scope="col">
                                Fecha
                            </th>

                            <th scope="col">
                                Acciones
                            </th>

                        </tr>

                    </thead>

                    <tbody id="tablaIncidencias">

                        <tr>

                            <td colspan="8">
                                Cargando incidencias...
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- ESTADO VACÍO -->

            <div
                id="mensajeSinIncidencias"
                class="estado-vacio-productos"
                hidden
            >

                <i class="fa-solid fa-triangle-exclamation"></i>

                <h3>
                    No hay incidencias registradas
                </h3>

                <p>
                    Registra una incidencia para que aparezca
                    en esta sección.
                </p>

            </div>

        </section>

    </main>

</div>


<!-- ==========================================
     MODAL: NUEVA INCIDENCIA
=========================================== -->

<div
    id="modalIncidencia"
    class="modal-producto"
    hidden
>

    <div
        class="modal-producto-fondo"
        data-cerrar-modal-incidencia
    ></div>


    <section
        class="modal-producto-contenido modal-venta-contenido"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tituloModalIncidencia"
    >

        <div class="encabezado-modal-venta">

            <div>

                <h2 id="tituloModalIncidencia">
                    Registrar incidencia
                </h2>

                <p>
                    Complete la información de la incidencia técnica.
                </p>

            </div>


            <button
                type="button"
                id="botonCerrarModalIncidencia"
                class="boton-cerrar-modal"
                aria-label="Cerrar formulario"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form id="formularioIncidencia">

            <div class="cuadricula-formulario">

                <div class="grupo-formulario">

                    <label for="descripcion">
                        Descripción de la incidencia
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="5"
                        maxlength="1000"
                        placeholder="Describa el problema encontrado..."
                        required
                    ></textarea>

                </div>


                <div class="grupo-formulario">

                    <label for="modulo_afectado">
                        Módulo afectado
                    </label>

                    <input
                        type="text"
                        id="modulo_afectado"
                        name="modulo_afectado"
                        maxlength="100"
                        placeholder="Ej. Respaldos"
                        required
                    >

                </div>


                <div class="grupo-formulario">

                    <label for="prioridad">
                        Prioridad
                    </label>

                    <select
                        id="prioridad"
                        name="prioridad"
                        required
                    >

                        <option value="">
                            Seleccione una prioridad
                        </option>

                        <option value="BAJA">
                            Baja
                        </option>

                        <option value="MEDIA">
                            Media
                        </option>

                        <option value="ALTA">
                            Alta
                        </option>

                        <option value="CRITICA">
                            Crítica
                        </option>

                    </select>

                </div>

            </div>


            <p
                id="mensajeFormulario"
                class="mensaje-login"
                aria-live="polite"
            ></p>


            <div class="acciones-modal-producto">

                <button
                    type="button"
                    id="botonCancelarIncidencia"
                    class="boton boton-borde"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    id="btnRegistrar"
                    class="boton boton-azul"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Registrar incidencia

                </button>

            </div>

        </form>

    </section>

</div>


<!-- ==========================================
     MODAL: SEGUIMIENTO
=========================================== -->

<div
    id="modalSeguimiento"
    class="modal-producto"
    hidden
>

    <div
        class="modal-producto-fondo"
        data-cerrar-modal-seguimiento
    ></div>


    <section
        class="modal-producto-contenido"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tituloModalSeguimiento"
    >

        <div class="encabezado-modal-venta">

            <div>

                <h2 id="tituloModalSeguimiento">
                    Actualizar incidencia
                </h2>

                <p>
                    Registre el estado y las acciones realizadas.
                </p>

            </div>


            <button
                type="button"
                id="btnCerrarModal"
                class="boton-cerrar-modal"
                aria-label="Cerrar formulario"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form id="formularioSeguimiento">

            <input
                type="hidden"
                id="id_incidencia"
            >


            <div class="grupo-formulario">

                <label for="nuevo_estado">
                    Estado
                </label>

                <select
                    id="nuevo_estado"
                    required
                >

                    <option value="ABIERTA">
                        Abierta
                    </option>

                    <option value="EN_PROCESO">
                        En proceso
                    </option>

                    <option value="RESUELTA">
                        Resuelta
                    </option>

                    <option value="CERRADA">
                        Cerrada
                    </option>

                </select>

            </div>


            <div class="grupo-formulario">

                <label for="seguimiento">
                    Seguimiento
                </label>

                <textarea
                    id="seguimiento"
                    rows="5"
                    maxlength="2000"
                    placeholder="Describa las acciones realizadas..."
                    required
                ></textarea>

            </div>


            <p
                id="mensajeSeguimiento"
                class="mensaje-login"
                aria-live="polite"
            ></p>


            <div class="acciones-modal-producto">

                <button
                    type="button"
                    id="btnCancelar"
                    class="boton boton-borde"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    id="btnGuardarSeguimiento"
                    class="boton boton-azul"
                >

                    <i class="fa-solid fa-floppy-disk"></i>

                    Guardar cambios

                </button>

            </div>

        </form>

    </section>

</div>


<script src="../js/app.js"></script>

<script src="incidencias.js"></script>

</body>

</html>