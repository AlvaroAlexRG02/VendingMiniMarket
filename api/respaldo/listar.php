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

if (!esAdministradorOGerente()) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Acceso no autorizado."
    ]);

    exit;
}

try {

    $consulta = $pdo->query("
        SELECT
            r.id_respaldo,
            r.id_usuario,
            CONCAT(
                u.nombre,
                ' ',
                COALESCE(u.apellido, '')
            ) AS usuario,
            r.tipo,
            r.fecha_inicio,
            r.fecha_fin,
            r.estado,
            r.cobertura,
            r.referencia,
            r.observaciones,
            r.fecha_registro
        FROM respaldo r
        LEFT JOIN usuario u
            ON u.id_usuario = r.id_usuario
        ORDER BY r.fecha_inicio DESC
    ");

    $respaldos = $consulta->fetchAll();

    echo json_encode([
        "success" => true,
        "data" => $respaldos
    ]);

} catch (PDOException $e) {

    error_log(
        "Error en api/respaldo/listar.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible consultar los respaldos."
    ]);
}