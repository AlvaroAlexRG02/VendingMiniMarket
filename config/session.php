<?php

session_set_cookie_params([
    'httponly' => true,
    'secure' => false,//va a cmabiar a true cuando sea https
    'samesite' => 'Lax'
]);

session_start();

if (empty($_SESSION['usuario']['debe_cambiar_contrasena'])) {
    return;
}

$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$rutasPermitidas = [
    '/index/cambiar-contrasena.php',
    '/api/auth/cambiar_contrasena_inicial.php',
    '/api/auth/logout.php',
];
foreach ($rutasPermitidas as $rutaPermitida) {
    if (str_ends_with($rutaActual, $rutaPermitida)) {
        return;
    }
}

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

header('Location: /VendingMiniMarket/index/cambiar-contrasena.php');
exit;