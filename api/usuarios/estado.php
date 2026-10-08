<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/permisos.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["usuario"])) {
    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Sesión no válida."
    ]);

    exit;
}

if (!esAdministradorOGerente()) {
    http_response_code(403);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Acceso no autorizado."
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}

$datos = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($datos)) {
    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Datos inválidos."
    ]);

    exit;
}

$idUsuario = $datos["id_usuario"] ?? null;

if (!$idUsuario || !is_numeric($idUsuario)) {
    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Usuario no válido."
    ]);

    exit;
}

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Consultar estado actual
    |--------------------------------------------------------------------------
    */

    $consulta = $pdo->prepare("
        SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            estado
        FROM usuario
        WHERE id_usuario = :id_usuario
        FOR UPDATE
    ");

    $consulta->execute([
        ":id_usuario" => $idUsuario
    ]);

    $usuario = $consulta->fetch();

    if (!$usuario) {

        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            "exito" => false,
            "mensaje" => "No se encontró el usuario."
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Nuevo estado
    |--------------------------------------------------------------------------
    */

   $estadoAnterior =
    $usuario["estado"] === true ||
    $usuario["estado"] === "true" ||
    $usuario["estado"] === "t" ||
    $usuario["estado"] === 1 ||
    $usuario["estado"] === "1";

$estadoNuevo = !$estadoAnterior;


    /*
    |--------------------------------------------------------------------------
    | Actualizar estado
    |--------------------------------------------------------------------------
    */

    $actualizar = $pdo->prepare("
        UPDATE usuario
        SET estado = :estado
        WHERE id_usuario = :id_usuario
    ");

    $actualizar->execute([
    ":estado" => $estadoNuevo ? "true" : "false",
    ":id_usuario" => $idUsuario
]);


    /*
    |--------------------------------------------------------------------------
    | Auditoría
    |--------------------------------------------------------------------------
    */

    $auditoria = $pdo->prepare("
        INSERT INTO bitacora_auditoria (
            id_usuario,
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
            'USUARIO',
            'CAMBIO_ESTADO',
            :valor_anterior,
            :valor_nuevo,
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ");

    $valorAnterior = json_encode([
        "id_usuario" => $usuario["id_usuario"],
        "estado" => $estadoAnterior
    ]);

    $valorNuevo = json_encode([
        "id_usuario" => $usuario["id_usuario"],
        "estado" => $estadoNuevo
    ]);

    $auditoria->execute([
        ":id_usuario" =>
            $_SESSION["usuario"]["id_usuario"],

        ":valor_anterior" =>
            $valorAnterior,

        ":valor_nuevo" =>
            $valorNuevo,

        ":ip_origen" =>
            $_SERVER["REMOTE_ADDR"] ?? null
    ]);


    $pdo->commit();

    echo json_encode([
        "exito" => true,
        "mensaje" => $estadoNuevo
            ? "Usuario activado correctamente."
            : "Usuario desactivado correctamente.",
        "estado" => $estadoNuevo
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Error al cambiar estado del usuario: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible cambiar el estado del usuario."
    ]);
}