<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json; charset=UTF-8');

function responderCambioInicial(bool $exito, string $mensaje, int $estado): void
{
    http_response_code($estado);
    echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responderCambioInicial(false, 'Método no permitido.', 405);
}
if (empty($_SESSION['usuario']['id_usuario'])) {
    responderCambioInicial(false, 'Debe iniciar sesión.', 401);
}
if (empty($_SESSION['usuario']['debe_cambiar_contrasena'])) {
    responderCambioInicial(false, 'No hay un cambio inicial de contraseña pendiente.', 409);
}

$entrada = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($entrada)) {
    responderCambioInicial(false, 'La solicitud no tiene un formato válido.', 400);
}

$actual = (string)($entrada['contrasena_actual'] ?? '');
$nueva = (string)($entrada['contrasena_nueva'] ?? '');
$confirmacion = (string)($entrada['confirmar_contrasena'] ?? '');
if ($actual === '' || $nueva === '' || $confirmacion === '') {
    responderCambioInicial(false, 'Complete todos los campos.', 400);
}
if (strlen($nueva) < 8 || strlen($nueva) > 100) {
    responderCambioInicial(false, 'La nueva contraseña debe tener entre 8 y 100 caracteres.', 400);
}
if ($nueva !== $confirmacion) {
    responderCambioInicial(false, 'La nueva contraseña y su confirmación no coinciden.', 400);
}
if (hash_equals($actual, $nueva)) {
    responderCambioInicial(false, 'La nueva contraseña debe ser diferente de la contraseña temporal.', 400);
}

$idUsuario = (int)$_SESSION['usuario']['id_usuario'];
try {
    $pdo->beginTransaction();
    $consulta = $pdo->prepare("
        SELECT password_hash, debe_cambiar_contrasena
        FROM public.usuario
        WHERE id_usuario = :id_usuario
          AND estado = TRUE
        FOR UPDATE
    ");
    $consulta->execute([':id_usuario' => $idUsuario]);
    $usuario = $consulta->fetch();
    $consulta->closeCursor();

    if (!$usuario || !$usuario['debe_cambiar_contrasena']) {
        $pdo->rollBack();
        responderCambioInicial(false, 'No hay un cambio inicial de contraseña pendiente.', 409);
    }
    if (!password_verify($actual, $usuario['password_hash'])) {
        $pdo->rollBack();
        responderCambioInicial(false, 'La contraseña temporal no es correcta.', 401);
    }

    $actualizar = $pdo->prepare("
        UPDATE public.usuario
        SET password_hash = :password_hash,
            debe_cambiar_contrasena = FALSE,
            intentos_fallidos = 0,
            bloqueado_hasta = NULL
        WHERE id_usuario = :id_usuario
    ");
    $actualizar->execute([
        ':password_hash' => password_hash($nueva, PASSWORD_DEFAULT),
        ':id_usuario' => $idUsuario,
    ]);

    $auditoria = $pdo->prepare("
        INSERT INTO public.bitacora_auditoria
            (id_usuario, id_tienda, entidad, accion, valor_anterior,
             valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:id_usuario, NULL, 'USUARIO', 'CAMBIO_CONTRASENA_INICIAL',
             NULL, NULL, 'EXITOSO', :ip, NOW())
    ");
    $auditoria->execute([
        ':id_usuario' => $idUsuario,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    $pdo->commit();

    session_regenerate_id(true);
    $_SESSION['usuario']['debe_cambiar_contrasena'] = false;
    responderCambioInicial(true, 'Contraseña actualizada. Ya puede ingresar al sistema.', 200);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[cambio-contrasena-inicial] ' . $e->getMessage());
    responderCambioInicial(false, 'No fue posible actualizar la contraseña.', 500);
}
