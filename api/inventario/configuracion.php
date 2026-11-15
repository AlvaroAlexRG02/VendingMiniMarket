
<?php
/**
 * HU-28 — Configuración de stock negativo.
 * GET: consulta la configuración.
 * POST: permite al administrador modificarla.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/database.php';

function responderConfiguracion28(
    bool $success,
    $data = null,
    string $message = '',
    int $codigo = 200
): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    exit;
}

function booleanoConfiguracion28(mixed $valor): bool
{
    return in_array(
        $valor,
        [true, 1, '1', 't', 'true'],
        true
    );
}

// Permisos: exclusivamente Administrador.
if (empty($_SESSION['usuario']['id_usuario'])) {
    responderConfiguracion28(
        false, null, 'Debe iniciar sesión.', 401
    );
}

if (!esAdministrador()) {
    responderConfiguracion28(
        false, null,
        'Solo el Administrador puede modificar esta configuración.',
        403
    );
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

if (!in_array($metodo, ['GET', 'POST'], true)) {
    responderConfiguracion28(
        false, null, 'Método no permitido.', 405
    );
}

try {

    // CONSULTAR CONFIGURACIÓN
    if ($metodo === 'GET') {

        if (!isset($_SESSION['csrf_configuracion_28'])) {
            $_SESSION['csrf_configuracion_28'] =
                bin2hex(random_bytes(32));
        }

        $consulta = $pdo->prepare("
            SELECT
                id_configuracion,
                permitir_stock_negativo,
                requiere_autorizacion_negativo,
                id_usuario_actualiza,
                fecha_actualizacion
            FROM public.configuracion_inventario
            WHERE id_configuracion = 1
        ");

        $consulta->execute();
        $configuracion = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$configuracion) {
            responderConfiguracion28(
                false, null,
                'No existe la configuración del inventario.',
                404
            );
        }

        $configuracion['permitir_stock_negativo'] =
            booleanoConfiguracion28(
                $configuracion['permitir_stock_negativo']
            );

        $configuracion['requiere_autorizacion_negativo'] =
            booleanoConfiguracion28(
                $configuracion['requiere_autorizacion_negativo']
            );

        $configuracion['csrf_token'] =
            $_SESSION['csrf_configuracion_28'];

        responderConfiguracion28(
            true,
            $configuracion,
            'Configuración consultada correctamente.'
        );
    }

    // GUARDAR CONFIGURACIÓN
    // Token de seguridad para las solicitudes POST.
    $token = (string)(
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''
    );

    if (
        !isset($_SESSION['csrf_configuracion_28']) ||
        !hash_equals(
            (string) $_SESSION['csrf_configuracion_28'],
            $token
        )
    ) {
        responderConfiguracion28(
            false, null,
            'Solicitud no autorizada. Recargue la configuración.',
            403
        );
    }

    if (
        stripos(
            (string)($_SERVER['CONTENT_TYPE'] ?? ''),
            'application/json'
        ) !== 0
    ) {
        responderConfiguracion28(
            false, null,
            'Debe enviar la configuración en JSON.',
            415
        );
    }

    $entrada = json_decode(
        file_get_contents('php://input') ?: '',
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (
        !is_array($entrada) ||
        !array_key_exists('permitir_stock_negativo', $entrada) ||
        !is_bool($entrada['permitir_stock_negativo'])
    ) {
        responderConfiguracion28(
            false, null,
            'Indique permitir_stock_negativo como true o false.',
            400
        );
    }

    $permitir = $entrada['permitir_stock_negativo'];
    $idUsuario = (int) $_SESSION['usuario']['id_usuario'];

    $pdo->beginTransaction();

    try {
        $consulta = $pdo->query("
            SELECT
                id_configuracion,
                permitir_stock_negativo,
                requiere_autorizacion_negativo,
                id_usuario_actualiza,
                fecha_actualizacion
            FROM public.configuracion_inventario
            WHERE id_configuracion = 1
            FOR UPDATE
        ");

        $anterior = $consulta->fetch(PDO::FETCH_ASSOC);
        $consulta->closeCursor();

        if (!$anterior) {
            throw new RuntimeException(
                'La configuración de inventario no existe.'
            );
        }

        $anterior['permitir_stock_negativo'] =
            booleanoConfiguracion28(
                $anterior['permitir_stock_negativo']
            );

        $anterior['requiere_autorizacion_negativo'] =
            booleanoConfiguracion28(
                $anterior['requiere_autorizacion_negativo']
            );

        // Evitar cambios y auditorías innecesarias.
        if (
            $anterior['permitir_stock_negativo'] === $permitir &&
            $anterior['requiere_autorizacion_negativo']
        ) {
            $pdo->commit();

            responderConfiguracion28(
                true, $anterior,
                'La configuración ya tenía ese valor.'
            );
        }

        $guardar = $pdo->prepare("
            UPDATE public.configuracion_inventario
            SET
                permitir_stock_negativo = :permitir,
                requiere_autorizacion_negativo = TRUE,
                id_usuario_actualiza = :usuario,
                fecha_actualizacion = NOW()
            WHERE id_configuracion = 1
            RETURNING
                id_configuracion,
                permitir_stock_negativo,
                requiere_autorizacion_negativo,
                id_usuario_actualiza,
                fecha_actualizacion
        ");

        $guardar->bindValue(
            ':permitir',
            $permitir,
            PDO::PARAM_BOOL
        );

        $guardar->bindValue(
            ':usuario',
            $idUsuario,
            PDO::PARAM_INT
        );

        $guardar->execute();
        $nuevo = $guardar->fetch(PDO::FETCH_ASSOC);
        $guardar->closeCursor();

        if (!$nuevo) {
            throw new RuntimeException(
                'No se guardó la configuración.'
            );
        }

        $nuevo['permitir_stock_negativo'] =
            booleanoConfiguracion28(
                $nuevo['permitir_stock_negativo']
            );

        $nuevo['requiere_autorizacion_negativo'] =
            booleanoConfiguracion28(
                $nuevo['requiere_autorizacion_negativo']
            );

        // Registrar el cambio en la bitácora existente.
        $bitacora = $pdo->prepare("
            INSERT INTO public.bitacora_auditoria (
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
                :usuario,
                NULL,
                'CONFIGURACION_INVENTARIO',
                'MODIFICAR',
                CAST(:antes AS JSONB),
                CAST(:despues AS JSONB),
                'EXITOSO',
                :ip,
                NOW()
            )
        ");

        $bitacora->execute([
            ':usuario' => $idUsuario,
            ':antes' => json_encode(
                $anterior,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            ':despues' => json_encode(
                $nuevo,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $pdo->commit();

        responderConfiguracion28(
            true,
            $nuevo,
            'Configuración guardada correctamente.'
        );

    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $error;
    }

} catch (JsonException $error) {
    responderConfiguracion28(
        false, null, 'El JSON recibido no es válido.', 400
    );

} catch (Throwable $error) {
    error_log(
        '[HU-28 configuracion] ' . $error->getMessage()
    );

    responderConfiguracion28(
        false, null,
        'No fue posible procesar la configuración.',
        500
    );
}
