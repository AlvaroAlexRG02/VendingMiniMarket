<?php
/** VendingMiniMarket — HU-24 y HU-25 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/permisos.php';
if (!isset($_SESSION['usuario'])) { header('Location: ../index/index.html'); exit; }
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$nombre = trim(($_SESSION['usuario']['nombre'] ?? '') . ' ' . ($_SESSION['usuario']['apellido'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Conteos y ajustes | VendingMiniMarket</title>
<link rel="stylesheet" href="../css/estilos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
.ca24-page{max-width:1250px;margin:0 auto;padding:24px 20px 55px;color:#14213c}
.ca24-header{display:flex;justify-content:space-between;gap:15px;align-items:flex-start;flex-wrap:wrap;margin-bottom:21px}
.ca24-header h1{font-size:1.55rem;margin:0 0 7px;color:#102344}
.ca24-sub{font-size:.88rem;color:#59677f;margin:0}
.ca24-back{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #dbe3ef;border-radius:8px;padding:10px 13px;color:#12294f;text-decoration:none;font-size:.84rem}
.ca24-volver{display:flex;gap:8px;flex-wrap:wrap}
.ca24-panel{background:#fff;border:1px solid #e1e7f0;border-radius:14px;padding:19px;margin-bottom:19px;box-shadow:0 2px 7px rgba(10,29,67,.03)}
.ca24-panel h2{font-size:1.11rem;margin:0 0 6px}
.ca24-hint{font-size:.83rem;color:#66758c;margin:0 0 15px;line-height:1.5}
.ca24-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
.ca24-row.three{grid-template-columns:repeat(3,minmax(0,1fr))}
.ca24-field{min-width:0;margin-bottom:13px}
.ca24-field label{display:block;font-size:.77rem;font-weight:650;margin-bottom:7px;color:#263753}
.ca24-field select,.ca24-field input,.ca24-field textarea{width:100%;font:inherit;font-size:.85rem;border:1px solid #dce4ee;border-radius:8px;background:white;padding:10px 11px;color:#233755;box-sizing:border-box;min-height:40px}
.ca24-field textarea{min-height:68px}
.ca24-btn{border:1px solid #dce4ee;background:#fff;border-radius:8px;font:inherit;font-size:.83rem;font-weight:600;cursor:pointer;padding:10px 14px;color:#143567}
.ca24-btn.blue{background:#3164e9;color:white;border-color:#3164e9}
.ca24-btn:disabled{opacity:.5;cursor:not-allowed}
.ca24-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px;justify-content:flex-end;margin:12px 0 0}
.ca24-scroll{overflow-x:auto;max-width:100%;margin-top:13px;border:1px solid #e5ebf3;border-radius:9px}
.ca24-table{width:100%;border-collapse:collapse;font-size:.82rem;min-width:670px}
.ca24-table th{background:#f5f7fc;color:#4c5d77;text-transform:uppercase;font-size:.7rem;letter-spacing:.025em;text-align:left;padding:12px}
.ca24-table td{border-top:1px solid #ebeff6;padding:12px;color:#24344e}
.ca24-table input{width:95px;border:1px solid #d6deeb;border-radius:7px;padding:8px}
.ca24-note{border:1px solid #c5d8ff;background:#f1f6ff;padding:12px 14px;border-radius:9px;font-size:.85rem;color:#244979;margin:12px 0}
.ca24-alert{position:fixed;z-index:10000;left:50%;top:50%;transform:translate(-50%,-50%);padding:18px 22px;min-width:min(85vw,330px);max-width:540px;background:#fff;border:1px solid #dbe4f0;border-left:4px solid #2b61e9;box-shadow:0 15px 50px #11224444;border-radius:10px;color:#21344f;font-size:.92rem;display:none}
.ca24-alert.visible{display:block}.ca24-alert.error{border-left-color:#dc3a3a}.ca24-alert.ok{border-left-color:#23a565}
.ca24-kpis{display:flex;gap:11px;flex-wrap:wrap;margin:9px 0}.ca24-kpis span{background:#f3f6fb;padding:8px 12px;border-radius:8px;font-size:.82rem}
.ca24-badge{display:inline-block;padding:4px 9px;border-radius:99px;background:#e9eef8;font-size:.72rem;font-weight:650}
.ca24-badge.pending{background:#fff4db;color:#8a5c00}.ca24-badge.done{background:#e5f7e9;color:#20743c}
.ca24-detail{border:1px solid #e8edf5;border-radius:10px;padding:12px;margin:10px 0}
.ca24-detail summary{cursor:pointer;font-weight:650;font-size:.87rem}
.ca24-muted{color:#697890;font-size:.81rem}.ca24-negative{color:#b51f32;font-weight:650}.ca24-positive{color:#167444;font-weight:650}
@media(max-width:820px){.ca24-row,.ca24-row.three{grid-template-columns:1fr}.ca24-page{padding:15px}.ca24-panel{padding:14px}}
</style>
</head>
<body class="pagina-app">
<div class="ca24-page">
<header class="ca24-header">
  <div><h1><i class="fa-solid fa-clipboard-check"></i> Conteos físicos y ajustes</h1><p class="ca24-sub">Usuario: <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></p></div>
  <nav class="ca24-volver" aria-label="Regresar">
    <a class="ca24-back" href="movimientos.php?tipo=ajuste"><i class="fa-solid fa-arrow-left"></i> Volver a Ajustes</a>
    <a class="ca24-back" href="inventario.php">Inventario</a>
  </nav>
</header>
<div id="ca24Aviso" class="ca24-alert" role="alert" aria-live="assertive"></div>
<section class="ca24-panel" id="ca24PanelConteo">
  <h2>1. Registrar conteo físico</h2>
  <p class="ca24-hint">Registra los productos contados y sus cantidades.</p>
  <div class="ca24-row"><div class="ca24-field"><label for="ca24Ubicacion">Ubicación de conteo</label><select id="ca24Ubicacion"><option value="">Cargando ubicaciones...</option></select></div>
  <div class="ca24-field"><label for="ca24Observaciones">Observaciones</label><input id="ca24Observaciones" maxlength="500" placeholder="Ejemplo: Conteo del cierre de turno"></div></div>
  <div class="ca24-row three"><div class="ca24-field"><label for="ca24Buscar">Buscar producto</label><input id="ca24Buscar" placeholder="Nombre, código retail o barras"></div>
  <div class="ca24-field"><label for="ca24Producto">Producto</label><select id="ca24Producto"><option value="">Seleccione un producto</option></select></div>
  <div class="ca24-field"><label for="ca24Cantidad">Cantidad encontrada físicamente</label><input type="number" min="0" step="1" id="ca24Cantidad" placeholder="0, 1, 2..."></div></div>
  <div class="ca24-actions"><button type="button" id="ca24Agregar" class="ca24-btn"><i class="fa-solid fa-plus"></i> Agregar a la tabla</button></div>
  <div class="ca24-scroll"><table class="ca24-table"><thead><tr><th>Producto</th><th>Cantidad física</th><th>Acción</th></tr></thead><tbody id="ca24Items"><tr><td colspan="3">Aún no hay productos agregados.</td></tr></tbody></table></div>
  <div class="ca24-kpis"><span id="ca24TotalItems">0 productos incluidos</span></div>
  <div class="ca24-actions"><button type="button" id="ca24Limpiar" class="ca24-btn">Limpiar lista</button><button type="button" id="ca24Guardar" class="ca24-btn blue">Guardar conteo físico</button></div>
</section>
<section class="ca24-panel" id="ca24PanelAjuste">
  <h2>2. Solicitar ajuste de inventario</h2>
  <p class="ca24-hint">Indica la cantidad correcta y el motivo del ajuste.</p>
  <div class="ca24-note"><i class="fa-solid fa-circle-info"></i> El inventario se actualiza al aprobar el ajuste.</div>
  <div class="ca24-row"><div class="ca24-field"><label for="ca24AjusteUbicacion">Ubicación</label><select id="ca24AjusteUbicacion"></select></div>
  <div class="ca24-field"><label for="ca24AjusteProducto">Producto</label><select id="ca24AjusteProducto"></select></div></div>
  <div class="ca24-row"><div class="ca24-field"><label for="ca24Objetivo">Cantidad correcta (stock objetivo)</label><input type="number" min="0" step="1" id="ca24Objetivo" placeholder="Cantidad que debe quedar en el sistema"></div>
  <div class="ca24-field"><label for="ca24Motivo">Motivo</label><select id="ca24Motivo"><option value="">Seleccione motivo</option><option value="DIFERENCIA_CONTEO">Diferencia de conteo</option><option value="STOCK_NEGATIVO">Corrección de stock negativo</option><option value="ROBO">Robo</option><option value="PERDIDA">Pérdida</option><option value="ERROR_REGISTRO">Error de registro</option><option value="OTRO">Otro (explicar)</option></select></div></div>
  <div class="ca24-field"><label for="ca24Justificacion">Justificación / detalle</label><textarea id="ca24Justificacion" maxlength="450" placeholder="Indique por qué debe corregirse el stock"></textarea></div>
  <p id="ca24ConteoVinculado" class="ca24-muted">Ajuste manual (sin conteo vinculado).</p>
  <div class="ca24-actions"><button id="ca24Desvincular" type="button" class="ca24-btn">Limpiar vínculo de conteo</button><button id="ca24Solicitar" type="button" class="ca24-btn blue">Solicitar ajuste</button></div>
</section>
<section class="ca24-panel">
  <div class="ca24-header"><div><h2>3. Conteos registrados</h2><p class="ca24-hint">Revisa los conteos y sus diferencias.</p></div><button type="button" id="ca24Actualizar" class="ca24-btn"><i class="fa-solid fa-rotate"></i> Actualizar</button></div>
  <div id="ca24HistorialConteos"><p class="ca24-muted">Cargando conteos...</p></div>
</section>
<section class="ca24-panel"><h2>4. Ajustes y autorizaciones</h2><p class="ca24-hint">Aprueba o rechaza los ajustes pendientes.</p>
  <div class="ca24-scroll"><table class="ca24-table"><thead><tr><th>Movimiento</th><th>Producto / ubicación</th><th>Antes → Objetivo</th><th>Motivo</th><th>Estado</th><th>Acción</th></tr></thead><tbody id="ca24HistorialAjustes"><tr><td colspan="6">Cargando...</td></tr></tbody></table></div>
</section>
</div>
<script src="../js/conteos-ajustes.js"></script>
</body></html>
