<?php

header("Content-Type: application/json; charset=UTF-8");
http_response_code(410);
echo json_encode([
    "success" => false,
    "message" => "Este flujo ya no está disponible. Inicie sesión con su contraseña temporal y cámbiela."
], JSON_UNESCAPED_UNICODE);