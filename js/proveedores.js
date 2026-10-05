/*=========================================================
  VENDING MINI MARKET — Proveedores (proveedores.html)
  HU-12: registrar y mantener proveedores
  Requiere: app.js y catalogo-comun.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const $ = (id) => document.getElementById(id);

    const CAMPOS = ['nombre', 'contacto', 'telefono', 'correo', 'direccion'];

    let proveedores = [];
    let idEdicion = null;
    let guardando = false;


    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('formularioProveedor')) {
            return;
        }

        try {
            await C.cargarSesion({ soloAdmin: true });

            $('formularioProveedor').addEventListener('submit', guardar);
            $('botonCancelarEdicion').addEventListener('click', salirDeEdicion);
            $('cuerpoTablaProveedores').addEventListener('click', alHacerClic);

            await cargarProveedores();
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      LISTA
    -----------------------------------------------------*/

    async function cargarProveedores() {
        const resultado = await C.api('listar_proveedores');
        proveedores = resultado.data || [];
        render();
    }

    function render() {
        const cuerpo = $('cuerpoTablaProveedores');
        cuerpo.innerHTML = '';

        $('mensajeSinProveedores').hidden = proveedores.length > 0;
        $('contenedorTablaProveedores').style.display = proveedores.length ? '' : 'none';

        proveedores.forEach((proveedor) => {
            const fila = document.createElement('tr');
            const nombre = C.escapeHtml(proveedor.nombre);

            fila.innerHTML = `
                <td><strong>${nombre}</strong></td>
                <td>${C.escapeHtml(proveedor.contacto || '—')}</td>
                <td>${C.escapeHtml(proveedor.telefono || '—')}</td>
                <td>${C.escapeHtml(proveedor.correo || '—')}</td>
                <td>${C.escapeHtml(proveedor.direccion || '—')}</td>
                <td>${proveedor.total_productos}</td>
                <td>
                    <span class="insignia ${proveedor.estado ? 'insignia-verde' : 'insignia-rojo'}">
                        ${proveedor.estado ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <div class="acciones-producto-tabla">
                        <button type="button"
                                class="boton-accion-producto boton-editar-producto"
                                data-accion="editar" data-id="${proveedor.id_proveedor}"
                                title="Editar proveedor" aria-label="Editar ${nombre}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        ${proveedor.estado
                            ? `<button type="button"
                                       class="boton-accion-producto boton-inactivar-producto"
                                       data-accion="inactivar" data-id="${proveedor.id_proveedor}"
                                       title="Desactivar proveedor" aria-label="Desactivar ${nombre}">
                                    <i class="fa-solid fa-ban"></i>
                               </button>`
                            : `<button type="button"
                                       class="boton-accion-producto boton-activar-producto"
                                       data-accion="activar" data-id="${proveedor.id_proveedor}"
                                       title="Activar proveedor" aria-label="Activar ${nombre}">
                                    <i class="fa-solid fa-circle-check"></i>
                               </button>`}
                    </div>
                </td>
            `;

            cuerpo.appendChild(fila);
        });
    }

    function alHacerClic(evento) {
        const boton = evento.target.closest('[data-accion]');

        if (!boton) {
            return;
        }

        const proveedor = proveedores.find((p) => p.id_proveedor === Number(boton.dataset.id));

        if (!proveedor) {
            return;
        }

        if (boton.dataset.accion === 'editar') {
            entrarEnEdicion(proveedor);
        } else {
            cambiarEstado(proveedor, boton.dataset.accion === 'activar');
        }
    }


    /*-----------------------------------------------------
      FORMULARIO
    -----------------------------------------------------*/

    function entrarEnEdicion(proveedor) {
        idEdicion = proveedor.id_proveedor;

        CAMPOS.forEach((campo) => {
            $(`${campo}Proveedor`).value = proveedor[campo] || '';
        });

        $('tituloFormularioProveedor').textContent = 'Editar proveedor';
        $('botonGuardarProveedor').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        $('botonCancelarEdicion').hidden = false;

        C.limpiarMensaje();
        $('nombreProveedor').focus();
        $('formularioProveedor').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function salirDeEdicion() {
        idEdicion = null;

        $('formularioProveedor').reset();
        $('tituloFormularioProveedor').textContent = 'Nuevo proveedor';
        $('botonGuardarProveedor').innerHTML = '<i class="fa-solid fa-plus"></i> Registrar proveedor';
        $('botonCancelarEdicion').hidden = true;
    }

    async function guardar(evento) {
        evento.preventDefault();

        if (guardando) {
            return;
        }

        const datos = {};
        CAMPOS.forEach((campo) => {
            datos[campo] = $(`${campo}Proveedor`).value.trim();
        });

        guardando = true;
        $('botonGuardarProveedor').disabled = true;

        try {
            const resultado = idEdicion
                ? await C.api('editar_proveedor', { ...datos, id_proveedor: idEdicion })
                : await C.api('crear_proveedor', datos);

            C.mostrarMensaje(resultado.message, 'exito');
            salirDeEdicion();
            await cargarProveedores();
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        } finally {
            guardando = false;
            $('botonGuardarProveedor').disabled = false;
        }
    }


    /*-----------------------------------------------------
      ACTIVAR / DESACTIVAR
    -----------------------------------------------------*/

    async function cambiarEstado(proveedor, activar) {
        const confirmado = await C.confirmar({
            titulo: activar ? 'Activar proveedor' : 'Desactivar proveedor',
            texto: activar
                ? `"${proveedor.nombre}" volverá a estar disponible al registrar productos.`
                : `"${proveedor.nombre}" no se podrá elegir en productos nuevos. ` +
                  `Los ${proveedor.total_productos} producto(s) que ya lo usan lo conservan.`,
            textoConfirmar: activar ? 'Activar' : 'Desactivar',
            peligro: !activar
        });

        if (!confirmado) {
            return;
        }

        try {
            const resultado = await C.api(
                activar ? 'activar_proveedor' : 'inactivar_proveedor',
                { id_proveedor: proveedor.id_proveedor }
            );

            C.mostrarMensaje(resultado.message, 'exito');

            if (idEdicion === proveedor.id_proveedor) {
                salirDeEdicion();
            }

            await cargarProveedores();
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        }
    }
})();
