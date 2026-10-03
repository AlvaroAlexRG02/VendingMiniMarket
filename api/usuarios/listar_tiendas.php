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

try {

    $consulta = $pdo->query("
    SELECT
        id_tienda,
        nombre,
        estado
    FROM tienda
    WHERE estado = TRUE
    ORDER BY nombre ASC
");

    $tiendas = $consulta->fetchAll();

    echo json_encode([
        "exito" => true,
        "tiendas" => $tiendas
    ]);

} catch (PDOException $e) {

    error_log(
        "Error al consultar tiendas: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
    "exito" => false,
    "mensaje" => "No fue posible consultar las tiendas."
]);
}