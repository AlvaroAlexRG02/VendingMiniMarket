<?php

/*
 * Datos del usuario con sesión activa. Lo usan las pantallas estáticas (.html),
 * que no pueden renderizar el perfil de la barra superior con PHP.
 */

require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/permisos.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

if (!isset($_SESSION["usuario"])) {
    http_response_code(401);
    echo json_encode(["exito" => false, "mensaje" => "No hay una sesión activa."]);
    exit;
}

$usuario = $_SESSION["usuario"];

echo json_encode([
    "exito" => true,
    "data" => [
        "nombre" => trim(($usuario["nombre"] ?? "") . " " . ($usuario["apellido"] ?? "")),
        "correo" => (string)($usuario["correo"] ?? ""),
        "rol" => (string)($usuario["rol"] ?? ""),
        "id_rol" => (int)($usuario["id_rol"] ?? 0),
        "ubicacion" => resumenUbicacionUsuario($pdo)["texto"],
    ],
], JSON_UNESCAPED_UNICODE);
