<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);

    exit;
}

/*
 * La contraseña solamente puede cambiarse después
 * de haber validado correctamente el token.
 */
if (!isset($_SESSION["recuperacion"])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "La sesión de recuperación no es válida."
    ]);

    exit;
}

$entrada = json_decode(file_get_contents("php://input"), true);

$nuevaContrasena = $entrada["nuevaContrasena"] ?? "";
$confirmarContrasena = $entrada["confirmarContrasena"] ?? "";

if ($nuevaContrasena === "" || $confirmarContrasena === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Complete todos los campos."
    ]);

    exit;
}

if ($nuevaContrasena !== $confirmarContrasena) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Las contraseñas no coinciden."
    ]);

    exit;
}

/*
 * Validación mínima de seguridad.
 */
if (strlen($nuevaContrasena) < 8) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "La contraseña debe tener al menos 8 caracteres."
    ]);

    exit;
}

try {

    $idUsuario = (int)$_SESSION["recuperacion"]["id_usuario"];
    $idRecuperacion = (int)$_SESSION["recuperacion"]["id_recuperacion"];

    /*
     * Verificamos nuevamente que la recuperación
     * siga siendo válida.
     */
    $consulta = $pdo->prepare("
        SELECT
            id_recuperacion,
            id_usuario,
            fecha_expiracion,
            fecha_uso,
            estado
        FROM recuperacion_contrasena
        WHERE id_recuperacion = :id_recuperacion
          AND id_usuario = :id_usuario
        LIMIT 1
    ");

    $consulta->execute([
        ":id_recuperacion" => $idRecuperacion,
        ":id_usuario" => $idUsuario
    ]);

    $recuperacion = $consulta->fetch();

    if (!$recuperacion) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "La solicitud de recuperación no es válida."
        ]);

        exit;
    }

    if (!$recuperacion["estado"] || $recuperacion["fecha_uso"] !== null) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "La solicitud de recuperación ya no está disponible."
        ]);

        exit;
    }

    $ahora = new DateTimeImmutable();
    $expiracion = new DateTimeImmutable(
        $recuperacion["fecha_expiracion"]
    );

    if ($ahora >= $expiracion) {

        $actualizar = $pdo->prepare("
            UPDATE recuperacion_contrasena
            SET estado = FALSE
            WHERE id_recuperacion = :id_recuperacion
        ");

        $actualizar->execute([
            ":id_recuperacion" => $idRecuperacion
        ]);

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "La solicitud de recuperación ha expirado."
        ]);

        exit;
    }

    /*
     * Generamos el hash seguro de la nueva contraseña.
     */
    $passwordHash = password_hash(
        $nuevaContrasena,
        PASSWORD_DEFAULT
    );

    $pdo->beginTransaction();

    /*
     * Actualizamos la contraseña del usuario.
     */
    $actualizarUsuario = $pdo->prepare("
        UPDATE usuario
        SET
            password_hash = :password_hash,
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id_usuario = :id_usuario
    ");

    $actualizarUsuario->execute([
        ":password_hash" => $passwordHash,
        ":id_usuario" => $idUsuario
    ]);

    /*
     * Marcamos la recuperación como utilizada.
     */
    $marcarUsada = $pdo->prepare("
        UPDATE recuperacion_contrasena
        SET
            fecha_uso = NOW(),
            estado = FALSE
        WHERE id_recuperacion = :id_recuperacion
    ");

    $marcarUsada->execute([
        ":id_recuperacion" => $idRecuperacion
    ]);

    /*
     * Registramos el restablecimiento en la bitácora.
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
            'USUARIO',
            'RESTABLECIMIENTO_CONTRASENA',
            NULL,
            NULL,
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ");

    $auditoria->execute([
        ":id_usuario" => $idUsuario,
        ":ip_origen" => $_SERVER["REMOTE_ADDR"] ?? null
    ]);

    $pdo->commit();

    /*
     * Eliminamos la sesión de recuperación.
     */
    unset($_SESSION["recuperacion"]);

    echo json_encode([
        "success" => true,
        "message" => "La contraseña se restableció correctamente."
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Error en restablecer_contrasena.php: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "ERROR PHP: " . $e->getMessage()
    ]);
}