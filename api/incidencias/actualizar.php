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

    $idIncidencia = (int) (
        $datos["id_incidencia"] ?? 0
    );

    $estado = strtoupper(
        trim($datos["estado"] ?? "")
    );

    $seguimiento = trim(
        $datos["seguimiento"] ?? ""
    );

    if ($idIncidencia <= 0) {
        throw new Exception(
            "La incidencia seleccionada no es válida."
        );
    }

    if (!in_array(
        $estado,
        [
            "ABIERTA",
            "EN_PROCESO",
            "RESUELTA",
            "CERRADA"
        ],
        true
    )) {
        throw new Exception(
            "El estado seleccionado no es válido."
        );
    }

    if ($seguimiento === "") {
        throw new Exception(
            "El seguimiento es obligatorio."
        );
    }

    $pdo->beginTransaction();

    /*
     * Obtener información actual de la incidencia
     */
    $consulta = $pdo->prepare("
        SELECT
            estado,
            seguimiento
        FROM incidencia
        WHERE id_incidencia = :id_incidencia
        FOR UPDATE
    ");

    $consulta->execute([
        ":id_incidencia" => $idIncidencia
    ]);

    $incidenciaAnterior =
        $consulta->fetch();

    if (!$incidenciaAnterior) {
        throw new Exception(
            "La incidencia no existe."
        );
    }

    /*
     * Actualizar incidencia
     */
    $actualizar = $pdo->prepare("
        UPDATE incidencia
        SET
            estado = :estado,
            seguimiento = :seguimiento,
            fecha_actualizacion = NOW()
        WHERE id_incidencia = :id_incidencia
    ");

    $actualizar->execute([
        ":estado" =>
            $estado,

        ":seguimiento" =>
            $seguimiento,

        ":id_incidencia" =>
            $idIncidencia
    ]);

    /*
     * Registrar auditoría
     */
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
            'ACTUALIZACION',
            :valor_anterior,
            :valor_nuevo,
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ");

    $auditoria->execute([
        ":id_usuario" =>
            $_SESSION["usuario"]["id_usuario"],

        ":valor_anterior" =>
            json_encode([
                "id_incidencia" =>
                    $idIncidencia,

                "estado" =>
                    $incidenciaAnterior["estado"],

                "seguimiento" =>
                    $incidenciaAnterior["seguimiento"]
            ]),

        ":valor_nuevo" =>
            json_encode([
                "id_incidencia" =>
                    $idIncidencia,

                "estado" =>
                    $estado,

                "seguimiento" =>
                    $seguimiento
            ]),

        ":ip_origen" =>
            $_SERVER["REMOTE_ADDR"] ?? null
    ]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" =>
            "La incidencia fue actualizada correctamente."
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Error en api/incidencias/actualizar.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "No fue posible actualizar la incidencia."
    ]);
}