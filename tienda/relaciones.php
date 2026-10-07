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
 * Igual que la página de Tienda: solo Administrador y Gerente General.
 * Únicamente el Administrador puede configurar relaciones.
 */
if (!esAdministradorOGerente()) {
    header("Location: ../dashboard/dashboard.php");
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

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Relaciones de abastecimiento entre ubicaciones de Vending Mini Market">

    <title>Relaciones de abastecimiento | Vending Mini Market</title>

    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="../css/catalogo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="pagina-app">

    <!-- ==========================================
         BARRA LATERAL
    =========================================== -->
    <aside class="barra-lateral">

        <div class="logo-sistema">
            <a href="../dashboard/dashboard.php" aria-label="Ir al Dashboard">
                <img src="../img/logo/logo-vending.jpeg"
                     alt="Logo de Vending Mini Market"
                     class="imagen-logo-sistema">
            </a>
        </div>

        <nav class="nav-lateral" aria-label="Navegación principal">

            <a href="../dashboard/dashboard.php">
                <i class="fa-solid fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="../productos/productos.php">
                <i class="fa-solid fa-box"></i>
                Productos
            </a>

            <a href="tienda.php" class="activo" aria-current="page">
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

            <?php if (esAdministradorOGerente()): ?>
            <a href="../bitacora/bitacora.php">
                <i class="fa-solid fa-clipboard-list"></i>
                Bitácora
            </a>
            <?php endif; ?>

        </nav>

        <div class="pie-barra-lateral">
            <a href="../index/index.html" id="botonCerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>
        </div>

    </aside>

    <!-- ==========================================
         COLUMNA PRINCIPAL
    =========================================== -->
    <div class="columna-principal">

        <header class="barra-superior">

            <button type="button" class="boton-menu" aria-label="Abrir o cerrar menú lateral">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="buscador-superior">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search"
                       id="buscadorGeneralRelaciones"
                       placeholder="Buscar en el sistema..."
                       aria-label="Buscar en el sistema"
                       autocomplete="off">
            </div>

            <div class="acciones-superiores">

                <button type="button" class="boton-notificacion" aria-label="Consultar notificaciones">
                    <i class="fa-regular fa-bell"></i>
                    <span class="contador" id="contadorNotificacionesRelaciones" aria-label="0 notificaciones">0</span>
                </button>

                <div class="perfil-superior">
                    <div class="perfil-superior-texto">
                        <strong id="nombreUsuarioSuperior"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?></strong>
                        <span id="correoUsuarioSuperior"><?= htmlspecialchars($correoUsuario, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>

            </div>

        </header>

        <main class="contenido-app">

            <!-- ENCABEZADO -->
            <div class="encabezado-pagina">

                <div>
                    <h1>Relaciones de abastecimiento</h1>
                    <p>Define qué ubicación abastece a otra ubicación o a una máquina. Sirve como guía para traslados y reposiciones.</p>
                </div>

                <a href="tienda.php" class="boton boton-borde">
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver a Tienda
                </a>

            </div>

            <!-- MENSAJES -->
            <div id="mensajeRelacion" class="mensaje-producto" aria-live="polite"></div>

            <!-- RESUMEN -->
            <section class="resumen-productos" aria-label="Resumen de relaciones de abastecimiento">

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-diagram-project"></i></div>
                    <div>
                        <span>Total de relaciones</span>
                        <strong id="totalRelaciones">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <span>Relaciones activas</span>
                        <strong id="relacionesActivas">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div>
                        <span>Relaciones inactivas</span>
                        <strong id="relacionesInactivas">0</strong>
                    </div>
                </article>

                <article class="tarjeta-estadistica">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid fa-cash-register"></i></div>
                    <div>
                        <span>Hacia máquinas</span>
                        <strong id="relacionesMaquinas">0</strong>
                    </div>
                </article>

            </section>

            <?php if (esAdministrador()): ?>
            <!-- FORMULARIO (solo administrador) -->
            <section class="panel" id="panelFormularioRelacion">

                <div class="panel-encabezado">

                    <div>
                        <h2 id="tituloFormularioRelacion">Nueva relación</h2>
                        <p class="descripcion-panel">
                            Relaciones permitidas: Bodega → Tienda, Bodega → Máquina,
                            Tienda → Máquina y Tienda → Tienda.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>

                </div>

                <form id="formularioRelacion" novalidate>

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="origenRelacion">Ubicación que abastece (origen) *</label>
                            <select id="origenRelacion" required>
                                <option value="">Seleccione la ubicación de origen</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="tipoDestinoRelacion">Tipo de destino *</label>
                            <select id="tipoDestinoRelacion" required>
                                <option value="">Seleccione el tipo de destino</option>
                                <option value="UBICACION">Ubicación (tienda)</option>
                                <option value="MAQUINA">Máquina</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="destinoRelacion">Destino *</label>
                            <select id="destinoRelacion" required disabled>
                                <option value="">Seleccione primero el tipo de destino</option>
                            </select>
                            <small class="texto-ayuda">
                                Se habilita al elegir el tipo de destino y muestra las ubicaciones o las máquinas según ese tipo.
                            </small>
                        </div>

                        <div class="grupo-formulario">
                            <label>Tipo de relación</label>
                            <p id="vistaPreviaRelacion" class="texto-ayuda">—</p>
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="observacionesRelacion">Observaciones</label>
                            <textarea id="observacionesRelacion" rows="3" maxlength="500"
                                      placeholder="Observaciones de la relación..."></textarea>
                        </div>

                    </div>

                    <div class="acciones-formulario">

                        <button type="button" id="botonCancelarRelacion" class="boton boton-borde">
                            Limpiar
                        </button>

                        <button type="submit" id="botonGuardarRelacion" class="boton boton-azul">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar relación
                        </button>

                    </div>

                </form>

            </section>
            <?php endif; ?>

            <!-- VERIFICAR TRASLADO -->
            <section class="panel" id="panelVerificarTraslado">

                <div class="panel-encabezado">

                    <div>
                        <h2>Verificar un traslado</h2>
                        <p class="descripcion-panel">
                            Comprueba si un traslado o una reposición tiene una relación de abastecimiento configurada.
                        </p>
                    </div>

                    <div class="tarjeta-estadistica-icono">
                        <i class="fa-solid fa-truck"></i>
                    </div>

                </div>

                <form id="formularioVerificarTraslado" novalidate>

                    <div class="cuadricula-formulario">

                        <div class="grupo-formulario">
                            <label for="origenTraslado">Origen del traslado *</label>
                            <select id="origenTraslado" required>
                                <option value="">Seleccione el origen</option>
                            </select>
                        </div>

                        <div class="grupo-formulario">
                            <label for="tipoDestinoTraslado">Tipo de destino *</label>
                            <select id="tipoDestinoTraslado" required>
                                <option value="">Seleccione el tipo de destino</option>
                                <option value="UBICACION">Ubicación</option>
                                <option value="MAQUINA">Máquina</option>
                            </select>
                        </div>

                        <div class="grupo-formulario campo-completo">
                            <label for="destinoTraslado">Destino *</label>
                            <select id="destinoTraslado" required disabled>
                                <option value="">Seleccione primero el tipo de destino</option>
                            </select>
                        </div>

                    </div>

                    <div class="acciones-formulario">
                        <button type="submit" id="botonVerificarTraslado" class="boton boton-azul">
                            <i class="fa-solid fa-circle-check"></i>
                            Verificar traslado
                        </button>
                    </div>

                </form>

                <div id="resultadoTraslado" class="mensaje-producto" aria-live="polite"></div>

            </section>

            <!-- LISTADO -->
            <section class="panel">

                <div class="panel-encabezado">
                    <div>
                        <h2>Relaciones configuradas</h2>
                        <p class="descripcion-panel">
                            Busca por ubicación, máquina u observaciones.
                        </p>
                    </div>
                </div>

                <!-- FILTROS -->
                <div class="barra-filtros productos-filtros">

                    <div class="campo-busqueda-productos">
                        <i class="fa-solid fa-magnifying-glass"></i>

                        <label for="buscarRelacion" class="texto-solo-lectores">Buscar relaciones</label>

                        <input type="search"
                               id="buscarRelacion"
                               placeholder="Buscar por ubicación, máquina u observaciones..."
                               autocomplete="off">
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroOrigenRelacion">Origen</label>
                        <select id="filtroOrigenRelacion">
                            <option value="">Todos los orígenes</option>
                        </select>
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroTipoDestinoRelacion">Destino</label>
                        <select id="filtroTipoDestinoRelacion">
                            <option value="">Ubicaciones y máquinas</option>
                            <option value="UBICACION">Solo ubicaciones</option>
                            <option value="MAQUINA">Solo máquinas</option>
                        </select>
                    </div>

                    <div class="campo-filtro-producto">
                        <label for="filtroEstadoRelacion">Estado</label>
                        <select id="filtroEstadoRelacion">
                            <option value="">Todos los estados</option>
                            <option value="activas">Activas</option>
                            <option value="inactivas">Inactivas</option>
                        </select>
                    </div>

                    <button type="button" id="botonLimpiarFiltrosRelaciones" class="boton boton-borde">
                        <i class="fa-solid fa-filter-circle-xmark"></i>
                        Limpiar filtros
                    </button>

                </div>

                <p id="resultadoBusquedaRelaciones"
                   class="resultado-busqueda-productos"
                   aria-live="polite"></p>

                <!-- TABLA -->
                <div id="contenedorTablaRelaciones" class="contenedor-tabla"
                     data-es-admin="<?= esAdministrador() ? '1' : '0' ?>">

                    <table class="tabla-datos">

                        <caption class="texto-solo-lectores">Relaciones de abastecimiento configuradas</caption>

                        <thead>
                            <tr>
                                <th scope="col">Origen</th>
                                <th scope="col">Destino</th>
                                <th scope="col">Tipo de relación</th>
                                <th scope="col">Observaciones</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody id="cuerpoTablaRelaciones"></tbody>

                    </table>

                </div>

                <!-- SIN RELACIONES -->
                <div id="mensajeSinRelaciones" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-diagram-project"></i>
                    <h3>No hay relaciones configuradas</h3>
                    <p>Cuando se configure la primera relación de abastecimiento aparecerá en este listado.</p>
                </div>

                <!-- SIN RESULTADOS -->
                <div id="mensajeSinResultadosRelaciones" class="estado-vacio-productos" hidden>
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3>No se encontraron coincidencias</h3>
                    <p>Cambia el texto de búsqueda o limpia los filtros seleccionados.</p>
                </div>

            </section>

        </main>

    </div>

    <script src="../js/app.js"></script>
    <script src="../js/catalogo-comun.js"></script>
    <script src="../js/maquinas-api.js?v=<?= filemtime(__DIR__ . '/../js/maquinas-api.js') ?>"></script>
    <script src="../js/abastecimiento.js?v=<?= filemtime(__DIR__ . '/../js/abastecimiento.js') ?>"></script>
</body>
</html>
