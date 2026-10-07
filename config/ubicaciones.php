<?php

/**
 * Ubicaciones del usuario — VendingMiniMarket
 *
 * HU-19  Operar únicamente en la ubicación asignada.
 *
 * Funciones comunes que cualquier operación ligada a una ubicación
 * (movimientos de inventario, ventas, etc.) debe usar:
 *
 *   exigirOperarEnUbicacion($pdo, $idTienda);   // responde 403 y termina si no está autorizado
 *   registrarOperacion($pdo, ...);               // deja usuario y ubicación en la bitácora
 *
 * Reglas por rol (tabla rol):
 *   1 Administrador   consulta y opera en TODAS las ubicaciones
 *   2 Gerente General consulta y opera en TODAS las ubicaciones
 *   3 Encargado       consulta y opera solo en sus ubicaciones asignadas
 *   4 Dependiente     consulta y opera solo en sus ubicaciones asignadas
 *
 * La asignación se lee de usuario_tienda en cada llamada (no de la sesión),
 * así una reasignación hecha por el administrador se aplica de inmediato.
 *
 * Los nombres llevan el sufijo "Ubicacion" o el prefijo UBICACION_ para no
 * chocar con las funciones propias de cada API ni con config/permisos.php.
 */

/** Roles que pueden CONSULTAR cualquier ubicación. */
const UBICACION_ROLES_VEN_TODAS = [1, 2];

/** Roles que pueden OPERAR en cualquier ubicación. */
const UBICACION_ROLES_OPERAN_TODAS = [1, 2];


function ubicacionBool($valor): bool
{
    return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
}

/** Ubicaciones asignadas y activas en usuario_tienda (con su estado propio). */
function ubicacionesAsignadas(PDO $pdo, int $idUsuario): array
{
    $st = $pdo->prepare("
        SELECT t.id_tienda, t.nombre, t.tipo, t.estado
        FROM usuario_tienda ut
        JOIN tienda t ON t.id_tienda = ut.id_tienda
        WHERE ut.id_usuario = :usuario
          AND ut.estado = TRUE
        ORDER BY t.nombre
    ");
    $st->execute([':usuario' => $idUsuario]);

    return array_map(function (array $f): array {
        return [
            'id_tienda' => (int)$f['id_tienda'],
            'nombre'    => $f['nombre'],
            'tipo'      => $f['tipo'],
            'estado'    => ubicacionBool($f['estado']),
        ];
    }, $st->fetchAll());
}

/**
 * Decide si el usuario de la sesión puede consultar u operar en una ubicación.
 * $tipo: 'consulta' | 'operacion'. Devuelve ['permitido' => bool, 'motivo' => string].
 */
function evaluarAccesoUbicacion(PDO $pdo, int $idTienda, string $tipo = 'operacion'): array
{
    $usuario = $_SESSION['usuario'] ?? null;

    if (!$usuario) {
        return ['permitido' => false, 'motivo' => 'Debe iniciar sesión.'];
    }

    $idRol = (int)($usuario['id_rol'] ?? 0);

    $st = $pdo->prepare('SELECT id_tienda, nombre, estado FROM tienda WHERE id_tienda = :id');
    $st->execute([':id' => $idTienda]);
    $tienda = $st->fetch();

    if (!$tienda) {
        return ['permitido' => false, 'motivo' => 'La ubicación seleccionada no existe.'];
    }

    $nombre = $tienda['nombre'];

    if ($tipo === 'operacion' && !ubicacionBool($tienda['estado'])) {
        return [
            'permitido' => false,
            'motivo'    => "La ubicación \"$nombre\" está inactiva y no acepta nuevos movimientos.",
        ];
    }

    if ($tipo === 'consulta' && in_array($idRol, UBICACION_ROLES_VEN_TODAS, true)) {
        return ['permitido' => true, 'motivo' => ''];
    }

    if ($tipo === 'operacion' && in_array($idRol, UBICACION_ROLES_OPERAN_TODAS, true)) {
        return ['permitido' => true, 'motivo' => ''];
    }

    $st = $pdo->prepare("
        SELECT 1
        FROM usuario_tienda
        WHERE id_usuario = :usuario
          AND id_tienda = :tienda
          AND estado = TRUE
        LIMIT 1
    ");
    $st->execute([':usuario' => (int)($usuario['id_usuario'] ?? 0), ':tienda' => $idTienda]);

    if ($st->fetchColumn()) {
        return ['permitido' => true, 'motivo' => ''];
    }

    return [
        'permitido' => false,
        'motivo'    => $tipo === 'operacion'
            ? "No tiene permiso para operar en \"$nombre\". Solo puede operar en su ubicación asignada."
            : "No tiene permiso para consultar \"$nombre\". Solo puede ver su ubicación asignada.",
    ];
}

function usuarioPuedeOperarEnUbicacion(PDO $pdo, int $idTienda): bool
{
    return evaluarAccesoUbicacion($pdo, $idTienda, 'operacion')['permitido'];
}

function usuarioPuedeVerUbicacion(PDO $pdo, int $idTienda): bool
{
    return evaluarAccesoUbicacion($pdo, $idTienda, 'consulta')['permitido'];
}

/**
 * Llamar antes de registrar cualquier movimiento ligado a una ubicación.
 * Si el usuario no está autorizado responde 403 en JSON y termina el script,
 * por lo que la operación nunca llega a ejecutarse (criterio 2).
 */
function exigirOperarEnUbicacion(PDO $pdo, int $idTienda): void
{
    $evaluacion = evaluarAccesoUbicacion($pdo, $idTienda, 'operacion');

    if ($evaluacion['permitido']) {
        return;
    }

    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    echo json_encode(
        ['success' => false, 'data' => null, 'message' => $evaluacion['motivo']],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/**
 * Resumen de la ubicación del usuario de la sesión, listo para mostrar en pantalla.
 * Devuelve ['texto' => string, 'cantidad' => int, 'todas' => bool, 'sin_ubicacion' => bool].
 */
function resumenUbicacionUsuario(PDO $pdo): array
{
    $usuario = $_SESSION['usuario'] ?? null;

    if (!$usuario) {
        return ['texto' => '', 'cantidad' => 0, 'todas' => false, 'sin_ubicacion' => false];
    }

    if (in_array((int)($usuario['id_rol'] ?? 0), UBICACION_ROLES_VEN_TODAS, true)) {
        return ['texto' => 'Todas las ubicaciones', 'cantidad' => 0, 'todas' => true, 'sin_ubicacion' => false];
    }

    $asignadas = ubicacionesAsignadas($pdo, (int)($usuario['id_usuario'] ?? 0));

    if (!$asignadas) {
        return ['texto' => 'Sin ubicación asignada', 'cantidad' => 0, 'todas' => false, 'sin_ubicacion' => true];
    }

    $nombres = array_map(function (array $t): string {
        return $t['nombre'] . ($t['estado'] ? '' : ' (inactiva)');
    }, $asignadas);

    return [
        'texto'         => implode(', ', $nombres),
        'cantidad'      => count($asignadas),
        'todas'         => false,
        'sin_ubicacion' => false,
    ];
}

/**
 * Registra en bitacora_auditoria el usuario de la sesión y la ubicación de una
 * operación (criterio 4). Debe llamarse dentro de la misma transacción de la
 * operación, para que quede registrada solo cuando la transacción se confirma.
 */
function registrarOperacion(
    PDO $pdo,
    string $entidad,
    string $accion,
    int $idTienda,
    ?array $antes = null,
    ?array $despues = null
): void {
    $st = $pdo->prepare("
        INSERT INTO bitacora_auditoria
            (id_usuario, id_tienda, entidad, accion, valor_anterior, valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:id_usuario, :id_tienda, :entidad, :accion, CAST(:anterior AS jsonb), CAST(:nuevo AS jsonb), 'EXITOSO', :ip, NOW())
    ");

    $st->execute([
        ':id_usuario' => (int)($_SESSION['usuario']['id_usuario'] ?? 0) ?: null,
        ':id_tienda'  => $idTienda,
        ':entidad'    => $entidad,
        ':accion'     => $accion,
        ':anterior'   => $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
        ':nuevo'      => $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
