<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/permisos.php";
require_once __DIR__ . "/../../config/correo_helper.php";

header("Content-Type: application/json; charset=UTF-8");

function responderRestablecimientoAsistido(bool $exito, string $mensaje, int $estado): void
{
    http_response_code($estado);
    echo json_encode([
        "exito" => $exito,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    responderRestablecimientoAsistido(false, "Método no permitido.", 405);
}
if (empty($_SESSION["usuario"]["id_usuario"])) {
    responderRestablecimientoAsistido(false, "Sesión no válida.", 401);
}
if (!esAdministradorOGerente()) {
    responderRestablecimientoAsistido(false, "Acceso no autorizado.", 403);
}

$entrada = json_decode(file_get_contents("php://input") ?: "", true);
$idUsuario = is_array($entrada)
    ? filter_var($entrada["id_usuario"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
    : false;

if ($idUsuario === false) {
    responderRestablecimientoAsistido(false, "Usuario no válido.", 400);
}

try {
    $pdo->beginTransaction();
    $consulta = $pdo->prepare("
        SELECT u.id_usuario, u.correo, u.nombre, u.apellido, u.estado, r.estado AS rol_estado
        FROM usuario u
        INNER JOIN rol r ON r.id_rol = u.id_rol
        WHERE u.id_usuario = :id_usuario
        FOR UPDATE OF u
    ");
    $consulta->execute([":id_usuario" => $idUsuario]);
    $usuario = $consulta->fetch();

    if (!$usuario) {
        $pdo->rollBack();
        responderRestablecimientoAsistido(false, "No se encontró el usuario.", 404);
    }
    if (
        !in_array($usuario["estado"], [true, 1, "1", "t", "true"], true) ||
        !in_array($usuario["rol_estado"], [true, 1, "1", "t", "true"], true)
    ) {
        $pdo->rollBack();
        responderRestablecimientoAsistido(false, "No se puede restablecer la contraseña de un usuario inactivo.", 409);
    }

    $contrasenaTemporal = bin2hex(random_bytes(12));
    $actualizar = $pdo->prepare("
        UPDATE usuario
        SET password_hash = :password_hash,
            debe_cambiar_contrasena = TRUE,
            origen_cambio_contrasena = 'SOPORTE',
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id_usuario = :id_usuario
    ");
    $actualizar->execute([
        ":password_hash" => password_hash($contrasenaTemporal, PASSWORD_DEFAULT),
        ":id_usuario" => $idUsuario
    ]);

    $revocarRecuperaciones = $pdo->prepare("
        UPDATE recuperacion_contrasena
        SET estado = FALSE
        WHERE id_usuario = :id_usuario AND estado = TRUE
    ");
    $revocarRecuperaciones->execute([":id_usuario" => $idUsuario]);

    $nombre = trim($usuario["nombre"] . " " . $usuario["apellido"]);
    enviarContrasenaTemporal($usuario["correo"], $nombre, $contrasenaTemporal);

    $auditoria = $pdo->prepare("
        INSERT INTO bitacora_auditoria (
            id_usuario, id_tienda, entidad, accion, valor_anterior,
            valor_nuevo, resultado, ip_origen, fecha
        )
        VALUES (
            :id_usuario, NULL, 'USUARIO', 'RESTABLECIMIENTO_ASISTIDO',
            NULL, :valor_nuevo, 'EXITOSO', :ip_origen, NOW()
        )
    ");
    $auditoria->execute([
        ":id_usuario" => $_SESSION["usuario"]["id_usuario"],
        ":valor_nuevo" => json_encode([
            "id_usuario_objetivo" => (int)$usuario["id_usuario"],
            "correo" => $usuario["correo"],
            "contrasena_temporal_enviada" => true
        ], JSON_UNESCAPED_UNICODE),
        ":ip_origen" => $_SERVER["REMOTE_ADDR"] ?? null
    ]);
    $pdo->commit();

    responderRestablecimientoAsistido(true, "Se envió una contraseña temporal al correo del usuario.", 200);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("[restablecimiento-asistido] " . $e->getMessage());
    responderRestablecimientoAsistido(false, "No fue posible restablecer la contraseña.", 500);
}
