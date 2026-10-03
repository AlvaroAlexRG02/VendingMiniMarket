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

/*
 * Verificar que exista una sesión autenticada.
 */
if (!isset($_SESSION["usuario"])) {

    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No hay una sesión activa."
    ]);

    exit;
}

$usuario = $_SESSION["usuario"];

try {

    /*
     * Registrar el cierre de sesión en la bitácora.
     */
    $sqlAuditoria = "
        INSERT INTO bitacora_auditoria (
            id_usuario,
            entidad,
            accion,
            resultado,
            ip_origen,
            fecha
        )
        VALUES (
            :id_usuario,
            'USUARIO',
            'CIERRE_SESION',
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ";

    $stmtAuditoria = $pdo->prepare($sqlAuditoria);

    $stmtAuditoria->execute([
        ":id_usuario" => $usuario["id_usuario"],
        ":ip_origen" => $_SERVER["REMOTE_ADDR"] ?? null
    ]);

    /*
     * Vaciar las variables de sesión.
     */
    $_SESSION = [];

    /*
     * Eliminar la cookie de sesión.
     */
    if (ini_get("session.use_cookies")) {

        $parametrosCookie = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $parametrosCookie["path"],
            $parametrosCookie["domain"],
            $parametrosCookie["secure"],
            $parametrosCookie["httponly"]
        );
    }

    /*
     * Destruir completamente la sesión.
     */
    session_destroy();

    echo json_encode([
        "exito" => true,
        "mensaje" => "Sesión cerrada correctamente."
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible cerrar la sesión."
    ]);
}