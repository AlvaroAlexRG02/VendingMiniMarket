/*=========================================================
  VENDING MINI MARKET — Categorías (categorias.php)
  HU-11: crear, editar y desactivar categorías
  Requiere: app.js y catalogo-comun.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const $ = (id) => document.getElementById(id);

    let categorias = [];
    let idEdicion = null;
    let guardando = false;


    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        if (!$('formularioCategoria')) {
            return;
        }

        try {
            await C.cargarSesion({ soloAdmin: true });

            $('formularioCategoria').addEventListener('submit', guardar);
            $('botonCancelarEdicion').addEventListener('click', salirDeEdicion);
            $('cuerpoTablaCategorias').addEventListener('click', alHacerClic);

            await cargarCategorias();
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      LISTA
    -----------------------------------------------------*/

    async function cargarCategorias() {
        const resultado = await C.api('listar_categorias');
        categorias = resultado.data || [];
        render();
    }

    function render() {
        const cuerpo = $('cuerpoTablaCategorias');
        cuerpo.innerHTML = '';

        $('mensajeSinCategorias').hidden = categorias.length > 0;
        $('contenedorTablaCategorias').style.display = categorias.length ? '' : 'none';

        categorias.forEach((categoria) => {
            const fila = document.createElement('tr');
            const nombre = C.escapeHtml(categoria.nombre);

            fila.innerHTML = `
                <td><strong>${nombre}</strong></td>
                <td>${C.escapeHtml(categoria.descripcion || '—')}</td>
                <td>${categoria.total_productos}</td>
                <td>
                    <span class="insignia ${categoria.estado ? 'insignia-verde' : 'insignia-rojo'}">
                        ${categoria.estado ? 'Activa' : 'Inactiva'}
                    </span>
                </td>
                <td>
                    <div class="acciones-producto-tabla">
                        <button type="button"
                                class="boton-accion-producto boton-editar-producto"
                                data-accion="editar" data-id="${categoria.id_categoria}"
                                title="Editar categoría" aria-label="Editar ${nombre}">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        ${categoria.estado
                            ? `<button type="button"
                                       class="boton-accion-producto boton-inactivar-producto"
                                       data-accion="inactivar" data-id="${categoria.id_categoria}"
                                       title="Desactivar categoría" aria-label="Desactivar ${nombre}">
                                    <i class="fa-solid fa-ban"></i>
                               </button>`
                            : `<button type="button"
                                       class="boton-accion-producto boton-activar-producto"
                                       data-accion="activar" data-id="${categoria.id_categoria}"
                                       title="Activar categoría" aria-label="Activar ${nombre}">
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

        const categoria = categorias.find((c) => c.id_categoria === Number(boton.dataset.id));

        if (!categoria) {
            return;
        }

        if (boton.dataset.accion === 'editar') {
            entrarEnEdicion(categoria);
        } else {
            cambiarEstado(categoria, boton.dataset.accion === 'activar');
        }
    }


    /*-----------------------------------------------------
      FORMULARIO
    -----------------------------------------------------*/

    function entrarEnEdicion(categoria) {
        idEdicion = categoria.id_categoria;

        $('nombreCategoria').value = categoria.nombre;
        $('descripcionCategoria').value = categoria.descripcion || '';
        $('tituloFormularioCategoria').textContent = 'Editar categoría';
        $('botonGuardarCategoria').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        $('botonCancelarEdicion').hidden = false;

        C.limpiarMensaje();
        $('nombreCategoria').focus();
        $('formularioCategoria').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function salirDeEdicion() {
        idEdicion = null;

        $('formularioCategoria').reset();
        $('tituloFormularioCategoria').textContent = 'Nueva categoría';
        $('botonGuardarCategoria').innerHTML = '<i class="fa-solid fa-plus"></i> Crear categoría';
        $('botonCancelarEdicion').hidden = true;
    }

    async function guardar(evento) {
        evento.preventDefault();

        if (guardando) {
            return;
        }

        const datos = {
            nombre: $('nombreCategoria').value.trim(),
            descripcion: $('descripcionCategoria').value.trim()
        };

        guardando = true;
        $('botonGuardarCategoria').disabled = true;

        try {
            const resultado = idEdicion
                ? await C.api('editar_categoria', { ...datos, id_categoria: idEdicion })
                : await C.api('crear_categoria', datos);

            C.mostrarMensaje(resultado.message, 'exito');
            salirDeEdicion();
            await cargarCategorias();
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        } finally {
            guardando = false;
            $('botonGuardarCategoria').disabled = false;
        }
    }


    /*-----------------------------------------------------
      ACTIVAR / DESACTIVAR
    -----------------------------------------------------*/

    async function cambiarEstado(categoria, activar) {
        const confirmado = await C.confirmar({
            titulo: activar ? 'Activar categoría' : 'Desactivar categoría',
            texto: activar
                ? `"${categoria.nombre}" volverá a estar disponible al registrar productos.`
                : `"${categoria.nombre}" no se podrá elegir en productos nuevos. ` +
                  `Los ${categoria.total_productos} producto(s) que ya la usan la conservan.`,
            textoConfirmar: activar ? 'Activar' : 'Desactivar',
            peligro: !activar
        });

        if (!confirmado) {
            return;
        }

        try {
            const resultado = await C.api(
                activar ? 'activar_categoria' : 'inactivar_categoria',
                { id_categoria: categoria.id_categoria }
            );

            C.mostrarMensaje(resultado.message, 'exito');

            if (idEdicion === categoria.id_categoria) {
                salirDeEdicion();
            }

            await cargarCategorias();
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        }
    }
})();
