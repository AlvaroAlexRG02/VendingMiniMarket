/*
 * Reportes — HU-49 (ventas), HU-50 (utilidad), HU-51 (inventario),
 * HU-52 (rotación, bajas y pérdidas).
 *
 * Consume api/reportes/reportes.php. El alcance por rol (todas las tiendas
 * para Administrador/Gerente, solo las asignadas para Encargado) lo impone
 * la API; aquí solo se muestra lo que la API permite.
 */
(function () {
    'use strict';

    const API = '../api/reportes/reportes.php';

    const $ = (id) => document.getElementById(id);

    const REPORTES = {
        ventas: {
            titulo: 'Ventas por tienda y periodo',
            descripcion: 'Compara el desempeño comercial de cada punto de venta.',
            filtros: ['tienda', 'periodo', 'proveedor', 'productos']
        },
        utilidad: {
            titulo: 'Utilidad bruta estimada',
            descripcion: 'Ingresos por ventas confirmadas menos el costo de los productos vendidos.',
            filtros: ['tienda', 'periodo', 'proveedor', 'productos']
        },
        inventario: {
            titulo: 'Inventario por ubicación',
            descripcion: 'Existencias actuales por tienda, bodega y exhibición, con los productos críticos primero.',
            filtros: ['tienda', 'proveedor', 'productos', 'estado']
        },
        rotacion: {
            titulo: 'Rotación, bajas y pérdidas',
            descripcion: 'Lo que más y menos se vende, y el valor estimado de lo que se pierde.',
            filtros: ['tienda', 'periodo', 'proveedor', 'productos']
        },
        cierre: {
            titulo: 'Cierre operativo diario',
            descripcion: 'Consolida ventas, movimientos, diferencias y alertas del día y bloquea cambios posteriores.',
            filtros: ['tienda', 'dia']
        }
    };

    const ETIQUETA_ESTADO = {
        NORMAL: ['Normal', 'verde'],
        AGOTADO: ['Agotado', 'rojo'],
        BAJO_MINIMO: ['Bajo mínimo', 'naranja'],
        NEGATIVO: ['Negativo', 'rojo']
    };

    let tipoActual = 'ventas';
    let catalogos = null;
    let secuencia = 0;

    document.addEventListener('DOMContentLoaded', iniciar);

    /* ---------- utilidades ---------- */

    const {
        escapar, moneda, monedaCorta, entero, porcentaje, fechaLocal, formatearFecha, fechaCorta,
        barras, columnas, columnasAgrupadas, dona, separador
    } = window.VMComun;

    const PALETA = ['color-azul', 'color-verde', 'color-naranja', 'color-morado', 'color-teal', 'color-rojo'];

    function mensaje(texto, tipo = 'error') {
        const caja = $('mensajeReportes');
        if (!caja) return;
        caja.textContent = texto;
        caja.className = `mensaje-producto mensaje-${tipo === 'exito' ? 'exito' : 'error'}`;
        caja.hidden = !texto;
    }

    async function enviar(accion, cuerpo) {
        const respuesta = await fetch(`${API}?accion=${accion}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(cuerpo)
        });
        const json = await respuesta.json().catch(() => null);

        if (!respuesta.ok || !json || !json.success) {
            throw new Error((json && json.message) || 'No se pudo completar la operación.');
        }

        return json;
    }

    async function pedir(accion, parametros = {}) {
        const consulta = new URLSearchParams({ accion });
        Object.entries(parametros).forEach(([clave, valor]) => {
            if (valor !== '' && valor !== null && valor !== undefined && valor !== 0 && valor !== '0') {
                consulta.set(clave, valor);
            }
        });

        const respuesta = await fetch(`${API}?${consulta}`, { credentials: 'same-origin' });
        const json = await respuesta.json().catch(() => null);

        if (!respuesta.ok || !json || !json.success) {
            throw new Error((json && json.message) || 'No se pudo generar el reporte.');
        }

        return json.data;
    }

    /* ---------- piezas visuales ---------- */

    function tarjeta(titulo, valor, detalle, icono, color = '') {
        return `
            <article class="tarjeta-estadistica ${color ? `borde-${color}` : ''}">
                <div class="tarjeta-estadistica-encabezado">
                    <div class="tarjeta-estadistica-icono"><i class="fa-solid ${icono}"></i></div>
                    <div>
                        <div class="tarjeta-estadistica-titulo">${escapar(titulo)}</div>
                        <div class="tarjeta-estadistica-valor">${valor}</div>
                    </div>
                </div>
                <div class="tarjeta-estadistica-cambio">${escapar(detalle)}</div>
            </article>`;
    }

    function panel(titulo, descripcion, contenido, acciones = '') {
        return `
            <section class="panel panel-resultado-reporte">
                <div class="panel-encabezado">
                    <div>
                        <h2>${escapar(titulo)}</h2>
                        ${descripcion ? `<p class="descripcion-panel">${escapar(descripcion)}</p>` : ''}
                    </div>
                    ${acciones}
                </div>
                ${contenido}
            </section>`;
    }

    function aviso(texto, icono = 'fa-circle-info', clase = '') {
        return `<div class="aviso-reporte ${clase}"><i class="fa-solid ${icono}"></i><span>${texto}</span></div>`;
    }

    /**
     * columnas: [{ titulo, campo, formato?(valor, fila) => html, exportar?(valor, fila) => texto, numerica? }]
     * Devuelve el HTML con TODAS las filas; paginarTablas() las reparte de 15 en 15 en pantalla.
     */
    function tabla(id, titulo, columnas, filas, opciones = {}) {
        if (!filas.length) {
            return `<div class="estado-vacio-reporte"><i class="fa-solid fa-folder-open"></i><span>${escapar(opciones.vacio || 'No hay datos para mostrar.')}</span></div>`;
        }

        const encabezado = columnas.map((c) => `<th scope="col"${c.numerica ? ' class="columna-numerica"' : ''}>${escapar(c.titulo)}</th>`).join('');
        const cuerpo = filas.map((fila) => `<tr>${columnas.map((c) => {
            const valor = fila[c.campo];
            const html = c.formato ? c.formato(valor, fila) : escapar(valor ?? '-');
            return `<td${c.numerica ? ' class="columna-numerica"' : ''}>${html}</td>`;
        }).join('')}</tr>`).join('');

        return `
            <div class="contenedor-tabla">
                <table class="tabla-datos tabla-reporte" id="${id}">
                    <thead><tr>${encabezado}</tr></thead>
                    <tbody>${cuerpo}</tbody>
                </table>
            </div>`;
    }

    /*
     * Pagina en pantalla (15 por página) las tablas de resultados ya insertadas en `raiz`.
     * Todas las filas siguen en el DOM; solo se ocultan las de otras páginas. Antes de
     * imprimir se muestran todas y se ocultan los controles; el PDF lo genera la API.
     */
    let tablasPaginadas = [];

    function navDe(contenedor) {
        const nav = contenedor.nextElementSibling;
        return nav?.classList.contains('paginador') ? nav : null;
    }

    function paginarTablas(raiz) {
        raiz.querySelectorAll('table.tabla-reporte').forEach((tablaHtml) => {
            const filas = [...tablaHtml.tBodies[0].rows];
            const contenedor = tablaHtml.closest('.contenedor-tabla');
            if (filas.length <= 15 || !contenedor) return;

            const pag = Paginador.crear({ despuesDe: contenedor, unidad: 'filas', alCambiar: mostrar });

            function mostrar() {
                const pagina = new Set(pag.paginar(filas));
                filas.forEach((fila) => { fila.style.display = pagina.has(fila) ? '' : 'none'; });
            }

            mostrar();
            tablasPaginadas.push({ filas, contenedor, mostrar });
        });
    }

    window.addEventListener('beforeprint', () => {
        tablasPaginadas = tablasPaginadas.filter((t) => t.contenedor.isConnected);
        tablasPaginadas.forEach(({ filas, contenedor }) => {
            filas.forEach((fila) => { fila.style.display = ''; });
            const nav = navDe(contenedor);
            if (nav) nav.style.display = 'none';
        });
    });

    window.addEventListener('afterprint', () => {
        tablasPaginadas.forEach(({ contenedor, mostrar }) => {
            if (!contenedor.isConnected) return;
            mostrar();
            const nav = navDe(contenedor);
            if (nav) nav.style.display = '';
        });
    });

    const colMoneda = (titulo, campo) => ({
        titulo, campo, numerica: true,
        formato: (v) => moneda(v)
    });

    const colEntero = (titulo, campo) => ({
        titulo, campo, numerica: true,
        formato: (v) => entero(v)
    });

    const colUtilidad = (titulo, campo) => ({
        titulo, campo, numerica: true,
        formato: (v) => `<strong class="${Number(v) < 0 ? 'texto-perdida' : 'texto-ganancia'}">${moneda(v)}</strong>`
    });

    const colMargen = (titulo, campo) => ({
        titulo, campo, numerica: true,
        formato: (v) => porcentaje(v)
    });

    const colTexto = (titulo, campo) => ({ titulo, campo, formato: (v) => escapar(v ?? '-') });

    /* ---------- reportes ---------- */

    const grilla = (...paneles) => `<div class="grilla-graficos">${paneles.join('')}</div>`;

    const bloque = (titulo, icono, contenido) => separador(titulo, icono) + contenido;

    // Una columna por cada día del periodo (los días sin ventas quedan en cero).
    function serieDiaria(porDia, inicio, fin) {
        const valores = new Map(porDia.map((x) => [String(x.fecha).slice(0, 10), x.ingresos]));
        const serie = [];
        const cursor = new Date(`${inicio}T00:00:00`);
        const limite = new Date(`${fin}T00:00:00`);

        while (cursor <= limite && serie.length < 93) {
            const dia = fechaLocal(cursor);
            serie.push({ etiqueta: fechaCorta(dia), valor: valores.get(dia) || 0, texto: monedaCorta(valores.get(dia) || 0) });
            cursor.setDate(cursor.getDate() + 1);
        }

        return serie;
    }

    function reporteVentas(d, f) {
        const r = d.resumen;
        const notas = [d.nayax_disponible
            ? aviso('Incluye ventas Nayax y ventas manuales confirmadas.')
            : aviso('Incluye ventas manuales confirmadas. Las ventas Nayax se sumarán automáticamente cuando la integración esté activa.')];

        if (r.lineas_sin_costo > 0) {
            notas.push(aviso(`${entero(r.lineas_sin_costo)} línea(s) vendida(s) no tienen costo registrado; se valoraron en ₡0.`, 'fa-triangle-exclamation', 'aviso-advertencia'));
        }

        const kpis = `
            <section class="grilla-estadisticas">
                ${tarjeta('Ventas (sin IVA)', moneda(r.ingresos), `${moneda(r.total)} con IVA`, 'fa-coins', 'verde')}
                ${tarjeta('Unidades vendidas', entero(r.unidades), `${entero(r.ventas)} venta(s) confirmada(s)`, 'fa-box-open')}
                ${tarjeta('Costo asociado', moneda(r.costo), 'Costo de los productos vendidos', 'fa-receipt', 'naranja')}
                ${tarjeta('Venta promedio', moneda(r.ventas ? r.ingresos / r.ventas : 0), 'Ingreso por venta', 'fa-chart-simple', 'morado')}
            </section>`;

        const graficos = grilla(
            panel('Ventas por día', 'Ingresos diarios del periodo (sin IVA).',
                columnas(serieDiaria(d.por_dia, f.fecha_inicio, f.fecha_fin), { color: 'color-azul' })),
            panel('Participación por tienda', 'Cuánto aporta cada tienda a los ingresos.',
                dona(d.por_tienda.map((t, i) => ({ etiqueta: t.tienda, valor: t.ingresos, texto: moneda(t.ingresos), color: PALETA[i % PALETA.length] })),
                    { valor: monedaCorta(r.ingresos), texto: 'ingresos' }))
        );

        const detalle =
            panel('Ventas por tienda', 'Resumen numérico de cada punto de venta.',
                tabla('tablaVentasTienda', 'Ventas por tienda', [
                    colTexto('Tienda', 'tienda'), colEntero('Ventas', 'ventas'), colEntero('Unidades', 'unidades'),
                    colMoneda('Ingresos', 'ingresos'), colMoneda('Total con IVA', 'total'), colMoneda('Costo', 'costo')
                ], d.por_tienda, { vacio: 'No hay ventas confirmadas con estos filtros.' })) +
            panel('Detalle por producto', 'Unidades, ingresos y costo de cada producto vendido.',
                tabla('tablaVentasDetalle', 'Detalle de ventas por producto', [
                    colTexto('Tienda', 'tienda'), colTexto('Producto', 'producto'), colTexto('Categoría', 'categoria'),
                    colEntero('Unidades', 'unidades'), colMoneda('Ingresos', 'ingresos'), colMoneda('IVA', 'impuesto'),
                    colMoneda('Total', 'total'), colMoneda('Costo', 'costo'), colTexto('Origen', 'fuente')
                ], d.detalle, { vacio: 'No hay ventas confirmadas con estos filtros.' }));

        return notas.join('') + bloque('Resumen del periodo', 'fa-gauge-high', kpis) +
            bloque('Análisis', 'fa-chart-pie', graficos) + bloque('Detalle', 'fa-table-list', detalle);
    }

    function reporteUtilidad(d) {
        const r = d.resumen;
        const notas = [aviso('Utilidad bruta estimada = ingresos (sin IVA) − costo de los productos vendidos. Solo ventas confirmadas.')];

        if (r.lineas_sin_costo > 0) {
            notas.push(aviso(`${entero(r.lineas_sin_costo)} línea(s) vendida(s) no tienen costo registrado; la utilidad de esas líneas puede estar sobrevalorada.`, 'fa-triangle-exclamation', 'aviso-advertencia'));
        }

        const kpis = `
            <section class="grilla-estadisticas">
                ${tarjeta('Ingresos', moneda(r.ingresos), 'Ventas confirmadas sin IVA', 'fa-coins')}
                ${tarjeta('Costo de lo vendido', moneda(r.costo), 'Costo de los productos', 'fa-receipt', 'naranja')}
                ${tarjeta('Utilidad bruta', moneda(r.utilidad), 'Ingresos − costo', 'fa-sack-dollar', r.utilidad < 0 ? 'rojo' : 'verde')}
                ${tarjeta('Margen', porcentaje(r.margen), 'Utilidad sobre ingresos', 'fa-percent', 'morado')}
            </section>`;

        const graficos = grilla(
            panel('Ingresos, costo y utilidad por tienda', 'Compara la rentabilidad de cada tienda en el periodo.',
                columnasAgrupadas(
                    d.por_tienda.map((t) => ({ etiqueta: t.tienda, valores: [t.ingresos, t.costo, t.utilidad] })),
                    [{ nombre: 'Ingresos', color: 'color-azul' }, { nombre: 'Costo', color: 'color-naranja' }, { nombre: 'Utilidad', color: 'color-verde' }]
                )),
            panel('Aporte a la utilidad', 'Qué parte de la utilidad bruta genera cada tienda.',
                dona(d.por_tienda.map((t, i) => ({ etiqueta: t.tienda, valor: Math.max(0, t.utilidad), texto: `${moneda(t.utilidad)} · ${porcentaje(t.margen)} margen`, color: PALETA[i % PALETA.length] })),
                    { valor: monedaCorta(r.utilidad), texto: 'utilidad' }))
        );

        const detalle =
            panel('Utilidad por tienda', 'Detalle numérico de cada tienda.',
                tabla('tablaUtilidadTienda', 'Utilidad por tienda', [
                    colTexto('Tienda', 'tienda'), colEntero('Unidades', 'unidades'), colMoneda('Ingresos', 'ingresos'),
                    colMoneda('Costo', 'costo'), colUtilidad('Utilidad', 'utilidad'), colMargen('Margen', 'margen')
                ], d.por_tienda, { vacio: 'No hay ventas confirmadas con estos filtros.' })) +
            panel('Utilidad por producto', 'Productos que más y menos aportan a la utilidad.',
                tabla('tablaUtilidadDetalle', 'Utilidad por producto', [
                    colTexto('Tienda', 'tienda'), colTexto('Producto', 'producto'), colTexto('Categoría', 'categoria'),
                    colEntero('Unidades', 'unidades'), colMoneda('Ingresos', 'ingresos'), colMoneda('Costo', 'costo'),
                    colUtilidad('Utilidad', 'utilidad'), colMargen('Margen', 'margen')
                ], d.detalle, { vacio: 'No hay ventas confirmadas con estos filtros.' }));

        return notas.join('') + bloque('Resumen del periodo', 'fa-gauge-high', kpis) +
            bloque('Análisis', 'fa-chart-pie', graficos) + bloque('Detalle', 'fa-table-list', detalle);
    }

    function insigniaEstado(estado) {
        const [texto, color] = ETIQUETA_ESTADO[estado] || [estado, 'gris'];
        return `<span class="badge badge-${color}">${texto}</span>`;
    }

    function reporteInventario(d) {
        const r = d.resumen;
        const criticos = r.agotados + r.bajo_minimo + r.negativos;
        const normales = Math.max(0, r.lineas - criticos);

        const notas = [];
        if (criticos > 0) {
            notas.push(aviso(`Hay ${entero(criticos)} producto(s) críticos: ${entero(r.agotados)} agotado(s), ${entero(r.bajo_minimo)} bajo el mínimo y ${entero(r.negativos)} con saldo negativo.`, 'fa-triangle-exclamation', 'aviso-advertencia'));
        }

        const kpis = `
            <section class="grilla-estadisticas">
                ${tarjeta('Unidades en stock', entero(r.unidades), `${entero(r.ubicaciones)} ubicación(es) · ${entero(r.lineas)} línea(s)`, 'fa-boxes-stacked')}
                ${tarjeta('Agotados', entero(r.agotados), 'Existencia en cero', 'fa-ban', 'rojo')}
                ${tarjeta('Bajo mínimo', entero(r.bajo_minimo), 'Requieren reposición', 'fa-arrow-trend-down', 'naranja')}
                ${tarjeta('Saldo negativo', entero(r.negativos), 'Revisar movimientos', 'fa-circle-exclamation', r.negativos ? 'rojo' : 'verde')}
            </section>`;

        const graficos = grilla(
            panel('Stock por ubicación', 'Unidades disponibles en tiendas, bodegas y exhibiciones.',
                barras(d.por_ubicacion.map((u) => ({ etiqueta: u.ubicacion, valor: u.unidades, texto: `${entero(u.unidades)} u. · ${entero(u.criticos)} críticos` })), 'azul')),
            panel('Estado del inventario', 'Cómo se reparten los productos según su existencia.',
                dona([
                    { etiqueta: 'Normal', valor: normales, color: 'color-verde' },
                    { etiqueta: 'Bajo mínimo', valor: r.bajo_minimo, color: 'color-naranja' },
                    { etiqueta: 'Agotado', valor: r.agotados, color: 'color-rojo' },
                    { etiqueta: 'Negativo', valor: r.negativos, color: 'color-morado' }
                ], { valor: entero(r.lineas), texto: 'productos' }))
        );

        const detalle =
            panel('Detalle de existencias', 'Los productos críticos aparecen primero.',
                tabla('tablaInventarioDetalle', 'Detalle de existencias', [
                    colTexto('Tienda', 'tienda'), colTexto('Ubicación', 'ubicacion'), colTexto('Producto', 'producto'),
                    colTexto('Categoría', 'categoria'), colEntero('Existencia', 'cantidad'), colEntero('Mínimo', 'stock_minimo'),
                    { titulo: 'Estado', campo: 'estado', formato: (v) => insigniaEstado(v) }
                ], d.detalle, { vacio: 'No hay existencias con estos filtros.' }));

        return notas.join('') + bloque('Resumen', 'fa-gauge-high', kpis) +
            bloque('Análisis', 'fa-chart-pie', graficos) + bloque('Detalle', 'fa-table-list', detalle);
    }

    function reporteRotacion(d) {
        const r = d.resumen;
        const notas = [aviso('Las pérdidas incluyen bajas por vencimiento, daño, robo y pérdida, valoradas al costo vigente. Las devoluciones se muestran como baja pero no cuentan como pérdida.')];

        const kpis = `
            <section class="grilla-estadisticas">
                ${tarjeta('Unidades vendidas', entero(r.unidades_vendidas), 'En el periodo', 'fa-cart-shopping', 'verde')}
                ${tarjeta('Productos sin ventas', entero(r.productos_sin_venta), 'Con stock y sin rotación', 'fa-hourglass-half', 'naranja')}
                ${tarjeta('Unidades dadas de baja', entero(r.unidades_baja), `${moneda(r.valor_bajas)} en total`, 'fa-trash-can', 'morado')}
                ${tarjeta('Pérdida estimada', moneda(r.valor_perdidas), 'Valor económico de las pérdidas', 'fa-triangle-exclamation', r.valor_perdidas > 0 ? 'rojo' : 'verde')}
            </section>`;

        const graficos = grilla(
            panel('Más vendidos', 'Los 10 productos de mayor rotación.',
                barras(d.mas_vendidos.slice(0, 10).map((p) => ({ etiqueta: `${p.producto} · ${p.tienda}`, valor: p.unidades, texto: `${entero(p.unidades)} u.` })), 'verde')),
            panel('Bajas y pérdidas por motivo', 'Valor económico estimado de lo dado de baja.',
                dona(d.bajas_por_motivo.map((m, i) => ({ etiqueta: m.motivo, valor: m.valor, texto: `${moneda(m.valor)} · ${entero(m.unidades)} u.`, color: PALETA[(i + 2) % PALETA.length] })),
                    { valor: monedaCorta(r.valor_bajas), texto: 'en bajas' }))
        );

        const columnasRanking = [
            colTexto('Tienda', 'tienda'), colTexto('Producto', 'producto'), colTexto('Categoría', 'categoria'),
            colEntero('Unidades vendidas', 'unidades'), colMoneda('Ingresos', 'ingresos'), colEntero('Stock actual', 'stock')
        ];

        const detalle =
            panel('Ranking de más vendidos', 'Mayor rotación por tienda y periodo.',
                tabla('tablaMasVendidos', 'Más vendidos', columnasRanking, d.mas_vendidos, { vacio: 'No hubo ventas confirmadas en el periodo.' })) +
            panel('Menos vendidos', 'Productos con stock y la menor rotación del periodo.',
                tabla('tablaMenosVendidos', 'Menos vendidos', columnasRanking, d.menos_vendidos, { vacio: 'No hay productos con stock para analizar.' })) +
            panel('Detalle de bajas', 'Cada baja confirmada del periodo.',
                tabla('tablaBajasDetalle', 'Detalle de bajas', [
                    { titulo: 'Fecha', campo: 'fecha', formato: (v) => formatearFecha(v) },
                    colTexto('Tienda', 'tienda'), colTexto('Ubicación', 'ubicacion'), colTexto('Producto', 'producto'),
                    colTexto('Motivo', 'motivo'), colEntero('Cantidad', 'cantidad'), colMoneda('Costo unitario', 'costo_unitario'), colMoneda('Valor estimado', 'valor')
                ], d.bajas, { vacio: 'No se registraron bajas en el periodo.' }));

        return notas.join('') + bloque('Resumen del periodo', 'fa-gauge-high', kpis) +
            bloque('Análisis', 'fa-chart-pie', graficos) + bloque('Detalle', 'fa-table-list', detalle);
    }

    /* ---------- HU-53: cierre diario ---------- */

    function fechaHora(iso) {
        return iso ? new Date(iso).toLocaleString('es-CR') : '-';
    }

    const ETIQUETA_ACCION = { CIERRE: 'Cierre', RECIERRE: 'Nuevo cierre', REAPERTURA: 'Reapertura' };

    function bannerCierre(d) {
        const c = d.cierre;
        const fecha = formatearFecha(d.resumen.fecha);

        if (!c) {
            return aviso(`El día ${fecha} está abierto. Revisa el consolidado y confirma el cierre cuando termine la jornada.`);
        }

        if (c.estado === 'CERRADO') {
            return aviso(`Día ${fecha} cerrado por <strong>${escapar(c.usuario_cierra)}</strong> el ${escapar(fechaHora(c.fecha_cierre))} (versión ${c.version}). Ya no se pueden registrar ni modificar ventas o movimientos de esta tienda en ese día.`, 'fa-lock', 'aviso-cierre-cerrado');
        }

        return aviso(`El cierre del ${fecha} fue reabierto por un Administrador. Corrige lo necesario y vuelve a cerrar el día.`, 'fa-lock-open', 'aviso-advertencia');
    }

    /** multi = { sufijo, tienda } cuando se muestran varias tiendas: solo lectura, sin confirmar ni reabrir. */
    function reporteCierre(d, multi = null) {
        const suf = multi ? multi.sufijo : '';
        const r = d.resumen;
        const t = r.ventas.totales;
        const movimientos = r.movimientos.reduce((n, m) => n + m.movimientos, 0);
        const unidadesMov = r.movimientos.reduce((n, m) => n + m.unidades, 0);
        const avisos = [bannerCierre(d)];

        if (r.ventas_sin_confirmar.cantidad > 0) {
            avisos.push(aviso(`${entero(r.ventas_sin_confirmar.cantidad)} venta(s) manual(es) por ${moneda(r.ventas_sin_confirmar.total)} no tienen su movimiento de inventario confirmado y no se cuentan en el consolidado.`, 'fa-triangle-exclamation', 'aviso-advertencia'));
        }

        if (r.movimientos_pendientes > 0) {
            avisos.push(aviso(`Hay ${entero(r.movimientos_pendientes)} movimiento(s) pendientes de confirmar en este día.`, 'fa-triangle-exclamation', 'aviso-advertencia'));
        }

        const kpis = `
            <section class="grilla-estadisticas">
                ${tarjeta('Ventas del día', moneda(t.total), `${entero(t.unidades)} unidad(es) · ${entero(t.ventas)} venta(s)`, 'fa-cash-register', 'verde')}
                ${tarjeta('Movimientos de inventario', entero(movimientos), `${entero(unidadesMov)} unidad(es) movidas`, 'fa-right-left')}
                ${tarjeta('Diferencias', entero(r.diferencias.cantidad), 'Ventas vs. salidas de inventario', 'fa-scale-unbalanced', r.diferencias.cantidad ? 'rojo' : 'verde')}
                ${tarjeta('Alertas', entero(r.alertas.pendientes), `${entero(r.alertas.generadas)} generada(s) en el día`, 'fa-bell', r.alertas.pendientes ? 'naranja' : 'verde')}
            </section>`;

        const ventas = panel('Ventas del día', 'Ventas confirmadas por origen.',
            tabla('tablaCierreVentas' + suf, 'Ventas del día', [
                { titulo: 'Origen', campo: 'fuente', formato: (v) => (v === 'NAYAX' ? 'Nayax' : 'Manual')},
                colEntero('Ventas', 'ventas'), colEntero('Unidades', 'unidades'), colMoneda('Subtotal', 'subtotal'),
                colMoneda('IVA', 'impuesto'), colMoneda('Total', 'total'), colMoneda('Costo', 'costo')
            ], r.ventas.por_fuente, { vacio: 'No hubo ventas confirmadas en este día.' }));

        const movs = panel('Movimientos de inventario', 'Entradas, salidas, ajustes, bajas y traslados del día.',
            tabla('tablaCierreMovimientos' + suf, 'Movimientos del día', [
                colTexto('Tipo', 'tipo'),
                { titulo: 'Estado', campo: 'estado', formato: (v) => `<span class="badge badge-${v === 'CONFIRMADO' ? 'verde' : 'naranja'}">${v === 'CONFIRMADO' ? 'Confirmado' : 'Pendiente'}</span>`},
                colEntero('Movimientos', 'movimientos'), colEntero('Unidades', 'unidades')
            ], r.movimientos, { vacio: 'No hubo movimientos de inventario en este día.' }));

        const difs = panel('Diferencias del día', 'Unidades vendidas que no coinciden con las salidas de inventario registradas por venta.',
            tabla('tablaCierreDiferencias' + suf, 'Diferencias del día', [
                colTexto('Producto', 'producto'), colEntero('Vendidas', 'vendida'), colEntero('Salidas de inventario', 'salida'),
                {
                    titulo: 'Diferencia', campo: 'diferencia', numerica: true,
                    formato: (v) => `<strong class="texto-perdida">${v > 0 ? '+' : ''}${entero(v)}</strong>`
                }
            ], r.diferencias.detalle, { vacio: 'Sin diferencias: las ventas coinciden con las salidas de inventario.' }));

        let accion = '';
        if (d.puede_cerrar) {
            const obligatoria = r.diferencias.cantidad > 0;
            accion = panel('Confirmar cierre del día', 'Al confirmar se guarda el consolidado, el usuario, la fecha y la hora, y se bloquean los cambios de este día.', `
                <div class="grupo-formulario">
                    <label for="observacionesCierre">Observaciones${obligatoria ? ' (obligatorias: hay diferencias)' : ' (opcional)'}</label>
                    <textarea id="observacionesCierre" rows="3" maxlength="1000" placeholder="Explica las diferencias o cualquier novedad de la jornada"></textarea>
                </div>
                <div class="acciones-cierre-reporte">
                    <button type="button" class="boton boton-azul" data-accion-cierre="confirmar" data-obligatoria="${obligatoria ? '1' : '0'}">
                        <i class="fa-solid fa-lock"></i> Confirmar cierre del día
                    </button>
                </div>`);
        } else if (d.puede_reabrir) {
            accion = panel('Reabrir cierre', 'Solo un Administrador puede reabrir un día cerrado. Quedará registrado en la bitácora.', `
                <div class="grupo-formulario">
                    <label for="justificacionReapertura">Justificación (obligatoria)</label>
                    <textarea id="justificacionReapertura" rows="3" maxlength="1000" placeholder="Explica por qué es necesario corregir o generar un nuevo cierre"></textarea>
                </div>
                <div class="acciones-cierre-reporte">
                    <button type="button" class="boton boton-borde" data-accion-cierre="reabrir" data-cierre="${d.cierre.id_cierre}">
                        <i class="fa-solid fa-lock-open"></i> Reabrir cierre
                    </button>
                </div>`);
        }

        const observacion = d.cierre && d.cierre.observaciones
            ? panel('Observaciones del cierre', '', `<p class="texto-observaciones-cierre">${escapar(d.cierre.observaciones)}</p>`)
            : '';

        const eventos = d.eventos.length
            ? panel('Historial de este cierre', 'Cierres, reaperturas y nuevos cierres.',
                tabla('tablaCierreEventos', 'Historial del cierre', [
                    { titulo: 'Acción', campo: 'accion', formato: (v) => escapar(ETIQUETA_ACCION[v] || v)},
                    colEntero('Versión', 'version'), colTexto('Usuario', 'usuario'),
                    { titulo: 'Fecha y hora', campo: 'fecha', formato: (v) => escapar(fechaHora(v))},
                    { titulo: 'Justificación / observaciones', campo: 'justificacion', formato: (v, f) => escapar(v || f.observaciones || '-')}
                ], d.eventos))
            : '';

        const tiposMov = {};
        r.movimientos.forEach((m) => { tiposMov[m.tipo] = (tiposMov[m.tipo] || 0) + m.movimientos; });
        const grafico = panel('Movimientos por tipo', 'Cómo se reparte la actividad de inventario del día.',
            dona(Object.entries(tiposMov).map(([tipo, cantidad], i) => ({ etiqueta: tipo, valor: cantidad, texto: entero(cantidad), color: PALETA[i % PALETA.length] })),
                { valor: entero(movimientos), texto: 'movimientos' }));

        if (multi) {
            return bloque(`Tienda: ${multi.tienda}`, 'fa-store',
                avisos.join('') + kpis + ventas + movs + difs);
        }

        const cierre = observacion + eventos + accion;

        return avisos.join('') +
            bloque('Resumen del día', 'fa-gauge-high', kpis) +
            bloque('Consolidado', 'fa-layer-group', ventas + (movimientos ? grilla(movs, grafico) : movs) + difs) +
            (cierre ? bloque('Cierre', 'fa-lock', cierre) : '') +
            '<div id="historialCierres"></div>';
    }

    async function cargarHistorialCierres() {
        const contenedor = $('historialCierres');
        if (!contenedor) return;

        try {
            const filas = await pedir('cierre_historial', { id_tienda: idTiendaUnica() });
            contenedor.innerHTML = separador('Historial', 'fa-clock-rotate-left') + panel('Historial de cierres', 'Últimos 30 días. Usa "Ver" para abrir el consolidado de un día.',
                tabla('tablaHistorialCierres', 'Historial de cierres', [
                    { titulo: 'Día', campo: 'fecha', formato: (v) => formatearFecha(v)},
                    colTexto('Tienda', 'tienda'),
                    { titulo: 'Estado', campo: 'estado', formato: (v) => `<span class="badge badge-${v === 'CERRADO' ? 'verde' : 'naranja'}">${v === 'CERRADO' ? 'Cerrado' : 'Reabierto'}</span>`},
                    colEntero('Versión', 'version'), colTexto('Cerrado por', 'usuario_cierra'),
                    { titulo: 'Fecha y hora', campo: 'fecha_cierre', formato: (v) => escapar(fechaHora(v))},
                    colMoneda('Ventas', 'ventas_total'), colEntero('Diferencias', 'diferencias'), colEntero('Reaperturas', 'reaperturas'),
                    {
                        titulo: '', campo: 'id_cierre',
                        formato: (v, f) => `<div class="acciones-tabla"><button type="button" class="boton-accion accion-ver" data-accion-cierre="ver" data-tienda="${f.id_tienda}" data-fecha="${f.fecha}" title="Ver cierre" aria-label="Ver cierre del ${escapar(f.fecha)}"><i class="fa-solid fa-eye"></i></button></div>`
                    }
                ], filas, { vacio: 'Todavía no hay cierres registrados.' }));
            paginarTablas(contenedor);
        } catch (error) {
            contenedor.innerHTML = '';
        }
    }

    async function manejarAccionCierre(evento) {
        const boton = evento.target.closest('[data-accion-cierre]');
        if (!boton) return;

        const accion = boton.dataset.accionCierre;

        if (accion === 'ver') {
            establecerSelector('tiendaReporte', [boton.dataset.tienda]);
            $('fechaCierreReporte').value = boton.dataset.fecha;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            await generar();
            return;
        }

        try {
            if (accion === 'confirmar') {
                const observaciones = $('observacionesCierre').value.trim();

                if (boton.dataset.obligatoria === '1' && observaciones.length < 5) {
                    mensaje('Hay diferencias en el día: registra una observación que las explique antes de cerrar.');
                    $('observacionesCierre').focus();
                    return;
                }

                if (!window.confirm('¿Confirmar el cierre del día? Después de cerrarlo no se podrán registrar ni modificar ventas o movimientos de esta tienda en ese día.')) {
                    return;
                }

                boton.disabled = true;
                const resultado = await enviar('cierre_confirmar', {
                    id_tienda: Number(idTiendaUnica()),
                    fecha: $('fechaCierreReporte').value,
                    observaciones
                });
                await generar();
                mensaje(resultado.message, 'exito');
            } else if (accion === 'reabrir') {
                const justificacion = $('justificacionReapertura').value.trim();

                if (justificacion.length < 10) {
                    mensaje('Indica una justificación de al menos 10 caracteres para reabrir el cierre.');
                    $('justificacionReapertura').focus();
                    return;
                }

                if (!window.confirm('¿Reabrir este cierre? Quedará registrado en la bitácora con tu justificación.')) {
                    return;
                }

                boton.disabled = true;
                const resultado = await enviar('cierre_reabrir', { id_cierre: Number(boton.dataset.cierre), justificacion });
                await generar();
                mensaje(resultado.message, 'exito');
            }
        } catch (error) {
            boton.disabled = false;
            mensaje(error.message);
        }
    }

    const DIBUJAR = {
        cierre: reporteCierre,
        ventas: reporteVentas,
        utilidad: reporteUtilidad,
        inventario: reporteInventario,
        rotacion: reporteRotacion
    };

    /* ---------- filtros ---------- */

    function leerFiltros() {
        return {
            id_tiendas: valoresSelector('tiendaReporte').join(','),
            id_tienda: idTiendaUnica(),
            fecha_inicio: $('fechaInicioReporte').value,
            fecha_fin: $('fechaFinReporte').value,
            ids_proveedores: valoresSelector('proveedorReporte').join(','),
            ids_productos: valoresSelector('productosReporte').join(','),
            estado: $('estadoReporte').value
        };
    }

    /* ---------- selector múltiple (casillas dentro de un desplegable) ---------- */

    /** Ids marcados; arreglo vacío = "Todos". */
    function valoresSelector(id) {
        return [...$(id).querySelectorAll('input[data-item]:checked')].map((c) => c.value);
    }

    function resumenSelector(contenedor) {
        const marcados = [...contenedor.querySelectorAll('input[data-item]:checked')];
        const texto = contenedor.querySelector('.selector-multiple-texto');

        if (contenedor.dataset.deshabilitado) {
            texto.textContent = contenedor.dataset.textoDeshabilitado;
        } else if (!marcados.length) {
            texto.textContent = contenedor.dataset.textoTodos;
        } else if (marcados.length === 1) {
            texto.textContent = marcados[0].closest('label').textContent.trim();
        } else {
            texto.textContent = `${marcados.length} ${contenedor.dataset.plural} seleccionad${contenedor.dataset.plural.endsWith('as') ? 'as' : 'os'}`;
        }
    }

    function establecerSelector(id, valores) {
        const contenedor = $(id);
        const elegidos = new Set(valores.map(String));

        contenedor.querySelectorAll('input[data-item]').forEach((c) => { c.checked = elegidos.has(c.value); });
        contenedor.querySelector('input[data-todos]').checked = valoresSelector(id).length === 0;
        resumenSelector(contenedor);
    }

    /** Redibuja las opciones conservando las que siguen existiendo marcadas. */
    function llenarOpcionesSelector(id, items, valor, texto) {
        const contenedor = $(id);
        const previos = new Set(valoresSelector(id));
        const lista = contenedor.querySelector('.selector-multiple-opciones');

        lista.innerHTML = items.map((i) => `
            <label class="selector-multiple-opcion">
                <input type="checkbox" data-item value="${i[valor]}" ${previos.has(String(i[valor])) ? 'checked' : ''}><span>${escapar(i[texto])}</span>
            </label>`).join('');

        contenedor.querySelector('input[data-todos]').checked = valoresSelector(id).length === 0;
        resumenSelector(contenedor);
    }

    /** Bloquea el selector (con un texto guía) o lo vuelve a habilitar. */
    function bloquearSelector(id, bloqueado, textoGuia = '') {
        const contenedor = $(id);

        if (bloqueado) {
            contenedor.dataset.deshabilitado = '1';
            contenedor.dataset.textoDeshabilitado = textoGuia;
            contenedor.querySelector('details').open = false;
        } else {
            delete contenedor.dataset.deshabilitado;
        }

        contenedor.classList.toggle('selector-multiple-bloqueado', bloqueado);
        resumenSelector(contenedor);
    }

    function crearSelectorMultiple(contenedor, textoTodos, items, valor, texto, plural = 'elementos', conBusqueda = false) {
        contenedor.dataset.textoTodos = textoTodos;
        contenedor.dataset.plural = plural;
        contenedor.innerHTML = `
            <details>
                <summary><span class="selector-multiple-texto">${escapar(textoTodos)}</span><i class="fa-solid fa-chevron-down"></i></summary>
                <div class="selector-multiple-lista">
                    ${conBusqueda ? '<input type="search" class="selector-multiple-buscar" placeholder="Buscar..." autocomplete="off">' : ''}
                    <label class="selector-multiple-opcion selector-multiple-todos">
                        <input type="checkbox" data-todos checked><span>${escapar(textoTodos)}</span>
                    </label>
                    <div class="selector-multiple-opciones"></div>
                </div>
            </details>`;

        llenarOpcionesSelector(contenedor.id, items, valor, texto);

        contenedor.querySelector('summary').addEventListener('click', (evento) => {
            if (contenedor.dataset.deshabilitado) evento.preventDefault();
        });

        contenedor.querySelector('.selector-multiple-buscar')?.addEventListener('input', (evento) => {
            const termino = evento.target.value.trim().toLowerCase();
            contenedor.querySelectorAll('.selector-multiple-opciones .selector-multiple-opcion').forEach((fila) => {
                fila.hidden = termino !== '' && !fila.textContent.toLowerCase().includes(termino);
            });
        });

        contenedor.addEventListener('change', (evento) => {
            const caja = evento.target;

            if (caja.matches('input[data-todos]')) {
                // "Todos" limpia la selección; no se puede desmarcar sin elegir un elemento.
                establecerSelector(contenedor.id, []);
            } else if (caja.matches('input[data-item]')) {
                contenedor.querySelector('input[data-todos]').checked = !valoresSelector(contenedor.id).length;
                resumenSelector(contenedor);
            }
        });

        document.addEventListener('click', (evento) => {
            const detalle = contenedor.querySelector('details');
            if (detalle && !contenedor.contains(evento.target)) detalle.open = false;
        });
    }

    /** Productos activos de los proveedores marcados; sin proveedores el selector queda bloqueado. */
    let secuenciaProductos = {};

    async function cargarProductosDeProveedores(idProveedores, idProductos) {
        const proveedores = valoresSelector(idProveedores);
        const numero = (secuenciaProductos[idProductos] = (secuenciaProductos[idProductos] || 0) + 1);

        if (!proveedores.length) {
            llenarOpcionesSelector(idProductos, [], 'id_producto', 'nombre');
            establecerSelector(idProductos, []);
            bloquearSelector(idProductos, true, 'Elige uno o más proveedores');
            return;
        }

        try {
            const productos = await pedir('productos_proveedor', { ids_proveedores: proveedores.join(',') });

            if (numero !== secuenciaProductos[idProductos]) return;

            bloquearSelector(idProductos, false);
            llenarOpcionesSelector(idProductos, productos, 'id_producto', 'nombre');
        } catch (error) {
            if (numero === secuenciaProductos[idProductos]) mensaje(error.message);
        }
    }

    function vincularProductosAProveedores(idProveedores, idProductos) {
        $(idProveedores).addEventListener('change', () => cargarProductosDeProveedores(idProveedores, idProductos));
        cargarProductosDeProveedores(idProveedores, idProductos);
    }

    function llenarSelect(select, primera, items, valor, texto) {
        select.innerHTML = (primera ? `<option value="">${escapar(primera)}</option>` : '') +
            items.map((i) => `<option value="${i[valor]}">${escapar(i[texto])}</option>`).join('');
    }


    function aplicarAtajo(atajo) {
        const hoy = new Date();
        let inicio = new Date(hoy);
        let fin = new Date(hoy);

        if (atajo === '7dias') {
            inicio.setDate(hoy.getDate() - 6);
        } else if (atajo === 'mes') {
            inicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        } else if (atajo === 'mesAnterior') {
            inicio = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
            fin = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        }

        $('fechaInicioReporte').value = fechaLocal(inicio);
        $('fechaFinReporte').value = fechaLocal(fin);
    }

    function actualizarFiltrosVisibles() {
        const visibles = REPORTES[tipoActual].filtros;

        document.querySelectorAll('[data-filtro]').forEach((elemento) => {
            elemento.hidden = !visibles.includes(elemento.dataset.filtro);
        });

        mostrarVista('reportes');
    }

    // Reportes y Resumen programado son vistas de la misma página.
    function mostrarVista(vista) {
        const esReporte = vista === 'reportes';

        $('vistaReportes').hidden = !esReporte;
        if ($('vistaResumen')) $('vistaResumen').hidden = vista !== 'resumen';

        // Imprimir solo aplica a los reportes (Exportar PDF vive junto a "Generar reporte").
        const botonImprimir = $('botonImprimirReporte');
        if (botonImprimir) botonImprimir.hidden = !esReporte;

        document.querySelectorAll('.pestana-reporte').forEach((boton) => {
            const activa = esReporte ? boton.dataset.reporte === tipoActual : boton.dataset.vista === vista;
            boton.classList.toggle('activa', activa);
            boton.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
    }

    /* ---------- generación ---------- */

    /** Id de la tienda cuando hay exactamente una elegida ('' si son varias o todas). */
    function idTiendaUnica() {
        const ids = valoresSelector('tiendaReporte');
        if (!ids.length && catalogos.tiendas.length === 1) return String(catalogos.tiendas[0].id_tienda);
        return ids.length === 1 ? ids[0] : '';
    }

    function textoTienda() {
        const nombres = [...$('tiendaReporte').querySelectorAll('input[data-item]:checked')]
            .map((c) => c.closest('label').textContent.trim());
        return nombres.length ? nombres.join(', ') : $('tiendaReporte').dataset.textoTodos;
    }

    function encabezadoResultado(f) {
        const usaPeriodo = REPORTES[tipoActual].filtros.includes('periodo');
        let periodo = 'Existencias actuales';
        if (usaPeriodo) {
            periodo = `${formatearFecha(f.fecha_inicio)} al ${formatearFecha(f.fecha_fin)}`;
        } else if (tipoActual === 'cierre') {
            periodo = `Día ${formatearFecha($('fechaCierreReporte').value)}`;
        }

        return `
            <header class="cabecera-resultado-reporte">
                <div class="cabecera-resultado-titulo">
                    <h2>${escapar(REPORTES[tipoActual].titulo)}</h2>
                    <p>${escapar(REPORTES[tipoActual].descripcion)}</p>
                </div>
                <ul class="cabecera-resultado-datos">
                    <li><i class="fa-solid fa-store"></i><span>Tienda</span><strong>${escapar(textoTienda())}</strong></li>
                    <li><i class="fa-regular fa-calendar"></i><span>Periodo</span><strong>${escapar(periodo)}</strong></li>
                    <li><i class="fa-regular fa-clock"></i><span>Generado</span><strong>${escapar(new Date().toLocaleString('es-CR'))}</strong></li>
                </ul>
            </header>`;
    }

    async function generar(evento) {
        evento?.preventDefault();

        const filtros = leerFiltros();
        const firma = firmaActual();
        const fechaCierre = $('fechaCierreReporte').value;
        const usaPeriodo = REPORTES[tipoActual].filtros.includes('periodo');

        actualizarBotonExportar();

        if (tipoActual === 'cierre') {
            await generarCierre(filtros, firma);
            return;
        }

        if (usaPeriodo && filtros.fecha_inicio > filtros.fecha_fin) {
            mensaje('La fecha inicial no puede ser posterior a la final.');
            return;
        }

        mensaje('');
        const area = $('areaReporte');
        const numero = ++secuencia;
        area.classList.add('cargando-reporte');

        const consulta = { ...filtros };
        if (!usaPeriodo) {
            delete consulta.fecha_inicio;
            delete consulta.fecha_fin;
        }
        if (tipoActual !== 'inventario') delete consulta.estado;
        if (tipoActual !== 'cierre') delete consulta.id_tienda;

        try {
            const datos = await pedir(tipoActual, consulta);
            if (numero !== secuencia) return;

            area.innerHTML = encabezadoResultado(filtros) + DIBUJAR[tipoActual](datos, filtros);
            paginarTablas(area);
            registrarConsultaGenerada(filtros, firma, fechaCierre);
        } catch (error) {
            if (numero !== secuencia) return;
            area.innerHTML = '';
            mensaje(error.message);
        } finally {
            if (numero === secuencia) area.classList.remove('cargando-reporte');
        }
    }

    async function generarCierre(filtros, firma) {
        const fecha = $('fechaCierreReporte').value;

        if (!fecha) {
            mensaje('Selecciona el día a cerrar.');
            return;
        }

        const elegidas = valoresSelector('tiendaReporte');
        const tiendas = catalogos.tiendas.filter((t) => !elegidas.length || elegidas.includes(String(t.id_tienda)));

        mensaje('');
        const area = $('areaReporte');
        const numero = ++secuencia;
        area.classList.add('cargando-reporte');

        try {
            if (tiendas.length === 1) {
                const datos = await pedir('cierre_preparar', { id_tienda: tiendas[0].id_tienda, fecha });
                if (numero !== secuencia) return;

                filtros = { ...filtros, id_tienda: String(tiendas[0].id_tienda) };
                area.innerHTML = encabezadoResultado(filtros) + reporteCierre(datos);
            } else {
                const respuestas = await Promise.all(tiendas.map((t) =>
                    pedir('cierre_preparar', { id_tienda: t.id_tienda, fecha }).then((datos) => ({ t, datos }))));
                if (numero !== secuencia) return;

                area.innerHTML = encabezadoResultado(filtros) + resumenCierreVarias(respuestas, fecha) +
                    respuestas.map(({ t, datos }) => reporteCierre(datos, { sufijo: `_${t.id_tienda}`, tienda: t.nombre })).join('') +
                    '<div id="historialCierres"></div>';
            }

            paginarTablas(area);
            registrarConsultaGenerada(filtros, firma, fecha);
            await cargarHistorialCierres();
        } catch (error) {
            if (numero !== secuencia) return;
            area.innerHTML = '';
            mensaje(error.message);
        } finally {
            if (numero === secuencia) area.classList.remove('cargando-reporte');
        }
    }

    /** Totales del día sumando todas las tiendas elegidas. */
    function resumenCierreVarias(respuestas, fecha) {
        const suma = (extraer) => respuestas.reduce((n, { datos }) => n + (Number(extraer(datos.resumen)) || 0), 0);
        const ventas = suma((r) => r.ventas.totales.total);
        const unidades = suma((r) => r.ventas.totales.unidades);
        const movimientos = suma((r) => r.movimientos.reduce((n, m) => n + m.movimientos, 0));
        const diferencias = suma((r) => r.diferencias.cantidad);
        const alertas = suma((r) => r.alertas.pendientes);
        const cerradas = respuestas.filter(({ datos }) => datos.cierre && datos.cierre.estado === 'CERRADO').length;

        return aviso(`Cierre del ${formatearFecha(fecha)} de ${respuestas.length} tiendas (${cerradas} cerrada(s)). Para confirmar o reabrir un cierre, elige una sola tienda.`, 'fa-store') +
            bloque('Resumen de todas las tiendas', 'fa-gauge-high', `
                <section class="grilla-estadisticas">
                    ${tarjeta('Ventas del día', moneda(ventas), `${entero(unidades)} unidad(es)`, 'fa-cash-register', 'verde')}
                    ${tarjeta('Movimientos de inventario', entero(movimientos), 'Todas las tiendas', 'fa-right-left')}
                    ${tarjeta('Diferencias', entero(diferencias), 'Ventas vs. salidas de inventario', 'fa-scale-unbalanced', diferencias ? 'rojo' : 'verde')}
                    ${tarjeta('Alertas', entero(alertas), 'Pendientes', 'fa-bell', alertas ? 'naranja' : 'verde')}
                </section>`);
    }

    /* ---------- exportar a PDF ---------- */

    /** Reporte generado por última vez: el PDF se arma con exactamente estos filtros. */
    let consultaGenerada = null;

    function firmaActual() {
        return JSON.stringify([tipoActual, leerFiltros(), $('fechaCierreReporte').value]);
    }

    function registrarConsultaGenerada(filtros, firma, fechaCierre) {
        consultaGenerada = { tipo: tipoActual, filtros: { ...filtros }, fechaCierre, firma };
        actualizarBotonExportar();
    }

    /** Solo se exporta lo que se ve en pantalla: si los filtros cambian, hay que generar de nuevo. */
    function actualizarBotonExportar() {
        const boton = $('botonExportarReporte');
        if (!boton) return;

        // El Encargado no exporta: el botón ni se muestra.
        boton.hidden = !catalogos.puede_exportar;
        if (boton.hidden) return;

        const variasEnCierre = tipoActual === 'cierre' && !idTiendaUnica() && !catalogos.puede_ver_todas && (valoresSelector('tiendaReporte').length || catalogos.tiendas.length) > 1;
        const vigente = Boolean(consultaGenerada) && consultaGenerada.firma === firmaActual() && consultaGenerada.tipo === tipoActual && !variasEnCierre;
        boton.disabled = !vigente;
        boton.title = vigente
            ? 'Descargar en PDF el reporte generado'
            : (variasEnCierre ? 'Solo Administración o Gerencia pueden exportar el cierre de varias tiendas: elige una sola' : consultaGenerada ? 'Los filtros cambiaron: genera el reporte de nuevo para exportarlo' : 'Genera el reporte para poder exportarlo');
    }

    async function exportarPdf() {
        if (!consultaGenerada || consultaGenerada.firma !== firmaActual()) return;

        const { tipo, filtros, fechaCierre } = consultaGenerada;
        const def = REPORTES[tipo];
        const parametros = new URLSearchParams({ accion: 'exportar_pdf', tipo });

        if (filtros.id_tiendas) parametros.set('id_tiendas', filtros.id_tiendas);
        if (filtros.id_tienda) parametros.set('id_tienda', filtros.id_tienda);

        if (def.filtros.includes('periodo')) {
            parametros.set('fecha_inicio', filtros.fecha_inicio);
            parametros.set('fecha_fin', filtros.fecha_fin);
        }

        if (tipo === 'cierre') parametros.set('fecha', fechaCierre);
        if (def.filtros.includes('proveedor') && filtros.ids_proveedores) parametros.set('ids_proveedores', filtros.ids_proveedores);
        if (def.filtros.includes('productos') && filtros.ids_productos) parametros.set('ids_productos', filtros.ids_productos);
        if (def.filtros.includes('estado') && filtros.estado) parametros.set('estado', filtros.estado);

        const boton = $('botonExportarReporte');
        const textoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando PDF…';
        mensaje('');

        try {
            const respuesta = await fetch(`${API}?${parametros}`, { credentials: 'same-origin' });

            if (!respuesta.ok || !(respuesta.headers.get('Content-Type') || '').includes('application/pdf')) {
                const json = await respuesta.json().catch(() => null);
                throw new Error((json && json.message) || 'No se pudo generar el PDF.');
            }

            const nombre = (respuesta.headers.get('Content-Disposition') || '').match(/filename="([^"]+)"/)?.[1] || `reporte-${tipo}.pdf`;
            const blob = await respuesta.blob();
            const enlace = document.createElement('a');
            enlace.href = URL.createObjectURL(blob);
            enlace.download = nombre;
            enlace.click();
            URL.revokeObjectURL(enlace.href);

            mensaje('El PDF se generó correctamente.', 'exito');
        } catch (error) {
            mensaje(error.message);
        } finally {
            boton.innerHTML = textoOriginal;
            actualizarBotonExportar();
        }
    }

    /* ---------- inicio ---------- */

    async function iniciar() {
        if (!$('formularioReporte')) return;

        try {
            catalogos = await pedir('filtros');
        } catch (error) {
            mensaje(error.message);
            $('areaReporte').innerHTML = '';
            return;
        }

        // Una, varias o todas las tiendas permitidas (Encargado: solo las que tiene asignadas).
        crearSelectorMultiple($('tiendaReporte'), catalogos.puede_ver_todas ? 'Todas las tiendas' : 'Todas mis tiendas', catalogos.tiendas, 'id_tienda', 'nombre', 'tiendas');
        if (catalogos.tiendas.length === 1) {
            establecerSelector('tiendaReporte', [catalogos.tiendas[0].id_tienda]);
            bloquearSelector('tiendaReporte', true, catalogos.tiendas[0].nombre);
        }

        crearSelectorMultiple($('proveedorReporte'), 'Todos los proveedores', catalogos.proveedores, 'id_proveedor', 'nombre', 'proveedores');
        crearSelectorMultiple($('productosReporte'), 'Todos los productos', [], 'id_producto', 'nombre', 'productos', true);
        vincularProductosAProveedores('proveedorReporte', 'productosReporte');
        aplicarAtajo('mes');
        $('fechaCierreReporte').value = fechaLocal(new Date());
        $('fechaCierreReporte').max = fechaLocal(new Date());

        // Acceso directo: reportes.php?reporte=cierre
        const pedido = new URLSearchParams(window.location.search).get('reporte');
        if (pedido && REPORTES[pedido] && !(pedido === 'utilidad' && !catalogos.puede_ver_utilidad)) {
            tipoActual = pedido;
        }

        actualizarFiltrosVisibles();

        $('formularioReporte').addEventListener('submit', generar);
        $('areaReporte').addEventListener('click', manejarAccionCierre);
        $('botonExportarReporte')?.addEventListener('click', exportarPdf);
        $('formularioReporte').addEventListener('input', actualizarBotonExportar);
        $('formularioReporte').addEventListener('change', actualizarBotonExportar);
        $('botonImprimirReporte')?.addEventListener('click', () => window.print());

        document.querySelectorAll('.atajo-periodo').forEach((boton) => {
            boton.addEventListener('click', () => {
                aplicarAtajo(boton.dataset.atajo);
                generar();
            });
        });

        document.querySelectorAll('.pestana-reporte[data-reporte]').forEach((boton) => {
            boton.addEventListener('click', () => {
                tipoActual = boton.dataset.reporte;
                actualizarFiltrosVisibles();
                generar();
            });
        });

        document.querySelectorAll('.pestana-reporte[data-vista]').forEach((boton) => {
            boton.addEventListener('click', () => mostrarVista(boton.dataset.vista));
        });

        $('botonLimpiarReporte')?.addEventListener('click', () => {
            $('formularioReporte').reset();
            establecerSelector('proveedorReporte', []);
            cargarProductosDeProveedores('proveedorReporte', 'productosReporte');
            establecerSelector('tiendaReporte', catalogos.tiendas.length === 1 ? [catalogos.tiendas[0].id_tienda] : []);
            aplicarAtajo('mes');
            $('fechaCierreReporte').value = fechaLocal(new Date());
            actualizarFiltrosVisibles();
            generar();
        });

        await generar();
    }
})();
