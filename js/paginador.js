/*=========================================================
  VENDING MINI MARKET — Paginador compartido (15 por página)

  Dibuja debajo de un listado:
    "Mostrando del 1 al 15 de 120 registros"  [Anterior] 1 2 3 … 8 [Siguiente]

  Dos modos:

  1) LOCAL (la pantalla ya tiene todos los registros filtrados en un arreglo):
       const pag = Paginador.crear({
           despuesDe: '#contenedorTablaFacturas',   // elemento o selector
           filtros: ['#buscarFactura', '#filtroEstado'],  // al cambiar su valor vuelve a la página 1
           unidad: 'facturas',                      // opcional, por defecto "registros"
           alCambiar: renderFacturas                // se invoca al cambiar de página
       });
       function renderFacturas() {
           const visibles = pag.paginar(facturas);  // devuelve solo los 15 de la página actual
           ...dibujar visibles...
       }

  2) SERVIDOR (la API devuelve una página y el total):
       const pag = Paginador.crear({ despuesDe: '#tabla', alCambiar: (pagina) => cargar(pagina) });
       // al responder la API:
       pag.actualizar(total, pagina);
       // al cambiar un filtro o la búsqueda: pag.reiniciar() y cargar(1)

  La página se conserva al recargar datos (aprobar, editar, etc.) siempre
  que los filtros sigan iguales, y se ajusta si ya no existe.
=========================================================*/

(() => {
    'use strict';

    const POR_PAGINA = 15;

    function resolver(ref) {
        return typeof ref === 'string' ? document.querySelector(ref) : ref;
    }

    function formatoEntero(n) {
        return Number(n).toLocaleString('es-CR');
    }

    /** Páginas a mostrar: primera, última y vecinas de la actual; null = "…". */
    function paginasVisibles(actual, total) {
        const numeros = new Set([1, total]);

        for (let n = actual - 1; n <= actual + 1; n += 1) {
            numeros.add(n);
        }

        if (actual <= 3) {
            [2, 3, 4].forEach((n) => numeros.add(n));
        }

        if (actual >= total - 2) {
            [total - 1, total - 2, total - 3].forEach((n) => numeros.add(n));
        }

        const ordenados = [...numeros].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b);
        const resultado = [];

        ordenados.forEach((n, i) => {
            if (i > 0 && n - ordenados[i - 1] > 1) {
                resultado.push(null);
            }
            resultado.push(n);
        });

        return resultado;
    }

    function crear(opciones = {}) {
        const porPagina = opciones.porPagina || POR_PAGINA;
        const unidad = opciones.unidad || 'registros';
        const filtros = opciones.filtros || [];
        const alCambiar = typeof opciones.alCambiar === 'function' ? opciones.alCambiar : () => {};
        const anclaje = resolver(opciones.despuesDe);

        const nav = document.createElement('nav');
        nav.className = 'paginador';
        nav.setAttribute('aria-label', 'Paginación');
        nav.hidden = true;

        if (anclaje) {
            anclaje.insertAdjacentElement('afterend', nav);
        }

        const estado = {
            pagina: 1,
            total: 0,
            firma: null,
            porPagina
        };

        function totalPaginas() {
            return Math.max(1, Math.ceil(estado.total / porPagina));
        }

        function leerFirma() {
            return filtros.map((ref) => {
                const el = resolver(ref);
                if (!el) return '';
                return el.type === 'checkbox' ? String(el.checked) : String(el.value ?? '');
            }).join('\u0001');
        }

        function dibujar() {
            const paginas = totalPaginas();

            nav.hidden = estado.total === 0;
            nav.innerHTML = '';

            if (estado.total === 0) {
                return;
            }

            const desde = (estado.pagina - 1) * porPagina + 1;
            const hasta = Math.min(estado.pagina * porPagina, estado.total);

            const resumen = document.createElement('p');
            resumen.className = 'paginador-resumen';
            resumen.setAttribute('aria-live', 'polite');
            resumen.textContent =
                `Mostrando del ${formatoEntero(desde)} al ${formatoEntero(hasta)} de ${formatoEntero(estado.total)} ${unidad}`;

            const controles = document.createElement('div');
            controles.className = 'paginador-controles';

            const boton = (html, ir, { desactivado = false, activo = false, etiqueta = '', clase = '' } = {}) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = `paginador-boton ${clase}`.trim();
                b.innerHTML = html;
                b.dataset.ir = String(ir);
                b.disabled = desactivado;
                if (etiqueta) b.setAttribute('aria-label', etiqueta);
                if (activo) {
                    b.classList.add('activo');
                    b.setAttribute('aria-current', 'page');
                }
                return b;
            };

            controles.appendChild(boton(
                '<i class="fa-solid fa-chevron-left"></i><span>Anterior</span>',
                estado.pagina - 1,
                { desactivado: estado.pagina <= 1, etiqueta: 'Página anterior', clase: 'paginador-nav' }
            ));

            paginasVisibles(estado.pagina, paginas).forEach((n) => {
                if (n === null) {
                    const puntos = document.createElement('span');
                    puntos.className = 'paginador-puntos';
                    puntos.textContent = '…';
                    controles.appendChild(puntos);
                    return;
                }

                controles.appendChild(boton(String(n), n, {
                    activo: n === estado.pagina,
                    etiqueta: `Página ${n}`,
                    clase: 'paginador-numero'
                }));
            });

            controles.appendChild(boton(
                '<span>Siguiente</span><i class="fa-solid fa-chevron-right"></i>',
                estado.pagina + 1,
                { desactivado: estado.pagina >= paginas, etiqueta: 'Página siguiente', clase: 'paginador-nav' }
            ));

            nav.append(resumen, controles);
        }

        nav.addEventListener('click', (evento) => {
            const b = evento.target.closest('button[data-ir]');

            if (!b || b.disabled) {
                return;
            }

            const destino = Number(b.dataset.ir);

            if (!Number.isInteger(destino) || destino < 1 || destino > totalPaginas() || destino === estado.pagina) {
                return;
            }

            estado.pagina = destino;
            dibujar();
            alCambiar(destino);
        });

        const api = {
            get pagina() { return estado.pagina; },
            get porPagina() { return porPagina; },

            /** Vuelve a la primera página (filtro o búsqueda nuevos). */
            reiniciar() {
                estado.pagina = 1;
            },

            /** Modo servidor: la API ya devolvió la página; solo se dibujan los controles. */
            actualizar(total, pagina) {
                estado.total = Math.max(0, Number(total) || 0);
                estado.pagina = Math.min(Math.max(Number(pagina) || estado.pagina, 1), totalPaginas());
                dibujar();
            },

            /** Modo local: recibe todos los registros filtrados y devuelve los de la página actual. */
            paginar(items) {
                const firma = leerFirma();

                if (estado.firma !== null && firma !== estado.firma) {
                    estado.pagina = 1;
                }
                estado.firma = firma;

                estado.total = items.length;
                estado.pagina = Math.min(Math.max(estado.pagina, 1), totalPaginas());
                dibujar();

                const inicio = (estado.pagina - 1) * porPagina;
                return items.slice(inicio, inicio + porPagina);
            },

            /** Oculta los controles (p. ej. al mostrar el estado vacío). */
            ocultar() {
                nav.hidden = true;
            }
        };

        return api;
    }

    window.Paginador = { crear, POR_PAGINA };
})();
