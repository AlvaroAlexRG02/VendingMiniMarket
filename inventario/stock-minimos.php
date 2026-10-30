<?php

/** VendingMiniMarket — HU-27: stock mínimo y alertas preventivas. */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/permisos.php';
if (empty($_SESSION['usuario'])) {
    header('Location: ../index/index.html');
    exit;
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$usuario = $_SESSION['usuario'];
$nombre = trim((string)($usuario['nombre'] ?? '') . ' ' . (string)($usuario['apellido'] ?? ''));
$puedeConfigurar = in_array((int)($usuario['id_rol'] ?? 0), [1, 2, 3], true);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Stock mínimo | VendingMiniMarket</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .sm27 {
            max-width: 1250px;
            margin: auto;
            padding: 24px 18px 50px;
            color: #172b4e
        }

        .sm27-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px
        }

        .sm27 h1 {
            font-size: 1.55rem;
            margin: 0 0 5px
        }

        .sm27 h2 {
            font-size: 1.05rem;
            margin: 0 0 6px
        }

        .sm27-muted {
            font-size: .83rem;
            color: #66758b;
            margin: 0 0 15px
        }

        .sm27-card {
            background: #fff;
            border: 1px solid #e0e7f1;
            border-radius: 13px;
            padding: 18px;
            margin: 0 0 18px;
            box-shadow: 0 2px 8px #14213b08
        }

        .sm27-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px
        }

        .sm27-field {
            min-width: 0
        }

        .sm27-field label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            margin: 0 0 6px
        }

        .sm27-field input,
        .sm27-field select {
            box-sizing: border-box;
            width: 100%;
            padding: 10px 12px;
            min-height: 40px;
            border: 1px solid #d9e2ef;
            border-radius: 8px;
            background: white;
            color: #142b4b;
            font: inherit;
            font-size: .87rem
        }

        .sm27-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 13px
        }

        .sm27-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #d9e2ef;
            background: #fff;
            color: #153460;
            text-decoration: none;
            font-size: .83rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer
        }

        .sm27-btn.primary {
            background: #3264e9;
            border-color: #3264e9;
            color: #fff
        }

        .sm27-btn:disabled {
            opacity: .55;
            cursor: not-allowed
        }

        .sm27-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 15px
        }

        .sm27-kpi {
            background: #f6f8fd;
            border: 1px solid #e6edf6;
            border-radius: 10px;
            padding: 13px
        }

        .sm27-kpi strong {
            display: block;
            font-size: 1.45rem;
            color: #132c54
        }

        .sm27-kpi span {
            font-size: .8rem;
            color: #5a6b85
        }

        .sm27-scroll {
            max-width: 100%;
            overflow-x: auto;
            border: 1px solid #e5ebf3;
            border-radius: 8px
        }

        .sm27 table {
            border-collapse: collapse;
            width: 100%;
            min-width: 740px;
            font-size: .85rem
        }

        .sm27 th {
            background: #f5f7fb;
            color: #596b83;
            text-align: left;
            font-size: .72rem;
            text-transform: uppercase;
            padding: 12px
        }

        .sm27 td {
            padding: 11px;
            border-top: 1px solid #e8edf5
        }

        .sm27-badge {
            display: inline-block;
            border-radius: 50px;
            background: #e9f7ee;
            color: #207644;
            padding: 5px 9px;
            font-size: .77rem;
            font-weight: 600
        }

        .sm27-badge.low {
            background: #fff2d9;
            color: #895a00
        }

        .sm27-badge.zero {
            background: #fde9ec;
            color: #af2843
        }

        .sm27-msg {
            display: none;
            border-radius: 9px;
            padding: 11px 15px;
            margin: 0 0 16px;
            background: #edf4ff;
            color: #27466d
        }

        .sm27-msg.show {
            display: block
        }

        .sm27-msg.error {
            background: #ffeded;
            color: #a42333
        }

        .sm27-msg.ok {
            background: #eaf8ee;
            color: #18643c
        }

        .sm27-help {
            font-size: .78rem;
            color: #697b93;
            margin: 5px 0 0
        }

        @media(max-width:750px) {

            .sm27-grid,
            .sm27-kpis {
                grid-template-columns: 1fr
            }

            .sm27 {
                padding: 15px
            }
        }
    </style>
</head>

<body class="pagina-app">
    <main class="sm27">
        <div class="sm27-head">
            <div>
                <h1><i class="fa-solid fa-bell"></i> Stock mínimo y alertas</h1>
                <p class="sm27-muted">Usuario: <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></p>
            </div><a class="sm27-btn" href="inventario.php"><i class="fa-solid fa-arrow-left"></i> Volver al inventario</a>
        </div>
        <div id="sm27Mensaje" class="sm27-msg" role="status" aria-live="polite"></div>
        <section class="sm27-card">
            <h2>1. Configurar stock mínimo</h2>
            <p class="sm27-muted">Selecciona la ubicación, el producto y la cantidad mínima deseada.</p>
            <div class="sm27-grid">
                <div class="sm27-field"><label for="sm27Ubicacion">Ubicación</label><select id="sm27Ubicacion">
                        <option value="">Cargando ubicaciones...</option>
                    </select></div>
                <div class="sm27-field"><label for="sm27Buscar">Buscar producto</label><input id="sm27Buscar" placeholder="Nombre, código retail o código de barras" autocomplete="off"></div>
                <div class="sm27-field"><label for="sm27Producto">Producto</label><select id="sm27Producto">
                        <option value="">Seleccione un producto</option>
                    </select>
                    <div class="sm27-help" id="sm27Coincidencias"></div>
                </div>
                <div class="sm27-field"><label for="sm27Minimo">Stock mínimo (unidades)</label><input type="number" min="0" max="1000000" step="1" id="sm27Minimo" placeholder="Ejemplo: 10" <?= $puedeConfigurar ? '' : 'disabled' ?>></div>
            </div>
            <p class="sm27-help" id="sm27VistaPrevia">El mínimo cero desactiva las alertas para este producto y ubicación.</p>
            <div class="sm27-actions"><button id="sm27Guardar" class="sm27-btn primary" type="button" <?= $puedeConfigurar ? '' : 'disabled' ?>><i class="fa-solid fa-floppy-disk"></i> Guardar mínimo</button></div>
        </section>
        <section class="sm27-card">
            <h2>2. Alertas y sugerencias</h2>
            <p class="sm27-muted">Las alertas se calculan con las existencias actuales de la ubicación seleccionada.</p>
            <div class="sm27-kpis">
                <div class="sm27-kpi"><strong id="sm27Total">0</strong><span>Productos con existencias registradas</span></div>
                <div class="sm27-kpi"><strong id="sm27Alertas">0</strong><span>Productos en alerta</span></div>
                <div class="sm27-kpi"><strong id="sm27Sugeridas">0</strong><span>Unidades sugeridas para reponer</span></div>
            </div>
            <div class="sm27-actions" style="margin-bottom:12px"><label for="sm27SoloAlertas" style="font-size:.85rem"><input type="checkbox" id="sm27SoloAlertas"> Ver solo alertas</label><button class="sm27-btn" id="sm27Actualizar" type="button"><i class="fa-solid fa-rotate"></i> Actualizar</button></div>
            <div class="sm27-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Código retail</th>
                            <th>Stock actual</th>
                            <th>Mínimo</th>
                            <th>Estado</th>
                            <th>Sugerencia</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="sm27Filas">
                        <tr>
                            <td colspan="7">Seleccione una ubicación.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="sm27-help">Sugerencia = máximo entre cero y (mínimo − stock actual). Es orientativa; no crea pedidos automáticamente.</p>
        </section>
    </main>
    <script>
        window.SM27_PUEDE_GUARDAR = <?= $puedeConfigurar ? 'true' : 'false' ?>;
    </script>
    <script src="../js/stock-minimos.js"></script>
</body>

</html>