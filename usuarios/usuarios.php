<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/permisos.php";
require_once __DIR__ . "/../config/layout.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

if (!esAdministradorOGerente()) {
    http_response_code(403);
    echo "Acceso no autorizado.";
    exit;
}

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
        content="Administración de usuarios del sistema Vending Mini Market"
    >

    <title>Usuarios | Vending Mini Market</title>

    <?php vmEstilosGlobales(); ?>

</head>

<body class="pagina-app">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->

    
<?php
?>

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

                    <h1>Usuarios</h1>

                    <p>
                        Administra las cuentas, roles y accesos
                        de los usuarios del sistema.
                    </p>

                </div>

                <button
                    type="button"
                    id="botonNuevoUsuario"
                    class="boton boton-azul"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Nuevo usuario
                </button>

            </div>

            <!-- Mensaje general -->

            <div
                id="mensajeUsuarios"
                class="mensaje-producto"
                aria-live="polite"
                hidden
            ></div>

            <!-- ==========================================
                 RESUMEN
            =========================================== -->

            <section
                class="grilla-estadisticas"
                aria-label="Resumen de usuarios"
            >

                <article class="tarjeta-estadistica">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Total de usuarios
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalUsuarios"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Cuentas registradas
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-verde">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-user-check"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Usuarios activos
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="usuariosActivos"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Cuentas con acceso
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-morado">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Administradores
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalAdministradores"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Acceso administrativo
                    </div>

                </article>

                <article class="tarjeta-estadistica borde-naranja">

                    <div class="tarjeta-estadistica-encabezado">

                        <div class="tarjeta-estadistica-icono">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>

                        <div>

                            <div class="tarjeta-estadistica-titulo">
                                Trabajadores
                            </div>

                            <div
                                class="tarjeta-estadistica-valor"
                                id="totalTrabajadores"
                            >
                                0
                            </div>

                        </div>

                    </div>

                    <div class="tarjeta-estadistica-cambio">
                        Personal operativo
                    </div>

                </article>

            </section>

            <!-- ==========================================
                 LISTA DE USUARIOS
            =========================================== -->

            <section class="panel">

                <div class="panel-encabezado">

                    <div>

                        <h2>Usuarios registrados</h2>

                        <p class="descripcion-panel">
                            Consulta las cuentas y modifica su estado.
                        </p>

                    </div>

                </div>

                <!-- Filtros -->

                <div class="barra-filtros">

                    <div class="campo-busqueda-filtro">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <label
                            for="buscarUsuario"
                            class="texto-solo-lectores"
                        >
                            Buscar usuario
                        </label>

                        <input
                            type="search"
                            id="buscarUsuario"
                            placeholder="Buscar por nombre, correo o usuario..."
                            autocomplete="off"
                        >

                    </div>

                    <div class="selector-filtro">

                        <label for="filtroRolUsuario">
                            Rol
                        </label>

                        <select id="filtroRolUsuario">

                            <option value="">
                                Todos los roles
                            </option>

                            <option value="Administrador">
                                Administrador
                            </option>

                            <option value="Trabajador">
                                Trabajador
                            </option>

                        </select>

                    </div>

                    <div class="selector-filtro">

                        <label for="filtroTiendaUsuario">
                            Tienda
                        </label>

                        <select id="filtroTiendaUsuario">

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

                    <div class="selector-filtro">

                        <label for="filtroEstadoUsuario">
                            Estado
                        </label>

                        <select id="filtroEstadoUsuario">

                            <option value="">
                                Todos los estados
                            </option>

                            <option value="Activo">
                                Activo
                            </option>

                            <option value="Inactivo">
                                Inactivo
                            </option>

                        </select>

                    </div>

                    <button
                        type="button"
                        id="botonLimpiarFiltrosUsuarios"
                        class="boton boton-borde"
                    >
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <p
                    id="resultadoBusquedaUsuarios"
                    class="resultado-busqueda-productos"
                    aria-live="polite"
                ></p>

                <!-- Tabla -->

                <div
                    id="contenedorTablaUsuarios"
                    class="contenedor-tabla"
                    hidden
                >

                    <table class="tabla-datos tabla-usuarios">

                        <caption class="texto-solo-lectores">
                            Usuarios registrados en el sistema
                        </caption>

                        <thead>

                            <tr>
                                <th scope="col">Usuario</th>
                                <th scope="col">Correo</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Tienda</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Fecha de registro</th>
                                <th scope="col">Acciones</th>
                            </tr>

                        </thead>

                        <tbody id="cuerpoTablaUsuarios">
                            <!-- JavaScript agregará los usuarios -->
                        </tbody>

                    </table>

                </div>

                <!-- Estado vacío -->

                <div
                    id="mensajeSinUsuarios"
                    class="estado-vacio-productos"
                    hidden
                >

                    <i class="fa-solid fa-users-slash"></i>

                    <h3>
                        No hay usuarios registrados
                    </h3>

                    <p>
                        Registra una cuenta para que aparezca
                        en esta sección.
                    </p>

                    <button
                        type="button"
                        id="botonPrimerUsuario"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-user-plus"></i>
                        Registrar usuario
                    </button>

                </div>

                <!-- Sin resultados -->

                <div
                    id="mensajeSinResultadosUsuarios"
                    class="estado-vacio-productos"
                    hidden
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <h3>
                        No se encontraron usuarios
                    </h3>

                    <p>
                        Cambia los filtros o el texto de búsqueda.
                    </p>

                </div>

            </section>

        </main>

    </div>

    <!-- ==========================================
         MODAL: NUEVO USUARIO
    =========================================== -->

    <div
        id="modalUsuario"
        class="modal-producto"
        hidden
    >

        <div
            class="modal-producto-fondo"
            data-cerrar-modal-usuario
        ></div>

        <section
            class="modal-producto-contenido modal-venta-contenido"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalUsuario"
        >

            <div class="encabezado-modal-venta">

                <div>

                    <h2 id="tituloModalUsuario">
                        Nuevo usuario
                    </h2>

                    <p>
                        Complete los datos para crear una cuenta.
                    </p>

                </div>

                <button
                    type="button"
                    id="botonCerrarModalUsuario"
                    class="boton-cerrar-modal"
                    aria-label="Cerrar formulario"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>

            <form id="formularioUsuario">

                <input
                    type="hidden"
                    id="idUsuario"
                >

                <div class="cuadricula-formulario">

                    <!-- Nombre -->

                    <div class="campo-formulario">
                        <label for="nombreUsuario">Nombre</label>
                        <input
                            type="text"
                            id="nombreUsuario"
                            name="nombreUsuario"
                            placeholder="Ingrese el nombre"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="campo-formulario">
                        <label for="apellidoUsuario">Apellido</label>
                        <input
                            type="text"
                            id="apellidoUsuario"
                            name="apellidoUsuario"
                            placeholder="Ingrese el apellido"
                            maxlength="100"
                            required
                        >
                    </div>

                 

                    <!-- Correo -->

                    <div class="grupo-formulario">

                        <label for="correoUsuario">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correoUsuario"
                            name="correoUsuario"
                            placeholder="usuario@vending.com"
                            maxlength="100"
                            autocomplete="off"
                            required
                        >

                    </div>

                    <!-- Rol -->

                    <div class="grupo-formulario">

                        <label for="rolUsuario">
                            Rol
                        </label>

                        <select
                            id="rolUsuario"
                            name="rolUsuario"
                            required
                        >

                            <option value="">Seleccione un rol</option>
                            <option value="1">Administrador</option>
                            <option value="2">Gerente General</option>
                            <option value="3">Encargado</option>
                            <option value="4">Dependiente</option>

                        </select>

                    </div>

                    <!-- Tienda -->

                    <div class="grupo-formulario">

                        <label for="tiendaUsuario">
                            Tienda asignada
                        </label>

                        <select
                            id="tiendaUsuario"
                            name="tiendaUsuario"
                            required
                        >

                            <option value="">
                                Seleccione una tienda
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

                    <!-- Estado -->

                    <div class="grupo-formulario">

                        <label for="estadoUsuario">
                            Estado
                        </label>

                        <select
                            id="estadoUsuario"
                            name="estadoUsuario"
                            required
                        >

                            <option value="Activo">
                                Activo
                            </option>

                            <option value="Inactivo">
                                Inactivo
                            </option>

                        </select>

                    </div>

                    <!-- Contraseña -->

                    <div class="grupo-formulario">

                        <label id="etiquetaContrasenaUsuario" for="contrasenaUsuario">
                            Contraseña
                        </label>

                        <input
                            type="password"
                            id="contrasenaUsuario"
                            name="contrasenaUsuario"
                            placeholder="Mínimo 8 caracteres"
                            minlength="8"
                            maxlength="100"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                    <!-- Confirmar contraseña -->

                    <div class="grupo-formulario">

                        <label for="confirmarContrasenaUsuario">
                            Confirmar contraseña
                        </label>

                        <input
                            type="password"
                            id="confirmarContrasenaUsuario"
                            name="confirmarContrasenaUsuario"
                            placeholder="Repita la contraseña"
                            minlength="8"
                            maxlength="100"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>

                <p
                    id="mensajeFormularioUsuario"
                    class="mensaje-login"
                    aria-live="polite"
                ></p>

                <div class="acciones-modal-producto">

                    <button
                        type="button"
                        id="botonCancelarUsuario"
                        class="boton boton-borde"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="boton boton-azul"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar usuario
                    </button>

                </div>

            </form>

        </section>

    </div>

    <!-- ==========================================
         MODAL: CAMBIAR ESTADO
    =========================================== -->

    <div
        id="modalEstadoUsuario"
        class="modal-producto"
        hidden
    >

        <div
            class="modal-producto-fondo"
            data-cerrar-modal-estado-usuario
        ></div>

        <section
            class="modal-producto-contenido"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloModalEstadoUsuario"
        >

            <div class="modal-producto-icono">
                <i class="fa-solid fa-user-lock"></i>
            </div>

            <h2 id="tituloModalEstadoUsuario">
                Cambiar estado
            </h2>

            <p id="textoModalEstadoUsuario">
                ¿Desea cambiar el estado de este usuario?
            </p>

            <div class="acciones-modal-producto">

                <button
                    type="button"
                    id="botonCancelarEstadoUsuario"
                    class="boton boton-borde"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    id="botonConfirmarEstadoUsuario"
                    class="boton boton-azul"
                >
                    Confirmar
                </button>

            </div>

        </section>

    </div>

    <script src="../js/paginador.js"></script>
    <script src="../js/app.js"></script>

</body>
</html>