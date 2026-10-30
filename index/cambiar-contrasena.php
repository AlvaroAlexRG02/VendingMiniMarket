<?php

require_once __DIR__ . '/../config/session.php';

if (empty($_SESSION['usuario']['id_usuario'])) {
    header('Location: index.html');
    exit;
}
if (empty($_SESSION['usuario']['debe_cambiar_contrasena'])) {
    header('Location: ../dashboard/dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Actualice la contraseña inicial de Vending Mini Market">
    <title>Cambiar contraseña | Vending Mini Market</title>
    <link rel="stylesheet" href="../css/estilos.css?v=20261007-1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="pagina-login">
    <main class="contenedor-login">
        <section class="panel-login-formulario cambio-inicial-contenedor">
            <div class="tarjeta-login">
                <div class="icono-login"><i class="fa-solid fa-lock"></i></div>
                <header class="encabezado-login">
                    <h1>Cambie su contraseña</h1>
                    <p>Por seguridad, debe cambiar la contraseña temporal antes de usar el sistema.</p>
                </header>

                <form id="formularioCambioContrasenaInicial" class="formulario-login">
                    <div class="grupo-formulario">
                        <label for="contrasenaActualInicial">Contraseña temporal</label>
                        <input type="password" id="contrasenaActualInicial" autocomplete="current-password" maxlength="100" required>
                    </div>
                    <div class="grupo-formulario">
                        <label for="contrasenaNuevaInicial">Nueva contraseña</label>
                        <input type="password" id="contrasenaNuevaInicial" autocomplete="new-password" minlength="8" maxlength="100" required>
                    </div>
                    <div class="grupo-formulario">
                        <label for="confirmarContrasenaInicial">Confirmar nueva contraseña</label>
                        <input type="password" id="confirmarContrasenaInicial" autocomplete="new-password" minlength="8" maxlength="100" required>
                    </div>

                    <p id="mensajeCambioContrasenaInicial" class="mensaje-login" aria-live="polite"></p>

                    <button type="submit" class="boton-principal boton-ingresar">
                        <span>Actualizar contraseña</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <button type="button" id="botonCerrarSesionCambioInicial" class="boton boton-borde">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </section>
    </main>
    <script src="../js/app.js"></script>
</body>
</html>
