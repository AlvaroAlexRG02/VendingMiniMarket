<?php

/**
 * API de ubicación del usuario — VendingMiniMarket
 *
 * HU-19  Visualización de ubicación   mi_ubicacion
 *        Restricción por ubicación    validar_acceso (usa config/ubicaciones.php)
 *
 * Solo lectura. La asignación se modifica desde el módulo de Usuarios.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';   // define $pdo
require_once __DIR__ . '/../../config/session.php';    // inicia la sesión
require_once __DIR__ . '/../../config/ubicaciones.php';


function respuesta(bool $success, $data = null, string $message = '', int $status = 200): void
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

function exigirSesion(): array
{
    if (empty($_SESSION['usuario'])) {
        respuesta(false, null, 'Debe iniciar sesión.', 401);
    }

    return $_SESSION['usuario'];
}


/**
 * Ubicación asignada al usuario de la sesión y alcance de su rol.
 *
 *  alcance_consulta   TODAS | ASIGNADAS
 *  alcance_operacion  TODAS | ASIGNADAS
 *  sin_ubicacion      true si debe tener ubicación asignada y no la tiene
 */
function accionMiUbicacion(array $in): void
{
    $u = exigirSesion();

    $idUsuario = (int)$u['id_usuario'];
    $idRol     = (int)($u['id_rol'] ?? 0);

    $verTodas   = in_array($idRol, UBICACION_ROLES_VEN_TODAS, true);
    $operaTodas = in_array($idRol, UBICACION_ROLES_OPERAN_TODAS, true);

    $asignadas = ubicacionesAsignadas(db(), $idUsuario);

    if ($operaTodas) {
        $operables = array_map(function (array $f): array {
            return [
                'id_tienda' => (int)$f['id_tienda'],
                'nombre'    => $f['nombre'],
                'tipo'      => $f['tipo'],
                'estado'    => true,
            ];
        }, db()->query("SELECT id_tienda, nombre, tipo FROM tienda WHERE estado = TRUE ORDER BY nombre")->fetchAll());
    } else {
        $operables = array_values(array_filter($asignadas, function (array $t): bool {
            return $t['estado'];
        }));
    }

    respuesta(true, [
        'usuario' => [
            'id_usuario' => $idUsuario,
            'nombre'     => trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')),
            'id_rol'     => $idRol,
            'rol'        => $u['rol'] ?? '',
        ],
        'alcance_consulta'      => $verTodas ? 'TODAS' : 'ASIGNADAS',
        'alcance_operacion'     => $operaTodas ? 'TODAS' : 'ASIGNADAS',
        'ubicaciones_asignadas' => $asignadas,
        'ubicaciones_operables' => $operables,
        'sin_ubicacion'         => !$verTodas && count($asignadas) === 0,
    ]);
}

/**
 * ¿Puede el usuario consultar u operar en una ubicación?
 * Parámetros: id_tienda, tipo (consulta | operacion; por defecto operacion).
 * La consulta es exitosa siempre; `permitido` indica el resultado.
 */
function accionValidarAcceso(array $in): void
{
    exigirSesion();

    $idTienda = (int)($_GET['id_tienda'] ?? $in['id_tienda'] ?? 0);
    if ($idTienda <= 0) {
        respuesta(false, null, 'Debe indicar la ubicación.', 400);
    }

    $tipo = (string)($_GET['tipo'] ?? $in['tipo'] ?? 'operacion');
    if (!in_array($tipo, ['consulta', 'operacion'], true)) {
        respuesta(false, null, 'El tipo de acceso debe ser consulta u operacion.', 400);
    }

    $evaluacion = evaluarAccesoUbicacion(db(), $idTienda, $tipo);

    respuesta(
        true,
        ['permitido' => $evaluacion['permitido'], 'tipo' => $tipo, 'id_tienda' => $idTienda],
        $evaluacion['permitido'] ? 'Acceso permitido a la ubicación.' : $evaluacion['motivo']
    );
}

function db(): PDO
{
    global $pdo;
    return $pdo;
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$accion = (string)($_GET['accion'] ?? '');

$acciones = [
    'mi_ubicacion'  => 'accionMiUbicacion',
    'validar_acceso' => 'accionValidarAcceso',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]([]);
} catch (Throwable $e) {
    error_log('[ubicaciones] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
