/* VendingMiniMarket — HU-29: consulta, filtros y exportación de movimientos reales.
   Este archivo NO modifica las operaciones de entrada, salida, traslado o Nayax. */
(() => {
  'use strict';
  const $ = (id) => document.getElementById(id);
  const API = '../api/inventario/historial-movimientos.php';
  const estado = { pagina: 1, paginas: 1, total: 0, filas: [], cargando: false, controlador: null };
  const FORMATO_FECHA = new Intl.DateTimeFormat('es-CR', { dateStyle: 'short', timeStyle: 'short', timeZone: 'America/Costa_Rica' });
  const escapeTexto = (valor) => String(valor ?? '').trim();
  const tipos = { ENTRADA:'Entrada', SALIDA:'Salida', AJUSTE:'Ajuste', BAJA:'Merma / baja', TRASLADO:'Traslado' };

  document.addEventListener('DOMContentLoaded', iniciar);

  function hacer(tag, clase, texto) {
    const el = document.createElement(tag);
    if (clase) el.className = clase;
    if (texto !== undefined) el.textContent = texto;
    return el;
  }
  function fecha(valor) {
    if (!valor) return '—';
    // PostgreSQL puede devolver microsegundos y zonas horarias como "+00".
    // Convertir a ISO reconocido por Date antes de mostrar la hora de Costa Rica.
    const s = String(valor).trim().replace(' ', 'T')
      .replace(/([+-]\d{2})$/, '$1:00');
    const d = new Date(s);
    return Number.isNaN(d.getTime()) ? String(valor) : FORMATO_FECHA.format(d);
  }
  function nombreUbicacion(tienda, ubicacion) {
    if (!ubicacion) return '—';
    return tienda && tienda !== ubicacion ? `${tienda} / ${ubicacion}` : (tienda || ubicacion);
  }
  function ubicacionesMovimiento(fila) {
    const tipo = String(fila.tipo_movimiento || '').toUpperCase();
    const origen = nombreUbicacion(fila.tienda_origen, fila.origen);
    const destino = nombreUbicacion(fila.tienda_destino, fila.destino);
    const descripcion = `${fila.motivo || ''} ${fila.observaciones || ''}`;
    if (tipo === 'ENTRADA') {
      // Un proveedor no es una ubicación interna: no debemos inventar su nombre.
      const externo = fila.id_pedido ? 'Ingreso por pedido' :
        /proveedor/i.test(descripcion) ? 'Proveedor externo' : 'Ingreso externo';
      return { origen: origen !== '—' ? origen : externo, destino: destino !== '—' ? destino : 'No registrado' };
    }
    if (tipo === 'SALIDA') {
      const causa = /nayax/i.test(descripcion) ? 'Venta Nayax' :
        /venta/i.test(descripcion) ? 'Venta / cliente' : 'Salida externa';
      return { origen: origen !== '—' ? origen : 'No registrado', destino: destino !== '—' ? destino : causa };
    }
    if (tipo === 'BAJA') {
      return { origen: origen !== '—' ? origen : 'No registrado', destino: destino !== '—' ? destino : 'Baja de inventario' };
    }
    if (tipo === 'AJUSTE') {
      return { origen: origen !== '—' ? origen : (destino !== '—' ? destino : 'No registrado'),
        destino: destino !== '—' ? destino : 'Ajuste de inventario' };
    }
    return { origen: origen !== '—' ? origen : 'No registrado', destino: destino !== '—' ? destino : 'No registrado' };
  }
  function referencia(fila) {
    return fila.id_pedido ? 'PED-' + String(fila.id_pedido).padStart(6, '0')
      : 'MOV-' + String(fila.id_movimiento).padStart(6, '0');
  }
  function saldo(fila) {
    const tipo = String(fila.tipo_movimiento || '').toUpperCase();
    const origen = fila.saldo_origen_resultante;
    const destino = fila.saldo_destino_resultante;
    // El saldo relevante depende de dónde se actualizó físicamente el inventario.
    if (tipo === 'ENTRADA') return destino != null ? String(destino) : (origen != null ? String(origen) : '—');
    if (tipo === 'SALIDA' || tipo === 'BAJA') return origen != null ? String(origen) : (destino != null ? String(destino) : '—');
    if (tipo === 'TRASLADO' && origen != null && destino != null) return `O: ${origen} · D: ${destino}`;
    if (origen != null) return String(origen);
    if (destino != null) return String(destino);
    return '—';
  }
  function mostrarInfo(msg, error = false) {
    const box = $('resultadoBusquedaMovimientos');
    if (!box) return;
    box.textContent = msg;
    box.classList.toggle('hist29-error', error);
  }
  function query(accion) {
    const p = new URLSearchParams({ accion });
    const map = {
      busqueda: $('buscarMovimiento')?.value.trim() || '',
      tipo: $('filtroTipoMovimiento')?.value || '',
      id_tienda: $('filtroUbicacionMovimiento')?.value || '',
      id_ubicacion: $('hist29Ubicacion')?.value || '',
      id_usuario: $('hist29Usuario')?.value || '',
      desde: $('filtroFecha')?.value || '',
      hasta: $('hist29Hasta')?.value || ''
    };
    Object.entries(map).forEach(([k,v]) => { if (v) p.set(k,v); });
    if (accion === 'listar') { p.set('pagina', String(estado.pagina)); p.set('por_pagina', '30'); }
    return `${API}?${p.toString()}`;
  }
  async function pedir(url, signal) {
    const resp = await fetch(url, {
      credentials:'same-origin', cache:'no-store',
      headers:{ Accept:'application/json' }, signal
    });
    let res;
    try { res = await resp.json(); }
    catch { throw new Error('El servidor no devolvió un historial válido.'); }
    if (!resp.ok || !res.success) throw new Error(res.message || 'No fue posible consultar los movimientos.');
    return res.data;
  }
  function crearFiltro(tipo, id, label) {
    const grupo = hacer('div', 'selector-filtro hist29-campo');
    const lab = hacer('label', '', label);
    lab.htmlFor = id;
    const input = document.createElement(tipo);
    input.id = id;
    if (tipo === 'input') input.type = 'date';
    grupo.append(lab, input);
    return grupo;
  }
  function opcion(value, text) {
    const o = document.createElement('option');
    o.value = String(value);
    o.textContent = text;
    return o;
  }
  function prepararFiltros() {
    const barra = $('buscarMovimiento')?.closest('.barra-filtros');
    if (!barra) return;
    barra.classList.add('hist29-barra');
    const etiquetaFecha = document.querySelector('label[for="filtroFecha"]');
    if (etiquetaFecha) etiquetaFecha.textContent = 'Desde';
    const botonLimpiar = $('botonLimpiarFiltros');
    for (const [t,id,l] of [['input','hist29Hasta','Hasta'], ['select','hist29Usuario','Responsable'], ['select','hist29Ubicacion','Ubicación']]) {
      const grupo = crearFiltro(t,id,l);
      barra.insertBefore(grupo, botonLimpiar || null);
    }
    $('hist29Usuario').append(opcion('', 'Todos los responsables'));
    $('hist29Ubicacion').append(opcion('', 'Todas las ubicaciones'));

    const table = $('tablaMovimientos')?.closest('table');
    if (table && !table.querySelector('.hist29-saldo-header')) {
      const th = hacer('th','hist29-saldo-header','Saldo final');
      const responsable = [...table.querySelectorAll('thead th')].find(x => x.textContent.trim() === 'Responsable');
      if (responsable) responsable.before(th);
    }
    const controles = hacer('div','hist29-paginacion');
    controles.id = 'hist29Paginacion';
    controles.append(
      hacer('span','hist29-paginas-texto',''),
      hacer('button','boton boton-borde','Anterior'),
      hacer('span','hist29-num-pagina',''),
      hacer('button','boton boton-borde','Siguiente')
    );
    const botones = controles.querySelectorAll('button');
    botones[0].type = botones[1].type = 'button';
    botones[0].addEventListener('click', () => { if (estado.pagina > 1) { estado.pagina--; cargar(); } });
    botones[1].addEventListener('click', () => { if (estado.pagina < estado.paginas) { estado.pagina++; cargar(); } });
    $('contenedorTablaMovimientos').after(controles);
    const dlg = document.createElement('dialog');
    dlg.id = 'hist29Detalle';
    dlg.className = 'hist29-detalle';
    dlg.innerHTML = '<header class="hist29-detalle-header"><h2>Detalle del movimiento</h2><button type="button" class="boton boton-borde" id="hist29Cerrar">Cerrar</button></header><div id="hist29DetalleCuerpo"></div>';
    document.body.appendChild(dlg);
    $('hist29Cerrar').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', ev => { if (ev.target === dlg) dlg.close(); });
  }
  function poblar(select, registros, id, label) {
    const valor = select.value;
    select.replaceChildren(select.firstElementChild.cloneNode(true));
    registros.forEach(x => select.append(opcion(x[id],x[label])));
    if ([...select.options].some(x => x.value === valor)) select.value = valor;
  }
  function configurarUbicaciones(ubicaciones) {
    const select = $('hist29Ubicacion');
    const idTienda = $('filtroUbicacionMovimiento').value;
    const valor = select.value;
    select.replaceChildren(opcion('', 'Todas las ubicaciones'));
    ubicaciones.filter(x => !idTienda || String(x.id_tienda) === idTienda)
      .forEach(x => select.append(opcion(x.id_ubicacion, `${x.tienda} / ${x.nombre}`)));
    if ([...select.options].some(x => x.value === valor)) select.value = valor;
  }
  async function cargarOpciones() {
    const data = await pedir(`${API}?accion=opciones`);
    const tiendas = $('filtroUbicacionMovimiento');
    tiendas.replaceChildren(opcion('', 'Todas las tiendas'));
    data.tiendas.forEach(x => tiendas.append(opcion(x.id_tienda, x.nombre)));
    poblar($('hist29Usuario'), data.usuarios, 'id_usuario','nombre');
    configurarUbicaciones(data.ubicaciones);
    tiendas.addEventListener('change', () => configurarUbicaciones(data.ubicaciones));
    prepararExportacion(data.puede_exportar);
  }
  function prepararExportacion(permitido) {
    const boton = $('botonExportarMovimientos');
    if (!boton) return;
    boton.hidden = !permitido;
    if (!permitido) return;
    const cont = hacer('div', 'hist29-exportar');
    boton.parentNode.insertBefore(cont, boton);
    cont.append(boton);
    boton.type = 'button';
    boton.setAttribute('aria-haspopup', 'true');
    boton.setAttribute('aria-expanded', 'false');
    boton.textContent = 'Exportar  ▾';
    const menu = hacer('div', 'hist29-menu-exportar');
    menu.hidden = true;
    menu.setAttribute('role', 'menu');
    const items = [
      ['Excel (.xlsx)', 'xlsx', 'fa-solid fa-file-excel'],
      ['PDF (.pdf)', 'pdf', 'fa-solid fa-file-pdf'],
      ['Imprimir', 'imprimir', 'fa-solid fa-print']
    ];
    items.forEach(([texto, accion, icono]) => {
      const control = hacer('button', 'hist29-opcion-exportar');
      control.type = 'button';
      control.setAttribute('role','menuitem');
      const iconoElemento = hacer('i', icono);
      iconoElemento.setAttribute('aria-hidden', 'true');
      control.append(iconoElemento, document.createTextNode(texto));
      control.addEventListener('click', () => {
        cerrar();
        exportarFormato(accion);
      });
      menu.append(control);
    });
    cont.append(menu);
    function cerrar() { menu.hidden = true; boton.setAttribute('aria-expanded', 'false'); }
    boton.addEventListener('click', () => {
      if (menu.hidden) { menu.hidden = false; boton.setAttribute('aria-expanded','true'); }
      else cerrar();
    });
    document.addEventListener('click', (ev) => { if (!cont.contains(ev.target)) cerrar(); });
    cont.addEventListener('keydown', ev => { if (ev.key === 'Escape') { cerrar(); boton.focus(); } });
  }
  async function exportarFormato(accion) {
    if ($('filtroFecha').value && $('hist29Hasta').value && $('filtroFecha').value > $('hist29Hasta').value) {
      mostrarInfo('La fecha inicial no puede ser posterior a la fecha final.', true); return;
    }
    const url = query(accion);
    if (accion === 'imprimir') {
      const ventana = window.open(url,'_blank');
      if (!ventana) mostrarInfo('El navegador bloqueó la ventana. Permite ventanas emergentes para imprimir.',true);
      return;
    }
    mostrarInfo('Preparando ' + (accion === 'xlsx' ? 'Excel' : 'PDF') + ' con los filtros seleccionados...');
    try {
      const response = await fetch(url,{credentials:'same-origin',cache:'no-store'});
      const tipo = response.headers.get('content-type') || '';
      if (!response.ok || tipo.includes('application/json')) {
        const err = await response.json().catch(() => ({}));
        throw new Error(err.message || 'No se pudo generar el archivo.');
      }
      const blob = await response.blob();
      const tmp = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = tmp;
      a.download = `VMM_Historial_Movimientos_${new Date().toLocaleDateString('sv-SE',{timeZone:'America/Costa_Rica'})}.${accion}`;
      document.body.appendChild(a);a.click();a.remove();
      setTimeout(() => URL.revokeObjectURL(tmp), 120000);
      mostrarInfo('Archivo descargado. Se respetaron los filtros del historial.');
    } catch(err) { mostrarInfo(err.message || 'No se pudo exportar.',true); }
  }
  function celda(tr, contenido, className = '') {
    const td = hacer('td',className, contenido);
    tr.appendChild(td);
    return td;
  }
  function renderizar(data) {
    const tbody = $('tablaMovimientos');
    tbody.replaceChildren();
    estado.filas = data.movimientos || [];
    estado.total = Number(data.total || 0);
    estado.pagina = Number(data.pagina || 1);
    estado.paginas = Number(data.paginas || 1);
    const vacio = $('mensajeSinMovimientos');
    const sinResultados = $('mensajeSinResultadosMovimientos');
    const tabla = $('contenedorTablaMovimientos');
    const sinFilas = estado.filas.length === 0;
    tabla.hidden = sinFilas;
    vacio.hidden = !sinFilas;
    sinResultados.hidden = true;
    if (sinFilas) {
      vacio.querySelector('h3').textContent = 'No hay movimientos para esta búsqueda';
      vacio.querySelector('p').textContent = 'Modifica los filtros o selecciona otra fecha.';
    }
    for (const fila of estado.filas) {
      const tr = document.createElement('tr');
      const sitios = ubicacionesMovimiento(fila);
      celda(tr, fecha(fila.fecha));
      celda(tr, referencia(fila));
      celda(tr, `${fila.producto} (${fila.codigo_retail || 'sin código retail'})`);
      celda(tr, tipos[fila.tipo_movimiento] || fila.tipo_movimiento);
      celda(tr, sitios.origen);
      celda(tr, sitios.destino);
      celda(tr, String(fila.cantidad),'hist29-numero');
      const s = celda(tr, saldo(fila),'hist29-numero');
      if (saldo(fila) === '—') s.title = 'El movimiento histórico no tiene un saldo registrado.';
      if (String(fila.tipo_movimiento).toUpperCase() === 'TRASLADO' && s.textContent.includes('O:'))
        s.title = 'O: saldo final en origen; D: saldo final en destino';
      if ((fila.saldo_origen_resultante !== null && Number(fila.saldo_origen_resultante)<0) ||
          (fila.saldo_destino_resultante !== null && Number(fila.saldo_destino_resultante)<0)) s.classList.add('hist29-negativo');
      celda(tr, fila.responsable || '—');
      const acciones = celda(tr, '');
      const boton = hacer('button','boton boton-borde hist29-ver','Ver');
      boton.type='button';
      boton.setAttribute('aria-label', `Ver movimiento ${referencia(fila)} de ${fila.producto}`);
      boton.addEventListener('click', () => verDetalle(fila));
      acciones.append(boton);
      tbody.appendChild(tr);
    }
    const controles = $('hist29Paginacion');
    controles.hidden = estado.total === 0;
    controles.querySelector('.hist29-paginas-texto').textContent = `${estado.total} registros`;
    controles.querySelector('.hist29-num-pagina').textContent = `Página ${estado.pagina} de ${estado.paginas}`;
    const buttons = controles.querySelectorAll('button');
    buttons[0].disabled = estado.pagina <= 1;
    buttons[1].disabled = estado.pagina >= estado.paginas;
    mostrarInfo(estado.total ? `${estado.total} movimiento(s) encontrado(s).` : 'Sin movimientos para los filtros seleccionados.');
  }
  function verDetalle(fila) {
    const cuerpo = $('hist29DetalleCuerpo');
    cuerpo.replaceChildren();
    const sitios = ubicacionesMovimiento(fila);
    const datos = [
      ['Referencia',referencia(fila)], ['Fecha y hora',fecha(fila.fecha)],
      ['Producto',fila.producto], ['Código retail',fila.codigo_retail || '—'],
      ['Tipo',tipos[fila.tipo_movimiento] || fila.tipo_movimiento],
      ['Cantidad',fila.cantidad], ['Origen',sitios.origen],
      ['Destino',sitios.destino],
      ['Saldo anterior en origen',fila.saldo_origen_anterior],
      ['Saldo final en origen',fila.saldo_origen_resultante],
      ['Saldo anterior en destino',fila.saldo_destino_anterior],
      ['Saldo final en destino',fila.saldo_destino_resultante],
      ['Responsable',fila.responsable || '—'], ['Estado',fila.estado],
      ['Motivo',fila.motivo], ['Observaciones',fila.observaciones],
    ];
    const dl = hacer('dl','hist29-datos');
    datos.forEach(([etiqueta,valor]) => {
      const wrap = hacer('div','hist29-campo-detalle');
      wrap.append(hacer('dt','',etiqueta),hacer('dd','',valor === null || valor === '' ? '—' : String(valor)));
      dl.append(wrap);
    });
    cuerpo.append(dl);
    $('hist29Detalle').showModal();
  }
  async function cargar() {
    if (estado.controlador) estado.controlador.abort();
    estado.controlador = new AbortController();
    const controlador = estado.controlador;
    mostrarInfo('Cargando movimientos...');
    try {
      const data = await pedir(query('listar'), controlador.signal);
      if (estado.controlador !== controlador) return;
      renderizar(data);
    } catch (err) {
      if (err.name === 'AbortError') return;
      mostrarInfo(err.message, true);
      $('tablaMovimientos').replaceChildren();
      $('contenedorTablaMovimientos').hidden = true;
      $('hist29Paginacion').hidden = true;
    }
  }
  function iniciarEventos() {
    let temporizador;
    const actualizar = () => {
      clearTimeout(temporizador);
      estado.pagina = 1;
      temporizador = setTimeout(cargar, 250);
    };
    ['buscarMovimiento','filtroTipoMovimiento','filtroUbicacionMovimiento','filtroFecha',
      'hist29Hasta','hist29Usuario','hist29Ubicacion'].forEach(id => {
        const el = $(id);
        el?.addEventListener(el?.tagName === 'INPUT' && el.type === 'search' ? 'input' : 'change', actualizar);
      });
    $('botonLimpiarFiltros')?.addEventListener('click', () => {
      ['buscarMovimiento','filtroTipoMovimiento','filtroUbicacionMovimiento','filtroFecha',
        'hist29Hasta','hist29Usuario','hist29Ubicacion'].forEach(id => { if ($(id)) $(id).value = ''; });
      estado.pagina = 1;
      cargar();
    });
  }
  async function iniciar() {
    if (!$('tablaMovimientos') || !$('buscarMovimiento')) return;
    prepararFiltros();
    iniciarEventos();
    try { await cargarOpciones(); await cargar(); }
    catch (err) { mostrarInfo(err.message, true); }
  }
})();
