<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["usuario"])) {
    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Sesión no válida."
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}

$idUsuario = $_GET["id"] ?? null;

if (!$idUsuario || !is_numeric($idUsuario)) {
    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Usuario no válido."
    ]);

    exit;
}

try {

    $consulta = $pdo->prepare("
       SELECT
    u.id_usuario,
    u.nombre,
    u.apellido,
    u.correo,
    u.id_rol,
    u.estado,
    r.nombre AS rol,
    ut.id_tienda
FROM usuario u
INNER JOIN rol r
    ON r.id_rol = u.id_rol
LEFT JOIN usuario_tienda ut
    ON ut.id_usuario = u.id_usuario
   AND ut.estado = TRUE
WHERE u.id_usuario = :id_usuario
LIMIT 1
    ");

    $consulta->execute([
        ":id_usuario" => $idUsuario
    ]);

    $usuario = $consulta->fetch();

    if (!$usuario) {

        http_response_code(404);

        echo json_encode([
            "exito" => false,
            "mensaje" => "No se encontró el usuario."
        ]);

        exit;
    }

    echo json_encode([
        "exito" => true,
        "usuario" => $usuario
    ]);

} catch (PDOException $e) {

    error_log(
        "Error al consultar usuario: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible consultar el usuario."
    ]);
}