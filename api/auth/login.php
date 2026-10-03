<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}

$datos = json_decode(file_get_contents("php://input"), true);

$correo = trim($datos["correo"] ?? "");
$contrasena = $datos["contrasena"] ?? "";

if ($correo === "" || $contrasena === "") {
    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Debe ingresar el correo y la contraseña."
    ]);

    exit;
}

try {

    $sql = "
        SELECT
            u.id_usuario,
            u.id_rol,
            u.nombre,
            u.apellido,
            u.correo,
            u.password_hash,
            u.estado,
            u.intentos_fallidos,
            u.bloqueado_hasta,
            r.nombre AS rol_nombre,
            r.estado AS rol_estado
        FROM usuario u
        INNER JOIN rol r
            ON r.id_rol = u.id_rol
        WHERE LOWER(u.correo) = LOWER(:correo)
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":correo" => $correo
    ]);

    $usuario = $stmt->fetch();

    /*
     * Mensaje genérico para no revelar si
     * el correo existe o no.
     */
    if (!$usuario) {
        http_response_code(401);

        echo json_encode([
            "exito" => false,
            "mensaje" => "Las credenciales ingresadas no son válidas."
        ]);

        exit;
    }

    /*
     * Verificar si la cuenta está activa.
     */
    if (!$usuario["estado"] || !$usuario["rol_estado"]) {
        http_response_code(401);

        echo json_encode([
            "exito" => false,
            "mensaje" => "Las credenciales ingresadas no son válidas."
        ]);

        exit;
    }

    if (
        $usuario["bloqueado_hasta"] !== null &&
        strtotime($usuario["bloqueado_hasta"]) <= time()
    ) {

        $sqlDesbloqueo = "
            UPDATE usuario
            SET
                intentos_fallidos = 0,
                bloqueado_hasta = NULL
            WHERE id_usuario = :id_usuario
        ";

        $stmtDesbloqueo = $pdo->prepare($sqlDesbloqueo);

        $stmtDesbloqueo->execute([
            ":id_usuario" => $usuario["id_usuario"]
        ]);

        $usuario["intentos_fallidos"] = 0;
        $usuario["bloqueado_hasta"] = null;
    }

    /*
     * Verificar si la cuenta está temporalmente bloqueada.
     */
    if (
        $usuario["bloqueado_hasta"] !== null &&
        strtotime($usuario["bloqueado_hasta"]) > time()
    ) {
        http_response_code(401);

        echo json_encode([
            "exito" => false,
            "mensaje" => "Las credenciales ingresadas no son válidas."
        ]);

        exit;
    }

    /*
     * Verificar contraseña.
     */
   if (!password_verify($contrasena, $usuario["password_hash"])) {

    /*
     * Máximo de intentos permitidos.
     */
    $maximoIntentos = 5;

    /*
     * Duración provisional del bloqueo.
     * Si el requisito del proyecto establece otro tiempo,
     * solo debemos modificar este valor.
     */
    $minutosBloqueo = 15;

    /*
     * El valor actual de intentos_fallidos viene de la BD.
     * Sumamos 1 por el intento actual.
     */
    $nuevosIntentos = (int)$usuario["intentos_fallidos"] + 1;

    if ($nuevosIntentos >= $maximoIntentos) {

        $sqlIntento = "
            UPDATE usuario
            SET
                intentos_fallidos = :intentos,
                bloqueado_hasta = NOW() + (:minutos * INTERVAL '1 minute')
            WHERE id_usuario = :id_usuario
        ";

        $stmtIntento = $pdo->prepare($sqlIntento);

        $stmtIntento->execute([
            ":intentos" => $nuevosIntentos,
            ":minutos" => $minutosBloqueo,
            ":id_usuario" => $usuario["id_usuario"]
        ]);

    } else {

        $sqlIntento = "
            UPDATE usuario
            SET intentos_fallidos = :intentos
            WHERE id_usuario = :id_usuario
        ";

        $stmtIntento = $pdo->prepare($sqlIntento);

        $stmtIntento->execute([
            ":intentos" => $nuevosIntentos,
            ":id_usuario" => $usuario["id_usuario"]
        ]);
    }

    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Las credenciales ingresadas no son válidas."
    ]);

    exit;
}

    /*
     * Regenerar el identificador de sesión
     * después de una autenticación exitosa.
     */
    session_regenerate_id(true);

    /*
     * Guardar información mínima del usuario
     * en la sesión.
     */
    $_SESSION["usuario"] = [
        "id_usuario" => $usuario["id_usuario"],
        "id_rol" => $usuario["id_rol"],
        "nombre" => $usuario["nombre"],
        "apellido" => $usuario["apellido"],
        "correo" => $usuario["correo"],
        "rol" => $usuario["rol_nombre"]
    ];

    /*
     * Actualizar información de acceso.
     */
    $sqlActualizar = "
        UPDATE usuario
        SET
            ultimo_acceso = NOW(),
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id_usuario = :id_usuario
    ";

    $stmtActualizar = $pdo->prepare($sqlActualizar);

    $stmtActualizar->execute([
        ":id_usuario" => $usuario["id_usuario"]
    ]);

    echo json_encode([
        "exito" => true,
        "mensaje" => "Inicio de sesión exitoso.",
        "usuario" => $_SESSION["usuario"]
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Ocurrió un error al procesar el inicio de sesión."
    ]);
}