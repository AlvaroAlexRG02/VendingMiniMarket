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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);

    exit;
}

try {

    $datos = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (!is_array($datos)) {
        throw new Exception("Datos inválidos.");
    }

    $descripcion = trim(
        $datos["descripcion"] ?? ""
    );

    $modulo = trim(
        $datos["modulo_afectado"] ?? ""
    );

    $prioridad = strtoupper(
        trim($datos["prioridad"] ?? "")
    );

    if ($descripcion === "") {
        throw new Exception(
            "La descripción es obligatoria."
        );
    }

    if ($modulo === "") {
        throw new Exception(
            "El módulo afectado es obligatorio."
        );
    }

    if (!in_array(
        $prioridad,
        ["BAJA", "MEDIA", "ALTA", "CRITICA"],
        true
    )) {
        throw new Exception(
            "La prioridad seleccionada no es válida."
        );
    }

    $pdo->beginTransaction();

    $consulta = $pdo->prepare("
        INSERT INTO incidencia (
            id_usuario,
            descripcion,
            modulo_afectado,
            prioridad,
            estado,
            seguimiento
        )
        VALUES (
            :id_usuario,
            :descripcion,
            :modulo_afectado,
            :prioridad,
            'ABIERTA',
            NULL
        )
        RETURNING id_incidencia
    ");

    $consulta->execute([
        ":id_usuario" =>
            $_SESSION["usuario"]["id_usuario"],

        ":descripcion" =>
            $descripcion,

        ":modulo_afectado" =>
            $modulo,

        ":prioridad" =>
            $prioridad
    ]);

    $idIncidencia =
        $consulta->fetchColumn();

    $auditoria = $pdo->prepare("
        INSERT INTO bitacora_auditoria (
            id_usuario,
            id_tienda,
            entidad,
            accion,
            valor_anterior,
            valor_nuevo,
            resultado,
            ip_origen,
            fecha
        )
        VALUES (
            :id_usuario,
            NULL,
            'INCIDENCIA',
            'CREACION',
            NULL,
            :valor_nuevo,
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ");

    $auditoria->execute([
        ":id_usuario" =>
            $_SESSION["usuario"]["id_usuario"],

        ":valor_nuevo" =>
            json_encode([
                "id_incidencia" =>
                    $idIncidencia,

                "modulo_afectado" =>
                    $modulo,

                "prioridad" =>
                    $prioridad,

                "estado" =>
                    "ABIERTA"
            ]),

        ":ip_origen" =>
            $_SERVER["REMOTE_ADDR"] ?? null
    ]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" =>
            "La incidencia fue registrada correctamente.",

        "id_incidencia" =>
            (int) $idIncidencia
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Error en api/incidencias/guardar.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "No fue posible registrar la incidencia."
    ]);
}