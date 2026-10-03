<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";

header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| Verificar sesión
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario"])) {

    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Sesión no válida."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Verificar método
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Consultar usuarios
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT
            u.id_usuario,
            u.nombre,
            u.apellido,
            u.correo,
            u.estado,
            u.ultimo_acceso,
            u.intentos_fallidos,
            u.bloqueado_hasta,
            r.id_rol,
            r.nombre AS rol
        FROM usuario u
        INNER JOIN rol r
            ON r.id_rol = u.id_rol
        ORDER BY u.id_usuario ASC
    ";

    $consulta = $pdo->query($sql);

    $usuarios = $consulta->fetchAll();


    echo json_encode([
        "exito" => true,
        "usuarios" => $usuarios
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible consultar los usuarios."
    ]);
}