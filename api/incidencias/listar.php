<?php

require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/permisos.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["usuario"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión no válida."
    ]);

    exit;
}

if (!esAdministrador()) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Acceso no autorizado."
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);

    exit;
}

try {

    $consulta = $pdo->query("
        SELECT
            i.id_incidencia,
            i.descripcion,
            i.modulo_afectado,
            i.prioridad,
            i.estado,
            i.seguimiento,
            i.fecha_registro,
            i.fecha_actualizacion,
            u.nombre,
            u.apellido
        FROM incidencia i
        INNER JOIN usuario u
            ON u.id_usuario = i.id_usuario
        ORDER BY i.fecha_registro DESC
    ");

    $incidencias = $consulta->fetchAll();

    echo json_encode([
        "success" => true,
        "data" => $incidencias
    ]);

} catch (Throwable $e) {

    error_log(
        "Error en api/incidencias/listar.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "No fue posible consultar las incidencias."
    ]);
}