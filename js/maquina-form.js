/*=========================================================
  VENDING MINI MARKET — Formulario de máquina (maquina-nueva.php)
  HU-17 registrar · editar (?id=) · identificador único
  Requiere: app.js, catalogo-comun.js y maquinas-api.js antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const { api } = window.MaquinasApi;
    const $ = (id) => document.getElementById(id);

    const PAGINA_LISTADO = 'maquinas.php';

    let idMaquina = null;
    let maquinaOriginal = null;
    let guardando = false;


    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        const formulario = $('formularioMaquina');

        if (!formulario) {
            return;
        }

        try {
            const parametro = new URLSearchParams(window.location.search).get('id');
            idMaquina = parametro && Number(parametro) > 0 ? Number(parametro) : null;

            const [tiendas, maquina] = await Promise.all([
                api('listar_tiendas'),
                idMaquina ? api('obtener_maquina', null, { id: idMaquina }) : Promise.resolve(null)
            ]);

            llenarTiendas(tiendas.data, maquina?.data?.id_tienda);

            if (maquina) {
                prepararEdicion(maquina.data);
            }

            formulario.addEventListener('submit', guardar);
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error', 'mensajeMaquina');
            $('botonGuardarMaquina').disabled = true;
        }
    }


    /*-----------------------------------------------------
      CAMPOS
    -----------------------------------------------------*/

    /** Solo ubicaciones activas; en edición se conserva la actual aunque esté inactiva. */
    function llenarTiendas(tiendas, seleccionada) {
        const select = $('tiendaMaquina');
        select.innerHTML = '<option value="">Seleccione una ubicación</option>';

        tiendas
            .filter((t) => t.estado || t.id_tienda === seleccionada)
            .forEach((t) => {
                const opcion = document.createElement('option');
                opcion.value = t.id_tienda;
                opcion.textContent = t.estado ? t.nombre : `${t.nombre} (inactiva)`;
                select.appendChild(opcion);
            });

        select.value = seleccionada ? String(seleccionada) : '';
    }

    function prepararEdicion(maquina) {
        $('tituloPagina').textContent = 'Editar máquina';
        $('textoPagina').textContent =
            'Actualiza la información de la máquina. Los cambios quedan en su historial.';
        $('botonGuardarMaquina').innerHTML =
            '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        document.title = 'Editar máquina | Vending Mini Market';

        $('codigoMaquina').value = maquina.codigo || '';
        $('nombreMaquina').value = maquina.nombre || '';
        $('ubicacionMaquina').value = maquina.ubicacion || '';
        $('modeloMaquina').value = maquina.modelo || '';
        $('tipoMaquina').value = maquina.tipo || '';
        $('observacionesMaquina').value = maquina.observaciones || '';
        $('estadoMaquina').value = maquina.estado ? 'true' : 'false';

        maquinaOriginal = maquina;
    }


    /*-----------------------------------------------------
      GUARDAR
    -----------------------------------------------------*/

    function leerDatos() {
        return {
            codigo: $('codigoMaquina').value.trim(),
            nombre: $('nombreMaquina').value.trim(),
            id_tienda: $('tiendaMaquina').value,
            ubicacion: $('ubicacionMaquina').value.trim(),
            modelo: $('modeloMaquina').value.trim(),
            tipo: $('tipoMaquina').value.trim(),
            observaciones: $('observacionesMaquina').value.trim(),
            estado: $('estadoMaquina').value === 'true'
        };
    }

    /** Devuelve [mensaje, campo] del primer problema, o null si todo está bien. */
    function validar(datos) {
        if (!datos.codigo) {
            return ['El identificador (código) es obligatorio.', 'codigoMaquina'];
        }

        if (!datos.nombre) {
            return ['El nombre es obligatorio.', 'nombreMaquina'];
        }

        if (!datos.id_tienda) {
            return ['Debe seleccionar la ubicación de la máquina.', 'tiendaMaquina'];
        }

        return null;
    }

    function normalizarCodigo(valor) {
        return String(valor ?? '').trim().replace(/\s+/g, ' ').toUpperCase();
    }

    /** En edición: indica si cambió algo distinto del estado. */
    function huboCambiosDeDatos(datos) {
        const o = maquinaOriginal;

        return normalizarCodigo(datos.codigo) !== normalizarCodigo(o.codigo)
            || datos.nombre !== (o.nombre || '')
            || Number(datos.id_tienda) !== o.id_tienda
            || datos.ubicacion !== (o.ubicacion || '')
            || datos.modelo !== (o.modelo || '')
            || datos.tipo !== (o.tipo || '')
            || datos.observaciones !== (o.observaciones || '');
    }

    /**
     * Edita los datos (editar_maquina) y, si cambió el estado, usa
     * inactivar/activar_maquina para que quede en el historial.
     * Devuelve el mensaje que se mostrará al usuario.
     */
    async function guardarEdicion(datos) {
        const cambioDatos = huboCambiosDeDatos(datos);
        const cambioEstado = datos.estado !== maquinaOriginal.estado;

        if (!cambioDatos && !cambioEstado) {
            throw new Error('No hay cambios para guardar.');
        }

        const mensajes = [];

        if (cambioDatos) {
            const { estado, ...resto } = datos;
            const resultado = await api('editar_maquina', { ...resto, id_maquina: idMaquina });

            maquinaOriginal = { ...maquinaOriginal, ...resultado.data };
            mensajes.push(resultado.message);
        }

        if (cambioEstado) {
            try {
                const resultado = await api(
                    datos.estado ? 'activar_maquina' : 'inactivar_maquina',
                    { id_maquina: idMaquina }
                );

                maquinaOriginal = { ...maquinaOriginal, ...resultado.data };
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
            C.mostrarMensaje(problema[0], 'error', 'mensajeMaquina');
            $(problema[1]).focus();
            return;
        }

        // Inactivar pide confirmación, igual que en el listado.
        if (idMaquina && maquinaOriginal.estado && !datos.estado) {
            const confirmado = await C.confirmar({
                titulo: 'Inactivar máquina',
                texto: `"${datos.codigo}" dejará de aceptar nuevos movimientos, pero su historial se conserva.`,
                textoConfirmar: 'Inactivar',
                peligro: true
            });

            if (!confirmado) {
                return;
            }
        }

        const boton = $('botonGuardarMaquina');

        guardando = true;
        boton.disabled = true;
        C.limpiarMensaje('mensajeMaquina');

        try {
            const mensaje = idMaquina
                ? await guardarEdicion(datos)
                : (await api('crear_maquina', datos)).message;

            C.mostrarMensaje(mensaje, 'exito', 'mensajeMaquina');

            setTimeout(() => {
                window.location.href = PAGINA_LISTADO;
            }, 900);
        } catch (error) {
            guardando = false;
            boton.disabled = false;

            C.mostrarMensaje(error.message, 'error', 'mensajeMaquina');

            // 409 por identificador repetido: se vuelve al campo del código.
            if (error.status === 409 && /identificador/i.test(error.message)) {
                $('codigoMaquina').focus();
                $('codigoMaquina').select();
            }
        }
    }
})();
