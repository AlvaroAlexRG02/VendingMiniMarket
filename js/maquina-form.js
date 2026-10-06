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

        // El estado no se edita aquí: se cambia con las acciones de inactivar/activar.
        $('estadoMaquina').value = maquina.estado ? 'true' : 'false';
        $('estadoMaquina').disabled = true;
        $('ayudaEstadoMaquina').hidden = false;
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

        const boton = $('botonGuardarMaquina');

        guardando = true;
        boton.disabled = true;
        C.limpiarMensaje('mensajeMaquina');

        try {
            const accion = idMaquina ? 'editar_maquina' : 'crear_maquina';
            const carga = idMaquina ? { ...datos, id_maquina: idMaquina } : datos;

            // Al editar, el estado no se envía: lo gobiernan inactivar/activar.
            if (idMaquina) {
                delete carga.estado;
            }

            const resultado = await api(accion, carga);

            C.mostrarMensaje(resultado.message, 'exito', 'mensajeMaquina');

            setTimeout(() => {
                window.location.href = PAGINA_LISTADO;
            }, 900);
        } catch (error) {
            guardando = false;
            boton.disabled = false;

            C.mostrarMensaje(error.message, 'error', 'mensajeMaquina');

            // 409 = identificador repetido (u otro conflicto): se vuelve al campo del código.
            if (error.status === 409) {
                $('codigoMaquina').focus();
                $('codigoMaquina').select();
            }
        }
    }
})();
