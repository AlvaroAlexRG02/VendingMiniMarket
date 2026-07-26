/*=========================================================
  VENDING MINI MARKET — Script general del sistema
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {
    inicializarMostrarContrasena();
    inicializarMenuLateral();
});

/**
 * Alterna la visibilidad del campo de contraseña en el login.
 */
function inicializarMostrarContrasena() {
    const boton = document.getElementById("botonMostrarContrasena");
    const campo = document.getElementById("contrasena");
    const icono = document.getElementById("iconoContrasena");

    /*
     * Si la página actual no contiene estos elementos,
     * la función termina sin producir errores.
     */
    if (!boton || !campo || !icono) {
        return;
    }

    boton.addEventListener("click", () => {
        const contrasenaVisible = campo.type === "text";

        if (contrasenaVisible) {
            campo.type = "password";

            icono.classList.remove("fa-eye-slash");
            icono.classList.add("fa-eye");

            boton.setAttribute("title", "Mostrar contraseña");
            boton.setAttribute(
                "aria-label",
                "Mostrar contraseña"
            );
        } else {
            campo.type = "text";

            icono.classList.remove("fa-eye");
            icono.classList.add("fa-eye-slash");

            boton.setAttribute("title", "Ocultar contraseña");
            boton.setAttribute(
                "aria-label",
                "Ocultar contraseña"
            );
        }
    });
}

/**
 * Colapsa o expande la barra lateral.
 *
 * Esta función se utilizará en las páginas internas,
 * como dashboard.html, productos.html e inventario.html.
 */
function inicializarMenuLateral() {
    const botonMenu = document.querySelector(".boton-menu");
    const barraLateral = document.querySelector(".barra-lateral");

    /*
     * En el index.html no existe una barra lateral,
     * por eso la función termina sin generar errores.
     */
    if (!botonMenu || !barraLateral) {
        return;
    }

    botonMenu.addEventListener("click", () => {
        barraLateral.classList.toggle(
            "barra-lateral-abierta"
        );
    });
}