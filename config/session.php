
<?php

// Detectar si la aplicación utiliza HTTPS.
// Funciona tanto en localhost como detrás del proxy de DigitalOcean.
$esHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0])) === 'https'
);

// Configuración de seguridad de la sesión.
session_set_cookie_params([
    'httponly' => true,
    'secure' => $esHttps,
    'samesite' => 'Lax',
    'path' => '/'
]);

session_start();

// Rol visible para el JS del menú lateral (solo afecta lo que se muestra;
// los permisos reales se validan siempre en el servidor).
if (!empty($_SESSION['usuario']['id_rol']) && !headers_sent()) {
    setcookie('vm_rol', (string)(int)$_SESSION['usuario']['id_rol'], [
        'path' => '/',
        'secure' => $esHttps,
        'samesite' => 'Lax',
    ]);
}

// Si el usuario no debe cambiar su contraseña, continúa normalmente.
if (empty($_SESSION['usuario']['debe_cambiar_contrasena'])) {
    return;
}

// Comprobar la ruta actual.
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$rutasPermitidas = [
    '/index/cambiar-contrasena.php',
    '/api/auth/cambiar_contrasena_inicial.php',
    '/api/auth/logout.php',
];

// Permitir únicamente las rutas necesarias para cambiar la contraseña.
foreach ($rutasPermitidas as $rutaPermitida) {
    if (str_ends_with($rutaActual, $rutaPermitida)) {
        return;
    }
}

// Bloquear otras operaciones de la API hasta cambiar la contraseña.
if (str_contains($rutaActual, '/api/')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Debe cambiar su contraseña antes de continuar.',
        'debe_cambiar_contrasena' => true,
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Redirigir a la página de cambio obligatorio de contraseña.
header('Location: /index/cambiar-contrasena.php');
exit;
