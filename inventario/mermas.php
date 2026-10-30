<?php

/** HU-26 — Bajas de inventario. Pantalla independiente para no alterar Movimientos. */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/permisos.php';
if (!isset($_SESSION['usuario'])) {
    header('Location: ../index/index.html');
    exit;
}
if (!usuarioTieneRol([1, 2, 3])) {
    http_response_code(403);
    echo 'No tiene permiso para registrar bajas de inventario.';
    exit;
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$nombre = trim(($_SESSION['usuario']['nombre'] ?? '') . ' ' . ($_SESSION['usuario']['apellido'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mermas y bajas | VendingMiniMarket</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .baja-page {
            max-width: 1250px;
            margin: 0 auto;
            padding: 24px 22px 55px;
            color: #132542
        }

        .baja-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px
        }

        .baja-head h1 {
            margin: 0 0 5px;
            font-size: 1.55rem
        }

        .baja-sub {
            font-size: .85rem;
            color: #697990;
            margin: 0
        }

        .baja-back {
            background: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 13px;
            border-radius: 8px;
            color: #15315a;
            border: 1px solid #dbe3f1;
            font-size: .85rem
        }

        .baja-card {
            border: 1px solid #e0e7f0;
            box-shadow: 0 2px 7px rgba(20, 45, 70, .03);
            border-radius: 13px;
            padding: 20px;
            margin: 0 0 18px;
            background: #fff
        }

        .baja-card h2 {
            margin: 0 0 7px;
            font-size: 1.05rem
        }

        .baja-desc {
            font-size: .84rem;
            color: #64758b;
            line-height: 1.5;
            margin: 0 0 17px
        }

        .baja-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px
        }

        .baja-grid.three {
            grid-template-columns: 2fr 1fr 1fr
        }

        .baja-field {
            min-width: 0;
            margin-bottom: 12px
        }

        .baja-field label {
            display: block;
            font-size: .78rem;
            font-weight: 650;
            margin-bottom: 7px;
            color: #2c415d
        }

        .baja-field input,
        .baja-field select,
        .baja-field textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: 1px solid #d7e1ee;
            border-radius: 8px;
            font: inherit;
            font-size: .85rem;
            background: #fff;
            color: #17314d;
            min-height: 41px
        }

        .baja-field textarea {
            min-height: 66px;
            resize: vertical
        }

        .baja-field small {
            display: block;
            color: #728196;
            font-size: .76rem;
            margin-top: 4px
        }

        .baja-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px
        }

        .baja-btn {
            border: 1px solid #d4dfed;
            border-radius: 8px;
            background: #fff;
            color: #18375c;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
            font-size: .84rem
        }

        .baja-btn.blue {
            background: #3164e9;
            border-color: #3164e9;
            color: #fff
        }

        .baja-btn:disabled {
            opacity: .55;
            cursor: not-allowed
        }

        .baja-table-wrap {
            border: 1px solid #e6ecf3;
            border-radius: 9px;
            overflow-x: auto;
            margin-top: 16px
        }

        .baja-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 690px;
            font-size: .82rem
        }

        .baja-table th {
            font-size: .70rem;
            background: #f5f7fc;
            color: #4d607b;
            text-align: left;
            padding: 12px;
            text-transform: uppercase
        }

        .baja-table td {
            border-top: 1px solid #e8edf4;
            padding: 12px
        }

        .baja-table input {
            width: 80px;
            padding: 6px;
            border: 1px solid #cbd7e7;
            border-radius: 6px
        }

        .baja-preview {
            background: #f3f7ff;
            border: 1px solid #cce0ff;
            border-radius: 9px;
            padding: 12px 15px;
            color: #285380;
            font-size: .86rem;
            margin-top: 14px
        }

        .baja-alert {
            position: fixed;
            z-index: 9999;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #dce5f2;
            border-left: 4px solid #346be9;
            box-shadow: 0 16px 46px #15234250;
            border-radius: 11px;
            padding: 18px 22px;
            max-width: 520px;
            width: min(80vw, 450px);
            display: none;
            color: #203653;
            font-size: .91rem
        }

        .baja-alert.visible {
            display: block
        }

        .baja-alert.error {
            border-left-color: #d54040
        }

        .baja-alert.ok {
            border-left-color: #2c9761
        }

        .baja-search-menu {
            position: relative
        }

        .baja-opciones {
            position: absolute;
            z-index: 1000;
            top: calc(100% + 2px);
            width: 100%;
            max-height: 220px;
            overflow: auto;
            background: #fff;
            border: 1px solid #bdcbe0;
            border-radius: 8px;
            box-shadow: 0 10px 24px #17284b24;
            padding: 5px
        }

        .baja-opciones[hidden] {
            display: none !important
        }

        .baja-opciones button {
            display: block;
            border: 0;
            width: 100%;
            text-align: left;
            background: #fff;
            padding: 9px;
            color: #193651;
            border-radius: 5px;
            cursor: pointer;
            font: inherit;
            font-size: .83rem
        }

        .baja-opciones button:hover,
        .baja-opciones button:focus {
            background: #e8f1ff
        }

        .baja-chip {
            font-size: .8rem;
            color: #537398;
            background: #edf5ff;
            padding: 7px 10px;
            border-radius: 7px;
            margin-top: 5px
        }

        .baja-modal {
            border: 1px solid #dae4f0;
            border-radius: 12px;
            max-width: 470px;
            width: 90%;
            padding: 24px;
            color: #1b3354;
            box-shadow: 0 25px 65px #12203855
        }

        .baja-modal::backdrop {
            background: #14233a8f
        }

        .baja-modal h3 {
            margin: 0 0 9px
        }

        .baja-modal p {
            line-height: 1.5
        }

        @media(max-width:760px) {

            .baja-grid,
            .baja-grid.three {
                grid-template-columns: 1fr
            }

            .baja-page {
                padding: 14px
            }

            .baja-card {
                padding: 14px
            }
        }
    </style>
</head>

<body class="pagina-app">
    <main class="baja-page">
        <header class="baja-head">
            <div>
                <h1><i class="fa-solid fa-box-open"></i> Mermas y bajas de inventario</h1>
                <p class="baja-sub">Usuario: <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <nav><a href="movimientos.php?tipo=merma" class="baja-back"><i class="fa-solid fa-arrow-left"></i> Volver a Movimientos</a></nav>
        </header>
        <div id="bajaAviso" role="alert" aria-live="assertive" class="baja-alert"></div>
        <section class="baja-card">
            <h2>1. Registrar baja</h2>
            <p class="baja-desc">Selecciona la ubicación y el motivo; agrega los productos que se retirarán del inventario.</p>
            <div class="baja-grid">
                <div class="baja-field"><label for="bajaUbicacion">Ubicación de origen</label><select id="bajaUbicacion">
                        <option value="">Cargando ubicaciones...</option>
                    </select></div>
                <div class="baja-field"><label for="bajaMotivo">Motivo</label><select id="bajaMotivo">
                        <option value="">Seleccione un motivo</option>
                        <option value="VENCIMIENTO">Vencimiento</option>
                        <option value="DAÑO">Daño</option>
                        <option value="ROBO">Robo</option>
                        <option value="PÉRDIDA">Pérdida</option>
                        <option value="DEVOLUCIÓN">Devolución</option>
                    </select></div>
            </div>
            <div class="baja-grid three">
                <div class="baja-field baja-search-menu"><label for="bajaBuscar">Buscar producto</label><input type="search" id="bajaBuscar" placeholder="Nombre, código retail o código de barras" autocomplete="off" aria-controls="bajaOpciones" aria-expanded="false">
                    <div id="bajaOpciones" class="baja-opciones" hidden></div><small id="bajaCoincidencias">Cargando productos...</small>
                    <div id="bajaElegido" class="baja-chip">Producto: sin seleccionar</div>
                </div>
                <div class="baja-field"><label for="bajaCantidad">Cantidad a retirar</label><input id="bajaCantidad" type="number" min="1" step="1" placeholder="1, 2, 3..."></div>
                <div class="baja-field"><label>Stock disponible</label>
                    <div class="baja-chip" id="bajaDisponible">Selecciona un producto</div>
                </div>
            </div>
            <div class="baja-actions"><button type="button" id="bajaAgregar" class="baja-btn"><i class="fa-solid fa-plus"></i> Agregar producto</button></div>
            <div class="baja-table-wrap">
                <table class="baja-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Stock actual</th>
                            <th>Retirar</th>
                            <th>Stock resultante</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="bajaItems">
                        <tr>
                            <td colspan="5">No hay productos agregados.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="baja-preview" id="bajaResumen">Agrega un producto para visualizar la baja.</div>
            <div class="baja-grid" style="margin-top:15px">
                <div class="baja-field"><label for="bajaObservaciones">Observaciones</label><textarea id="bajaObservaciones" maxlength="500" placeholder="Detalles de la baja (opcional)"></textarea></div>
                <div class="baja-field"><label for="bajaEvidencia">Adjuntar evidencia (opcional)</label><input id="bajaEvidencia" type="file" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"><small>Selecciona una foto o PDF de hasta 2 MB. Se guarda en el equipo servidor.</small></div>
            </div>
            <div class="baja-actions"><button type="button" id="bajaLimpiar" class="baja-btn">Limpiar formulario</button><button type="button" id="bajaGuardar" class="baja-btn blue"><i class="fa-solid fa-floppy-disk"></i> Registrar baja</button></div>
        </section>
        <section class="baja-card">
            <div class="baja-head">
                <div>
                    <h2>2. Bajas registradas</h2>
                    <p class="baja-desc">Consulta las bajas realizadas y los saldos registrados.</p>
                </div><button type="button" class="baja-btn" id="bajaActualizar"><i class="fa-solid fa-rotate"></i> Actualizar</button>
            </div>
            <div class="baja-table-wrap">
                <table class="baja-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Ubicación</th>
                            <th>Motivo</th>
                            <th>Cantidad</th>
                            <th>Stock anterior → final</th>
                            <th>Responsable</th>
                            <th>Evidencia</th>
                        </tr>
                    </thead>
                    <tbody id="bajaHistorial">
                        <tr>
                            <td colspan="7">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <dialog id="bajaConfirmar" class="baja-modal">
            <h3>Confirmar baja de inventario</h3>
            <p id="bajaConfirmarTexto"></p>
            <div class="baja-actions"><button type="button" id="bajaCancelar" class="baja-btn">Cancelar</button><button type="button" id="bajaAceptar" class="baja-btn blue">Confirmar baja</button></div>
        </dialog>
    </main>
    <script src="../js/mermas.js?v=20261029-2"></script>
</body>

</html>