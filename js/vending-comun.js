/*
 * Utilidades comunes de reportes y dashboard: formato de números/fechas y
 * gráficos SVG sin librerías externas (columnas, columnas agrupadas, dona y barras).
 *
 * Los colores salen del CSS (clases .color-*), así los gráficos siguen la
 * paleta del proyecto.
 */
(function () {
    'use strict';

    const ESPACIO = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

    function escapar(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (c) => ESPACIO[c]);
    }

    function moneda(valor) {
        return '₡' + Number(valor || 0).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function monedaCorta(valor) {
        const n = Number(valor || 0);
        const abs = Math.abs(n);
        if (abs >= 1e6) return `₡${(n / 1e6).toLocaleString('es-CR', { maximumFractionDigits: 1 })} M`;
        if (abs >= 1e3) return `₡${(n / 1e3).toLocaleString('es-CR', { maximumFractionDigits: 1 })} mil`;
        return `₡${n.toLocaleString('es-CR', { maximumFractionDigits: 0 })}`;
    }

    function entero(valor) {
        return Number(valor || 0).toLocaleString('es-CR', { maximumFractionDigits: 0 });
    }

    function porcentaje(valor) {
        return Number(valor || 0).toLocaleString('es-CR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
    }

    function fechaLocal(fecha = new Date()) {
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${fecha.getFullYear()}-${mes}-${dia}`;
    }

    function formatearFecha(iso) {
        if (!iso) return '-';
        const [a, m, d] = String(iso).slice(0, 10).split('-');
        return `${d}/${m}/${a}`;
    }

    function fechaCorta(iso) {
        if (!iso) return '-';
        const [, m, d] = String(iso).slice(0, 10).split('-');
        return `${d}/${m}`;
    }

    /* ---------- gráficos ---------- */

    function sinDatos(texto = 'Sin datos para graficar en este periodo.') {
        return `<p class="grafico-vacio">${escapar(texto)}</p>`;
    }

    /** Barras horizontales: items = [{ etiqueta, valor, texto, color? }] */
    function barras(items, color = '') {
        const maximo = Math.max(...items.map((i) => Math.abs(i.valor)), 0);

        if (!items.length || maximo === 0) return sinDatos();

        return `<div class="barras-reporte">${items.map((item) => `
            <div class="barra-reporte">
                <div class="barra-reporte-etiqueta" title="${escapar(item.etiqueta)}">${escapar(item.etiqueta)}</div>
                <div class="barra-reporte-pista">
                    <div class="barra-reporte-relleno ${item.valor < 0 ? 'negativa' : (item.color || color)}"
                         style="width:${item.valor === 0 ? 0 : Math.max(2, Math.abs(item.valor) / maximo * 100)}%"></div>
                </div>
                <div class="barra-reporte-valor">${escapar(item.texto)}</div>
            </div>`).join('')}</div>`;
    }

    /**
     * Columnas verticales (una serie): items = [{ etiqueta, valor, texto }]
     * opciones: { color, formatoEje }
     */
    function columnas(items, opciones = {}) {
        const maximo = Math.max(...items.map((i) => i.valor), 0);
        if (!items.length || maximo === 0) return sinDatos();

        const ancho = 720;
        const alto = 240;
        const margen = { arriba: 24, abajo: 34, izquierda: 8, derecha: 8 };
        const util = { w: ancho - margen.izquierda - margen.derecha, h: alto - margen.arriba - margen.abajo };
        const paso = util.w / items.length;
        const grosor = Math.min(54, paso * 0.62);
        const cadaN = Math.ceil(items.length / 12);
        const conValores = items.filter((i) => i.valor > 0).length <= 12;
        const formato = opciones.formatoEje || monedaCorta;

        const columnasSvg = items.map((item, i) => {
            const h = item.valor <= 0 ? 0 : Math.max(2, item.valor / maximo * util.h);
            const x = margen.izquierda + paso * i + (paso - grosor) / 2;
            const y = margen.arriba + util.h - h;
            const etiqueta = i % cadaN === 0
                ? `<text class="grafico-eje" x="${x + grosor / 2}" y="${alto - 12}" text-anchor="middle">${escapar(item.etiqueta)}</text>`
                : '';
            const valor = conValores && item.valor > 0
                ? `<text class="grafico-valor" x="${x + grosor / 2}" y="${y - 6}" text-anchor="middle">${escapar(item.texto ?? formato(item.valor))}</text>`
                : '';

            return `<g><title>${escapar(item.etiqueta)}: ${escapar(item.texto ?? formato(item.valor))}</title>
                <rect class="grafico-columna ${opciones.color || 'color-azul'}" x="${x}" y="${y}" width="${grosor}" height="${h}" rx="4"></rect>
                ${valor}${etiqueta}</g>`;
        }).join('');

        return `<svg class="grafico-svg" viewBox="0 0 ${ancho} ${alto}" role="img" aria-label="Gráfico de columnas" preserveAspectRatio="xMidYMid meet">
            <line class="grafico-base" x1="${margen.izquierda}" x2="${ancho - margen.derecha}" y1="${margen.arriba + util.h}" y2="${margen.arriba + util.h}"></line>
            ${columnasSvg}
        </svg>`;
    }

    /**
     * Columnas agrupadas: grupos = [{ etiqueta, valores: [n, n, n] }], series = [{ nombre, color }]
     * Admite valores negativos (utilidad en pérdida).
     */
    function columnasAgrupadas(grupos, series) {
        const todos = grupos.flatMap((g) => g.valores);
        const maximo = Math.max(...todos, 0);
        const minimo = Math.min(...todos, 0);

        if (!grupos.length || (maximo === 0 && minimo === 0)) return sinDatos();

        const ancho = 720;
        const alto = 260;
        const margen = { arriba: 26, abajo: 34, lados: 8 };
        const util = { w: ancho - margen.lados * 2, h: alto - margen.arriba - margen.abajo };
        const rango = (maximo - minimo) || 1;
        const cero = margen.arriba + (maximo / rango) * util.h;
        const pasoGrupo = util.w / grupos.length;
        const grosor = Math.min(34, (pasoGrupo * 0.78) / series.length);

        const svg = grupos.map((g, gi) => {
            const x0 = margen.lados + pasoGrupo * gi + (pasoGrupo - grosor * series.length) / 2;

            const barrasGrupo = g.valores.map((v, si) => {
                const h = Math.abs(v) / rango * util.h;
                const y = v >= 0 ? cero - h : cero;
                const x = x0 + grosor * si;
                const rotulo = v !== 0
                    ? `<text class="grafico-valor" x="${x + grosor / 2}" y="${v >= 0 ? y - 5 : y + h + 12}" text-anchor="middle">${escapar(monedaCorta(v))}</text>`
                    : '';

                return `<g><title>${escapar(g.etiqueta)} · ${escapar(series[si].nombre)}: ${escapar(moneda(v))}</title>
                    <rect class="grafico-columna ${series[si].color}" x="${x + 1}" y="${y}" width="${grosor - 2}" height="${Math.max(v === 0 ? 0 : 2, h)}" rx="3"></rect>${rotulo}</g>`;
            }).join('');

            return `${barrasGrupo}<text class="grafico-eje" x="${x0 + (grosor * series.length) / 2}" y="${alto - 12}" text-anchor="middle">${escapar(g.etiqueta)}</text>`;
        }).join('');

        const leyenda = `<div class="grafico-leyenda">${series.map((s) => `<span><i class="punto-leyenda ${s.color}"></i>${escapar(s.nombre)}</span>`).join('')}</div>`;

        return `${leyenda}<svg class="grafico-svg" viewBox="0 0 ${ancho} ${alto}" role="img" aria-label="Gráfico de columnas agrupadas" preserveAspectRatio="xMidYMid meet">
            <line class="grafico-base" x1="${margen.lados}" x2="${ancho - margen.lados}" y1="${cero}" y2="${cero}"></line>
            ${svg}
        </svg>`;
    }

    /**
     * Dona con leyenda: items = [{ etiqueta, valor, texto, color }], centro = { valor, texto }
     */
    function dona(items, centro = null) {
        const total = items.reduce((suma, i) => suma + Math.max(0, i.valor), 0);
        if (!items.length || total === 0) return sinDatos();

        const radio = 70;
        const circunferencia = 2 * Math.PI * radio;
        let acumulado = 0;

        const segmentos = items.filter((i) => i.valor > 0).map((item) => {
            const largo = item.valor / total * circunferencia;
            const segmento = `<circle class="grafico-segmento ${item.color}" cx="100" cy="100" r="${radio}"
                stroke-dasharray="${largo} ${circunferencia - largo}" stroke-dashoffset="${-acumulado}"
                transform="rotate(-90 100 100)"><title>${escapar(item.etiqueta)}: ${escapar(item.texto ?? entero(item.valor))}</title></circle>`;
            acumulado += largo;
            return segmento;
        }).join('');

        const textoCentro = centro
            ? `<text class="grafico-centro-valor" x="100" y="100" text-anchor="middle">${escapar(centro.valor)}</text>
               <text class="grafico-centro-texto" x="100" y="120" text-anchor="middle">${escapar(centro.texto)}</text>`
            : '';

        const leyenda = items.map((item) => `
            <li>
                <i class="punto-leyenda ${item.color}"></i>
                <span class="leyenda-etiqueta">${escapar(item.etiqueta)}</span>
                <span class="leyenda-porcentaje">${porcentaje(item.valor / total * 100)}</span>
                <span class="leyenda-valor">${escapar(item.texto ?? entero(item.valor))}</span>
            </li>`).join('');

        return `<div class="grafico-dona">
            <svg viewBox="0 0 200 200" role="img" aria-label="Gráfico de dona">
                <circle class="grafico-segmento-fondo" cx="100" cy="100" r="${radio}"></circle>
                ${segmentos}${textoCentro}
            </svg>
            <ul class="grafico-dona-leyenda">${leyenda}</ul>
        </div>`;
    }

    /** Separador de sección: línea con título. */
    function separador(titulo, icono = '') {
        return `<div class="separador-seccion" role="separator"><span>${icono ? `<i class="fa-solid ${icono}"></i>` : ''}${escapar(titulo)}</span></div>`;
    }

    window.VMComun = {
        escapar, moneda, monedaCorta, entero, porcentaje, fechaLocal, formatearFecha, fechaCorta,
        barras, columnas, columnasAgrupadas, dona, separador, sinDatos
    };
})();
