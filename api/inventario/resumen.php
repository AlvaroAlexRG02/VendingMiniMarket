<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/resumen_inventario.php';

function responderResumenInventario(bool $success, $data = null, string $message = '', int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(
        ['success' => $success, 'data' => $data, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

if (empty($_SESSION['usuario'])) {
    responderResumenInventario(false, null, 'Debe iniciar sesión.', 401);
}
if (!esAdministrador()) {
    responderResumenInventario(false, null, 'Solo Administración puede configurar el resumen periódico.', 403);
}

$entrada = [];
$accion = (string)($_GET['accion'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $entrada = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($entrada)) {
        responderResumenInventario(false, null, 'La solicitud no tiene un formato válido.', 400);
    }
    $accion = (string)($entrada['accion'] ?? $accion);
}

try {
    if ($accion === 'configuracion' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
        $consulta = $pdo->prepare("
            SELECT habilitado, frecuencia, dia_semana,
                   to_char(hora, 'HH24:MI') AS hora,
                   correo_destino, proxima_ejecucion,
                   id_usuario_configuracion, fecha_configuracion
            FROM public.resumen_inventario_config
            WHERE id_configuracion = 1
        ");
        $consulta->execute();
        $configuracion = $consulta->fetch();
        $consulta->closeCursor();
        $configuracion = $configuracion ?: [
            'habilitado' => false,
            'frecuencia' => 'DIARIA',
            'dia_semana' => 1,
            'hora' => '07:00',
            'correo_destino' => (string)($_SESSION['usuario']['correo'] ?? ''),
            'proxima_ejecucion' => null,
            'id_usuario_configuracion' => null,
            'fecha_configuracion' => null,
        ];

        $historial = $pdo->prepare("
            SELECT e.id_ejecucion, e.correo_destino, e.periodo_inicio,
                   e.periodo_fin, e.estado_envio, e.error, e.fecha_ejecucion,
                   e.fecha_finalizacion,
                   concat_ws(' ', u.nombre, u.apellido) AS responsable
            FROM public.resumen_inventario_ejecucion e
            LEFT JOIN public.usuario u
              ON u.id_usuario = e.id_usuario_configuracion
            ORDER BY e.fecha_ejecucion DESC, e.id_ejecucion DESC
            LIMIT 10
        ");
        $historial->execute();
        $registros = $historial->fetchAll();
        $historial->closeCursor();
        responderResumenInventario(true, [
            'configuracion' => $configuracion,
            'historial' => $registros,
        ]);
    }

    if ($accion !== 'guardar' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responderResumenInventario(false, null, 'Acción o método no permitido.', 405);
    }

    $habilitado = filter_var($entrada['habilitado'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $frecuencia = strtoupper(trim((string)($entrada['frecuencia'] ?? '')));
    $diaSemana = (int)($entrada['dia_semana'] ?? 1);
    $hora = trim((string)($entrada['hora'] ?? ''));
    $correo = trim((string)($entrada['correo_destino'] ?? ''));
    if (!in_array($frecuencia, ['DIARIA', 'SEMANAL'], true)
        || $diaSemana < 1 || $diaSemana > 7
        || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)
        || !filter_var($correo, FILTER_VALIDATE_EMAIL)
        || mb_strlen($correo, 'UTF-8') > 254) {
        responderResumenInventario(false, null, 'Revise la frecuencia, el día, la hora y el correo electrónico.', 400);
    }

    $proxima = $habilitado
        ? proximaEjecucionResumenInventario($frecuencia, $diaSemana, $hora)
        : null;
    $idUsuario = (int)$_SESSION['usuario']['id_usuario'];
    $pdo->beginTransaction();
    try {
        $anteriorStmt = $pdo->prepare("
            SELECT habilitado, frecuencia, dia_semana,
                   to_char(hora, 'HH24:MI') AS hora,
                   correo_destino, proxima_ejecucion,
                   id_usuario_configuracion, fecha_configuracion
            FROM public.resumen_inventario_config
            WHERE id_configuracion = 1
            FOR UPDATE
        ");
        $anteriorStmt->execute();
        $anterior = $anteriorStmt->fetch() ?: null;
        $anteriorStmt->closeCursor();

        $guardar = $pdo->prepare("
            INSERT INTO public.resumen_inventario_config
                (id_configuracion, habilitado, frecuencia, dia_semana, hora,
                 correo_destino, proxima_ejecucion, id_usuario_configuracion,
                 fecha_configuracion)
            VALUES
                (1, :habilitado, :frecuencia, :dia_semana, CAST(:hora AS TIME),
                 :correo, :proxima, :usuario, NOW())
            ON CONFLICT (id_configuracion) DO UPDATE SET
                habilitado = EXCLUDED.habilitado,
                frecuencia = EXCLUDED.frecuencia,
                dia_semana = EXCLUDED.dia_semana,
                hora = EXCLUDED.hora,
                correo_destino = EXCLUDED.correo_destino,
                proxima_ejecucion = EXCLUDED.proxima_ejecucion,
                id_usuario_configuracion = EXCLUDED.id_usuario_configuracion,
                fecha_configuracion = NOW()
        ");
        $guardar->execute([
            ':habilitado' => $habilitado,
            ':frecuencia' => $frecuencia,
            ':dia_semana' => $diaSemana,
            ':hora' => $hora,
            ':correo' => $correo,
            ':proxima' => $proxima?->format('Y-m-d H:i:sP'),
            ':usuario' => $idUsuario,
        ]);
        $guardar->closeCursor();

        $auditoria = $pdo->prepare("
            INSERT INTO public.bitacora_auditoria
                (id_usuario, id_tienda, entidad, accion, valor_anterior,
                 valor_nuevo, resultado, ip_origen, fecha)
            VALUES
                (:usuario, NULL, 'RESUMEN_INVENTARIO', 'CONFIGURAR',
                 CAST(:anterior AS JSONB), CAST(:nuevo AS JSONB),
                 'EXITOSO', :ip, NOW())
        ");
        $nuevo = [
            'habilitado' => $habilitado,
            'frecuencia' => $frecuencia,
            'dia_semana' => $diaSemana,
            'hora' => $hora,
            'correo_destino' => $correo,
            'proxima_ejecucion' => $proxima?->format(DateTimeInterface::ATOM),
        ];
        $auditoria->execute([
            ':usuario' => $idUsuario,
            ':anterior' => $anterior === null ? null : json_encode($anterior, JSON_UNESCAPED_UNICODE),
            ':nuevo' => json_encode($nuevo, JSON_UNESCAPED_UNICODE),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        $auditoria->closeCursor();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    responderResumenInventario(true, [
        'proxima_ejecucion' => $proxima?->format(DateTimeInterface::ATOM),
    ], 'Configuración guardada.');
} catch (Throwable $e) {
    error_log('[resumen-inventario] ' . $e->getMessage());
    if ($e instanceof PDOException && in_array((string)$e->getCode(), ['42P01', '42703'], true)) {
        responderResumenInventario(false, null, 'Falta actualizar el esquema. Ejecuta ResumenInventario.sql después del esquema de inventario, Seguridad.sql y Alertas.sql.', 500);
    }
    responderResumenInventario(false, null, 'No fue posible procesar la configuración del resumen.', 500);
}
