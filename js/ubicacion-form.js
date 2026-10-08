/*=========================================================
  VENDING MINI MARKET — Formulario de ubicación (ubicacion-nueva.php)
  HU-16 registrar · editar (?id=) una tienda o una bodega.
  Requiere: app.js, catalogo-comun.js y maquinas-api.js antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const api = window.MaquinasApi.crearCliente('../api/tienda/tienda.php');
    const $ = (id) => document.getElementById(id);

    const PAGINA_LISTADO = 'tienda.php';

    let idUbicacion = null;
    let ubicacionOriginal = null;
    let guardando = false;


    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        const formulario = $('formularioUbicacion');

        if (!formulario) {
            return;
        }

        $('tipoUbicacion').addEventListener('change', actualizarAbastecidaPor);
        $('estadoUbicacion').addEventListener('change', actualizarAbastecidaPor);
        formulario.addEventListener('submit', guardar);

        try {
            const parametro = new URLSearchParams(window.location.search).get('id');
            idUbicacion = parametro && Number(parametro) > 0 ? Number(parametro) : null;

            if (idUbicacion) {
                // En edición las relaciones de abastecimiento se gestionan en su propia pantalla.
                $('grupoAbastecidaPorUbicacion').hidden = true;

                const ubicacion = await api('obtener', null, { id: idUbicacion });

                prepararEdicion(ubicacion.data);
            } else {
                const ubicaciones = await api('listar');

                llenarAbastecedoras(ubicaciones.data);
            }
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error', 'mensajeUbicacion');
            $('botonGuardarUbicacion').disabled = true;
        }

        actualizarAbastecidaPor();
    }


    /*-----------------------------------------------------
      CAMPOS
    -----------------------------------------------------*/

    /** Solo ubicaciones activas pueden abastecer a la nueva. */
    function llenarAbastecedoras(ubicaciones) {
        const select = $('abastecidaPorUbicacion');
        select.innerHTML = '<option value="">Sin relación operativa</option>';

        ubicaciones
            .filter((u) => u.estado)
            .forEach((u) => {
                const opcion = document.createElement('option');
                opcion.value = u.id_tienda;
                opcion.textContent = u.nombre;
                select.appendChild(opcion);
            });
    }

    /** Una bodega o una ubicación inactiva no pueden tener una ubicación que las abastezca. */
    function actualizarAbastecidaPor() {
        const select = $('abastecidaPorUbicacion');
        const bloqueada = $('tipoUbicacion').value === 'Bodega' || $('estadoUbicacion').value !== 'true';

        if (bloqueada) {
            select.value = '';
        }

        select.disabled = bloqueada;
        select.title = bloqueada
            ? 'Solo una tienda activa puede ser abastecida por otra ubicación.'
            : '';
    }

    function prepararEdicion(ubicacion) {
        $('tituloPagina').textContent = 'Editar ubicación';
        $('textoPagina').textContent =
            'Actualiza la información de la ubicación. Los cambios quedan en su historial.';
        $('botonGuardarUbicacion').innerHTML =
            '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        document.title = 'Editar ubicación | Vending Mini Market';

        $('nombreUbicacion').value = ubicacion.nombre || '';
        $('tipoUbicacion').value = ubicacion.tipo === 'BODEGA' ? 'Bodega' : 'Tienda';
        $('estadoUbicacion').value = ubicacion.estado ? 'true' : 'false';
        $('principalUbicacion').value = ubicacion.es_principal ? 'true' : 'false';
        $('observacionesUbicacion').value = ubicacion.observaciones || '';

        ubicacionOriginal = ubicacion;
    }


    /*-----------------------------------------------------
      GUARDAR
    -----------------------------------------------------*/

    function leerDatos() {
        const abastecedora = $('abastecidaPorUbicacion').value;

        return {
            nombre: $('nombreUbicacion').value.trim(),
            tipo: $('tipoUbicacion').value,
            estado: $('estadoUbicacion').value === 'true',
            es_principal: $('principalUbicacion').value === 'true',
            observaciones: $('observacionesUbicacion').value.trim(),
            id_abastecedora: abastecedora ? Number(abastecedora) : 0
        };
    }

    /** Devuelve [mensaje, campo] del primer problema, o null si todo está bien. */
    function validar(datos) {
        if (!datos.nombre) {
            return ['El nombre es obligatorio.', 'nombreUbicacion'];
        }

        if (!datos.tipo) {
            return ['Debe seleccionar el tipo de la ubicación.', 'tipoUbicacion'];
        }

        return null;
    }

    /** En edición: indica si cambió algo distinto del estado. */
    function huboCambiosDeDatos(datos) {
        const o = ubicacionOriginal;
        const tipoOriginal = o.tipo === 'BODEGA' ? 'Bodega' : 'Tienda';

        return datos.nombre !== (o.nombre || '')
            || datos.tipo !== tipoOriginal
            || datos.es_principal !== o.es_principal
            || datos.observaciones !== (o.observaciones || '');
    }

    /**
     * Edita los datos (editar) y, si cambió el estado, usa cambiar_estado
     * para que quede en el historial. Devuelve el mensaje para el usuario.
     */
    async function guardarEdicion(datos) {
        const cambioDatos = huboCambiosDeDatos(datos);
        const cambioEstado = datos.estado !== ubicacionOriginal.estado;

        if (!cambioDatos && !cambioEstado) {
            throw new Error('No hay cambios para guardar.');
        }

        const mensajes = [];

        if (cambioDatos) {
            const resultado = await api('editar', {
                id_tienda: idUbicacion,
                nombre: datos.nombre,
                tipo: datos.tipo,
                es_principal: datos.es_principal,
                observaciones: datos.observaciones
            });

            ubicacionOriginal = { ...ubicacionOriginal, ...resultado.data };
            mensajes.push(resultado.message);
        }

        if (cambioEstado) {
            try {
                const resultado = await api('cambiar_estado', {
                    id_tienda: idUbicacion,
                    estado: datos.estado
                });

                ubicacionOriginal = { ...ubicacionOriginal, ...resultado.data };
                mensajes.push(resultado.message);
            } catch (error) {
                if (cambioDatos) {
                    error.message = 'Los datos se guardaron, pero no se pudo cambiar el estado: ' + error.message;
                }

                throw error;
            }
        }

        return mensajes.join(' ');
    }

    async function guardar(evento) {
        evento.preventDefault();

        if (guardando) {
            return;
        }

        const datos = leerDatos();
        const problema = validar(datos);

        if (problema) {
            C.mostrarMensaje(problema[0], 'error', 'mensajeUbicacion');
            $(problema[1]).focus();
            return;
        }

        // Inactivar pide confirmación, igual que en el listado.
        if (idUbicacion && ubicacionOriginal.estado && !datos.estado) {
            const confirmado = await C.confirmar({
                titulo: 'Inactivar ubicación',
                texto: `"${datos.nombre}" dejará de aceptar nuevas operaciones, pero su historial se conserva.`,
                textoConfirmar: 'Inactivar',
                peligro: true
            });

            if (!confirmado) {
                return;
            }
        }

        const boton = $('botonGuardarUbicacion');

        guardando = true;
        boton.disabled = true;
        C.limpiarMensaje('mensajeUbicacion');

        try {
            const mensaje = idUbicacion
                ? await guardarEdicion(datos)
                : (await api('crear', datos)).message;

            C.mostrarMensaje(mensaje, 'exito', 'mensajeUbicacion');

            setTimeout(() => {
                window.location.href = PAGINA_LISTADO;
            }, 900);
        } catch (error) {
            guardando = false;
            boton.disabled = false;

            C.mostrarMensaje(error.message, 'error', 'mensajeUbicacion');

            // 409 por nombre repetido: se vuelve al campo del nombre.
            if (error.status === 409 && /nombre/i.test(error.message)) {
                $('nombreUbicacion').focus();
                $('nombreUbicacion').select();
            }
        }
    }
})();
