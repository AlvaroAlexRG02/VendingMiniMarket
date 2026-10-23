/*=========================================================
  VENDING MINI MARKET — Importar catálogo (importar.php)
  HU-14: carga inicial desde CSV o Excel
  Requiere: app.js y catalogo-comun.js cargados antes.
=========================================================*/


(() => {
    'use strict';

    const C = window.CatalogoComun;
    const $ = (id) => document.getElementById(id);

    const API = '../api/catalogo/importar.php';
    const PAGINA_LOGIN = '../index/index.html';
    const MAX_BYTES = 10 * 1024 * 1024;

    let enviando = false;




    document.addEventListener('DOMContentLoaded', iniciar);


    async function iniciar() {
        if (!$('formularioImportar')) {
            return;
        }

        try {
            await C.cargarSesion();

            $('formularioImportar').addEventListener('submit', importar);
            $('soloValidar').addEventListener('change', actualizarTextoBoton);
            actualizarTextoBoton();

            await cargarHistorial();
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }

    function actualizarTextoBoton() {
        $('botonImportar').innerHTML = $('soloValidar').checked
            ? '<i class="fa-solid fa-clipboard-check"></i> Validar archivo'
            : '<i class="fa-solid fa-file-import"></i> Importar archivo';
    }


    async function pedir(url, opciones) {
        let respuesta;

        try {
            respuesta = await fetch(url, { credentials: 'same-origin', ...opciones });
        } catch (error) {
            throw new Error('No fue posible comunicarse con el servidor.');
        }

        let resultado;

        try {
            resultado = await respuesta.json();
        } catch (error) {
            throw new Error('El servidor devolvió una respuesta no válida. Revise XAMPP, php.ini (límite de subida) y los registros de error de PHP.');
        }

        if (respuesta.status === 401) {
            window.location.href = PAGINA_LOGIN;
            throw new Error(resultado.message || 'Debe iniciar sesión.');
        }

        if (!respuesta.ok || !resultado.success) {
            throw new Error(resultado.message || 'Ocurrió un error.');
        }

        return resultado;
    }




    /*-----------------------------------------------------
      IMPORTAR / VALIDAR
    -----------------------------------------------------*/


    async function importar(evento) {
        evento.preventDefault();

        if (enviando) {
            return;
        }

        const archivo = $('archivoImportar').files[0];

        if (!archivo) {
            C.mostrarMensaje('Seleccione un archivo CSV o XLSX.', 'error');
            return;
        }

        if (archivo.size > MAX_BYTES) {
            C.mostrarMensaje('El archivo supera el máximo de 10 MB.', 'error');
            return;
        }

        if (!/\.(csv|xlsx)$/i.test(archivo.name)) {
            C.mostrarMensaje('Formato no admitido. Use CSV (UTF-8) o XLSX.', 'error');
            return;
        }

        const soloValidar = $('soloValidar').checked;

        if (!soloValidar) {
            const confirmado = await C.confirmar({
                titulo: 'Importar datos',
                texto: 'Se guardarán en el catálogo todas las filas válidas del archivo. Las filas con error se rechazarán. ¿Desea continuar?',
                textoConfirmar: 'Importar'
            });

            if (!confirmado) {
                return;
            }
        }

        const datos = new FormData();
        datos.append('archivo', archivo);

        if (soloValidar) {
            datos.append('solo_validar', '1');
        }

        const boton = $('botonImportar');

        enviando = true;
        boton.disabled = true;
        C.limpiarMensaje();
        C.mostrarMensaje('Procesando archivo…');

        try {
            const resultado = await pedir(API, { method: 'POST', body: datos });

            mostrarResultado(resultado.data, resultado.message);
            C.mostrarMensaje(resultado.message, resultado.data.rechazadas ? 'error' : 'exito');

            if (!soloValidar) {
                await cargarHistorial();
            }
        } catch (error) {
            C.mostrarMensaje(error.message, 'error');
        } finally {
            enviando = false;
            boton.disabled = false;
        }
    }

    function mostrarResultado(datos, mensaje) {
        const validacion = datos.modo === 'validacion';

        $('panelResultado').hidden = false;
        $('tituloResultado').textContent = validacion ? 'Resultado de la validación' : 'Resultado de la importación';
        $('textoResultado').textContent = mensaje;

        const cifras = [
            ['Filas procesadas', datos.procesadas],
            [validacion ? 'Filas válidas' : 'Filas importadas', datos.importadas],
            ['Filas omitidas (ya existían)', datos.omitidas],
            ['Filas rechazadas', datos.rechazadas],
            ['Categorías creadas', datos.categorias_creadas],
            ['Proveedores creados', datos.proveedores_creados],
            ['Productos creados', datos.productos_creados],
            ['Precios aplicados', datos.precios_aplicados],
            ['Inventario cargado', datos.inventario_cargado]
        ];

        const resumen = $('resumenImportacion');
        resumen.replaceChildren();

        cifras.forEach(([etiqueta, valor]) => {
            const caja = document.createElement('div');
            const numero = document.createElement('strong');
            const texto = document.createElement('span');

            numero.textContent = String(valor ?? 0);
            texto.textContent = etiqueta;
            caja.append(numero, texto);
            resumen.appendChild(caja);
        });

        const cuerpo = $('cuerpoErrores');
        cuerpo.replaceChildren();

        (datos.errores || []).forEach((error) => {
            const fila = document.createElement('tr');
            const celdaFila = document.createElement('td');
            const celdaMotivo = document.createElement('td');

            celdaFila.textContent = String(error.fila);
            celdaMotivo.textContent = error.error;
            fila.append(celdaFila, celdaMotivo);
            cuerpo.appendChild(fila);
        });

        const hayErrores = (datos.errores || []).length > 0;
        const nota = $('notaErrores');

        $('contenedorErrores').hidden = !hayErrores;
        nota.hidden = !(datos.errores_total > (datos.errores || []).length);

        if (!nota.hidden) {
            nota.textContent = `Se muestran ${(datos.errores || []).length} de ${datos.errores_total} errores. Corrija estos y vuelva a validar.`;
        }

        $('panelResultado').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }




    /*-----------------------------------------------------
      HISTORIAL
    -----------------------------------------------------*/


    async function cargarHistorial() {
        const url = new URL(API, window.location.href);
        url.searchParams.set('accion', 'historial');

        const { data } = await pedir(url.toString(), { method: 'GET', headers: { Accept: 'application/json' } });
        const cuerpo = $('cuerpoHistorialImportaciones');

        cuerpo.replaceChildren();
        $('sinImportaciones').hidden = data.length > 0;

        const estados = { COMPLETADA: 'Completada', CON_ERRORES: 'Con errores', FALLIDA: 'Fallida', INICIADA: 'Iniciada' };

        data.forEach((i) => {
            const fila = document.createElement('tr');

            [
                C.fechaHora(i.fecha_inicio),
                i.nombre_archivo,
                i.usuario || '—',
                i.filas_procesadas,
                i.filas_importadas,
                i.filas_rechazadas,
                estados[i.estado] || i.estado
            ].forEach((valor) => {
                const td = document.createElement('td');
                td.textContent = String(valor ?? '');
                fila.appendChild(td);
            });

            cuerpo.appendChild(fila);
        });
    }
})();
