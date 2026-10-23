<?php

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/permisos.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index/index.html");
    exit;
}

if (!esAdministradorOGerente()) {
    http_response_code(403);
    exit("Acceso no autorizado.");
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$nombreUsuario = trim(
    ($_SESSION["usuario"]["nombre"] ?? "") . " " .
    ($_SESSION["usuario"]["apellido"] ?? "")
);
$nombreUsuario = $nombreUsuario !== "" ? $nombreUsuario : "Usuario";
$rolUsuario = $_SESSION["usuario"]["nombre_rol"] ?? "Usuario";
$paginaActual = basename($_SERVER["PHP_SELF"]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestión de respaldos de Vending Mini Market">
    <title>Respaldos | Vending Mini Market</title>
    <link rel="stylesheet" href="../css/estilos.css?v=<?= filemtime(__DIR__ . '/../css/estilos.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .respaldo-encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }
        .respaldo-encabezado h1 { margin: 0 0 8px; }
        .respaldo-encabezado p { margin: 0; color: #64748b; }
        .respaldo-tarjeta {
            padding: 24px;
            margin-bottom: 24px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }
        .respaldo-tarjeta h2 { margin: 0 0 20px; font-size: 18px; }
        .respaldo-resumen {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }
        .respaldo-dato {
            min-width: 0;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }
        .respaldo-dato h3 { margin: 0 0 12px; color: #64748b; font-size: 14px; }
        .respaldo-dato p { margin: 0; color: #1e293b; font-size: 16px; font-weight: 600; overflow-wrap: anywhere; }
        .respaldo-tabla { width: 100%; overflow-x: auto; }
        .respaldo-tabla table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        .respaldo-tabla th, .respaldo-tabla td { padding: 13px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .respaldo-tabla th { background: #f8fafc; color: #334155; font-weight: 600; }
        .respaldo-tabla tbody tr:hover { background: #f8fafc; }
        .respaldo-mensaje { margin: 0 0 16px; color: #b91c1c; }
        #btnRegistrarRespaldo:disabled { opacity: .7; cursor: wait; }
        .estado-badge { display: inline-block; padding: 4px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .estado-exito { background: #dcfce7; color: #166534; }
        .estado-error { background: #fee2e2; color: #991b1b; }
        .estado-proceso { background: #fef3c7; color: #92400e; }
        .estado-neutral { background: #e2e8f0; color: #334155; }
        @media (max-width: 900px) { .respaldo-resumen { grid-template-columns: 1fr; } }
        @media (max-width: 600px) {
            .respaldo-encabezado { align-items: flex-start; flex-direction: column; }
            .respaldo-tarjeta { padding: 16px; }
        }
    </style>
</head>
<body class="pagina-app">
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

        <a href="/VendingMiniMarket/inventario/inventario.html"
           class="<?= $paginaActual === 'inventario.html' ? 'activo' : '' ?>"
           <?= $paginaActual === 'inventario.html' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-clipboard-list"></i>
            Inventario
        </a>

        <a href="/VendingMiniMarket/compras/compras.html"
           class="<?= $paginaActual === 'compras.html' ? 'activo' : '' ?>"
           <?= $paginaActual === 'compras.html' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-cart-shopping"></i>
            Compras
        </a>

        <a href="/VendingMiniMarket/ventas/ventas.html"
           class="<?= $paginaActual === 'ventas.html' ? 'activo' : '' ?>"
           <?= $paginaActual === 'ventas.html' ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-chart-column"></i>
            Ventas
        </a>

        <a href="/VendingMiniMarket/reportes/reportes.html"
           class="<?= $paginaActual === 'reportes.html' ? 'activo' : '' ?>"
           <?= $paginaActual === 'reportes.html' ? 'aria-current="page"' : '' ?>>
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

    <div class="columna-principal">
        <header class="barra-superior">
            <button type="button" class="boton-menu" aria-label="Abrir o cerrar el menú lateral">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="buscador-superior">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Buscar..." aria-label="Buscar">
            </div>
            <div class="acciones-superiores">
                <button type="button" class="boton-notificacion" aria-label="Notificaciones">
                    <i class="fa-regular fa-bell"></i><span class="contador">0</span>
                </button>
                <div class="perfil-superior">
                    <div class="perfil-superior-icono"><i class="fa-solid fa-user"></i></div>
                    <div class="perfil-superior-texto">
                        <strong><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, "UTF-8") ?></strong>
                        <span><?= htmlspecialchars($rolUsuario, ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                </div>
            </div>
        </header>

        <main class="contenido-app">
            <section class="respaldo-encabezado">
                <div>
                    <h1><i class="fa-solid fa-database"></i> Gestión de respaldos</h1>
                    <p>Consulta el historial y genera respaldos de la base de datos.</p>
                </div>
                <button type="button" id="btnRegistrarRespaldo" class="boton boton-azul">
                    <i class="fa-solid fa-database"></i> Generar respaldo
                </button>
            </section>

            <p id="mensajeRespaldo" class="respaldo-mensaje" role="status" aria-live="polite"></p>

            <section class="respaldo-tarjeta" aria-labelledby="tituloUltimoRespaldo">
                <h2 id="tituloUltimoRespaldo"><i class="fa-solid fa-clock-rotate-left"></i> Último respaldo</h2>
                <div class="respaldo-resumen">
                    <div class="respaldo-dato"><h3>Fecha y hora</h3><p id="fechaUltimoRespaldo">Cargando...</p></div>
                    <div class="respaldo-dato"><h3>Estado</h3><p id="estadoUltimoRespaldo">Cargando...</p></div>
                    <div class="respaldo-dato"><h3>Tipo</h3><p id="tipoUltimoRespaldo">Cargando...</p></div>
                </div>
            </section>

            <section class="respaldo-tarjeta" aria-labelledby="tituloHistorialRespaldos">
                <h2 id="tituloHistorialRespaldos"><i class="fa-solid fa-list"></i> Historial de respaldos</h2>
                <div class="respaldo-tabla">
                    <table>
                        <thead>
                            <tr><th>Fecha</th><th>Tipo</th><th>Estado</th><th>Cobertura</th><th>Usuario</th></tr>
                        </thead>
                        <tbody id="tablaRespaldos">
                            <tr><td colspan="5">Cargando respaldos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="../js/app.js"></script>
    <script>
        const fechaUltimoRespaldo = document.getElementById("fechaUltimoRespaldo");
        const estadoUltimoRespaldo = document.getElementById("estadoUltimoRespaldo");
        const tipoUltimoRespaldo = document.getElementById("tipoUltimoRespaldo");
        const tablaRespaldos = document.getElementById("tablaRespaldos");
        const mensajeRespaldo = document.getElementById("mensajeRespaldo");
        const botonRegistrarRespaldo = document.getElementById("btnRegistrarRespaldo");

        function escaparHTML(valor) {
            return String(valor ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatearFecha(fecha) {
            if (!fecha) return "-";
            const fechaObjeto = new Date(fecha);
            return Number.isNaN(fechaObjeto.getTime())
                ? String(fecha)
                : fechaObjeto.toLocaleString("es-CR");
        }

        function formatearEstado(estado) {
            const valor = String(estado || "-");
            const normalizado = valor.toUpperCase();
            let clase = "estado-neutral";
            if (normalizado.includes("EXITOS") || normalizado.includes("COMPLET")) {
                clase = "estado-exito";
            } else if (normalizado.includes("ERROR") || normalizado.includes("FALL")) {
                clase = "estado-error";
            } else if (normalizado.includes("PROCES")) {
                clase = "estado-proceso";
            }
            return `<span class="estado-badge ${clase}">${escaparHTML(valor)}</span>`;
        }

        async function cargarRespaldos() {
            try {
                const respuesta = await fetch("../api/respaldo/listar.php", {
                    headers: { "Accept": "application/json" }
                });
                const resultado = await respuesta.json();
                if (!respuesta.ok || !resultado.success) {
                    throw new Error(resultado.message || "No fue posible consultar los respaldos.");
                }

                const respaldos = Array.isArray(resultado.data) ? resultado.data : [];
                if (respaldos.length === 0) {
                    fechaUltimoRespaldo.textContent = "Sin registros";
                    estadoUltimoRespaldo.textContent = "Sin registros";
                    tipoUltimoRespaldo.textContent = "Sin registros";
                    tablaRespaldos.innerHTML = '<tr><td colspan="5">No hay respaldos registrados.</td></tr>';
                    return;
                }

                const ultimo = respaldos[0];
                fechaUltimoRespaldo.textContent = formatearFecha(ultimo.fecha_inicio);
                estadoUltimoRespaldo.innerHTML = formatearEstado(ultimo.estado);
                tipoUltimoRespaldo.textContent = ultimo.tipo || "-";
                tablaRespaldos.innerHTML = respaldos.map((respaldo) => `
                    <tr>
                        <td>${escaparHTML(formatearFecha(respaldo.fecha_inicio))}</td>
                        <td>${escaparHTML(respaldo.tipo || "-")}</td>
                        <td>${formatearEstado(respaldo.estado)}</td>
                        <td>${escaparHTML(respaldo.cobertura || "-")}</td>
                        <td>${escaparHTML(respaldo.usuario || "-")}</td>
                    </tr>
                `).join("");
            } catch (error) {
                console.error("Error consultando respaldos:", error);
                fechaUltimoRespaldo.textContent = "No disponible";
                estadoUltimoRespaldo.textContent = "No disponible";
                tipoUltimoRespaldo.textContent = "No disponible";
                tablaRespaldos.innerHTML = '<tr><td colspan="5">No fue posible cargar los respaldos.</td></tr>';
                mensajeRespaldo.textContent = error.message || "Error al consultar los respaldos.";
            }
        }

        botonRegistrarRespaldo.addEventListener("click", async () => {
            mensajeRespaldo.textContent = "";
            botonRegistrarRespaldo.disabled = true;
            botonRegistrarRespaldo.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';

            try {
                const respuesta = await fetch("../api/respaldo/registrar.php", {
                    method: "POST",
                    headers: { "Accept": "application/json" }
                });
                const resultado = await respuesta.json();
                if (!respuesta.ok || !resultado.success) {
                    throw new Error(resultado.message || "No fue posible generar el respaldo.");
                }

                mensajeRespaldo.textContent = `Respaldo generado: ${resultado.archivo} (${resultado.tamano} bytes).`;
                await cargarRespaldos();
            } catch (error) {
                console.error("Error generando respaldo:", error);
                mensajeRespaldo.textContent = error.message || "Ocurrió un error al generar el respaldo.";
            } finally {
                botonRegistrarRespaldo.disabled = false;
                botonRegistrarRespaldo.innerHTML = '<i class="fa-solid fa-database"></i> Generar respaldo';
            }
        });

        cargarRespaldos();
    </script>
</body>
</html>