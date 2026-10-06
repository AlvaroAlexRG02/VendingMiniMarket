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

    $usuario = $_GET["usuario"] ?? "";
    $fechaInicio = $_GET["fecha_inicio"] ?? "";
    $fechaFin = $_GET["fecha_fin"] ?? "";
    $entidad = $_GET["entidad"] ?? "";
    $accion = $_GET["accion"] ?? "";
    $resultado = $_GET["resultado"] ?? "";

    $sql = "
        SELECT
            b.id_evento,
            b.id_usuario,
            CONCAT(
                u.nombre,
                ' ',
                COALESCE(u.apellido, '')
            ) AS usuario,
            b.id_tienda,
            t.nombre AS tienda,
            b.entidad,
            b.accion,
            b.resultado,
            b.ip_origen,
            b.fecha
        FROM bitacora_auditoria b
        LEFT JOIN usuario u
            ON u.id_usuario = b.id_usuario
        LEFT JOIN tienda t
            ON t.id_tienda = b.id_tienda
        WHERE 1 = 1
    ";

    $parametros = [];

    if ($usuario !== "") {

        $sql .= "
            AND b.id_usuario = :usuario
        ";

        $parametros[":usuario"] = (int) $usuario;
    }

    if ($fechaInicio !== "") {

        $sql .= "
            AND b.fecha >= :fecha_inicio
        ";

        $parametros[":fecha_inicio"] = $fechaInicio . " 00:00:00";
    }

    if ($fechaFin !== "") {

        $sql .= "
            AND b.fecha < (:fecha_fin::date + INTERVAL '1 day')
        ";

        $parametros[":fecha_fin"] = $fechaFin;
    }

    if ($entidad !== "") {

        $sql .= "
            AND b.entidad = :entidad
        ";

        $parametros[":entidad"] = $entidad;
    }

    if ($accion !== "") {

        $sql .= "
            AND b.accion = :accion
        ";

        $parametros[":accion"] = $accion;
    }

    if ($resultado !== "") {

        $sql .= "
            AND b.resultado = :resultado
        ";

        $parametros[":resultado"] = $resultado;
    }

    $sql .= "
        ORDER BY b.fecha DESC
    ";

    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);

    $registros = $consulta->fetchAll();

    echo json_encode([
        "success" => true,
        "data" => $registros
    ]);

} catch (PDOException $e) {

    error_log(
        "Error en api/bitacora/listar.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible consultar la bitácora."
    ]);
}