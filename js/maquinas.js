/*=========================================================
  VENDING MINI MARKET — Máquinas expendedoras (maquinas.php)
  HU-17 · consulta de máquinas con su ubicación y estado
  Requiere: app.js, catalogo-comun.js y maquinas-api.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const { api: apiMaquinas } = window.MaquinasApi;
    const $ = (id) => document.getElementById(id);

    const ID_MENSAJE = 'mensajeMaquina';

    let maquinas = [];
    let numeroPeticion = 0;


    /*-----------------------------------------------------
      INICIO
    -----------------------------------------------------*/

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaMaquinas')) {
            return;
        }

        try {
            await cargarTiendas();
            registrarEventos();
            await Promise.all([cargarMaquinas(), cargarResumen()]);
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }


    /*-----------------------------------------------------
      FILTROS
    -----------------------------------------------------*/

    function leerFiltros() {
        return {
            busqueda: $('buscarMaquina')?.value.trim() || '',
            id_tienda: $('filtroTiendaMaquina')?.value || '',
            estado: $('filtroEstadoMaquina')?.value || ''
        };
    }

    function hayFiltros(filtros = leerFiltros()) {
        return Boolean(filtros.busqueda || filtros.id_tienda || filtros.estado);
    }

    async function cargarTiendas() {
        const resultado = await apiMaquinas('listar_tiendas');
        const filtro = $('filtroTiendaMaquina');

        if (!filtro) {
            return;
        }

        filtro.innerHTML = '<option value="">Todas las ubicaciones</option>';

        (resultado.data || []).forEach((tienda) => {
            const opcion = document.createElement('option');
            opcion.value = tienda.id_tienda;
            opcion.textContent = tienda.estado
                ? tienda.nombre
                : `${tienda.nombre} (inactiva)`;
            filtro.appendChild(opcion);
        });
    }


    /*-----------------------------------------------------
      CONSULTA
    -----------------------------------------------------*/

    async function cargarMaquinas() {
        const peticion = ++numeroPeticion;
        const filtros = leerFiltros();

        try {
            const resultado = await apiMaquinas('listar_maquinas', null, filtros);

            // Si el usuario siguió escribiendo, se descarta esta respuesta.
            if (peticion !== numeroPeticion) {
                return;
            }

            maquinas = resultado.data || [];
            renderMaquinas(filtros);
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }

    async function cargarResumen() {
        try {
            const { data } = await apiMaquinas('resumen_maquinas');

            $('totalMaquinas').textContent = data.total;
            $('maquinasActivas').textContent = data.activas;
            $('maquinasInactivas').textContent = data.inactivas;
            $('tiendasConMaquinas').textContent = data.tiendas_con_maquinas;
        } catch (error) {
            console.error(error);
        }
    }

    function renderMaquinas(filtros) {
        const cuerpo = $('cuerpoTablaMaquinas');
        const contenedorTabla = $('contenedorTablaMaquinas');
        const sinMaquinas = $('mensajeSinMaquinas');
        const sinResultados = $('mensajeSinResultadosMaquinas');

        cuerpo.innerHTML = '';

        const admin = contenedorTabla.dataset.esAdmin === '1';
        const vacio = maquinas.length === 0;
        const conFiltros = hayFiltros(filtros);

        contenedorTabla.style.display = vacio ? 'none' : '';
        sinMaquinas.hidden = !(vacio && !conFiltros);
        sinResultados.hidden = !(vacio && conFiltros);

        $('resultadoBusquedaMaquinas').textContent = vacio
            ? ''
            : `Mostrando ${maquinas.length} máquina(s).`;

        maquinas.forEach((maquina) => {
            const fila = document.createElement('tr');

            const ubicacion = C.escapeHtml(maquina.tienda_nombre) +
                (maquina.tienda_activa ? '' : ' <small>(inactiva)</small>');

            const modeloTipo = [maquina.modelo, maquina.tipo]
                .filter(Boolean)
                .map((valor) => C.escapeHtml(valor))
                .join(' · ');

            const nombre = C.escapeHtml(maquina.nombre);

            const celdaAcciones = admin
                ? `
                <td>
                    <div class="acciones-producto-tabla">
                        <a href="maquina-nueva.php?id=${maquina.id_maquina}"
                           class="boton-accion-producto boton-editar-producto"
                           title="Editar máquina" aria-label="Editar ${nombre}">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                    </div>
                </td>`
                : '';

            fila.innerHTML = `
                <td><strong>${C.escapeHtml(maquina.codigo)}</strong></td>
                <td>
                    ${nombre}
                    ${maquina.observaciones
                        ? `<br><small>${C.escapeHtml(maquina.observaciones)}</small>`
                        : ''}
                </td>
                <td>
                    ${ubicacion}
                    ${maquina.ubicacion
                        ? `<br><small>${C.escapeHtml(maquina.ubicacion)}</small>`
                        : ''}
                </td>
                <td>${modeloTipo || '—'}</td>
                <td>
                    <span class="insignia ${maquina.estado ? 'insignia-verde' : 'insignia-rojo'}">
                        ${maquina.estado ? 'Activa' : 'Inactiva'}
                    </span>
                </td>
                ${celdaAcciones}
            `;

            cuerpo.appendChild(fila);
        });
    }


    /*-----------------------------------------------------
      EVENTOS
    -----------------------------------------------------*/

    function registrarEventos() {
        $('buscarMaquina')?.addEventListener('input', C.debounce(cargarMaquinas, 300));
        $('filtroTiendaMaquina')?.addEventListener('change', cargarMaquinas);
        $('filtroEstadoMaquina')?.addEventListener('change', cargarMaquinas);

        $('botonLimpiarFiltrosMaquinas')?.addEventListener('click', () => {
            $('buscarMaquina').value = '';
            $('filtroTiendaMaquina').value = '';
            $('filtroEstadoMaquina').value = '';
            cargarMaquinas();
            $('buscarMaquina').focus();
        });
    }
})();
