<?php

/**
 * API de relaciones de abastecimiento — VendingMiniMarket
 *
 * Tabla: relacion_abastecimiento
 *   origen  -> ubicación (tabla tienda, tipo TIENDA o BODEGA)
 *   destino -> ubicación (tipo TIENDA) o máquina, nunca ambos
 *
 * Relaciones permitidas:
 *   Bodega -> Tienda, Bodega -> Máquina, Tienda -> Máquina, Tienda -> Tienda
 *
 * HU-18  Configuración de relaciones     crear_relacion
 *        Uso en traslados                validar_traslado
 *        Validación de traslados         validar_traslado (advertencia si no está configurada)
 *        Modificación con trazabilidad   editar_relacion / inactivar_relacion / activar_relacion
 *                                        + historial en bitacora_auditoria
 *
 * Usa PDO ($pdo), igual que api/maquinas/maquinas.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';   // define $pdo
require_once __DIR__ . '/../../config/session.php';    // inicia la sesión

const ENTIDAD_AUDITORIA = 'RELACION_ABASTECIMIENTO';
const COMBINACIONES_PERMITIDAS = 'Bodega → Tienda, Bodega → Máquina, Tienda → Máquina y Tienda → Tienda';


/* ==========================================================
   UTILIDADES GENERALES
========================================================== */

function db(): PDO
{
    global $pdo;
    return $pdo;
}

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

/** Administrador y Gerente General gestionan las relaciones de abastecimiento. */
function esAdministrador(): bool
{
    if (in_array((int)($_SESSION['usuario']['id_rol'] ?? 0), [1, 2], true)) {
        return true;
    }

    $rol = mb_strtolower(trim((string)($_SESSION['usuario']['rol'] ?? '')), 'UTF-8');

    return in_array($rol, ['administrador', 'admin', 'gerente general', 'gerente'], true);
}

function exigirAdministrador(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador()) {
        respuesta(false, null, 'No tiene permisos para modificar relaciones de abastecimiento.', 403);
    }

    return $usuario;
}

function exigirPost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respuesta(false, null, 'Método no permitido.', 405);
    }
}

function leerEntrada(): array
{
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo === false ? '' : $crudo, true);

    return is_array($datos) ? $datos : $_POST;
}

function aBool($valor): bool
{
    return $valor === true || $valor === 1 || $valor === '1' || $valor === 't' || $valor === 'true';
}

function mensajeRelacionDuplicada(): string
{
    return 'Ya existe una relación de abastecimiento entre ese origen y ese destino.';
}

function manejarErrorBD(PDOException $e, string $mensajeGeneral): void
{
    $codigo = (string)$e->getCode();

    if ($codigo === '23505') {
        respuesta(false, null, mensajeRelacionDuplicada(), 409);
    }

    if ($codigo === '23503') {
        respuesta(false, null, 'La operación se relaciona con un registro que no existe.', 409);
    }

    if ($codigo === '23514') {
        respuesta(false, null, 'La relación no cumple las reglas de abastecimiento permitidas.', 400);
    }

    error_log('[abastecimiento] ' . $e->getMessage());
    respuesta(false, null, $mensajeGeneral, 500);
}

/** Ejecuta $accion dentro de una transacción; revierte todo si algo falla. */
function enTransaccion(callable $accion, string $mensajeError)
{
    $pdo = db();

    try {
        $pdo->beginTransaction();
        $resultado = $accion();
        $pdo->commit();

        return $resultado;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        manejarErrorBD($e, $mensajeError);
    }
}

function campoTexto(array $in, string $clave, int $max, bool $obligatorio, string $etiqueta): ?string
{
    $valor = trim((string)($in[$clave] ?? ''));

    if ($valor === '') {
        if ($obligatorio) {
            respuesta(false, null, "$etiqueta es obligatorio.", 400);
        }
        return null;
    }

    if (mb_strlen($valor, 'UTF-8') > $max) {
        respuesta(false, null, "$etiqueta no puede superar $max caracteres.", 400);
    }

    return $valor;
}

function etiquetaTipo(string $tipo): string
{
    return $tipo === 'BODEGA' ? 'Bodega' : 'Tienda';
}


/* ==========================================================
   BITÁCORA (historial) — tabla bitacora_auditoria
========================================================== */

function auditar(string $accion, ?array $antes, ?array $despues): void
{
    $st = db()->prepare("
        INSERT INTO bitacora_auditoria
            (id_usuario, id_tienda, entidad, accion, valor_anterior, valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:id_usuario, :id_tienda, :entidad, :accion, CAST(:anterior AS jsonb), CAST(:nuevo AS jsonb), 'EXITOSO', :ip, NOW())
    ");

    $referencia = $despues ?? $antes;

    $st->execute([
        ':id_usuario' => (int)($_SESSION['usuario']['id_usuario'] ?? 0) ?: null,
        ':id_tienda'  => isset($referencia['id_tienda_origen']) ? (int)$referencia['id_tienda_origen'] : null,
        ':entidad'    => ENTIDAD_AUDITORIA,
        ':accion'     => $accion,
        ':anterior'   => $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
        ':nuevo'      => $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}


/* ==========================================================
   RELACIONES — LECTURA
========================================================== */

const SQL_RELACION_BASE = "
    SELECT
        r.id_relacion,
        r.id_tienda_origen,
        r.id_tienda_destino,
        r.id_maquina_destino,
        r.estado,
        r.observaciones,
        r.fecha_registro,
        o.nombre AS origen_nombre,
        o.tipo   AS origen_tipo,
        o.estado AS origen_activa,
        CASE WHEN r.id_maquina_destino IS NOT NULL THEN 'MAQUINA' ELSE 'UBICACION' END AS destino_tipo,
        COALESCE(td.nombre, m.codigo) AS destino_nombre,
        m.nombre AS maquina_nombre,
        COALESCE(td.estado, m.estado) AS destino_activo
    FROM relacion_abastecimiento r
    JOIN tienda o ON o.id_tienda = r.id_tienda_origen
    LEFT JOIN tienda td ON td.id_tienda = r.id_tienda_destino
    LEFT JOIN maquina m ON m.id_maquina = r.id_maquina_destino
";

function normalizarFilaRelacion(array $f): array
{
    $esMaquina = $f['destino_tipo'] === 'MAQUINA';

    $f['id_relacion']        = (int)$f['id_relacion'];
    $f['id_tienda_origen']   = (int)$f['id_tienda_origen'];
    $f['id_tienda_destino']  = $f['id_tienda_destino'] !== null ? (int)$f['id_tienda_destino'] : null;
    $f['id_maquina_destino'] = $f['id_maquina_destino'] !== null ? (int)$f['id_maquina_destino'] : null;
    $f['id_destino']         = $esMaquina ? $f['id_maquina_destino'] : $f['id_tienda_destino'];
    $f['estado']             = aBool($f['estado']);
    $f['origen_activa']      = aBool($f['origen_activa']);
    $f['destino_activo']     = aBool($f['destino_activo']);
    $f['tipo_relacion']      = etiquetaTipo((string)$f['origen_tipo']) . ' → ' . ($esMaquina ? 'Máquina' : 'Tienda');

    return $f;
}

function obtenerRelacion(int $id): ?array
{
    $st = db()->prepare(SQL_RELACION_BASE . ' WHERE r.id_relacion = :id');
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ? normalizarFilaRelacion($fila) : null;
}

/** Estado completo de una relación, usado como valor anterior/nuevo en la bitácora. */
function fotoRelacion(int $id): array
{
    return obtenerRelacion($id) ?? ['id_relacion' => $id];
}

function accionSesion(array $in): void
{
    $u = exigirSesion();

    respuesta(true, [
        'nombre'   => trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')),
        'correo'   => $u['correo'] ?? '',
        'rol'      => $u['rol'] ?? '',
        'es_admin' => esAdministrador(),
    ]);
}

/** Ubicaciones (con su tipo) y máquinas disponibles para elegir origen y destino. */
function accionOpciones(array $in): void
{
    exigirSesion();

    $ubicaciones = db()->query("
        SELECT id_tienda, nombre, tipo, estado
        FROM tienda
        ORDER BY id_tienda
    ")->fetchAll();

    $maquinas = db()->query("
        SELECT m.id_maquina, m.codigo, m.nombre, m.estado, m.id_tienda, t.nombre AS tienda_nombre
        FROM maquina m
        JOIN tienda t ON t.id_tienda = m.id_tienda
        ORDER BY t.id_tienda, m.codigo
    ")->fetchAll();

    respuesta(true, [
        'ubicaciones' => array_map(function (array $f): array {
            return [
                'id_tienda' => (int)$f['id_tienda'],
                'nombre'    => $f['nombre'],
                'tipo'      => $f['tipo'],
                'estado'    => aBool($f['estado']),
            ];
        }, $ubicaciones),
        'maquinas' => array_map(function (array $f): array {
            return [
                'id_maquina'    => (int)$f['id_maquina'],
                'codigo'        => $f['codigo'],
                'nombre'        => $f['nombre'],
                'estado'        => aBool($f['estado']),
                'id_tienda'     => (int)$f['id_tienda'],
                'tienda_nombre' => $f['tienda_nombre'],
            ];
        }, $maquinas),
    ]);
}

function accionListarRelaciones(array $in): void
{
    exigirSesion();

    $where  = [];
    $params = [];

    $busqueda = trim((string)($_GET['busqueda'] ?? $in['busqueda'] ?? ''));
    if ($busqueda !== '') {
        $where[] = "(o.nombre ILIKE :b
                     OR COALESCE(td.nombre, '') ILIKE :b
                     OR COALESCE(m.codigo, '') ILIKE :b
                     OR COALESCE(m.nombre, '') ILIKE :b
                     OR COALESCE(r.observaciones, '') ILIKE :b)";
        $params[':b'] = '%' . addcslashes($busqueda, '%_\\') . '%';
    }

    $idOrigen = (int)($_GET['id_origen'] ?? $in['id_origen'] ?? 0);
    if ($idOrigen > 0) {
        $where[] = 'r.id_tienda_origen = :origen';
        $params[':origen'] = $idOrigen;
    }

    $destinoTipo = strtoupper((string)($_GET['destino_tipo'] ?? $in['destino_tipo'] ?? ''));
    if ($destinoTipo === 'MAQUINA') {
        $where[] = 'r.id_maquina_destino IS NOT NULL';
    } elseif ($destinoTipo === 'UBICACION') {
        $where[] = 'r.id_tienda_destino IS NOT NULL';
    }

    $estado = (string)($_GET['estado'] ?? $in['estado'] ?? '');
    if ($estado === 'activas') {
        $where[] = 'r.estado = TRUE';
    } elseif ($estado === 'inactivas') {
        $where[] = 'r.estado = FALSE';
    }

    $sql = SQL_RELACION_BASE
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY o.nombre, destino_tipo, destino_nombre';

    $st = db()->prepare($sql);
    $st->execute($params);

    respuesta(true, array_map('normalizarFilaRelacion', $st->fetchAll()));
}

function accionObtenerRelacion(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id'] ?? $in['id'] ?? $in['id_relacion'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Relación inválida.', 400);
    }

    $relacion = obtenerRelacion($id);
    if (!$relacion) {
        respuesta(false, null, 'Relación no encontrada.', 404);
    }

    respuesta(true, $relacion);
}

function accionResumenRelaciones(array $in): void
{
    exigirSesion();

    $fila = db()->query("
        SELECT
            COUNT(*)                                          AS total,
            COUNT(*) FILTER (WHERE estado = TRUE)             AS activas,
            COUNT(*) FILTER (WHERE estado = FALSE)            AS inactivas,
            COUNT(*) FILTER (WHERE id_maquina_destino IS NOT NULL) AS hacia_maquinas
        FROM relacion_abastecimiento
    ")->fetch();

    respuesta(true, [
        'total'          => (int)$fila['total'],
        'activas'        => (int)$fila['activas'],
        'inactivas'      => (int)$fila['inactivas'],
        'hacia_maquinas' => (int)$fila['hacia_maquinas'],
    ]);
}

function accionHistorialRelacion(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Relación inválida.', 400);
    }

    $st = db()->prepare("
        SELECT b.id_evento, b.accion, b.valor_anterior, b.valor_nuevo, b.fecha,
               TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario
        FROM bitacora_auditoria b
        LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
        WHERE b.entidad = :entidad
          AND COALESCE(b.valor_nuevo->>'id_relacion', b.valor_anterior->>'id_relacion') = :id
        ORDER BY b.fecha DESC, b.id_evento DESC
        LIMIT 100
    ");
    $st->execute([':entidad' => ENTIDAD_AUDITORIA, ':id' => (string)$id]);

    $filas = array_map(function (array $f): array {
        return [
            'id_evento' => (int)$f['id_evento'],
            'accion'    => $f['accion'],
            'antes'     => $f['valor_anterior'] !== null ? json_decode((string)$f['valor_anterior'], true) : null,
            'despues'   => $f['valor_nuevo'] !== null ? json_decode((string)$f['valor_nuevo'], true) : null,
            'fecha'     => $f['fecha'],
            'usuario'   => $f['usuario'],
        ];
    }, $st->fetchAll());

    respuesta(true, $filas);
}


/* ==========================================================
   VALIDACIÓN DE LA RELACIÓN (HU-18, criterio 1)
========================================================== */

function cargarUbicacion(int $id): ?array
{
    $st = db()->prepare('SELECT id_tienda, nombre, tipo, estado FROM tienda WHERE id_tienda = :id');
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ?: null;
}

function cargarMaquina(int $id): ?array
{
    $st = db()->prepare('SELECT id_maquina, codigo, nombre, estado FROM maquina WHERE id_maquina = :id');
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ?: null;
}

/** Lee y valida la forma de los datos de una relación. */
function leerRelacion(array $in): array
{
    $origen = (int)($in['id_tienda_origen'] ?? 0);
    if ($origen <= 0) {
        respuesta(false, null, 'Debe seleccionar la ubicación de origen.', 400);
    }

    $destinoTipo = strtoupper(trim((string)($in['destino_tipo'] ?? '')));
    if (!in_array($destinoTipo, ['UBICACION', 'MAQUINA'], true)) {
        respuesta(false, null, 'Debe indicar si el destino es una ubicación o una máquina.', 400);
    }

    $idDestino = (int)($in['id_destino'] ?? 0);
    if ($idDestino <= 0) {
        respuesta(false, null, 'Debe seleccionar el destino.', 400);
    }

    return [
        'id_tienda_origen' => $origen,
        'destino_tipo'     => $destinoTipo,
        'id_destino'       => $idDestino,
        'observaciones'    => campoTexto($in, 'observaciones', 500, false, 'Las observaciones'),
    ];
}

/**
 * Valida que origen y destino existan, estén activos y formen una
 * combinación permitida. Al editar ($actual), un origen o destino que
 * ya estaba asignado puede conservarse aunque haya quedado inactivo.
 */
function validarReglas(array $r, ?array $actual = null): void
{
    $origen = cargarUbicacion($r['id_tienda_origen']);

    if (!$origen) {
        respuesta(false, null, 'La ubicación de origen no existe.', 400);
    }

    $origenConservado = $actual !== null && $actual['id_tienda_origen'] === $r['id_tienda_origen'];

    if (!aBool($origen['estado']) && !$origenConservado) {
        respuesta(false, null, 'La ubicación de origen está inactiva.', 409);
    }

    if ($r['destino_tipo'] === 'UBICACION') {
        if ($r['id_destino'] === $r['id_tienda_origen']) {
            respuesta(false, null, 'Una ubicación no puede abastecerse a sí misma.', 400);
        }

        $destino = cargarUbicacion($r['id_destino']);

        if (!$destino) {
            respuesta(false, null, 'La ubicación de destino no existe.', 400);
        }

        if ($destino['tipo'] === 'BODEGA') {
            respuesta(
                false,
                null,
                'Una bodega no puede ser destino de abastecimiento. Relaciones permitidas: ' . COMBINACIONES_PERMITIDAS . '.',
                409
            );
        }

        $destinoConservado = $actual !== null
            && $actual['destino_tipo'] === 'UBICACION'
            && $actual['id_destino'] === $r['id_destino'];

        if (!aBool($destino['estado']) && !$destinoConservado) {
            respuesta(false, null, 'La ubicación de destino está inactiva.', 409);
        }

        return;
    }

    $maquina = cargarMaquina($r['id_destino']);

    if (!$maquina) {
        respuesta(false, null, 'La máquina de destino no existe.', 400);
    }

    $destinoConservado = $actual !== null
        && $actual['destino_tipo'] === 'MAQUINA'
        && $actual['id_destino'] === $r['id_destino'];

    if (!aBool($maquina['estado']) && !$destinoConservado) {
        respuesta(false, null, 'La máquina de destino está inactiva.', 409);
    }
}

/** No se repite la misma relación (origen -> destino); si existe inactiva, se sugiere activarla. */
function verificarRelacionUnica(array $r, int $idExcluir = 0): void
{
    $columna = $r['destino_tipo'] === 'MAQUINA' ? 'id_maquina_destino' : 'id_tienda_destino';

    $st = db()->prepare("
        SELECT estado
        FROM relacion_abastecimiento
        WHERE id_tienda_origen = :origen
          AND $columna = :destino
          AND id_relacion <> :id
        LIMIT 1
    ");
    $st->execute([
        ':origen'  => $r['id_tienda_origen'],
        ':destino' => $r['id_destino'],
        ':id'      => $idExcluir,
    ]);

    $fila = $st->fetch();

    if ($fila) {
        respuesta(
            false,
            null,
            aBool($fila['estado'])
                ? mensajeRelacionDuplicada()
                : 'Esa relación ya existe pero está inactiva. Actívela en lugar de crearla de nuevo.',
            409
        );
    }
}


/* ==========================================================
   RELACIONES — ESCRITURA (HU-18)
========================================================== */

function accionCrearRelacion(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $r = leerRelacion($in);

    validarReglas($r);
    verificarRelacionUnica($r);

    $nueva = enTransaccion(function () use ($r) {
        $st = db()->prepare("
            INSERT INTO relacion_abastecimiento
                (id_tienda_origen, id_tienda_destino, id_maquina_destino, estado, observaciones)
            VALUES
                (:origen, :tienda_destino, :maquina_destino, TRUE, :observaciones)
            RETURNING id_relacion
        ");
        $st->execute([
            ':origen'          => $r['id_tienda_origen'],
            ':tienda_destino'  => $r['destino_tipo'] === 'UBICACION' ? $r['id_destino'] : null,
            ':maquina_destino' => $r['destino_tipo'] === 'MAQUINA' ? $r['id_destino'] : null,
            ':observaciones'   => $r['observaciones'],
        ]);

        $id   = (int)$st->fetchColumn();
        $foto = fotoRelacion($id);

        auditar('CREAR', null, $foto);

        return $foto;
    }, 'No se pudo registrar la relación de abastecimiento.');

    respuesta(true, $nueva, 'Relación de abastecimiento registrada correctamente.', 201);
}

function accionEditarRelacion(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_relacion'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Relación inválida.', 400);
    }

    $actual = obtenerRelacion($id);
    if (!$actual) {
        respuesta(false, null, 'Relación no encontrada.', 404);
    }

    $r = leerRelacion($in);

    validarReglas($r, $actual);
    verificarRelacionUnica($r, $id);

    $nueva = enTransaccion(function () use ($id, $r) {
        $antes = fotoRelacion($id);

        $st = db()->prepare("
            UPDATE relacion_abastecimiento SET
                id_tienda_origen   = :origen,
                id_tienda_destino  = :tienda_destino,
                id_maquina_destino = :maquina_destino,
                observaciones      = :observaciones
            WHERE id_relacion = :id
        ");
        $st->execute([
            ':origen'          => $r['id_tienda_origen'],
            ':tienda_destino'  => $r['destino_tipo'] === 'UBICACION' ? $r['id_destino'] : null,
            ':maquina_destino' => $r['destino_tipo'] === 'MAQUINA' ? $r['id_destino'] : null,
            ':observaciones'   => $r['observaciones'],
            ':id'              => $id,
        ]);

        $despues = fotoRelacion($id);
        auditar('EDITAR', $antes, $despues);

        return $despues;
    }, 'No se pudo actualizar la relación de abastecimiento.');

    respuesta(true, $nueva, 'Relación actualizada correctamente.');
}

/** Inactivar = estado FALSE. El registro y su historial no se eliminan. */
function cambiarEstadoRelacion(array $in, bool $activar): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_relacion'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Relación inválida.', 400);
    }

    $actual = obtenerRelacion($id);
    if (!$actual) {
        respuesta(false, null, 'Relación no encontrada.', 404);
    }

    if ($actual['estado'] === $activar) {
        respuesta(false, null, $activar ? 'La relación ya está activa.' : 'La relación ya está inactiva.', 409);
    }

    if ($activar && (!$actual['origen_activa'] || !$actual['destino_activo'])) {
        respuesta(false, null, 'No se puede activar: el origen o el destino de la relación está inactivo.', 409);
    }

    $nueva = enTransaccion(function () use ($id, $activar) {
        $antes = fotoRelacion($id);

        $st = db()->prepare('UPDATE relacion_abastecimiento SET estado = :estado WHERE id_relacion = :id');
        $st->bindValue(':estado', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = fotoRelacion($id);
        auditar($activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    }, 'No se pudo cambiar el estado de la relación.');

    respuesta(
        true,
        $nueva,
        $activar
            ? 'Relación activada correctamente.'
            : 'Relación inactivada correctamente. Su historial se conserva.'
    );
}

function accionInactivarRelacion(array $in): void
{
    cambiarEstadoRelacion($in, false);
}

function accionActivarRelacion(array $in): void
{
    cambiarEstadoRelacion($in, true);
}


/* ==========================================================
   VALIDACIÓN DE TRASLADOS (HU-18, criterios 2 y 3)
========================================================== */

/**
 * Indica si un traslado de origen -> destino está respaldado por una
 * relación configurada. Si no lo está, devuelve una advertencia; la
 * consulta en sí es exitosa (success = true) y `permitido` dice el resultado.
 *
 * Parámetros: id_tienda_origen, destino_tipo (UBICACION | MAQUINA), id_destino.
 */
function accionValidarTraslado(array $in): void
{
    exigirSesion();

    $r = leerRelacion([
        'id_tienda_origen' => $_GET['id_tienda_origen'] ?? $in['id_tienda_origen'] ?? 0,
        'destino_tipo'     => $_GET['destino_tipo'] ?? $in['destino_tipo'] ?? '',
        'id_destino'       => $_GET['id_destino'] ?? $in['id_destino'] ?? 0,
    ]);

    $columna = $r['destino_tipo'] === 'MAQUINA' ? 'id_maquina_destino' : 'id_tienda_destino';

    $st = db()->prepare(SQL_RELACION_BASE . "
        WHERE r.id_tienda_origen = :origen
          AND r.$columna = :destino
        LIMIT 1
    ");
    $st->execute([':origen' => $r['id_tienda_origen'], ':destino' => $r['id_destino']]);
    $fila = $st->fetch();

    if (!$fila) {
        respuesta(true, [
            'permitido' => false,
            'motivo'    => 'NO_CONFIGURADA',
            'relacion'  => null,
        ], 'Advertencia: la relación de abastecimiento no está configurada.');
    }

    $relacion = normalizarFilaRelacion($fila);

    if (!$relacion['estado']) {
        respuesta(true, [
            'permitido' => false,
            'motivo'    => 'INACTIVA',
            'relacion'  => $relacion,
        ], 'Advertencia: la relación de abastecimiento existe pero está inactiva.');
    }

    respuesta(true, [
        'permitido' => true,
        'motivo'    => 'CONFIGURADA',
        'relacion'  => $relacion,
    ], 'Traslado respaldado por la relación configurada: ' . $relacion['tipo_relacion'] . '.');
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? '');

$acciones = [
    'sesion'             => 'accionSesion',
    'opciones'           => 'accionOpciones',

    'listar_relaciones'  => 'accionListarRelaciones',
    'obtener_relacion'   => 'accionObtenerRelacion',
    'resumen_relaciones' => 'accionResumenRelaciones',
    'historial_relacion' => 'accionHistorialRelacion',

    'crear_relacion'     => 'accionCrearRelacion',
    'editar_relacion'    => 'accionEditarRelacion',
    'inactivar_relacion' => 'accionInactivarRelacion',
    'activar_relacion'   => 'accionActivarRelacion',

    'validar_traslado'   => 'accionValidarTraslado',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[abastecimiento] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
