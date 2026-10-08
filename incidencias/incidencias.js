document.addEventListener("DOMContentLoaded", () => {

    const formularioIncidencia =
        document.getElementById("formularioIncidencia");

    const tablaIncidencias =
        document.getElementById("tablaIncidencias");

    const mensajeFormulario =
        document.getElementById("mensajeFormulario");

    const mensajeIncidencias =
        document.getElementById("mensajeIncidencias");

    const modalIncidencia =
        document.getElementById("modalIncidencia");

    const modalSeguimiento =
        document.getElementById("modalSeguimiento");

    const btnNuevaIncidencia =
        document.getElementById("botonNuevaIncidencia");

    const btnCerrarModalIncidencia =
        document.getElementById("botonCerrarModalIncidencia");

    const btnCancelarIncidencia =
        document.getElementById("botonCancelarIncidencia");

    const btnCerrarModalSeguimiento =
        document.getElementById("btnCerrarModal");

    const btnCancelarSeguimiento =
        document.getElementById("btnCancelar");

    const formularioSeguimiento =
        document.getElementById("formularioSeguimiento");

    const mensajeSeguimiento =
        document.getElementById("mensajeSeguimiento");

    const buscarIncidencia =
        document.getElementById("buscarIncidencia");

    const filtroEstado =
        document.getElementById("filtroEstado");

    const filtroPrioridad =
        document.getElementById("filtroPrioridad");

    const btnLimpiarFiltros =
        document.getElementById("botonLimpiarFiltros");


    let incidencias = [];


    /*
     * ==========================================
     * CARGAR INCIDENCIAS
     * ==========================================
     */

    async function cargarIncidencias() {

        tablaIncidencias.innerHTML = `
            <tr>
                <td colspan="8">
                    Cargando incidencias...
                </td>
            </tr>
        `;

        try {

            const respuesta = await fetch(
                "../api/incidencias/listar.php",
                {
                    method: "GET",
                    cache: "no-store"
                }
            );

            const resultado =
                await respuesta.json();

            if (!resultado.success) {
                throw new Error(
                    resultado.message ||
                    "No fue posible consultar las incidencias."
                );
            }

            incidencias =
                resultado.data || [];

            actualizarEstadisticas();

            mostrarIncidencias();

        } catch (error) {

            tablaIncidencias.innerHTML = `
                <tr>
                    <td colspan="8">
                        ${escapeHtml(error.message)}
                    </td>
                </tr>
            `;

        }

    }


    /*
     * ==========================================
     * ESTADÍSTICAS
     * ==========================================
     */

    function actualizarEstadisticas() {

        const total =
            incidencias.length;

        const abiertas =
            incidencias.filter(
                incidencia =>
                    incidencia.estado === "ABIERTA"
            ).length;

        const enProceso =
            incidencias.filter(
                incidencia =>
                    incidencia.estado === "EN_PROCESO"
            ).length;

        const criticas =
            incidencias.filter(
                incidencia =>
                    incidencia.prioridad === "CRITICA"
            ).length;


        document.getElementById(
            "totalIncidencias"
        ).textContent = total;

        document.getElementById(
            "incidenciasAbiertas"
        ).textContent = abiertas;

        document.getElementById(
            "incidenciasEnProceso"
        ).textContent = enProceso;

        document.getElementById(
            "incidenciasCriticas"
        ).textContent = criticas;

    }


    /*
     * ==========================================
     * MOSTRAR INCIDENCIAS
     * ==========================================
     */

    function mostrarIncidencias() {

        const texto =
            buscarIncidencia.value
                .trim()
                .toLowerCase();

        const estado =
            filtroEstado.value;

        const prioridad =
            filtroPrioridad.value;


        const filtradas =
            incidencias.filter(incidencia => {

                const coincideTexto =
                    !texto ||
                    incidencia.descripcion
                        .toLowerCase()
                        .includes(texto) ||
                    incidencia.modulo_afectado
                        .toLowerCase()
                        .includes(texto);


                const coincideEstado =
                    !estado ||
                    incidencia.estado === estado;


                const coincidePrioridad =
                    !prioridad ||
                    incidencia.prioridad === prioridad;


                return (
                    coincideTexto &&
                    coincideEstado &&
                    coincidePrioridad
                );

            });


        if (filtradas.length === 0) {

            tablaIncidencias.innerHTML = `
                <tr>
                    <td colspan="8">
                        No se encontraron incidencias.
                    </td>
                </tr>
            `;

            return;

        }


        tablaIncidencias.innerHTML =
            filtradas.map(incidencia => {

                const nombreUsuario =
                    `${incidencia.nombre || ""} ${
                        incidencia.apellido || ""
                    }`.trim();


                return `
                    <tr>

                        <td>
                            ${incidencia.id_incidencia}
                        </td>

                        <td>
                            ${escapeHtml(
                                incidencia.descripcion
                            )}
                        </td>

                        <td>
                            ${escapeHtml(
                                incidencia.modulo_afectado
                            )}
                        </td>

                        <td>
                            <span>
                                ${formatearPrioridad(
                                    incidencia.prioridad
                                )}
                            </span>
                        </td>

                        <td>
                            <span>
                                ${formatearEstado(
                                    incidencia.estado
                                )}
                            </span>
                        </td>

                        <td>
                            ${escapeHtml(
                                nombreUsuario
                            )}
                        </td>

                        <td>
                            ${formatearFecha(
                                incidencia.fecha_registro
                            )}
                        </td>

                        <td>

                            <button
                                type="button"
                                class="boton boton-borde boton-seguimiento"
                                data-id="${incidencia.id_incidencia}"
                            >

                                <i class="fa-solid fa-pen"></i>

                                Seguimiento

                            </button>

                        </td>

                    </tr>
                `;

            }).join("");


        document
            .querySelectorAll(".boton-seguimiento")
            .forEach(boton => {

                boton.addEventListener(
                    "click",
                    () => {

                        const id =
                            parseInt(
                                boton.dataset.id
                            );

                        abrirModalSeguimiento(id);

                    }
                );

            });

    }


    /*
     * ==========================================
     * NUEVA INCIDENCIA
     * ==========================================
     */

    btnNuevaIncidencia.addEventListener(
        "click",
        () => {

            formularioIncidencia.reset();

            mensajeFormulario.textContent = "";

            modalIncidencia.hidden = false;

        }
    );


    /*
     * ==========================================
     * CERRAR MODAL NUEVA INCIDENCIA
     * ==========================================
     */

    function cerrarModalIncidencia() {

        modalIncidencia.hidden = true;

        formularioIncidencia.reset();

        mensajeFormulario.textContent = "";

    }


    btnCerrarModalIncidencia.addEventListener(
        "click",
        cerrarModalIncidencia
    );


    btnCancelarIncidencia.addEventListener(
        "click",
        cerrarModalIncidencia
    );


    /*
     * Cerrar al presionar el fondo
     */

    const fondoModalIncidencia =
        document.querySelector(
            "[data-cerrar-modal-incidencia]"
        );

    if (fondoModalIncidencia) {

        fondoModalIncidencia.addEventListener(
            "click",
            cerrarModalIncidencia
        );

    }


    /*
     * ==========================================
     * REGISTRAR INCIDENCIA
     * ==========================================
     */

    formularioIncidencia.addEventListener(
        "submit",
        async (evento) => {

            evento.preventDefault();


            const btnRegistrar =
                document.getElementById(
                    "btnRegistrar"
                );


            const datos = {

                descripcion:
                    document
                        .getElementById("descripcion")
                        .value
                        .trim(),

                modulo_afectado:
                    document
                        .getElementById("modulo_afectado")
                        .value
                        .trim(),

                prioridad:
                    document
                        .getElementById("prioridad")
                        .value

            };


            if (
                !datos.descripcion ||
                !datos.modulo_afectado ||
                !datos.prioridad
            ) {

                mensajeFormulario.textContent =
                    "Complete todos los campos.";

                return;

            }


            btnRegistrar.disabled = true;

            btnRegistrar.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Registrando...
            `;


            try {

                const respuesta =
                    await fetch(
                        "../api/incidencias/guardar.php",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify(datos)
                        }
                    );


                const resultado =
                    await respuesta.json();


                if (!resultado.success) {

                    throw new Error(
                        resultado.message ||
                        "No fue posible registrar la incidencia."
                    );

                }


                cerrarModalIncidencia();

                mostrarMensaje(
                    resultado.message
                );

                await cargarIncidencias();


            } catch (error) {

                mensajeFormulario.textContent =
                    error.message;

            } finally {

                btnRegistrar.disabled = false;

                btnRegistrar.innerHTML = `
                    <i class="fa-solid fa-floppy-disk"></i>
                    Registrar incidencia
                `;

            }

        }
    );


    /*
     * ==========================================
     * ABRIR MODAL DE SEGUIMIENTO
     * ==========================================
     */

    function abrirModalSeguimiento(
        idIncidencia
    ) {

        const incidencia =
            incidencias.find(
                item =>
                    parseInt(
                        item.id_incidencia
                    ) === idIncidencia
            );


        if (!incidencia) {
            return;
        }


        document.getElementById(
            "id_incidencia"
        ).value =
            incidencia.id_incidencia;


        document.getElementById(
            "nuevo_estado"
        ).value =
            incidencia.estado;


        document.getElementById(
            "seguimiento"
        ).value =
            incidencia.seguimiento || "";


        mensajeSeguimiento.textContent = "";

        modalSeguimiento.hidden = false;

    }


    /*
     * ==========================================
     * CERRAR MODAL SEGUIMIENTO
     * ==========================================
     */

    function cerrarModalSeguimiento() {

        modalSeguimiento.hidden = true;

        formularioSeguimiento.reset();

        mensajeSeguimiento.textContent = "";

    }


    btnCerrarModalSeguimiento.addEventListener(
        "click",
        cerrarModalSeguimiento
    );


    btnCancelarSeguimiento.addEventListener(
        "click",
        cerrarModalSeguimiento
    );


    const fondoModalSeguimiento =
        document.querySelector(
            "[data-cerrar-modal-seguimiento]"
        );


    if (fondoModalSeguimiento) {

        fondoModalSeguimiento.addEventListener(
            "click",
            cerrarModalSeguimiento
        );

    }


    /*
     * ==========================================
     * ACTUALIZAR INCIDENCIA
     * ==========================================
     */

    formularioSeguimiento.addEventListener(
        "submit",
        async (evento) => {

            evento.preventDefault();


            const btnGuardar =
                document.getElementById(
                    "btnGuardarSeguimiento"
                );


            const datos = {

                id_incidencia:
                    document.getElementById(
                        "id_incidencia"
                    ).value,

                estado:
                    document.getElementById(
                        "nuevo_estado"
                    ).value,

                seguimiento:
                    document.getElementById(
                        "seguimiento"
                    ).value.trim()

            };


            if (!datos.seguimiento) {

                mensajeSeguimiento.textContent =
                    "El seguimiento es obligatorio.";

                return;

            }


            btnGuardar.disabled = true;

            btnGuardar.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin"></i>
                Guardando...
            `;


            try {

                const respuesta =
                    await fetch(
                        "../api/incidencias/actualizar.php",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify(datos)
                        }
                    );


                const resultado =
                    await respuesta.json();


                if (!resultado.success) {

                    throw new Error(
                        resultado.message ||
                        "No fue posible actualizar la incidencia."
                    );

                }


                cerrarModalSeguimiento();

                mostrarMensaje(
                    resultado.message
                );

                await cargarIncidencias();


            } catch (error) {

                mensajeSeguimiento.textContent =
                    error.message;

            } finally {

                btnGuardar.disabled = false;

                btnGuardar.innerHTML = `
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar cambios
                `;

            }

        }
    );


    /*
     * ==========================================
     * FILTROS
     * ==========================================
     */

    buscarIncidencia.addEventListener(
        "input",
        mostrarIncidencias
    );


    filtroEstado.addEventListener(
        "change",
        mostrarIncidencias
    );


    filtroPrioridad.addEventListener(
        "change",
        mostrarIncidencias
    );


    btnLimpiarFiltros.addEventListener(
        "click",
        () => {

            buscarIncidencia.value = "";

            filtroEstado.value = "";

            filtroPrioridad.value = "";

            mostrarIncidencias();

        }
    );


    /*
     * ==========================================
     * MENSAJE GENERAL
     * ==========================================
     */

    function mostrarMensaje(
        mensaje
    ) {

        mensajeIncidencias.textContent =
            mensaje;

        mensajeIncidencias.hidden = false;


        setTimeout(() => {

            mensajeIncidencias.hidden = true;

        }, 4000);

    }


    /*
     * ==========================================
     * FORMATEAR ESTADO
     * ==========================================
     */

    function formatearEstado(
        estado
    ) {

        const estados = {

            ABIERTA: "Abierta",

            EN_PROCESO: "En proceso",

            RESUELTA: "Resuelta",

            CERRADA: "Cerrada"

        };

        return estados[estado] || estado;

    }


    /*
     * ==========================================
     * FORMATEAR PRIORIDAD
     * ==========================================
     */

    function formatearPrioridad(
        prioridad
    ) {

        const prioridades = {

            BAJA: "Baja",

            MEDIA: "Media",

            ALTA: "Alta",

            CRITICA: "Crítica"

        };

        return prioridades[prioridad] ||
            prioridad;

    }


    /*
     * ==========================================
     * FORMATEAR FECHA
     * ==========================================
     */

    function formatearFecha(
        fecha
    ) {

        if (!fecha) {
            return "";
        }


        const fechaObjeto =
            new Date(fecha);


        return fechaObjeto.toLocaleString(
            "es-CR"
        );

    }


    /*
     * ==========================================
     * PROTECCIÓN HTML
     * ==========================================
     */

    function escapeHtml(
        texto
    ) {

        return String(texto ?? "")
            .replace(
                /&/g,
                "&amp;"
            )
            .replace(
                /</g,
                "&lt;"
            )
            .replace(
                />/g,
                "&gt;"
            )
            .replace(
                /"/g,
                "&quot;"
            )
            .replace(
                /'/g,
                "&#039;"
            );

    }


    /*
     * ==========================================
     * INICIAR
     * ==========================================
     */

    cargarIncidencias();

});