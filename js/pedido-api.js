/*=========================================================
  VENDING MINI MARKET — Cliente de la API de pedidos
  Usado por: compras/pedido.php
=========================================================*/

(() => {
    'use strict';

    const API = '../api/compras/pedidos.php';
    const PAGINA_LOGIN = '../index/index.html';

    /**
     * Llama a una acción de la API de pedidos.
     * Sin `datos` hace GET; con `datos` hace POST con cuerpo JSON.
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

        if (datos) {
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

    window.PedidosApi = { api: llamarApi };
})();
