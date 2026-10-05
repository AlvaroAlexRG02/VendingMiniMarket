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

$entrada = json_decode(file_get_contents("php://input"), true);

$token = trim($entrada["token"] ?? "");

if ($token === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Ingrese el código de recuperación."
    ]);

    exit;
}

try {

    /*
     * Convertimos el token recibido en el mismo hash
     * que almacenamos al crear la solicitud.
     */
    $tokenHash = hash("sha256", $token);

    $sql = "
        SELECT
            r.id_recuperacion,
            r.id_usuario,
            r.fecha_expiracion,
            r.fecha_uso,
            r.estado,
            u.correo
        FROM recuperacion_contrasena r
        INNER JOIN usuario u
            ON u.id_usuario = r.id_usuario
        WHERE r.token_hash = :token_hash
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":token_hash" => $tokenHash
    ]);

    $recuperacion = $stmt->fetch();

    /*
     * No revelar detalles específicos del motivo
     * por el cual un token no es válido.
     */
    if (!$recuperacion) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El código de recuperación no es válido."
        ]);

        exit;
    }

    /*
     * Comprobamos que la solicitud siga activa.
     */
    if (!$recuperacion["estado"]) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El código de recuperación ya no está disponible."
        ]);

        exit;
    }

    /*
     * Comprobamos que el código no haya sido utilizado.
     */
    if ($recuperacion["fecha_uso"] !== null) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El código de recuperación ya fue utilizado."
        ]);

        exit;
    }

    /*
     * Comprobamos la fecha de expiración.
     */
    $ahora = new DateTimeImmutable();
    $expiracion = new DateTimeImmutable(
        $recuperacion["fecha_expiracion"]
    );

    if ($ahora >= $expiracion) {

        /*
         * Desactivamos el código expirado.
         */
        $actualizar = $pdo->prepare("
            UPDATE recuperacion_contrasena
            SET estado = FALSE
            WHERE id_recuperacion = :id_recuperacion
        ");

        $actualizar->execute([
            ":id_recuperacion" => $recuperacion["id_recuperacion"]
        ]);

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "El código de recuperación ha expirado."
        ]);

        exit;
    }

    /*
     * Guardamos temporalmente en sesión la recuperación
     * que acaba de ser validada.
     *
     * Todavía NO cambiamos la contraseña.
     */
    $_SESSION["recuperacion"] = [
        "id_recuperacion" => (int)$recuperacion["id_recuperacion"],
        "id_usuario" => (int)$recuperacion["id_usuario"],
        "correo" => $recuperacion["correo"]
    ];

    echo json_encode([
        "success" => true,
        "message" => "Código validado correctamente."
    ]);

} catch (Throwable $e) {

    error_log(
        "Error en validar_recuperacion.php: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible validar el código de recuperación."
    ]);
}