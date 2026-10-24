/*=========================================================
  VENDING MINI MARKET — Cliente de la API de facturas
  Usado por: compras/facturas.php
=========================================================*/

(() => {
    'use strict';

    const API = '../api/compras/facturas.php';
    const PAGINA_LOGIN = '../index/index.html';

    /**
     * Llama a una acción de la API de facturas.
     * - Sin `datos` hace GET.
     * - Con `datos` como FormData, envía multipart/form-data (crear_factura).
     * - Con `datos` como objeto plano, envía JSON.
     */
    async function llamarApi(accion, datos = null, parametros = {}) {
        const url = new URL(API, window.location.href);
        url.searchParams.set('accion', accion);

        Object.entries(parametros).forEach(([clave, valor]) => {
            if (valor !== undefined && valor !== null && valor !== '') {
                url.searchParams.set(clave, valor);
            }
        });

        const opciones = {
            method: datos ? 'POST' : 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        };

        if (datos instanceof FormData) {
            opciones.body = datos;
        } else if (datos) {
            opciones.headers['Content-Type'] = 'application/json';
            opciones.body = JSON.stringify(datos);
        }

        let respuesta;

        try {
            respuesta = await fetch(url.toString(), opciones);
        } catch (error) {
            throw new Error('No fue posible comunicarse con el servidor.');
        }

        let resultado;

        try {
            resultado = await respuesta.json();
        } catch (error) {
            throw new Error('El servidor devolvió una respuesta no válida.');
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

    window.FacturasApi = { api: llamarApi };
})();
