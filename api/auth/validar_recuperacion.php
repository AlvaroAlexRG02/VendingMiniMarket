<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/correo_helper.php";

header("Content-Type: application/json; charset=UTF-8");

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);
    exit;
}

$entrada = json_decode(file_get_contents("php://input") ?: "", true);
$token = is_array($entrada) ? trim($entrada["token"] ?? "") : "";

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "El enlace de recuperación no es válido."
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    $consulta = $pdo->prepare("
        SELECT
            r.id_recuperacion,
            r.id_usuario,
            r.fecha_expiracion,
            r.fecha_uso,
            r.estado AS recuperacion_estado,
            u.correo,
            u.nombre,
            u.apellido,
            u.estado AS usuario_estado,
            rol.estado AS rol_estado
        FROM recuperacion_contrasena r
        INNER JOIN usuario u ON u.id_usuario = r.id_usuario
        INNER JOIN rol ON rol.id_rol = u.id_rol
        WHERE r.token_hash = :token_hash
        LIMIT 1
        FOR UPDATE OF r, u
    ");
    $consulta->execute([":token_hash" => hash("sha256", $token)]);
    $recuperacion = $consulta->fetch();

    if (
        !$recuperacion ||
        !in_array($recuperacion["recuperacion_estado"], [true, 1, "1", "t", "true"], true) ||
        $recuperacion["fecha_uso"] !== null ||
        !in_array($recuperacion["usuario_estado"], [true, 1, "1", "t", "true"], true) ||
        !in_array($recuperacion["rol_estado"], [true, 1, "1", "t", "true"], true)
    ) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "El enlace de recuperación no es válido o ya fue utilizado."
        ]);
        exit;
    }

    if (new DateTimeImmutable() >= new DateTimeImmutable($recuperacion["fecha_expiracion"])) {
        $desactivar = $pdo->prepare("
            UPDATE recuperacion_contrasena
            SET estado = FALSE
            WHERE id_recuperacion = :id_recuperacion
        ");
        $desactivar->execute([":id_recuperacion" => $recuperacion["id_recuperacion"]]);
        $pdo->commit();

        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "El enlace de recuperación ha expirado."
        ]);
        exit;
    }

    $contrasenaTemporal = bin2hex(random_bytes(12));
    $actualizarUsuario = $pdo->prepare("
        UPDATE usuario
        SET password_hash = :password_hash,
            debe_cambiar_contrasena = TRUE,
            origen_cambio_contrasena = 'RECUPERACION',
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id_usuario = :id_usuario
    ");
    $actualizarUsuario->execute([
        ":password_hash" => password_hash($contrasenaTemporal, PASSWORD_DEFAULT),
        ":id_usuario" => $recuperacion["id_usuario"]
    ]);

    $marcarUsada = $pdo->prepare("
        UPDATE recuperacion_contrasena
        SET fecha_uso = NOW(), estado = FALSE
        WHERE id_recuperacion = :id_recuperacion
    ");
    $marcarUsada->execute([":id_recuperacion" => $recuperacion["id_recuperacion"]]);

    $nombre = trim($recuperacion["nombre"] . " " . $recuperacion["apellido"]);
    enviarContrasenaTemporal($recuperacion["correo"], $nombre, $contrasenaTemporal);
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Identidad validada. Se envió una contraseña temporal a su correo; úsela para iniciar sesión y cambiarla."
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error en validar_recuperacion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "No fue posible completar la validación. Solicite un nuevo enlace."
    ], JSON_UNESCAPED_UNICODE);
}
