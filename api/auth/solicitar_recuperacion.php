<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/correo_helper.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Método no permitido."
    ]);

    exit;
}

$entrada = json_decode(file_get_contents("php://input") ?: "", true);

$correo = is_array($entrada) ? trim($entrada["correo"] ?? "") : "";

if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Ingrese un correo electrónico válido."
    ]);

    exit;
}

try {
    $pdo->beginTransaction();

    /*
     * Buscamos el usuario utilizando la misma lógica
     * que utiliza login.php.
     */
    $sql = "
        SELECT
            u.id_usuario,
            u.correo,
            u.estado AS usuario_estado,
            r.estado AS rol_estado
        FROM usuario u
        INNER JOIN rol r
            ON r.id_rol = u.id_rol
        WHERE LOWER(u.correo) = LOWER(:correo)
        LIMIT 1
        FOR UPDATE OF u
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":correo" => $correo
    ]);

    $usuario = $stmt->fetch();

    /*
     * No revelamos si el correo existe o no.
     * Esto evita que alguien pueda descubrir
     * qué correos están registrados en el sistema.
     */
    if (
        !$usuario ||
        !in_array($usuario["usuario_estado"], [true, 1, "1", "t", "true"], true) ||
        !in_array($usuario["rol_estado"], [true, 1, "1", "t", "true"], true)
    ) {
        $pdo->rollBack();

        echo json_encode([
            "success" => true,
            "message" => "Si el correo está registrado, se continuará con el proceso de recuperación."
        ]);

        exit;
    }

    /*
     * Generamos un token seguro.
     */
    $token = bin2hex(random_bytes(32));

    /*
     * Guardamos solamente el hash del token.
     */
    $tokenHash = hash("sha256", $token);

    /*
     * El token tendrá una vigencia de 15 minutos.
     */
    $fechaExpiracion = (new DateTimeImmutable())
        ->modify("+15 minutes")
        ->format("Y-m-d H:i:sP");

    $baseUrl = rtrim(getenv("APP_BASE_URL") ?: "http://localhost/VendingMiniMarket", "/");
    $partesUrl = parse_url($baseUrl);
    if (
        !filter_var($baseUrl, FILTER_VALIDATE_URL) ||
        !in_array($partesUrl["scheme"] ?? "", ["http", "https"], true) ||
        empty($partesUrl["host"])
    ) {
        throw new RuntimeException("APP_BASE_URL debe ser una URL HTTP o HTTPS válida.");
    }

    $enlaceRecuperacion = $baseUrl . "/index/validar-recuperacion.html?token=" . urlencode($token);
    $desactivar = $pdo->prepare("
        UPDATE recuperacion_contrasena
        SET estado = FALSE
        WHERE id_usuario = :id_usuario
          AND estado = TRUE
    ");
    $desactivar->execute([":id_usuario" => $usuario["id_usuario"]]);

    $insertar = $pdo->prepare("
        INSERT INTO recuperacion_contrasena (
            id_usuario, token_hash, fecha_expiracion, estado
        )
        VALUES (
            :id_usuario, :token_hash, :fecha_expiracion, TRUE
        )
    ");
    $insertar->execute([
        ":id_usuario" => $usuario["id_usuario"],
        ":token_hash" => $tokenHash,
        ":fecha_expiracion" => $fechaExpiracion
    ]);

    enviarEnlaceRecuperacion($usuario["correo"], $enlaceRecuperacion);
    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" =>
            "Si el correo está registrado, se continuará con el proceso de recuperación."
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Error en solicitar_recuperacion.php: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible procesar la solicitud de recuperación."
    ]);
}