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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);

    exit;
}

try {

    /*
     * 1. Ejecutar el generador de respaldo real
     */
    $script = __DIR__ . "/../../scripts/generar_respaldo.php";

    $resultadoGenerador = include $script;

    /*
     * 2. Verificar que el respaldo realmente se generó
     */
    if (
        !is_array($resultadoGenerador) ||
        !isset($resultadoGenerador["success"]) ||
        $resultadoGenerador["success"] !== true
    ) {

        $pdo->beginTransaction();

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
                'RESPALDO',
                'GENERACION_RESPALDO',
                NULL,
                :valor_nuevo,
                'FALLIDO',
                :ip_origen,
                NOW()
            )
        ");

        $auditoria->execute([
            ":id_usuario" => $_SESSION["usuario"]["id_usuario"],
            ":valor_nuevo" => json_encode([
                "mensaje" => "No se pudo generar el archivo de respaldo."
            ]),
            ":ip_origen" => $_SERVER["REMOTE_ADDR"] ?? null
        ]);

        $pdo->commit();

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "No fue posible generar el respaldo."
        ]);

        exit;
    }

    /*
     * 3. Datos del respaldo generado
     */
    $nombreArchivo = $resultadoGenerador["archivo"];
    $tamano = (int) $resultadoGenerador["tamano"];

    $referencia = "respaldo/archivos/" . $nombreArchivo;

    /*
     * 4. Registrar el respaldo y la auditoría
     */
    $pdo->beginTransaction();

    $consulta = $pdo->prepare("
        INSERT INTO respaldo (
            id_usuario,
            tipo,
            fecha_inicio,
            fecha_fin,
            estado,
            cobertura,
            referencia,
            observaciones
        )
        VALUES (
            :id_usuario,
            'MANUAL',
            NOW(),
            NOW(),
            'EXITOSO',
            :cobertura,
            :referencia,
            :observaciones
        )
        RETURNING id_respaldo
    ");

    $consulta->execute([
        ":id_usuario" => $_SESSION["usuario"]["id_usuario"],
        ":cobertura" => "Base de datos y información crítica",
        ":referencia" => $referencia,
        ":observaciones" => "Respaldo generado mediante pg_dump. Tamaño: " . $tamano . " bytes."
    ]);

    $idRespaldo = $consulta->fetchColumn();

    /*
     * 5. Registrar en bitácora
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
            'RESPALDO',
            'GENERACION_RESPALDO',
            NULL,
            :valor_nuevo,
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ");

    $auditoria->execute([
        ":id_usuario" => $_SESSION["usuario"]["id_usuario"],
        ":valor_nuevo" => json_encode([
            "id_respaldo" => $idRespaldo,
            "archivo" => $nombreArchivo,
            "tamano" => $tamano
        ]),
        ":ip_origen" => $_SERVER["REMOTE_ADDR"] ?? null
    ]);

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "El respaldo fue generado y registrado correctamente.",
        "id_respaldo" => (int) $idRespaldo,
        "archivo" => $nombreArchivo,
        "tamano" => $tamano
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Error en api/respaldo/registrar.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible generar el respaldo."
    ]);
}