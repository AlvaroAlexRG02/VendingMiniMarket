/*=========================================================
  VENDING MINI MARKET — Utilidades compartidas del catálogo
  Usado por: productos, producto-nuevo, categorias, proveedores
  Cargar DESPUÉS de app.js
=========================================================*/

(() => {
    'use strict';

    const API = '../api/catalogo/catalogo.php';
    const PAGINA_LOGIN = '../index/index.html';
    const PAGINA_PRODUCTOS = '../productos/productos.html';

    let sesionActual = null;


    /*-----------------------------------------------------
      API
    -----------------------------------------------------*/

    function urlApi(accion, parametros = {}) {
        const url = new URL(API, window.location.href);
        url.searchParams.set('accion', accion);

        Object.entries(parametros).forEach(([clave, valor]) => {
            if (valor !== undefined && valor !== null && valor !== '') {
                url.searchParams.set(clave, valor);
            }
        });

        return url.toString();
    }

    async function api(accion, datos = null, parametros = {}) {
        const opciones = {
            method: datos ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        };

        if (datos) {
            opciones.headers['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(datos);
        }

        let respuesta;

        try {
            respuesta = await fetch(urlApi(accion, parametros), opciones);
        } catch (error) {
            throw new Error('No fue posible comunicarse con el servidor.');
        }

        let resultado;

        try {
            resultado = await respuesta.json();
        } catch (error) {
            throw new Error(
                'El servidor devolvió una respuesta no válida. ' +
                'Revise XAMPP, config/database.php y los registros de error de PHP.'
            );
        }

        if (respuesta.status === 401) {
            window.location.href = PAGINA_LOGIN;
            throw new Error(resultado.message || 'Debe iniciar sesión.');
        }

        if (!respuesta.ok || !resultado.success) {
            const error = new Error(resultado.message || 'Ocurrió un error.');
            error.data = resultado.data;
            error.status = respuesta.status;
            throw error;
        }

        return resultado;
    }


    /*-----------------------------------------------------
      SESIÓN
    -----------------------------------------------------*/

    /**
     * Carga el usuario de la sesión PHP, lo muestra en la barra superior
     * y oculta lo marcado con data-solo-admin si no es administrador.
     * Con { soloAdmin: true } redirige a Productos a quien no lo sea.
     */
    async function cargarSesion({ soloAdmin = false } = {}) {
        const resultado = await api('sesion');
        sesionActual = resultado.data;

        const nombre = document.getElementById('nombreUsuarioSuperior');
        const correo = document.getElementById('correoUsuarioSuperior');

        if (nombre) {
            nombre.textContent = sesionActual.nombre || 'Usuario';
        }

        if (correo) {
            correo.textContent = sesionActual.correo || '';
        }

        if (soloAdmin && !sesionActual.es_admin) {
            window.location.href = PAGINA_PRODUCTOS;
            throw new Error('Esta sección es solo para administradores.');
        }

        if (!sesionActual.es_admin) {
            document.querySelectorAll('[data-solo-admin]').forEach((elemento) => {
                elemento.style.display = 'none';
            });
        }

        return sesionActual;
    }

    function esAdmin() {
        return Boolean(sesionActual && sesionActual.es_admin);
    }


    /*-----------------------------------------------------
      FORMATO
    -----------------------------------------------------*/

    function escapeHtml(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function moneda(valor) {
        return '₡' + Number(valor || 0).toLocaleString('es-CR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function fechaHora(iso) {
        const fecha = new Date(iso);

        if (Number.isNaN(fecha.getTime())) {
            return '';
        }

        return fecha.toLocaleString('es-CR', {
            dateStyle: 'short',
            timeStyle: 'short'
        });
    }

    function debounce(funcion, espera = 300) {
        let temporizador;

        return (...argumentos) => {
            clearTimeout(temporizador);
            temporizador = setTimeout(() => funcion(...argumentos), espera);
        };
    }


    /*-----------------------------------------------------
      MENSAJES (usa .mensaje-producto del estilos.css)
    -----------------------------------------------------*/

    let temporizadorMensaje;

    function mostrarMensaje(texto, tipo = 'exito', id = 'mensajeProducto') {
        const caja = document.getElementById(id);

        if (!caja) {
            return;
        }

        clearTimeout(temporizadorMensaje);

        caja.className = `mensaje-producto ${tipo === 'error' ? 'mensaje-error' : 'mensaje-exito'}`;
        caja.textContent = texto;
        caja.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        if (tipo !== 'error') {
            temporizadorMensaje = setTimeout(() => limpiarMensaje(id), 5000);
        }
    }

    function limpiarMensaje(id = 'mensajeProducto') {
        const caja = document.getElementById(id);

        if (caja) {
            caja.textContent = '';
            caja.className = 'mensaje-producto';
        }
    }


    /*-----------------------------------------------------
      MODALES (usa .modal-producto del estilos.css)
    -----------------------------------------------------*/

    function crearModal(contenidoHtml, clasesExtra = '') {
        const modal = document.createElement('div');
        modal.className = 'modal-producto';
        modal.innerHTML = `
            <div class="modal-producto-fondo"></div>
            <section class="modal-producto-contenido ${clasesExtra}"
                     role="dialog" aria-modal="true">
                ${contenidoHtml}
            </section>
        `;

        document.body.appendChild(modal);
        document.body.classList.add('modal-producto-abierto');

        return modal;
    }

    /** Devuelve una promesa con true (confirmó) o false (canceló). */
    function confirmar({ titulo, texto, textoConfirmar = 'Confirmar', peligro = false }) {
        return new Promise((resolver) => {
            const modal = crearModal(`
                <div class="modal-producto-icono">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h2></h2>
                <p></p>
                <div class="acciones-modal-producto">
                    <button type="button" class="boton boton-borde" data-resultado="0">Cancelar</button>
                    <button type="button"
                            class="boton ${peligro ? 'boton-peligro' : 'boton-azul'}"
                            data-resultado="1"></button>
                </div>
            `);

            modal.querySelector('h2').textContent = titulo;
            modal.querySelector('p').textContent = texto;
            modal.querySelector('[data-resultado="1"]').textContent = textoConfirmar;

            const cerrar = (resultado) => {
                document.removeEventListener('keydown', alTeclear);
                modal.remove();

                if (!document.querySelector('.modal-producto')) {
                    document.body.classList.remove('modal-producto-abierto');
                }

                resolver(resultado);
            };

            const alTeclear = (evento) => {
                if (evento.key === 'Escape') {
                    cerrar(false);
                }
            };

            document.addEventListener('keydown', alTeclear);

            modal.querySelector('.modal-producto-fondo').addEventListener('click', () => cerrar(false));
            modal.querySelectorAll('[data-resultado]').forEach((boton) => {
                boton.addEventListener('click', () => cerrar(boton.dataset.resultado === '1'));
            });

            modal.querySelector('[data-resultado="0"]').focus();
        });
    }

    /** Ventana informativa con un nodo DOM como contenido. */
    function mostrarDialogo(titulo, nodoContenido) {
        const modal = crearModal(`
            <h2></h2>
            <div class="dialogo-cuerpo"></div>
            <div class="acciones-modal-producto">
                <button type="button" class="boton boton-azul" data-cerrar>Cerrar</button>
            </div>
        `, 'modal-ancho');

        modal.querySelector('h2').textContent = titulo;
        modal.querySelector('.dialogo-cuerpo').appendChild(nodoContenido);

        const cerrar = () => {
            document.removeEventListener('keydown', alTeclear);
            modal.remove();

            if (!document.querySelector('.modal-producto')) {
                document.body.classList.remove('modal-producto-abierto');
            }
        };

        const alTeclear = (evento) => {
            if (evento.key === 'Escape') {
                cerrar();
            }
        };

        document.addEventListener('keydown', alTeclear);
        modal.querySelector('.modal-producto-fondo').addEventListener('click', cerrar);
        modal.querySelector('[data-cerrar]').addEventListener('click', cerrar);
        modal.querySelector('[data-cerrar]').focus();
    }


    window.CatalogoComun = {
        api,
        urlApi,
        cargarSesion,
        esAdmin,
        escapeHtml,
        moneda,
        fechaHora,
        debounce,
        mostrarMensaje,
        limpiarMensaje,
        confirmar,
        mostrarDialogo
    };
})();
