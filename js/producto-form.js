/*=========================================================
  VENDING MINI MARKET — Formulario de producto (producto-nuevo.html)
  HU-08 registrar · HU-09 editar (?id=) · HU-13 duplicados
  Requiere: app.js y catalogo-comun.js cargados antes.
=========================================================*/

(() => {
    'use strict';

    const C = window.CatalogoComun;
    const $ = (id) => document.getElementById(id);

    let idProducto = null;
    let guardando = false;
    let proveedoresDisponibles = [];


    document.addEventListener('DOMContentLoaded', iniciar);

    async function iniciar() {
        const formulario = $('formularioProducto');

        if (!formulario) {
            return;
        }

        try {
            await C.cargarSesion({ soloAdmin: true });

            const parametro = new URLSearchParams(window.location.search).get('id');
            idProducto = parametro && Number(parametro) > 0 ? Number(parametro) : null;

            const [categorias, impuestos, proveedores] = await Promise.all([
                C.api('listar_categorias'),
                C.api('listar_impuestos'),
                C.api('listar_proveedores')
            ]);

            proveedoresDisponibles = proveedores.data;

            const producto = idProducto
                ? (await C.api('obtener_producto', null, { id: idProducto })).data
                : null;

            llenarCategorias(categorias.data, producto?.id_categoria);
            llenarImpuestos(impuestos.data, producto?.id_impuesto);

            if (producto) {
                prepararEdicion(producto);
            } else {
                actualizarMensajeSinProveedores();
            }

            $('botonAgregarProveedorProducto').addEventListener('click', () => agregarFilaProveedor());
            $('listaProveedoresProducto').addEventListener('click', alHacerClicEnProveedores);
            formulario.addEventListener('submit', guardar);
        } catch (error) {
            console.error(error);
            C.mostrarMensaje(error.message, 'error');
        }
    }


    /*-----------------------------------------------------
      CAMPOS
    -----------------------------------------------------*/

    function llenarCategorias(categorias, seleccionada) {
        const select = $('categoriaProducto');
        select.innerHTML = '<option value="">Seleccione una categoría</option>';

        categorias
            .filter((c) => c.estado || c.id_categoria === seleccionada)
            .forEach((c) => {
                const opcion = document.createElement('option');
                opcion.value = c.id_categoria;
                opcion.textContent = c.estado ? c.nombre : `${c.nombre} (inactiva)`;
                select.appendChild(opcion);
            });

        select.value = seleccionada ? String(seleccionada) : '';
    }

    function llenarImpuestos(impuestos, seleccionado) {
        const select = $('impuestoProducto');
        select.innerHTML = '<option value="">Seleccione un impuesto</option>';

        impuestos
            .filter((i) => i.estado || i.id_impuesto === seleccionado)
            .forEach((i) => {
                const opcion = document.createElement('option');
                opcion.value = i.id_impuesto;
                opcion.textContent = i.estado ? i.nombre : `${i.nombre} (inactivo)`;
                select.appendChild(opcion);
            });

        if (seleccionado) {
            select.value = String(seleccionado);
        } else {
            // En producto nuevo se sugiere el 13% si existe.
            const general = impuestos.find((i) => i.estado && Number(i.porcentaje) === 13);
            select.value = general ? String(general.id_impuesto) : '';
        }
    }

    function prepararEdicion(producto) {
        $('tituloPagina').textContent = 'Editar producto';
        $('textoPagina').textContent = 'Actualiza la información del producto. Los cambios quedan en su historial.';
        $('botonGuardarProducto').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar cambios';
        document.title = 'Editar producto | Vending Mini Market';

        $('nombreProducto').value = producto.nombre || '';
        $('codigoInternoProducto').value = producto.codigo_articulo_retail || '';
        $('codigoBarrasProducto').value = producto.codigo_barras || '';
        $('descripcionProducto').value = producto.descripcion || '';

        (producto.proveedores || []).forEach((p) => agregarFilaProveedor(p));
        actualizarMensajeSinProveedores();
    }


    /*-----------------------------------------------------
      PROVEEDORES DEL PRODUCTO (producto_proveedor)
    -----------------------------------------------------*/

    function agregarFilaProveedor(existente = null) {
        const fila = document.createElement('div');
        fila.className = 'fila-proveedor-producto';

        const select = document.createElement('select');
        select.setAttribute('aria-label', 'Proveedor');
        select.innerHTML = '<option value="">Seleccione un proveedor</option>';

        proveedoresDisponibles
            .filter((p) => p.estado || (existente && p.id_proveedor === existente.id_proveedor))
            .forEach((p) => {
                const opcion = document.createElement('option');
                opcion.value = p.id_proveedor;
                opcion.textContent = p.estado ? p.nombre : `${p.nombre} (inactivo)`;
                select.appendChild(opcion);
            });

        if (existente) {
            select.value = String(existente.id_proveedor);
        }

        const costo = document.createElement('input');
        costo.type = 'number';
        costo.min = '0';
        costo.step = '0.01';
        costo.placeholder = 'Costo (₡), opcional';
        costo.setAttribute('aria-label', 'Costo del proveedor');

        if (existente && existente.costo !== null && existente.costo !== undefined) {
            costo.value = existente.costo;
        }

        const quitar = document.createElement('button');
        quitar.type = 'button';
        quitar.className = 'boton-accion-producto boton-inactivar-producto';
        quitar.dataset.quitar = '1';
        quitar.title = 'Quitar proveedor';
        quitar.setAttribute('aria-label', 'Quitar proveedor');
        quitar.innerHTML = '<i class="fa-solid fa-xmark"></i>';

        fila.append(select, costo, quitar);
        $('listaProveedoresProducto').appendChild(fila);

        actualizarMensajeSinProveedores();

        if (!existente) {
            select.focus();
        }
    }

    function alHacerClicEnProveedores(evento) {
        const boton = evento.target.closest('[data-quitar]');

        if (boton) {
            boton.closest('.fila-proveedor-producto').remove();
            actualizarMensajeSinProveedores();
        }
    }

    function actualizarMensajeSinProveedores() {
        const hay = $('listaProveedoresProducto').children.length > 0;
        $('mensajeSinProveedoresProducto').hidden = hay;
    }

    function leerProveedores() {
        return Array.from($('listaProveedoresProducto').children)
            .map((fila) => ({
                id_proveedor: fila.querySelector('select').value,
                costo: fila.querySelector('input').value
            }))
            .filter((p) => p.id_proveedor !== '');
    }


    /*-----------------------------------------------------
      GUARDAR
    -----------------------------------------------------*/

    function leerDatos() {
        return {
            nombre: $('nombreProducto').value.trim(),
            codigo_articulo_retail: $('codigoInternoProducto').value.trim(),
            codigo_barras: $('codigoBarrasProducto').value.trim(),
            id_categoria: $('categoriaProducto').value,
            id_impuesto: $('impuestoProducto').value,
            descripcion: $('descripcionProducto').value.trim(),
            proveedores: leerProveedores()
        };
    }

    async function guardar(evento) {
        evento.preventDefault();

        if (guardando) {
            return;
        }

        const datos = leerDatos();
        const repetidos = new Set(datos.proveedores.map((p) => p.id_proveedor));

        if (repetidos.size !== datos.proveedores.length) {
            C.mostrarMensaje('Un proveedor está repetido en la lista.', 'error');
            return;
        }

        await enviar(datos);
    }

    async function enviar(datos) {
        const boton = $('botonGuardarProducto');

        guardando = true;
        boton.disabled = true;
        C.limpiarMensaje();

        try {
            const accion = idProducto ? 'editar_producto' : 'crear_producto';
            const carga = idProducto ? { ...datos, id_producto: idProducto } : datos;
            const resultado = await C.api(accion, carga);

            C.mostrarMensaje(resultado.message, 'exito');

            setTimeout(() => {
                window.location.href = 'productos.html';
            }, 900);
        } catch (error) {
            guardando = false;
            boton.disabled = false;

            await manejarError(error, datos);
        }
    }

    /** HU-13: muestra los posibles duplicados y, si corresponde, permite continuar. */
    async function manejarError(error, datos) {
        const duplicados = error.data?.duplicados;

        if (!duplicados || !duplicados.length) {
            C.mostrarMensaje(error.message, 'error');
            return;
        }

        const lista = duplicados
            .map((d) => {
                const codigo = d.codigo_articulo_retail || d.codigo_barras || 'sin código';
                return `• ${d.nombre} (${codigo}${d.estado ? '' : ', inactivo'}) — ${d.motivo}`;
            })
            .join('\n');

        if (!error.data.puede_continuar) {
            C.mostrarMensaje(`${error.message}\n${lista}`, 'error');
            return;
        }

        const continuar = await C.confirmar({
            titulo: 'Posible producto duplicado',
            texto: `${error.message}\n\n${lista}\n\n¿Desea guardar de todas formas?`,
            textoConfirmar: 'Guardar de todas formas'
        });

        if (continuar) {
            guardando = true;
            await enviar({ ...datos, confirmar_duplicado: true });
        } else {
            C.mostrarMensaje(`Guardado cancelado.\n${lista}`, 'error');
        }
    }
})();
