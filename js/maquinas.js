/*=========================================================
  VENDING MINI MARKET — Máquinas expendedoras (maquinas.php)
  HU-17 · consulta, inactivar/activar e historial de máquinas
  Requiere: app.js, catalogo-comun.js y maquinas-api.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const { api: apiMaquinas } = window.MaquinasApi;
    const $ = (id) => document.getElementById(id);

    const ID_MENSAJE = 'mensajeMaquina';

    const ETIQUETAS_CAMBIO = {
        codigo: 'Código',
        nombre: 'Nombre',
        tienda_nombre: 'Ubicación',
        ubicacion: 'Punto operativo',
        modelo: 'Modelo / Número de serie',
        tipo: 'Tipo',
        observaciones: 'Observaciones',
        estado: 'Estado'
    };

    const ACCIONES_HISTORIAL = {
        CREAR: 'Máquina registrada',
        EDITAR: 'Máquina editada',
        INACTIVAR: 'Máquina inactivada',
        ACTIVAR: 'Máquina activada'
    };

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

            const botonesAdmin = admin
                ? `
                        <a href="maquina-nueva.php?id=${maquina.id_maquina}"
                           class="boton-accion-producto boton-editar-producto"
                           title="Editar máquina" aria-label="Editar ${nombre}">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        ${maquina.estado
                            ? `<button type="button"
                                       class="boton-accion-producto boton-inactivar-producto"
                                       data-accion="inactivar" data-id="${maquina.id_maquina}"
                                       title="Inactivar máquina" aria-label="Inactivar ${nombre}">
                                    <i class="fa-solid fa-ban"></i>
                               </button>`
                            : `<button type="button"
                                       class="boton-accion-producto boton-activar-producto"
                                       data-accion="activar" data-id="${maquina.id_maquina}"
                                       title="Activar máquina" aria-label="Activar ${nombre}">
                                    <i class="fa-solid fa-circle-check"></i>
                               </button>`}`
                : '';

            const celdaAcciones = `
                <td>
                    <div class="acciones-producto-tabla">
                        <button type="button"
                                class="boton-accion-producto boton-historial-producto"
                                data-accion="historial" data-id="${maquina.id_maquina}"
                                title="Ver historial" aria-label="Ver historial de ${nombre}">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </button>${botonesAdmin}
                    </div>
                </td>`;

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

        $('cuerpoTablaMaquinas').addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion]');

            if (!boton) {
                return;
            }

            const id = Number(boton.dataset.id);

            if (boton.dataset.accion === 'inactivar') {
                cambiarEstado(id, false);
            } else if (boton.dataset.accion === 'activar') {
                cambiarEstado(id, true);
            } else if (boton.dataset.accion === 'historial') {
                verHistorial(id);
            }
        });
    }


    /*-----------------------------------------------------
      ACTIVAR / INACTIVAR — nunca se borra el registro
    -----------------------------------------------------*/

    async function cambiarEstado(id, activar) {
        const maquina = maquinas.find((m) => m.id_maquina === id);

        if (!maquina) {
            return;
        }

        const confirmado = await C.confirmar({
            titulo: activar ? 'Activar máquina' : 'Inactivar máquina',
            texto: activar
                ? `"${maquina.codigo}" volverá a aceptar movimientos.`
                : `"${maquina.codigo}" dejará de aceptar nuevos movimientos, pero su historial se conserva.`,
            textoConfirmar: activar ? 'Activar' : 'Inactivar',
            peligro: !activar
        });

        if (!confirmado) {
            return;
        }

        try {
            const resultado = await apiMaquinas(
                activar ? 'activar_maquina' : 'inactivar_maquina',
                { id_maquina: id }
            );

            C.mostrarMensaje(resultado.message, 'exito', ID_MENSAJE);
            await Promise.all([cargarMaquinas(), cargarResumen()]);
        } catch (error) {
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }


    /*-----------------------------------------------------
      HISTORIAL — viene de bitacora_auditoria
    -----------------------------------------------------*/

    function formatearValor(clave, valor) {
        if (valor === null || valor === undefined || valor === '') {
            return '—';
        }

        if (clave === 'estado') {
            return valor ? 'Activa' : 'Inactiva';
        }

        return String(valor);
    }

    function cambiosEntre(antes, despues) {
        if (!antes || !despues) {
            return [];
        }

        return Object.keys(ETIQUETAS_CAMBIO)
            .filter((clave) => String(antes[clave] ?? '') !== String(despues[clave] ?? ''))
            .map((clave) =>
                `${ETIQUETAS_CAMBIO[clave]}: ${formatearValor(clave, antes[clave])} → ${formatearValor(clave, despues[clave])}`
            );
    }

    async function verHistorial(id) {
        const maquina = maquinas.find((m) => m.id_maquina === id);

        try {
            const { data } = await apiMaquinas('historial_maquina', null, { id });
            const contenedor = document.createElement('div');

            if (!data.length) {
                contenedor.innerHTML = '<p>Esta máquina todavía no tiene movimientos registrados.</p>';
            } else {
                const lista = document.createElement('ul');
                lista.className = 'lista-historial';

                data.forEach((registro) => {
                    const elemento = document.createElement('li');

                    const cambios = registro.accion === 'CREAR'
                        ? [`Estado inicial: ${formatearValor('estado', registro.despues?.estado)}`]
                        : cambiosEntre(registro.antes, registro.despues);

                    elemento.innerHTML = `
                        <strong>${C.escapeHtml(ACCIONES_HISTORIAL[registro.accion] || registro.accion)}</strong>
                        <small>
                            ${C.escapeHtml(C.fechaHora(registro.fecha))}
                            ${registro.usuario ? ' · ' + C.escapeHtml(registro.usuario) : ''}
                        </small>
                        ${cambios.length
                            ? `<ul>${cambios.map((c) => `<li>${C.escapeHtml(c)}</li>`).join('')}</ul>`
                            : ''}
                    `;

                    lista.appendChild(elemento);
                });

                contenedor.appendChild(lista);
            }

            C.mostrarDialogo(`Historial: ${maquina ? maquina.codigo : 'máquina'}`, contenedor);
        } catch (error) {
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }
})();
