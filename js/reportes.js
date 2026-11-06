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
            filtros: ['tienda', 'periodo', 'busqueda', 'categoria', 'proveedor']
        },
        utilidad: {
            titulo: 'Utilidad bruta estimada',
            descripcion: 'Ingresos por ventas confirmadas menos el costo de los productos vendidos.',
            filtros: ['tienda', 'periodo', 'busqueda', 'categoria', 'proveedor', 'ubicacion']
        },
        inventario: {
            titulo: 'Inventario por ubicación',
            descripcion: 'Existencias actuales por tienda, bodega y exhibición, con los productos críticos primero.',
            filtros: ['tienda', 'busqueda', 'categoria', 'proveedor', 'ubicacion', 'estado']
        },
        rotacion: {
            titulo: 'Rotación, bajas y pérdidas',
            descripcion: 'Lo que más y menos se vende, y el valor estimado de lo que se pierde.',
            filtros: ['tienda', 'periodo', 'busqueda', 'categoria', 'proveedor']
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

    function reporteCierre(d) {
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
            tabla('tablaCierreVentas', 'Ventas del día', [
                { titulo: 'Origen', campo: 'fuente', formato: (v) => (v === 'NAYAX' ? 'Nayax' : 'Manual')},
                colEntero('Ventas', 'ventas'), colEntero('Unidades', 'unidades'), colMoneda('Subtotal', 'subtotal'),
                colMoneda('IVA', 'impuesto'), colMoneda('Total', 'total'), colMoneda('Costo', 'costo')
            ], r.ventas.por_fuente, { vacio: 'No hubo ventas confirmadas en este día.' }));

        const movs = panel('Movimientos de inventario', 'Entradas, salidas, ajustes, bajas y traslados del día.',
            tabla('tablaCierreMovimientos', 'Movimientos del día', [
                colTexto('Tipo', 'tipo'),
                { titulo: 'Estado', campo: 'estado', formato: (v) => `<span class="badge badge-${v === 'CONFIRMADO' ? 'verde' : 'naranja'}">${v === 'CONFIRMADO' ? 'Confirmado' : 'Pendiente'}</span>`},
                colEntero('Movimientos', 'movimientos'), colEntero('Unidades', 'unidades')
            ], r.movimientos, { vacio: 'No hubo movimientos de inventario en este día.' }));

        const difs = panel('Diferencias del día', 'Unidades vendidas que no coinciden con las salidas de inventario registradas por venta.',
            tabla('tablaCierreDiferencias', 'Diferencias del día', [
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
            const filas = await pedir('cierre_historial', { id_tienda: $('tiendaReporte').value });
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
            $('tiendaReporte').value = boton.dataset.tienda;
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
                    id_tienda: Number($('tiendaReporte').value),
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
            id_tienda: $('tiendaReporte').value,
            fecha_inicio: $('fechaInicioReporte').value,
            fecha_fin: $('fechaFinReporte').value,
            busqueda: $('busquedaReporte').value.trim(),
            id_categoria: $('categoriaReporte').value,
            id_proveedor: $('proveedorReporte').value,
            id_ubicacion: $('ubicacionReporte').value,
            estado: $('estadoReporte').value
        };
    }

    function llenarSelect(select, primera, items, valor, texto) {
        select.innerHTML = (primera ? `<option value="">${escapar(primera)}</option>` : '') +
            items.map((i) => `<option value="${i[valor]}">${escapar(i[texto])}</option>`).join('');
    }

    function llenarUbicaciones() {
        const idTienda = Number($('tiendaReporte').value) || 0;
        const ubicaciones = catalogos.ubicaciones.filter((u) => !idTienda || Number(u.id_tienda) === idTienda);
        llenarSelect($('ubicacionReporte'), 'Todas las ubicaciones', ubicaciones.map((u) => ({
            id_ubicacion: u.id_ubicacion, nombre: `${u.nombre} (${u.tipo})`
        })), 'id_ubicacion', 'nombre');
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

        // El cierre es siempre de una tienda concreta.
        const select = $('tiendaReporte');
        const opcionTodas = select.querySelector('option[value=""]');
        if (opcionTodas) opcionTodas.disabled = tipoActual === 'cierre';
        if (tipoActual === 'cierre' && !select.value && catalogos.tiendas.length) {
            select.value = catalogos.tiendas[0].id_tienda;
            llenarUbicaciones();
        }

        mostrarVista('reportes');
    }

    // Reportes, Conciliación y Resumen programado son vistas de la misma página.
    function mostrarVista(vista) {
        const esReporte = vista === 'reportes';

        $('vistaReportes').hidden = !esReporte;
        if ($('vistaConciliacion')) $('vistaConciliacion').hidden = vista !== 'conciliacion';
        if ($('vistaResumen')) $('vistaResumen').hidden = vista !== 'resumen';

        // Exportar PDF e Imprimir solo aplican a los reportes (el cierre se imprime, no se exporta).
        const botonExportar = $('botonExportarReporte');
        if (botonExportar) botonExportar.hidden = !esReporte;
        const botonImprimir = $('botonImprimirReporte');
        if (botonImprimir) botonImprimir.hidden = !esReporte;

        document.querySelectorAll('.pestana-reporte').forEach((boton) => {
            const activa = esReporte ? boton.dataset.reporte === tipoActual : boton.dataset.vista === vista;
            boton.classList.toggle('activa', activa);
            boton.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
    }

    /* ---------- generación ---------- */

    function textoTienda() {
        const select = $('tiendaReporte');
        return select.selectedOptions[0]?.textContent || 'Todas las tiendas';
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
        const usaPeriodo = REPORTES[tipoActual].filtros.includes('periodo');

        if (tipoActual === 'cierre') {
            await generarCierre(filtros);
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
        if (!['inventario', 'utilidad'].includes(tipoActual)) delete consulta.id_ubicacion;

        try {
            const datos = await pedir(tipoActual, consulta);
            if (numero !== secuencia) return;

            area.innerHTML = encabezadoResultado(filtros) + DIBUJAR[tipoActual](datos, filtros);
            paginarTablas(area);
        } catch (error) {
            if (numero !== secuencia) return;
            area.innerHTML = '';
            mensaje(error.message);
        } finally {
            if (numero === secuencia) area.classList.remove('cargando-reporte');
        }
    }

    async function generarCierre(filtros) {
        const fecha = $('fechaCierreReporte').value;

        if (!filtros.id_tienda) {
            mensaje('Selecciona la tienda cuyo día quieres cerrar.');
            return;
        }

        if (!fecha) {
            mensaje('Selecciona el día a cerrar.');
            return;
        }

        mensaje('');
        const area = $('areaReporte');
        const numero = ++secuencia;
        area.classList.add('cargando-reporte');

        try {
            const datos = await pedir('cierre_preparar', { id_tienda: filtros.id_tienda, fecha });
            if (numero !== secuencia) return;

            area.innerHTML = encabezadoResultado(filtros) + reporteCierre(datos);
            paginarTablas(area);
            await cargarHistorialCierres();
        } catch (error) {
            if (numero !== secuencia) return;
            area.innerHTML = '';
            mensaje(error.message);
        } finally {
            if (numero === secuencia) area.classList.remove('cargando-reporte');
        }
    }

    /* ---------- exportar a PDF ---------- */

    const TIPOS_EXPORTAR = {
        ventas: { titulo: 'Ventas', icono: 'fa-cash-register', color: 'color-azul', descripcion: 'Ingresos, unidades y costo por tienda y producto.', filtros: ['categoria', 'proveedor', 'busqueda'], detalle: 'productos vendidos', periodo: true },
        utilidad: { titulo: 'Utilidad', icono: 'fa-sack-dollar', color: 'color-verde', descripcion: 'Ingresos menos costo: utilidad y margen.', filtros: ['categoria', 'proveedor', 'busqueda'], detalle: 'utilidad por producto', periodo: true },
        inventario: { titulo: 'Inventario', icono: 'fa-boxes-stacked', color: 'color-naranja', descripcion: 'Existencias por ubicación y productos críticos.', filtros: ['categoria', 'proveedor', 'busqueda', 'estado'], detalle: 'existencias por producto', periodo: false },
        rotacion: { titulo: 'Rotación y pérdidas', icono: 'fa-arrows-rotate', color: 'color-morado', descripcion: 'Más y menos vendidos, bajas y pérdidas.', filtros: ['categoria', 'proveedor', 'busqueda', 'limite'], detalle: 'detalle de bajas', periodo: true },
        cierre: { titulo: 'Cierre diario', icono: 'fa-lock', color: 'color-teal', descripcion: 'Consolidado de un día de una tienda.', filtros: [], detalle: null, periodo: false, dia: true, unaTienda: true }
    };

    let tipoExportar = 'ventas';

    function iniciarModalExportar() {
        if (!$('modalExportarReporte')) return;

        const anio = new Date().getFullYear();
        $('anioExportarReporte').innerHTML = Array.from({ length: 6 }, (_, i) => anio - i)
            .map((a) => `<option value="${a}">${a}</option>`).join('');

        llenarSelect($('categoriaExportarReporte'), 'Todas las categorías', catalogos.categorias, 'id_categoria', 'nombre');
        llenarSelect($('proveedorExportarReporte'), 'Todos los proveedores', catalogos.proveedores, 'id_proveedor', 'nombre');

        const permitidos = Object.keys(TIPOS_EXPORTAR).filter((t) => t !== 'utilidad' || catalogos.puede_ver_utilidad);
        $('tiposExportarReporte').innerHTML = permitidos.map((t) => `
            <label class="exportar-tipo ${TIPOS_EXPORTAR[t].color}">
                <input type="radio" name="tipoExportarReporte" value="${t}">
                <i class="fa-solid ${TIPOS_EXPORTAR[t].icono}"></i>
                <strong>${escapar(TIPOS_EXPORTAR[t].titulo)}</strong>
                <span>${escapar(TIPOS_EXPORTAR[t].descripcion)}</span>
            </label>`).join('');

        $('tiposExportarReporte').addEventListener('change', (evento) => {
            if (evento.target.name === 'tipoExportarReporte') {
                tipoExportar = evento.target.value;
                actualizarCamposExportar();
            }
        });

        document.querySelectorAll('input[name="modoPeriodoExportar"]').forEach((radio) => {
            radio.addEventListener('change', actualizarCamposExportar);
        });

        $('formularioExportarReporte').addEventListener('submit', descargarPdf);
        $('botonCancelarExportarReporte').addEventListener('click', cerrarModalExportar);
        $('botonCerrarExportarReporte').addEventListener('click', cerrarModalExportar);
        document.querySelector('[data-cerrar-modal-exportar-reporte]').addEventListener('click', cerrarModalExportar);
        $('modalExportarReporte').addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarModalExportar(); });

        $('tiendasExportarReporte').addEventListener('change', (evento) => {
            const maestra = evento.target.closest('[data-todas]');
            const cajas = [...document.querySelectorAll('#tiendasExportarReporte input[data-tienda]')];

            if (maestra) {
                cajas.forEach((c) => { c.checked = maestra.checked; });
            } else {
                const todas = $('tiendasExportarReporte').querySelector('[data-todas]');
                if (todas) todas.checked = cajas.every((c) => c.checked);
            }
        });
    }

    function dibujarTiendasExportar() {
        const unaSola = TIPOS_EXPORTAR[tipoExportar].unaTienda;
        const seleccionada = $('tiendaReporte').value || (catalogos.tiendas[0] && String(catalogos.tiendas[0].id_tienda));
        const unica = catalogos.tiendas.length === 1;

        $('tituloTiendasExportar').textContent = unaSola ? 'Tienda' : 'Tiendas';

        if (unaSola) {
            $('tiendasExportarReporte').innerHTML = catalogos.tiendas.map((t) => `
                <label class="opcion-exportar">
                    <input type="radio" name="tiendaCierreExportar" data-tienda value="${t.id_tienda}"
                        ${String(t.id_tienda) === seleccionada ? 'checked' : ''} ${unica ? 'disabled' : ''}>
                    <span>${escapar(t.nombre)}</span>
                </label>`).join('');
            return;
        }

        const filtrada = $('tiendaReporte').value;
        $('tiendasExportarReporte').innerHTML =
            (catalogos.tiendas.length > 1
                ? `<label class="opcion-exportar opcion-exportar-todas"><input type="checkbox" data-todas ${filtrada ? '' : 'checked'}><span>Todas las tiendas</span></label>`
                : '') +
            catalogos.tiendas.map((t) => `
                <label class="opcion-exportar">
                    <input type="checkbox" data-tienda value="${t.id_tienda}"
                        ${unica || !filtrada || String(t.id_tienda) === filtrada ? 'checked' : ''} ${unica ? 'disabled' : ''}>
                    <span>${escapar(t.nombre)}</span>
                </label>`).join('');
    }

    function actualizarCamposExportar() {
        const tipo = TIPOS_EXPORTAR[tipoExportar];
        const modo = document.querySelector('input[name="modoPeriodoExportar"]:checked')?.value || 'mes';

        document.querySelectorAll('#tiposExportarReporte input').forEach((r) => {
            r.checked = r.value === tipoExportar;
            r.closest('.exportar-tipo').classList.toggle('seleccionado', r.checked);
        });

        document.querySelectorAll('[data-exportar="periodo"]').forEach((e) => { e.hidden = !tipo.periodo; });
        document.querySelectorAll('[data-exportar="dia"]').forEach((e) => { e.hidden = !tipo.dia; });
        document.querySelectorAll('[data-exportar="sin-periodo"]').forEach((e) => { e.hidden = tipo.periodo || tipo.dia; });
        document.querySelectorAll('[data-modo]').forEach((e) => { e.hidden = e.dataset.modo !== modo; });

        ['categoria', 'proveedor', 'busqueda', 'estado', 'limite'].forEach((campo) => {
            document.querySelectorAll(`[data-exportar="${campo}"]`).forEach((e) => { e.hidden = !tipo.filtros.includes(campo); });
        });

        document.querySelectorAll('[data-exportar="sin-filtros"]').forEach((e) => { e.hidden = tipo.filtros.length > 0; });
        document.querySelectorAll('[data-exportar="detalle"]').forEach((e) => { e.hidden = !tipo.detalle; });
        if (tipo.detalle) $('textoDetalleExportar').textContent = `(${tipo.detalle})`;

        dibujarTiendasExportar();
    }

    function abrirModalExportar() {
        if (!catalogos) return;

        tipoExportar = TIPOS_EXPORTAR[tipoActual] && (tipoActual !== 'utilidad' || catalogos.puede_ver_utilidad) ? tipoActual : 'ventas';

        // Valores iniciales tomados de lo que se está viendo en pantalla.
        const fechaBase = $('fechaInicioReporte').value ? new Date(`${$('fechaInicioReporte').value}T00:00:00`) : new Date();
        $('mesExportarReporte').value = String(fechaBase.getMonth() + 1).padStart(2, '0');
        $('anioExportarReporte').value = String(fechaBase.getFullYear());
        $('desdeExportarReporte').value = $('fechaInicioReporte').value;
        $('hastaExportarReporte').value = $('fechaFinReporte').value;
        $('diaExportarReporte').value = $('fechaCierreReporte').value || fechaLocal(new Date());
        $('diaExportarReporte').max = fechaLocal(new Date());

        $('categoriaExportarReporte').value = $('categoriaReporte').value;
        $('proveedorExportarReporte').value = $('proveedorReporte').value;
        $('busquedaExportarReporte').value = $('busquedaReporte').value;
        $('estadoExportarReporte').value = $('estadoReporte').value;
        $('mensajeExportarReporte').textContent = '';

        actualizarCamposExportar();
        $('modalExportarReporte').hidden = false;
    }

    function cerrarModalExportar() {
        $('modalExportarReporte').hidden = true;
    }

    async function descargarPdf(evento) {
        evento.preventDefault();

        const tipo = tipoExportar;
        const def = TIPOS_EXPORTAR[tipo];
        const tiendas = [...document.querySelectorAll('#tiendasExportarReporte input[data-tienda]:checked')].map((c) => c.value);
        const error = (texto) => { $('mensajeExportarReporte').textContent = texto; };

        if (!tiendas.length) return error(def.unaTienda ? 'Selecciona la tienda.' : 'Selecciona al menos una tienda.');

        const parametros = new URLSearchParams({ accion: 'exportar_pdf', tipo });

        if (def.unaTienda) {
            parametros.set('id_tienda', tiendas[0]);
            parametros.set('id_tiendas', tiendas[0]);
        } else {
            parametros.set('id_tiendas', tiendas.join(','));
        }

        if (def.periodo) {
            const modo = document.querySelector('input[name="modoPeriodoExportar"]:checked').value;

            if (modo === 'mes') {
                parametros.set('mes', `${$('anioExportarReporte').value}-${$('mesExportarReporte').value}`);
            } else {
                const desde = $('desdeExportarReporte').value;
                const hasta = $('hastaExportarReporte').value;

                if (!desde || !hasta) return error('Indica las fechas del rango.');
                if (desde > hasta) return error('La fecha inicial no puede ser posterior a la final.');

                parametros.set('fecha_inicio', desde);
                parametros.set('fecha_fin', hasta);
            }
        }

        if (def.dia) {
            if (!$('diaExportarReporte').value) return error('Selecciona el día del cierre.');
            parametros.set('fecha', $('diaExportarReporte').value);
        }

        if (def.filtros.includes('categoria') && $('categoriaExportarReporte').value) parametros.set('id_categoria', $('categoriaExportarReporte').value);
        if (def.filtros.includes('proveedor') && $('proveedorExportarReporte').value) parametros.set('id_proveedor', $('proveedorExportarReporte').value);
        if (def.filtros.includes('busqueda') && $('busquedaExportarReporte').value.trim()) parametros.set('busqueda', $('busquedaExportarReporte').value.trim());
        if (def.filtros.includes('estado') && $('estadoExportarReporte').value) parametros.set('estado', $('estadoExportarReporte').value);
        if (def.filtros.includes('limite')) parametros.set('limite', $('limiteExportarReporte').value);
        if (def.detalle) parametros.set('detalle', $('detalleExportarReporte').checked ? '1' : '0');

        const boton = $('botonGenerarExportarReporte');
        boton.disabled = true;
        error('Generando el PDF…');

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

            cerrarModalExportar();
            mensaje('El PDF se generó correctamente.', 'exito');
        } catch (e) {
            error(e.message);
        } finally {
            boton.disabled = false;
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

        const selectTienda = $('tiendaReporte');
        llenarSelect(selectTienda, catalogos.puede_elegir_tienda ? 'Todas las tiendas' : '', catalogos.tiendas, 'id_tienda', 'nombre');
        selectTienda.disabled = !catalogos.puede_elegir_tienda;

        llenarSelect($('categoriaReporte'), 'Todas las categorías', catalogos.categorias, 'id_categoria', 'nombre');
        llenarSelect($('proveedorReporte'), 'Todos los proveedores', catalogos.proveedores, 'id_proveedor', 'nombre');
        llenarUbicaciones();
        aplicarAtajo('mes');
        $('fechaCierreReporte').value = fechaLocal(new Date());
        $('fechaCierreReporte').max = fechaLocal(new Date());

        // Acceso directo: reportes.php?reporte=cierre
        const pedido = new URLSearchParams(window.location.search).get('reporte');
        if (pedido && REPORTES[pedido] && !(pedido === 'utilidad' && !catalogos.puede_ver_utilidad)) {
            tipoActual = pedido;
        }

        actualizarFiltrosVisibles();

        selectTienda.addEventListener('change', llenarUbicaciones);
        $('formularioReporte').addEventListener('submit', generar);
        $('areaReporte').addEventListener('click', manejarAccionCierre);
        $('botonExportarReporte')?.addEventListener('click', abrirModalExportar);
        iniciarModalExportar();
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
            if (catalogos.puede_elegir_tienda) selectTienda.value = '';
            llenarUbicaciones();
            aplicarAtajo('mes');
            $('fechaCierreReporte').value = fechaLocal(new Date());
            actualizarFiltrosVisibles();
            generar();
        });

        await generar();
    }
})();
