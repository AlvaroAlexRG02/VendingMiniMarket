<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";

require_once __DIR__ . "/../../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$correo = trim($entrada["correo"] ?? "");

if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Ingrese un correo electrónico válido."
    ]);

    exit;
}

try {
    $configCorreo = require __DIR__ . "/../../config/correo.php";

    /*
     * Buscamos el usuario utilizando la misma lógica
     * que utiliza login.php.
     */
    $sql = "
        SELECT
            u.id_usuario,
            u.correo,
            u.estado,
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
     * No revelamos si el correo existe o no.
     * Esto evita que alguien pueda descubrir
     * qué correos están registrados en el sistema.
     */
    if (!$usuario || !$usuario["estado"] || !$usuario["rol_estado"]) {

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

    /*
     * Desactivamos solicitudes anteriores
     * todavía activas para este usuario.
     */
    $desactivar = $pdo->prepare("
        UPDATE recuperacion_contrasena
        SET estado = FALSE
        WHERE id_usuario = :id_usuario
          AND estado = TRUE
    ");

    $desactivar->execute([
        ":id_usuario" => $usuario["id_usuario"]
    ]);

    /*
     * Guardamos la nueva solicitud.
     */
    $insertar = $pdo->prepare("
        INSERT INTO recuperacion_contrasena (
            id_usuario,
            token_hash,
            fecha_expiracion,
            estado
        )
        VALUES (
            :id_usuario,
            :token_hash,
            :fecha_expiracion,
            TRUE
        )
    ");

    $insertar->execute([
        ":id_usuario" => $usuario["id_usuario"],
        ":token_hash" => $tokenHash,
        ":fecha_expiracion" => $fechaExpiracion
    ]);

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $configCorreo["host"];
    $mail->SMTPAuth = true;
    $mail->Username = $configCorreo["username"];
    $mail->Password = $configCorreo["password"];
    $mail->SMTPSecure = $configCorreo["encryption"];
    $mail->Port = $configCorreo["port"];
    $mail->CharSet = "UTF-8";

    $mail->setFrom(
        $configCorreo["from_email"],
        $configCorreo["from_name"]
    );

    $mail->addAddress(
        $usuario["correo"]
    );

    $enlaceRecuperacion =
        "http://localhost/VendingMiniMarket/index/validar-recuperacion.html?token=" .
        urlencode($token);

    $mail->isHTML(true);

    $mail->Subject = "Recuperación de contraseña - Vending Mini Market";

    $mail->Body = "
        <div style='font-family: Arial, sans-serif;'>
            <h2>Vending Mini Market</h2>

            <p>
                Hemos recibido una solicitud para restablecer
                su contraseña.
            </p>

            <p>
                Para continuar con el proceso, haga clic en el
                siguiente botón:
            </p>

            <p>
                <a
                    href='{$enlaceRecuperacion}'
                    style='
                        display:inline-block;
                        padding:12px 20px;
                        background:#0d6efd;
                        color:white;
                        text-decoration:none;
                        border-radius:6px;
                    '
                >
                    Restablecer contraseña
                </a>
            </p>

            <p>
                Este enlace será válido durante 15 minutos.
            </p>

            <p>
                Si usted no solicitó este cambio, puede ignorar
                este correo.
            </p>
        </div>
    ";

    $mail->AltBody =
        "Hemos recibido una solicitud para restablecer su contraseña. " .
        "Utilice el siguiente enlace: " .
        $enlaceRecuperacion .
        ". El enlace será válido durante 15 minutos.";

    $mail->send();

    echo json_encode([
        "success" => true,
        "message" =>
            "Si el correo está registrado, se continuará con el proceso de recuperación."
    ]);

} catch (Throwable $e) {

    error_log(
        "Error en solicitar_recuperacion.php: " . $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible procesar la solicitud de recuperación."
    ]);
}