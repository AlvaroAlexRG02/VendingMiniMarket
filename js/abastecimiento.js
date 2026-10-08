/*=========================================================
  VENDING MINI MARKET — Relaciones de abastecimiento (relaciones.php)
  HU-18 · configuración de relaciones entre ubicaciones y máquinas
  Requiere: app.js, catalogo-comun.js y maquinas-api.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const api = window.MaquinasApi.crearCliente('../api/abastecimiento/abastecimiento.php');
    const $ = (id) => document.getElementById(id);

    const ID_MENSAJE = 'mensajeRelacion';

    const ETIQUETAS_CAMBIO = {
        origen_nombre: 'Origen',
        destino_nombre: 'Destino',
        observaciones: 'Observaciones',
        estado: 'Estado'
    };

    const ACCIONES_HISTORIAL = {
        CREAR: 'Relación registrada',
        EDITAR: 'Relación editada',
        INACTIVAR: 'Relación inactivada',
        ACTIVAR: 'Relación activada'
    };

    let opciones = { ubicaciones: [], maquinas: [] };
    let relaciones = [];
    let idEditando = null;
    let guardando = false;
    let numeroPeticion = 0;


    /*-----------------------------------------------------
      INICIO
    -----------------------------------------------------*/

    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('cuerpoTablaRelaciones')) {
            return;
        }

        try {
            opciones = (await api('opciones')).data;

            llenarFiltroOrigen();

            if ($('formularioRelacion')) {
                prepararFormulario();
            }

            prepararVerificacion();
            registrarEventos();
            await Promise.all([cargarRelaciones(), cargarResumen()]);
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }

    function etiquetaTipo(tipo) {
        return tipo === 'BODEGA' ? 'Bodega' : 'Tienda';
    }

    /** Llena un <select> con pares [valor, texto] usando textContent (sin HTML). */
    function llenarSelect(select, textoInicial, items, seleccionado) {
        select.innerHTML = '';

        const inicial = document.createElement('option');
        inicial.value = '';
        inicial.textContent = textoInicial;
        select.appendChild(inicial);

        items.forEach(([valor, texto]) => {
            const opcion = document.createElement('option');
            opcion.value = String(valor);
            opcion.textContent = texto;
            select.appendChild(opcion);
        });

        select.value = items.some(([valor]) => String(valor) === String(seleccionado))
            ? String(seleccionado)
            : '';
    }


    /*-----------------------------------------------------
      FILTROS
    -----------------------------------------------------*/

    function leerFiltros() {
        return {
            busqueda: $('buscarRelacion')?.value.trim() || '',
            id_origen: $('filtroOrigenRelacion')?.value || '',
            destino_tipo: $('filtroTipoDestinoRelacion')?.value || '',
            estado: $('filtroEstadoRelacion')?.value || ''
        };
    }

    function hayFiltros(filtros = leerFiltros()) {
        return Boolean(filtros.busqueda || filtros.id_origen || filtros.destino_tipo || filtros.estado);
    }

    function llenarFiltroOrigen() {
        llenarSelect(
            $('filtroOrigenRelacion'),
            'Todos los orígenes',
            opciones.ubicaciones.map((u) => [
                u.id_tienda,
                `${u.nombre} (${etiquetaTipo(u.tipo)})${u.estado ? '' : ' — inactiva'}`
            ]),
            ''
        );
    }


    /*-----------------------------------------------------
      CONSULTA
    -----------------------------------------------------*/

    async function cargarRelaciones() {
        const peticion = ++numeroPeticion;
        const filtros = leerFiltros();

        try {
            const resultado = await api('listar_relaciones', null, filtros);

            // Si el usuario siguió escribiendo, se descarta esta respuesta.
            if (peticion !== numeroPeticion) {
                return;
            }

            relaciones = resultado.data || [];
            renderRelaciones(filtros);
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }

    async function cargarResumen() {
        try {
            const { data } = await api('resumen_relaciones');

            $('totalRelaciones').textContent = data.total;
            $('relacionesActivas').textContent = data.activas;
            $('relacionesInactivas').textContent = data.inactivas;
            $('relacionesMaquinas').textContent = data.hacia_maquinas;
        } catch (error) {
            console.error(error);
        }
    }

    function renderRelaciones(filtros) {
        const cuerpo = $('cuerpoTablaRelaciones');
        const contenedorTabla = $('contenedorTablaRelaciones');
        const sinRelaciones = $('mensajeSinRelaciones');
        const sinResultados = $('mensajeSinResultadosRelaciones');

        cuerpo.innerHTML = '';

        const admin = contenedorTabla.dataset.esAdmin === '1';
        const vacio = relaciones.length === 0;
        const conFiltros = hayFiltros(filtros);

        contenedorTabla.style.display = vacio ? 'none' : '';
        sinRelaciones.hidden = !(vacio && !conFiltros);
        sinResultados.hidden = !(vacio && conFiltros);

        $('resultadoBusquedaRelaciones').textContent = vacio
            ? ''
            : `Mostrando ${relaciones.length} relación(es).`;

        relaciones.forEach((relacion) => {
            const fila = document.createElement('tr');
            const esMaquina = relacion.destino_tipo === 'MAQUINA';

            const origen = C.escapeHtml(relacion.origen_nombre) +
                `<br><small>${C.escapeHtml(etiquetaTipo(relacion.origen_tipo))}${relacion.origen_activa ? '' : ' · inactiva'}</small>`;

            const destino = C.escapeHtml(relacion.destino_nombre) +
                (esMaquina && relacion.maquina_nombre
                    ? `<br><small>${C.escapeHtml(relacion.maquina_nombre)}</small>`
                    : '') +
                (relacion.destino_activo ? '' : '<br><small>inactivo</small>');

            const nombreRelacion = `${C.escapeHtml(relacion.origen_nombre)} a ${C.escapeHtml(relacion.destino_nombre)}`;

            const botonesAdmin = admin
                ? `
                        <button type="button"
                                class="boton-accion-producto boton-editar-producto"
                                data-accion="editar" data-id="${relacion.id_relacion}"
                                title="Editar relación" aria-label="Editar relación ${nombreRelacion}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        ${relacion.estado
                            ? `<button type="button"
                                       class="boton-accion-producto boton-inactivar-producto"
                                       data-accion="inactivar" data-id="${relacion.id_relacion}"
                                       title="Inactivar relación" aria-label="Inactivar relación ${nombreRelacion}">
                                    <i class="fa-solid fa-ban"></i>
                               </button>`
                            : `<button type="button"
                                       class="boton-accion-producto boton-activar-producto"
                                       data-accion="activar" data-id="${relacion.id_relacion}"
                                       title="Activar relación" aria-label="Activar relación ${nombreRelacion}">
                                    <i class="fa-solid fa-circle-check"></i>
                               </button>`}`
                : '';

            const celdaAcciones = `
                <td>
                    <div class="acciones-producto-tabla">
                        <button type="button"
                                class="boton-accion-producto boton-historial-producto"
                                data-accion="historial" data-id="${relacion.id_relacion}"
                                title="Ver historial" aria-label="Ver historial de la relación ${nombreRelacion}">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </button>${botonesAdmin}
                    </div>
                </td>`;

            fila.innerHTML = `
                <td>${origen}</td>
                <td>${destino}</td>
                <td>${C.escapeHtml(relacion.tipo_relacion)}</td>
                <td>${relacion.observaciones ? C.escapeHtml(relacion.observaciones) : '—'}</td>
                <td>
                    <span class="insignia ${relacion.estado ? 'insignia-verde' : 'insignia-rojo'}">
                        ${relacion.estado ? 'Activa' : 'Inactiva'}
                    </span>
                </td>
                ${celdaAcciones}
            `;

            cuerpo.appendChild(fila);
        });
    }


    /*-----------------------------------------------------
      FORMULARIO (crear y editar)
    -----------------------------------------------------*/

    function prepararFormulario() {
        llenarOrigen();
        llenarDestino('', false);

        $('formularioRelacion').addEventListener('submit', guardar);
        $('botonCancelarRelacion').addEventListener('click', limpiarFormulario);

        $('origenRelacion').addEventListener('change', () => {
            llenarDestino('', true);
        });

        $('tipoDestinoRelacion').addEventListener('change', () => {
            llenarDestino('', false);
        });
    }

    /** Origen: ubicaciones activas; al editar se conserva la actual aunque esté inactiva. */
    function llenarOrigen(seleccionado = '') {
        const select = $('origenRelacion');
        const conservar = seleccionado !== '' ? String(seleccionado) : '';

        llenarSelect(
            select,
            'Seleccione la ubicación de origen',
            opciones.ubicaciones
                .filter((u) => u.estado || String(u.id_tienda) === conservar)
                .map((u) => [
                    u.id_tienda,
                    `${u.nombre} (${etiquetaTipo(u.tipo)})${u.estado ? '' : ' — inactiva'}`
                ]),
            conservar
        );
    }

    /**
     * Destino según el tipo elegido:
     *  - Ubicación: solo tiendas (una bodega no puede ser destino), distintas del origen.
     *  - Máquina: máquinas activas.
     * `conservar` mantiene la selección actual al reconstruir la lista.
     */
    function llenarDestino(seleccionado = '', conservar = true) {
        llenarDestinoEn({
            select: $('destinoRelacion'),
            tipo: $('tipoDestinoRelacion').value,
            origen: Number($('origenRelacion').value) || 0,
            seleccionado,
            conservar,
            soloPermitidos: true
        });
    }

    /**
     * Llena un selector de destino según el tipo elegido.
     * `soloPermitidos` (formulario): solo tiendas activas y máquinas activas.
     * Sin él (verificar traslado): cualquier ubicación o máquina, para poder
     * comprobar también combinaciones que no están configuradas.
     */
    function llenarDestinoEn({ select, tipo, origen, seleccionado = '', conservar = true, soloPermitidos }) {
        const actual = seleccionado !== ''
            ? String(seleccionado)
            : (conservar ? select.value : '');

        if (!tipo) {
            llenarSelect(select, 'Seleccione primero el tipo de destino', [], '');
            select.disabled = true;
            return;
        }

        select.disabled = false;

        const items = tipo === 'UBICACION'
            ? opciones.ubicaciones
                .filter((u) => u.id_tienda !== origen
                    && (!soloPermitidos
                        || (u.tipo === 'TIENDA' && (u.estado || String(u.id_tienda) === actual))))
                .map((u) => [
                    u.id_tienda,
                    `${u.nombre} (${etiquetaTipo(u.tipo)})${u.estado ? '' : ' — inactiva'}`
                ])
            : opciones.maquinas
                .filter((m) => !soloPermitidos || m.estado || String(m.id_maquina) === actual)
                .map((m) => [
                    m.id_maquina,
                    `${m.codigo} · ${m.nombre} (${m.tienda_nombre})${m.estado ? '' : ' — inactiva'}`
                ]);

        llenarSelect(select, 'Seleccione el destino', items, actual);
    }

    function leerDatosFormulario() {
        return {
            id_tienda_origen: $('origenRelacion').value,
            destino_tipo: $('tipoDestinoRelacion').value,
            id_destino: $('destinoRelacion').value,
            observaciones: $('observacionesRelacion').value.trim()
        };
    }

    /** Devuelve [mensaje, campo] del primer problema, o null si todo está bien. */
    function validar(datos) {
        if (!datos.id_tienda_origen) {
            return ['Debe seleccionar la ubicación de origen.', 'origenRelacion'];
        }

        if (!datos.destino_tipo) {
            return ['Debe seleccionar el tipo de destino.', 'tipoDestinoRelacion'];
        }

        if (!datos.id_destino) {
            return ['Debe seleccionar el destino.', 'destinoRelacion'];
        }

        return null;
    }

    function huboCambios(datos) {
        const actual = relaciones.find((r) => r.id_relacion === idEditando);

        if (!actual) {
            return true;
        }

        return Number(datos.id_tienda_origen) !== actual.id_tienda_origen
            || datos.destino_tipo !== actual.destino_tipo
            || Number(datos.id_destino) !== actual.id_destino
            || datos.observaciones !== (actual.observaciones || '');
    }

    async function guardar(evento) {
        evento.preventDefault();

        if (guardando) {
            return;
        }

        const datos = leerDatosFormulario();
        const problema = validar(datos);

        if (problema) {
            C.mostrarMensaje(problema[0], 'error', ID_MENSAJE);
            $(problema[1]).focus();
            return;
        }

        if (idEditando && !huboCambios(datos)) {
            C.mostrarMensaje('No hay cambios para guardar.', 'error', ID_MENSAJE);
            return;
        }

        const boton = $('botonGuardarRelacion');

        guardando = true;
        boton.disabled = true;
        C.limpiarMensaje(ID_MENSAJE);

        try {
            const resultado = idEditando
                ? await api('editar_relacion', { ...datos, id_relacion: idEditando })
                : await api('crear_relacion', datos);

            C.mostrarMensaje(resultado.message, 'exito', ID_MENSAJE);

            limpiarFormulario();
            await Promise.all([cargarRelaciones(), cargarResumen()]);
        } catch (error) {
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        } finally {
            guardando = false;
            boton.disabled = false;
        }
    }

    function limpiarFormulario() {
        idEditando = null;

        $('formularioRelacion').reset();
        llenarOrigen();
        llenarDestino('', false);

        $('tituloFormularioRelacion').textContent = 'Nueva relación';
        $('botonGuardarRelacion').innerHTML =
            '<i class="fa-solid fa-floppy-disk"></i> Guardar relación';
        $('botonCancelarRelacion').textContent = 'Limpiar';
    }

    function prepararEdicion(relacion) {
        idEditando = relacion.id_relacion;

        llenarOrigen(relacion.id_tienda_origen);
        $('tipoDestinoRelacion').value = relacion.destino_tipo;
        llenarDestino(relacion.id_destino, false);
        $('observacionesRelacion').value = relacion.observaciones || '';

        $('tituloFormularioRelacion').textContent = 'Editar relación';
        $('botonGuardarRelacion').innerHTML =
            '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        $('botonCancelarRelacion').textContent = 'Cancelar edición';

        C.limpiarMensaje(ID_MENSAJE);
        $('panelFormularioRelacion').scrollIntoView({ behavior: 'smooth', block: 'start' });
        $('origenRelacion').focus({ preventScroll: true });
    }


    /*-----------------------------------------------------
      EVENTOS
    -----------------------------------------------------*/

    function registrarEventos() {
        $('buscarRelacion')?.addEventListener('input', C.debounce(cargarRelaciones, 300));
        $('filtroOrigenRelacion')?.addEventListener('change', cargarRelaciones);
        $('filtroTipoDestinoRelacion')?.addEventListener('change', cargarRelaciones);
        $('filtroEstadoRelacion')?.addEventListener('change', cargarRelaciones);

        $('botonLimpiarFiltrosRelaciones')?.addEventListener('click', () => {
            $('buscarRelacion').value = '';
            $('filtroOrigenRelacion').value = '';
            $('filtroTipoDestinoRelacion').value = '';
            $('filtroEstadoRelacion').value = '';
            cargarRelaciones();
            $('buscarRelacion').focus();
        });

        $('cuerpoTablaRelaciones').addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-accion]');

            if (!boton) {
                return;
            }

            const id = Number(boton.dataset.id);

            if (boton.dataset.accion === 'editar') {
                const relacion = relaciones.find((r) => r.id_relacion === id);

                if (relacion) {
                    prepararEdicion(relacion);
                }
            } else if (boton.dataset.accion === 'inactivar') {
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
        const relacion = relaciones.find((r) => r.id_relacion === id);

        if (!relacion) {
            return;
        }

        const nombre = `${relacion.origen_nombre} → ${relacion.destino_nombre}`;

        const confirmado = await C.confirmar({
            titulo: activar ? 'Activar relación' : 'Inactivar relación',
            texto: activar
                ? `"${nombre}" volverá a usarse como guía en los traslados.`
                : `"${nombre}" dejará de usarse como guía en los traslados, pero su historial se conserva.`,
            textoConfirmar: activar ? 'Activar' : 'Inactivar',
            peligro: !activar
        });

        if (!confirmado) {
            return;
        }

        try {
            const resultado = await api(
                activar ? 'activar_relacion' : 'inactivar_relacion',
                { id_relacion: id }
            );

            C.mostrarMensaje(resultado.message, 'exito', ID_MENSAJE);
            await Promise.all([cargarRelaciones(), cargarResumen()]);
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
        const relacion = relaciones.find((r) => r.id_relacion === id);

        try {
            const { data } = await api('historial_relacion', null, { id });
            const contenedor = document.createElement('div');

            if (!data.length) {
                contenedor.innerHTML = '<p>Esta relación todavía no tiene movimientos registrados.</p>';
            } else {
                const lista = document.createElement('ul');
                lista.className = 'lista-historial';

                data.forEach((registro) => {
                    const elemento = document.createElement('li');

                    const cambios = registro.accion === 'CREAR'
                        ? [`Relación: ${registro.despues?.origen_nombre ?? '—'} → ${registro.despues?.destino_nombre ?? '—'}`]
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

            C.mostrarDialogo(
                `Historial: ${relacion ? `${relacion.origen_nombre} → ${relacion.destino_nombre}` : 'relación'}`,
                contenedor
            );
        } catch (error) {
            C.mostrarMensaje(error.message, 'error', ID_MENSAJE);
        }
    }


    /*-----------------------------------------------------
      VERIFICAR TRASLADO — ¿hay una relación configurada?
    -----------------------------------------------------*/

    function prepararVerificacion() {
        const formulario = $('formularioVerificarTraslado');

        if (!formulario) {
            return;
        }

        llenarSelect(
            $('origenTraslado'),
            'Seleccione el origen',
            opciones.ubicaciones.map((u) => [u.id_tienda, `${u.nombre} (${etiquetaTipo(u.tipo)})`]),
            ''
        );
        llenarDestinoTraslado('', false);

        $('origenTraslado').addEventListener('change', () => {
            llenarDestinoTraslado('', true);
            limpiarResultadoTraslado();
        });

        $('tipoDestinoTraslado').addEventListener('change', () => {
            llenarDestinoTraslado('', false);
            limpiarResultadoTraslado();
        });

        $('destinoTraslado').addEventListener('change', limpiarResultadoTraslado);
        formulario.addEventListener('submit', verificarTraslado);
    }

    function llenarDestinoTraslado(seleccionado = '', conservar = true) {
        llenarDestinoEn({
            select: $('destinoTraslado'),
            tipo: $('tipoDestinoTraslado').value,
            origen: Number($('origenTraslado').value) || 0,
            seleccionado,
            conservar,
            soloPermitidos: false
        });
    }

    function mostrarResultadoTraslado(texto, tipo) {
        const caja = $('resultadoTraslado');

        caja.className = `mensaje-producto ${tipo === 'exito' ? 'mensaje-exito' : 'mensaje-error'}`;
        caja.textContent = texto;
    }

    function limpiarResultadoTraslado() {
        const caja = $('resultadoTraslado');

        caja.className = 'mensaje-producto';
        caja.textContent = '';
    }

    async function verificarTraslado(evento) {
        evento.preventDefault();

        const datos = {
            id_tienda_origen: $('origenTraslado').value,
            destino_tipo: $('tipoDestinoTraslado').value,
            id_destino: $('destinoTraslado').value
        };

        if (!datos.id_tienda_origen) {
            mostrarResultadoTraslado('Debe seleccionar el origen del traslado.', 'error');
            return;
        }

        if (!datos.destino_tipo) {
            mostrarResultadoTraslado('Debe seleccionar el tipo de destino.', 'error');
            return;
        }

        if (!datos.id_destino) {
            mostrarResultadoTraslado('Debe seleccionar el destino.', 'error');
            return;
        }

        try {
            const { data, message } = await api('validar_traslado', null, datos);

            // Sin relación (o inactiva) es una advertencia, no un error del sistema.
            mostrarResultadoTraslado(message, data.permitido ? 'exito' : 'error');
        } catch (error) {
            mostrarResultadoTraslado(error.message, 'error');
        }
    }
})();
