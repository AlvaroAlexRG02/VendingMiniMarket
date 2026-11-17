/* Importación Nayax: vista previa, asociaciones, confirmación y registro de ventas. */
let nayaxVentasRegistradas = [];
// La tabla de Ventas existente sigue funcionando; solo añadimos los lotes confirmados.
const nayaxObtenerVentasOriginal = obtenerVentasGuardadas;
obtenerVentasGuardadas = function () {
  return [...nayaxObtenerVentasOriginal(), ...nayaxVentasRegistradas].sort((a, b) => (Date.parse(b.fecha) || 0) - (Date.parse(a.fecha) || 0));
};
const nayaxDetalleVentaOriginal = mostrarDetalleVentaVending;
mostrarDetalleVentaVending = function (venta) {
  if (venta.origen === 'Nayax' && venta.id_importacion) {
    abrirDetalleNayax(venta.id_importacion);
  } else {
    nayaxDetalleVentaOriginal(venta);
  }
};

async function nayaxPedir(url, opciones = {}) {
  const respuesta = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...opciones });
  let json;
  try { json = await respuesta.json(); } catch (_) { throw new Error('El servidor no devolvió una respuesta válida.'); }
  if (!respuesta.ok || !json.success) throw new Error(json.message || 'Error al procesar la solicitud.');
  return json.data;
}
async function cargarVentasImportadasNayax() {
  try {
    const ventas = await nayaxPedir('../api/ventas/importaciones.php');
    nayaxVentasRegistradas = Array.isArray(ventas) ? ventas : [];
    if (document.getElementById('cuerpoTablaVentas')) mostrarVentas();
  } catch (e) {
    console.error('No se pudieron leer las importaciones confirmadas:', e);
  }
}
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', cargarVentasImportadasNayax);
} else {
  cargarVentasImportadasNayax();
}

function inicializarImportacionVentasNayax() {
  const boton = document.getElementById('botonImportarVentasNayax');
  const selector = document.getElementById('archivoVentasNayax');
  if (!boton || !selector) return;
  const API = '../api/ventas/importacion_ventas.php';
  const css = document.createElement('link'); css.rel = 'stylesheet';
  css.href = '../css/importacion-ventas.css?v=20261115-5'; document.head.appendChild(css);
  let token = null, catalogo = [], categorias = [], impuestos = [], filas = [], datos = null, archivoActual = null;
  let pagina = 0; const porPagina = 35; const mapeos = {}, nuevos = {};
  const hoyCR = () => {
    const partes = new Intl.DateTimeFormat('en-US', { timeZone: 'America/Costa_Rica', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
    const p = Object.fromEntries(partes.map(x => [x.type, x.value]));
    return `${p.year}-${p.month}-${p.day}`;
  };
  const fechaExcelISO = s => { const [d,m,a] = String(s || '').split('-'); return `${a}-${m}-${d}`; };
  const periodoValido = () => {
    if (!datos || !datos.periodo || !datos.periodo[1]) return false;
    const inicio = fechaExcelISO(datos.periodo[0]), fin = fechaExcelISO(datos.periodo[1]);
    const corte = $('importacionFechaCorte').value;
    return corte >= inicio && corte <= fin && corte <= hoyCR() &&
      (corte === fin || $('importacionDeclaracionCorte').checked);
  };
  // Guardamos solo las decisiones del administrador, nunca el contenido del Excel.
  // La clave SHA-256 evita aplicar asociaciones de un archivo a otro distinto.
  const claveBorrador = () => datos?.hash_sha256 ? `vmm-nayax-borrador:${datos.hash_sha256}` : null;
  function guardarBorrador() {
    const clave = claveBorrador();
    if (!clave || !filas.length) return;
    try {
      const valor = {
        version: 1, guardado: Date.now(), archivo: datos.nombre_archivo,
        nuevos, mapeos,
        fecha_corte: document.getElementById('importacionFechaCorte')?.value || '',
        declaracion_corte: !!document.getElementById('importacionDeclaracionCorte')?.checked
      };
      localStorage.setItem(clave, JSON.stringify(valor));
    } catch (e) { console.warn('No se pudo conservar el borrador de importación:', e); }
  }
  function borrarBorrador() {
    try { const clave = claveBorrador(); if (clave) localStorage.removeItem(clave); }
    catch (e) { console.warn('No se pudo eliminar el borrador:', e); }
  }
  function recuperarBorrador() {
    const clave = claveBorrador();
    if (!clave) return 0;
    let guardado;
    try { guardado = JSON.parse(localStorage.getItem(clave) || 'null'); }
    catch (e) { return 0; }
    if (!guardado || guardado.version !== 1) return 0;
    let recuperados = 0;
    for (const f of filas) {
      const id = Number(guardado.mapeos?.[String(f.fila)] || 0);
      const encontrado = id > 0 && catalogo.find(x => Number(x.id_producto) === id);
      if (encontrado && ['NO_ENCONTRADO', 'CODIGO_AMBIGUO'].includes(f.estado)) {
        mapeos[String(f.fila)] = id;
        f.id_producto = id; f.producto_catalogo = encontrado.nombre; f.estado = 'IDENTIFICADO';
        recuperados++;
        continue;
      }
      const nuevo = guardado.nuevos?.[f.codigo];
      if (nuevo && f.estado === 'NO_ENCONTRADO') {
        const categoria = Number(nuevo.id_categoria) > 0 && categorias.some(x => Number(x.id_categoria) === Number(nuevo.id_categoria));
        const impuesto = Number(nuevo.id_impuesto) > 0 && impuestos.some(x => Number(x.id_impuesto) === Number(nuevo.id_impuesto));
        nuevos[f.codigo] = { id_categoria: categoria ? String(nuevo.id_categoria) : '', id_impuesto: impuesto ? String(nuevo.id_impuesto) : '' };
        nuevoEstado(f);
        recuperados++;
      }
    }
    const fecha = document.getElementById('importacionFechaCorte');
    const acepta = document.getElementById('importacionDeclaracionCorte');
    if (fecha && guardado.fecha_corte && (!fecha.min || guardado.fecha_corte >= fecha.min) && (!fecha.max || guardado.fecha_corte <= fecha.max)) fecha.value = guardado.fecha_corte;
    if (acepta) acepta.checked = !!guardado.declaracion_corte;
    return recuperados;
  }
  const modal = document.createElement('div');
  modal.id = 'modalImportacionNayax'; modal.className = 'importacion-fondo'; modal.hidden = true;
  modal.innerHTML = `
    <section class="importacion-panel" role="dialog" aria-modal="true" aria-labelledby="importacionTitulo">
      <header class="importacion-cabecera"><div>
        <h2 id="importacionTitulo">Importación de ventas</h2>
        <p>Revisa los productos, las cantidades vendidas y la tienda correspondiente.</p>
      </div><button type="button" class="importacion-cerrar" id="importacionCerrar" aria-label="Cerrar">×</button></header>
      <p id="importacionMensaje" class="importacion-mensaje" role="status"></p>
      <div class="importacion-resumen" id="importacionResumen"></div>
      <p id="importacionAdvertencia" class="importacion-advertencia" hidden></p>
      <div id="importacionPeriodo" class="importacion-periodo" hidden>
        <div><strong>Período de las ventas</strong><small id="importacionRangoReporte"></small></div>
        <label>Ventas incluidas hasta <input type="date" id="importacionFechaCorte" aria-label="Fecha hasta la cual el archivo contiene ventas"></label>
        <label class="importacion-declaracion"><input type="checkbox" id="importacionDeclaracionCorte"> Confirmo que el archivo no incluye ventas posteriores a la fecha indicada.</label>
      </div>
      <div id="importacionNuevosAviso" class="importacion-nuevos-aviso" hidden>
        <span id="importacionNuevosTexto"></span>
        <button type="button" class="boton boton-borde" id="importacionPrepararNuevos">Preparar productos nuevos</button>
      </div>
      <div class="importacion-controles"><label for="importacionFiltro">Mostrar</label>
        <select id="importacionFiltro"><option value="TODOS">Todos los registros</option>
          <option value="IDENTIFICADO">Productos identificados</option>
          <option value="NO_ENCONTRADO">Productos pendientes de asociar</option>
          <option value="NUEVO_LISTO">Productos nuevos preparados</option>
          <option value="SIN_TIENDA">Tienda no identificada</option>
          <option value="CODIGO_AMBIGUO">Código con varias coincidencias</option>
          <option value="RECHAZADO">Registros con errores</option>
        </select><span id="importacionContador"></span>
        <button type="button" class="boton boton-borde" id="importacionAnterior">Anterior</button>
        <button type="button" class="boton boton-borde" id="importacionSiguiente">Siguiente</button>
      </div>
      <div class="importacion-tabla-contenedor"><table class="importacion-tabla"><thead><tr>
        <th>Fila</th><th>Código retail</th><th>Producto del reporte</th><th>Producto registrado</th>
        <th>Tienda</th><th>Unidades</th><th>Ingresos</th><th>Estado</th><th>Producto / categoría / impuesto</th>
      </tr></thead><tbody id="importacionCuerpo"></tbody></table></div>
      <footer class="importacion-pie"><small id="importacionPie">Revisa la información del reporte antes de continuar.</small>
        <div class="importacion-botones"><button type="button" class="boton boton-borde" id="importacionCerrarAbajo">Cerrar</button>
          <button type="button" class="boton boton-azul" id="importacionConfirmar" disabled>Confirmar y descontar inventario</button>
        </div>
      </footer>
    </section>`;
  document.body.appendChild(modal);
  const $ = id => document.getElementById(id);
  const mensaje = (texto, error = false) => { $('importacionMensaje').textContent = texto; $('importacionMensaje').classList.toggle('importacion-error', error); };
  const limpiar = () => { modal.hidden = true; selector.value = ''; archivoActual = null; };
  async function solicitarCerrarImportacion() {
    if (modal.hidden) return;
    if (!datos || !filas.length) { limpiar(); return; }
    guardarBorrador();
    const salir = await confirmarVending({
      titulo: 'Cerrar importación',
      mensaje: 'Las categorías, impuestos, asociaciones y fechas se han guardado. Puedes recuperarlos al volver a seleccionar este archivo. ¿Deseas cerrar?',
      textoConfirmar: 'Cerrar', textoCancelar: 'Continuar revisando'
    });
    if (salir) limpiar();
  }
  $('importacionCerrar').addEventListener('click', solicitarCerrarImportacion);
  $('importacionCerrarAbajo').addEventListener('click', solicitarCerrarImportacion);
  // No cerrar por hacer clic sobre el fondo: así se evitan pérdidas accidentales.
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !modal.hidden && !document.querySelector('.popup-vending:not([hidden])')) {
      e.preventDefault(); // El botón Cerrar es la única salida explícita.
    }
  });
  function avisoLateralNayax(titulo, texto, tipo = 'exito') {
    let el = document.getElementById('avisoLateralImportacion');
    if (!el) {
      el = document.createElement('aside'); el.id = 'avisoLateralImportacion';
      el.className = 'importacion-aviso-lateral'; el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite'); document.body.appendChild(el);
    }
    el.className = 'importacion-aviso-lateral' + (tipo === 'error' ? ' importacion-aviso-error' : '');
    el.replaceChildren();
    const contenido = document.createElement('div');
    const h = document.createElement('strong'); h.textContent = titulo;
    const p = document.createElement('p'); p.textContent = texto;
    contenido.append(h,p);
    const cerrar = document.createElement('button'); cerrar.type = 'button'; cerrar.textContent = '×';
    cerrar.setAttribute('aria-label', 'Cerrar notificación');
    cerrar.addEventListener('click', () => { el.hidden = true; });
    el.append(contenido, cerrar); el.hidden = false;
    clearTimeout(el._ocultar);
    el._ocultar = setTimeout(() => { el.hidden = true; }, tipo === 'error' ? 14000 : 11000);
  }
  const moneda = n => new Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC', maximumFractionDigits: 2 }).format(Number(n || 0));
  const celda = (tr, valor) => { const td = document.createElement('td'); td.textContent = String(valor ?? '—'); tr.appendChild(td); return td; };
  const pendientes = () => filas.filter(f => !['IDENTIFICADO', 'NUEVO_LISTO', 'RECHAZADO'].includes(f.estado)).length;
  const permiteConfirmar = () => !!datos && datos.formato === 'RESUMEN_TIENDA' &&
    pendientes() === 0 && filas.length > 0 && !filas.some(f => f.estado === 'RECHAZADO') && periodoValido();
  function mostrarAvisoNuevos() {
    const sinAsociar = filas.filter(f => f.estado === 'NO_ENCONTRADO').length;
    const preparando = filas.filter(f => f.estado === 'NUEVO_PENDIENTE').length;
    const listos = filas.filter(f => f.estado === 'NUEVO_LISTO').length;
    $('importacionNuevosAviso').hidden = sinAsociar + preparando + listos === 0;
    $('importacionNuevosTexto').textContent = `${listos} productos nuevos preparados · ${sinAsociar + preparando} pendientes. El nombre y código retail se tomarán del Excel.`;
    $('importacionPrepararNuevos').disabled = sinAsociar === 0;
  }
  function refrescarResumen() {
    if (!datos) return;
    const identificados = filas.filter(f => f.estado === 'IDENTIFICADO').length;
    const listos = filas.filter(f => f.estado === 'NUEVO_LISTO').length;
    const info = [
      ['Tipo', datos.formato === 'RESUMEN_TIENDA' ? 'Por tienda' : 'Detalle general'],
      ['Productos', datos.filas.length], ['Unidades vendidas', datos.unidades],
      ['Identificados', identificados], ['Nuevos preparados', listos], ['Pendientes', pendientes()],
      ['Total reportado', moneda(datos.filas.reduce((s, f) => s + Number(f.ingresos || 0), 0))]
    ];
    const div = $('importacionResumen'); div.replaceChildren();
    info.forEach(([nombre, valor]) => {
      const bloque = document.createElement('div'); const etiqueta = document.createElement('small');
      const dato = document.createElement('strong'); etiqueta.textContent = nombre; dato.textContent = String(valor);
      bloque.append(etiqueta, dato); div.appendChild(bloque);
    });
    mostrarAvisoNuevos();
    guardarBorrador();
    $('importacionConfirmar').disabled = !permiteConfirmar();
    $('importacionPie').textContent = !permiteConfirmar()
      ? (datos.formato !== 'RESUMEN_TIENDA' ? 'Este reporte no permite descontar inventario por tienda.' : 'Revisa las fechas y completa categoría e impuesto de los productos nuevos antes de confirmar.')
      : 'Confirmar registrará las ventas y descontará el inventario de la tienda.';
  }
  function nuevoEstado(f) {
    const n = nuevos[f.codigo];
    f.estado = n && Number(n.id_categoria) > 0 && Number(n.id_impuesto) > 0 ? 'NUEVO_LISTO' : 'NUEVO_PENDIENTE';
    f.producto_catalogo = 'Nuevo: ' + f.nombre;
  }
  function hacerSelect(items, texto, elegido, cambiar) {
    const select = document.createElement('select');
    select.className = 'importacion-mapeo';
    select.appendChild(new Option(texto, ''));
    for (const it of items) select.add(new Option(it.nombre, String(it.id)));
    select.value = elegido ? String(elegido) : '';
    select.addEventListener('change', () => cambiar(select.value));
    return select;
  }
  function selectorExistente(f, lugar) {
    const panel = document.createElement('div'); panel.className = 'importacion-mapeo-panel';
    const buscar = document.createElement('input'); buscar.type = 'search'; buscar.placeholder = 'Buscar por nombre o código';
    buscar.setAttribute('aria-label', 'Buscar producto registrado para ' + f.codigo);
    const elegir = document.createElement('select'); elegir.className = 'importacion-mapeo';
    function filtrar() {
      elegir.replaceChildren(new Option('Seleccionar coincidencia...', ''));
      const q = buscar.value.trim().toLocaleLowerCase('es-CR');
      const lista = q ? catalogo.filter(p => `${p.nombre} ${p.codigo_articulo_retail || ''}`.toLocaleLowerCase('es-CR').includes(q)).slice(0, 40) : [];
      for (const p of lista) elegir.add(new Option(`${p.codigo_articulo_retail || 'Sin código'} · ${p.nombre}`, String(p.id_producto)));
    }
    buscar.addEventListener('input', filtrar); filtrar();
    elegir.addEventListener('change', () => {
      const p = catalogo.find(x => Number(x.id_producto) === Number(elegir.value));
      if (!p) return;
      delete nuevos[f.codigo]; mapeos[String(f.fila)] = Number(p.id_producto);
      f.id_producto = Number(p.id_producto); f.producto_catalogo = p.nombre; f.estado = 'IDENTIFICADO';
      verFilas(); refrescarResumen();
    });
    const regresar = document.createElement('button');
    regresar.type = 'button'; regresar.className = 'importacion-enlace';
    regresar.textContent = 'Volver a registrar como nuevo';
    regresar.addEventListener('click', () => {
      if (nuevos[f.codigo]) editorNuevo(f, lugar);
      else { f.estado = 'NO_ENCONTRADO'; f.producto_catalogo = null; verFilas(); }
      refrescarResumen();
    });
    panel.append(buscar, elegir, regresar); lugar.replaceChildren(panel); buscar.focus();
  }
  function editorNuevo(f, lugar) {
    if (!nuevos[f.codigo]) nuevos[f.codigo] = { id_categoria: '', id_impuesto: '' };
    nuevoEstado(f);
    const panel = document.createElement('div'); panel.className = 'importacion-nuevo-editor';
    const cat = hacerSelect(categorias.map(x => ({id:x.id_categoria,nombre:x.nombre})), 'Categoría...', nuevos[f.codigo].id_categoria, id => {
      nuevos[f.codigo].id_categoria = id; nuevoEstado(f); refrescarResumen();
      // Mantener el editor abierto para no perder la selección de impuesto.
      lugar.closest('tr').cells[7].textContent = f.estado === 'NUEVO_LISTO' ? 'Nuevo preparado' : 'Completar datos';
    });
    const iva = hacerSelect(impuestos.map(x => ({id:x.id_impuesto,nombre:`${x.nombre} (${Number(x.porcentaje)} %)`})), 'Impuesto...', nuevos[f.codigo].id_impuesto, id => {
      nuevos[f.codigo].id_impuesto = id; nuevoEstado(f); refrescarResumen();
      lugar.closest('tr').cells[7].textContent = f.estado === 'NUEVO_LISTO' ? 'Nuevo preparado' : 'Completar datos';
    });
    const cambiar = document.createElement('button'); cambiar.type='button'; cambiar.className='importacion-enlace'; cambiar.textContent='Buscar producto existente';
    cambiar.addEventListener('click', () => {
      // Guardar categoría e impuesto para poder volver sin rellenar el formulario.
      f.estado='NO_ENCONTRADO'; f.producto_catalogo=null;
      selectorExistente(f,lugar); refrescarResumen();
    });
    panel.append(cat,iva,cambiar); lugar.replaceChildren(panel);
  }
  function verFilas() {
    const filtro = $('importacionFiltro').value;
    const visibles = filas.filter(f => filtro === 'TODOS' ||
      (filtro === 'NO_ENCONTRADO' && ['NO_ENCONTRADO', 'NUEVO_PENDIENTE'].includes(f.estado)) || f.estado === filtro);
    const paginas = Math.max(1, Math.ceil(visibles.length / porPagina));
    pagina = Math.min(pagina, paginas - 1);
    $('importacionContador').textContent = `${visibles.length} registros · página ${pagina + 1} de ${paginas}`;
    $('importacionAnterior').disabled = pagina === 0;
    $('importacionSiguiente').disabled = pagina >= paginas - 1;
    const cuerpo = $('importacionCuerpo'); cuerpo.replaceChildren();
    for (const f of visibles.slice(pagina * porPagina, (pagina + 1) * porPagina)) {
      const tr = document.createElement('tr');
      tr.className = ['IDENTIFICADO','NUEVO_LISTO'].includes(f.estado) ? 'importacion-valido' : 'importacion-pendiente';
      celda(tr, f.fila); celda(tr, f.codigo); celda(tr, f.nombre);
      celda(tr, f.producto_catalogo || '—'); celda(tr, f.tienda || (f.id_tienda ? 'Tienda ' + f.id_tienda : 'Sin identificar'));
      celda(tr, f.cantidad); celda(tr, f.ingresos === null ? '—' : moneda(f.ingresos));
      const estado = {IDENTIFICADO:'Identificado', NUEVO_LISTO:'Nuevo preparado', NUEVO_PENDIENTE:'Completar datos',
        NO_ENCONTRADO:'Nuevo / sin asociar', SIN_TIENDA:'Tienda no identificada', CODIGO_AMBIGUO:'Varias coincidencias', RECHAZADO:'Con errores'};
      celda(tr, f.motivo || estado[f.estado] || 'Pendiente');
      const lugar = celda(tr, '');
      if (['NUEVO_LISTO','NUEVO_PENDIENTE'].includes(f.estado)) {
        editorNuevo(f, lugar);
      } else if (f.estado === 'NO_ENCONTRADO') {
        const registrar = document.createElement('button'); registrar.type='button'; registrar.className='boton boton-borde importacion-accion-nuevo';
        registrar.textContent='Registrar producto nuevo';
        registrar.addEventListener('click', () => { delete mapeos[String(f.fila)]; editorNuevo(f,lugar); tr.cells[7].textContent = 'Completar datos'; refrescarResumen(); });
        const buscar = document.createElement('button'); buscar.type='button'; buscar.className='importacion-enlace'; buscar.textContent='Ya existe en catálogo';
        buscar.addEventListener('click', () => selectorExistente(f,lugar));
        const panel = document.createElement('div'); panel.className='importacion-nuevo-editor';panel.append(registrar,buscar);lugar.replaceChildren(panel);
      } else if (f.estado === 'CODIGO_AMBIGUO') {
        const buscar = document.createElement('button'); buscar.type='button'; buscar.textContent='Elegir producto existente';buscar.className='boton boton-borde';
        buscar.addEventListener('click', () => selectorExistente(f,lugar));lugar.replaceChildren(buscar);
      } else lugar.textContent = '—';
      cuerpo.appendChild(tr);
    }
  }
  $('importacionPrepararNuevos').addEventListener('click', () => {
    for (const f of filas) if (f.estado === 'NO_ENCONTRADO') {
      if (!nuevos[f.codigo]) nuevos[f.codigo] = {id_categoria:'',id_impuesto:''}; nuevoEstado(f);
    }
    $('importacionFiltro').value='NO_ENCONTRADO'; pagina=0; verFilas(); refrescarResumen();
  });
  $('importacionFechaCorte').addEventListener('change', refrescarResumen);
  $('importacionDeclaracionCorte').addEventListener('change', refrescarResumen);
  $('importacionFiltro').addEventListener('change', () => { pagina = 0; verFilas(); });
  $('importacionAnterior').addEventListener('click', () => { if (pagina > 0) { pagina--; verFilas(); } });
  $('importacionSiguiente').addEventListener('click', () => { pagina++; verFilas(); });
  boton.addEventListener('click', async () => {
    try {
      const d = await nayaxPedir(API);
      token = d.csrf_token; catalogo = (d.catalogo || []).sort((a,b) => String(a.nombre).localeCompare(String(b.nombre),'es'));
      categorias = d.categorias || []; impuestos = d.impuestos || [];
      selector.value = ''; selector.accept = '.csv,.xlsx'; selector.click();
    } catch (e) { avisoLateralNayax('No se pudo iniciar la importación', e.message, 'error'); }
  });
  selector.addEventListener('change', async () => {
    const f = selector.files && selector.files[0]; if (!f) return;
    archivoActual = f; modal.hidden = false; datos = null; filas = []; pagina = 0;
    Object.keys(mapeos).forEach(k => delete mapeos[k]); Object.keys(nuevos).forEach(k => delete nuevos[k]);
    $('importacionCuerpo').replaceChildren(); $('importacionResumen').replaceChildren();
    $('importacionAdvertencia').hidden = true; $('importacionConfirmar').disabled = true;
    mensaje('Analizando “' + f.name + '” y comparando los productos del catálogo...');
    try {
      const form = new FormData(); form.append('archivo', f, f.name);
      datos = await nayaxPedir(API, { method: 'POST', body: form, headers: { 'X-CSRF-Token': token } });
      filas = [...datos.filas, ...datos.rechazadas.map(x => ({ ...x, estado: 'RECHAZADO' }))];
      mensaje(datos.nombre_archivo + ': archivo leído correctamente.');
      $('importacionPeriodo').hidden = !datos.periodo;
      $('importacionDeclaracionCorte').checked = false;
      if (datos.periodo && datos.periodo[1]) {
        const inicio = fechaExcelISO(datos.periodo[0]), fin = fechaExcelISO(datos.periodo[1]);
        const maximo = fin < hoyCR() ? fin : hoyCR();
        $('importacionRangoReporte').textContent = `Rango seleccionado en Nayax: ${datos.periodo[0]} al ${datos.periodo[1]}. La fecha de corte se guardará separadamente.`;
        $('importacionFechaCorte').min = inicio; $('importacionFechaCorte').max = maximo;
        $('importacionFechaCorte').value = fin > hoyCR() ? '' : (inicio <= maximo ? fin : '');
        if (fin > hoyCR()) {
          $('importacionAdvertencia').hidden = false;
          $('importacionAdvertencia').textContent = `El rango solicitado llega hasta el ${datos.periodo[1]}. Selecciona la última fecha de ventas que realmente contiene este archivo (no necesariamente hoy) y confírmala. Los períodos ya importados no se descontarán otra vez.`;
        }
      }
      if (datos.formato !== 'RESUMEN_TIENDA') {
        $('importacionAdvertencia').hidden = false;
        $('importacionAdvertencia').textContent = 'Este reporte reúne varias tiendas sin indicar la tienda de cada transacción. Solo se permite consultar; no se puede descontar inventario con este archivo.';
      }
      if (datos.rechazadas.length) {
        $('importacionAdvertencia').hidden = false;
        $('importacionAdvertencia').textContent = 'Hay registros rechazados: corrige el Excel y vuelve a importarlo antes de confirmar.';
      }
      const recuperados = recuperarBorrador();
      $('importacionFiltro').value = 'TODOS'; verFilas(); refrescarResumen();
      if (recuperados) mensaje(`Archivo leído. Se recuperaron ${recuperados} productos del borrador guardado.`);
    } catch (e) { mensaje('No se pudo procesar el archivo: ' + e.message, true); }
  });
  $('importacionConfirmar').addEventListener('click', async () => {
    if (!permiteConfirmar() || !archivoActual || !token) return;
    const d = datos;
    guardarBorrador();
    const seguro = await confirmarVending({
      titulo: 'Confirmar ventas de Nayax',
      mensaje: `¿Confirmas ${d.unidades} unidades vendidas de ${d.filas.length} productos hasta el ${$('importacionFechaCorte').value}?\n\nSe registrarán las ventas y se descontará el inventario de la tienda. Si el saldo no alcanza, se generarán negativos y alertas.\n\nNo se descontará dos veces el mismo archivo o período.`,
      textoConfirmar: 'Confirmar importación', textoCancelar: 'Seguir revisando'
    });
    if (!seguro) return;
    const botonConfirmar = $('importacionConfirmar'); botonConfirmar.disabled = true;
    mensaje('Registrando ventas y actualizando inventario...');
    try {
      const form = new FormData(); form.append('archivo', archivoActual, archivoActual.name);
      form.append('accion', 'confirmar'); form.append('mapeos', JSON.stringify(mapeos));
      form.append('nuevos', JSON.stringify(nuevos));
      form.append('fecha_corte', $('importacionFechaCorte').value);
      form.append('declaracion_corte', $('importacionDeclaracionCorte').checked ? 'si' : 'no');
      const respuesta = await nayaxPedir(API, { method: 'POST', body: form, headers: { 'X-CSRF-Token': token } });
      const numero = respuesta.numero;
      borrarBorrador();
      await cargarVentasImportadasNayax(); limpiar();
      avisoLateralNayax('Importación confirmada',
        `${numero} · ${respuesta.productos_creados} productos nuevos · ${respuesta.unidades} unidades descontadas · ${respuesta.alertas_negativas} alertas negativas. Consulta el detalle con el botón del ojo en Registro de ventas.`);
    } catch (e) {
      mensaje('No se confirmó la importación: ' + e.message, true);
      guardarBorrador();
      avisoLateralNayax('No se pudo confirmar', e.message, 'error');
    }
    finally { botonConfirmar.disabled = !permiteConfirmar(); }
  });
}

async function abrirDetalleNayax(idImportacion) {
  let modal = document.getElementById('modalDetalleImportacionNayax');
  if (!modal) {
    modal = document.createElement('div');modal.id='modalDetalleImportacionNayax';modal.className='importacion-fondo';modal.hidden=true;
    modal.innerHTML=`<section class="importacion-panel" role="dialog" aria-modal="true" aria-labelledby="nayaxDetalleTitulo">
      <header class="importacion-cabecera"><div><h2 id="nayaxDetalleTitulo">Detalle de ventas importadas</h2>
        <p id="nayaxDetalleSubtitulo"></p></div><button type="button" class="importacion-cerrar" id="nayaxCerrarDetalle" aria-label="Cerrar">×</button></header>
      <p id="nayaxDetalleMensaje" class="importacion-mensaje"></p>
      <div class="importacion-controles"><label for="nayaxBuscarDetalle">Buscar producto</label>
        <input id="nayaxBuscarDetalle" type="search" placeholder="Código o nombre">
        <span id="nayaxContadorDetalle"></span>
        <button type="button" class="boton boton-borde" id="nayaxDetalleAnterior">Anterior</button>
        <button type="button" class="boton boton-borde" id="nayaxDetalleSiguiente">Siguiente</button></div>
      <div class="importacion-tabla-contenedor"><table class="importacion-tabla"><thead><tr><th>Código retail</th><th>Producto</th><th>Producto registrado</th><th>Unidades</th><th>Subtotal</th><th>IVA incluido</th><th>Total</th></tr></thead><tbody id="nayaxDetalleFilas"></tbody></table></div>
      <footer class="importacion-pie"><small>Detalle del archivo original registrado en la base de datos.</small>
        <button type="button" class="boton boton-borde" id="nayaxDetalleCerrarAbajo">Cerrar</button></footer>
    </section>`;
    document.body.appendChild(modal);
    const cerrar=()=>{modal.hidden=true;};
    modal.querySelector('#nayaxCerrarDetalle').addEventListener('click',cerrar);
    modal.querySelector('#nayaxDetalleCerrarAbajo').addEventListener('click',cerrar);
    modal.addEventListener('click',e=>{if(e.target===modal)cerrar();});
  }
  const $=id=>document.getElementById(id);
  modal.hidden=false;
  $('nayaxDetalleMensaje').textContent='Cargando los productos vendidos...';
  $('nayaxDetalleFilas').replaceChildren();
  try {
    const lote=await nayaxPedir('../api/ventas/importaciones.php?id='+encodeURIComponent(idImportacion));
    const registros=lote.productos||[];const porPagina=40;let pagina=0;
    const moneda=n=>new Intl.NumberFormat('es-CR',{style:'currency',currency:'CRC',maximumFractionDigits:2}).format(Number(n||0));
    $('nayaxDetalleSubtitulo').textContent=`IMP-${String(idImportacion).padStart(6,'0')} · ${lote.tienda} · del ${lote.periodo_inicio} al ${lote.periodo_fin} · ${lote.nombre_archivo}`;
    $('nayaxDetalleMensaje').textContent=`${registros.length} productos · ${lote.unidades} unidades vendidas · Total: ${moneda(lote.total)}`;
    const celda=(tr,t)=>{const td=document.createElement('td');td.textContent=String(t??'—');tr.appendChild(td);};
    function dibujar() {
      const q=$('nayaxBuscarDetalle').value.trim().toLocaleLowerCase('es-CR');
      const visibles=registros.filter(r=>!q||[r.codigo_retail,r.nombre_archivo_producto,r.producto_catalogo].join(' ').toLocaleLowerCase('es-CR').includes(q));
      const paginas=Math.max(1,Math.ceil(visibles.length/porPagina));pagina=Math.min(pagina,paginas-1);
      $('nayaxContadorDetalle').textContent=`${visibles.length} resultados · ${pagina+1}/${paginas}`;
      $('nayaxDetalleAnterior').disabled=pagina===0;$('nayaxDetalleSiguiente').disabled=pagina>=paginas-1;
      const tbody=$('nayaxDetalleFilas');tbody.replaceChildren();
      visibles.slice(pagina*porPagina,(pagina+1)*porPagina).forEach(r=>{
        const tr=document.createElement('tr');
        celda(tr,r.codigo_retail);celda(tr,r.nombre_archivo_producto);celda(tr,r.producto_catalogo);celda(tr,r.cantidad);
        celda(tr,moneda(r.subtotal));celda(tr,moneda(r.impuesto));celda(tr,moneda(r.total));tbody.appendChild(tr);
      });
    }
    $('nayaxBuscarDetalle').value='';
    $('nayaxBuscarDetalle').oninput=()=>{pagina=0;dibujar();};
    $('nayaxDetalleAnterior').onclick=()=>{pagina=Math.max(0,pagina-1);dibujar();};
    $('nayaxDetalleSiguiente').onclick=()=>{pagina++;dibujar();};
    dibujar();
  } catch(e) { $('nayaxDetalleMensaje').textContent='No se pudo cargar el detalle: '+e.message; }
}
