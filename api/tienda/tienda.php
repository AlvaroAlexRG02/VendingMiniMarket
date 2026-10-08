<?php

/**
 * API de ubicaciones (tiendas y bodegas) — VendingMiniMarket
 *
 * Tabla: tienda (campos: nombre, tipo, estado, es_principal, observaciones,
 *                fecha_registro)
 * Relación "abastecida por": tabla relacion_abastecimiento (HU-18).
 *
 * HU-16  Consulta de ubicaciones      listar, obtener
 *        Registro de una ubicación    crear
 *        Edición con trazabilidad      editar
 *        Activar / inactivar          cambiar_estado (no se elimina; el
 *                                     historial se conserva)
 *        Historial de una ubicación  historial_ubicacion
 *
 * Acceso: Administrador y Gerente General.
 * Usa PDO ($pdo), igual que api/maquinas/maquinas.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';   // define $pdo
require_once __DIR__ . '/../../config/session.php';    // inicia la sesión
require_once __DIR__ . '/../../config/permisos.php';   // roles

const ENTIDAD_UBICACION = 'UBICACION';
const ENTIDAD_RELACION  = 'RELACION_ABASTECIMIENTO';


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

/** Sesión iniciada y rol Administrador o Gerente General. */
function exigirAcceso(): array
{
    if (empty($_SESSION['usuario'])) {
        respuesta(false, null, 'Debe iniciar sesión.', 401);
    }

    if (!esAdministradorOGerente()) {
        respuesta(false, null, 'Acceso no autorizado.', 403);
    }

    return $_SESSION['usuario'];
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

function manejarErrorBD(PDOException $e, string $mensajeGeneral): void
{
    $codigo = (string)$e->getCode();

    if ($codigo === '23505') {
        respuesta(false, null, 'Ya existe una ubicación registrada con ese nombre.', 409);
    }

    if ($codigo === '23503') {
        respuesta(false, null, 'La operación se relaciona con un registro que no existe.', 409);
    }

    if ($codigo === '23514') {
        respuesta(false, null, 'Los datos no cumplen las reglas permitidas.', 400);
    }

    error_log('[tienda] ' . $e->getMessage());
    respuesta(false, null, $mensajeGeneral, 500);
}

/**
 * Ejecuta $accion dentro de una transacción; revierte todo si algo falla.
 * Si ya hay una transacción abierta, participa en ella sin cerrarla.
 */
function enTransaccion(callable $accion, string $mensajeError)
{
    $pdo   = db();
    $propia = !$pdo->inTransaction();

    try {
        if ($propia) {
            $pdo->beginTransaction();
        }

        $resultado = $accion();

        if ($propia) {
            $pdo->commit();
        }

        return $resultado;
    } catch (PDOException $e) {
        if ($propia && $pdo->inTransaction()) {
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

function auditar(string $entidad, ?int $idTienda, string $accion, ?array $antes, ?array $despues): void
{
    $st = db()->prepare("
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


/* ==========================================================
   UBICACIONES
========================================================== */

function normalizarUbicacion(array $f): array
{
    return [
        'id_tienda'      => (int)$f['id_tienda'],
        'nombre'         => $f['nombre'],
        'tipo'           => $f['tipo'],
        'tipo_etiqueta'  => etiquetaTipo((string)$f['tipo']),
        'estado'         => aBool($f['estado']),
        'es_principal'   => aBool($f['es_principal']),
        'observaciones'  => $f['observaciones'],
        'fecha_registro' => $f['fecha_registro'],
    ];
}

function cargarUbicacion(int $id): ?array
{
    $st = db()->prepare("
        SELECT id_tienda, nombre, tipo, estado, es_principal, observaciones, fecha_registro
        FROM tienda
        WHERE id_tienda = :id
    ");
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ? normalizarUbicacion($fila) : null;
}

/** Estado de una ubicación guardado en la bitácora. */
function fotoUbicacion(int $id): array
{
    $u = cargarUbicacion($id);

    return $u === null ? ['id_tienda' => $id] : [
        'id_tienda'     => $u['id_tienda'],
        'nombre'        => $u['nombre'],
        'tipo'          => $u['tipo'],
        'estado'        => $u['estado'],
        'es_principal'  => $u['es_principal'],
        'observaciones' => $u['observaciones'],
    ];
}

function accionListar(array $in): void
{
    exigirAcceso();

    $ubicaciones = db()->query("
        SELECT id_tienda, nombre, tipo, estado, es_principal, observaciones, fecha_registro
        FROM tienda
        ORDER BY id_tienda
    ")->fetchAll();

    // Ubicaciones que abastecen a cada una (relaciones activas).
    $relaciones = db()->query("
        SELECT r.id_tienda_destino, o.id_tienda AS id_origen, o.nombre AS origen_nombre
        FROM relacion_abastecimiento r
        JOIN tienda o ON o.id_tienda = r.id_tienda_origen
        WHERE r.estado = TRUE AND r.id_tienda_destino IS NOT NULL
        ORDER BY o.nombre
    ")->fetchAll();

    $abastecidaPor = [];
    foreach ($relaciones as $r) {
        $abastecidaPor[(int)$r['id_tienda_destino']][] = [
            'id_tienda' => (int)$r['id_origen'],
            'nombre'    => $r['origen_nombre'],
        ];
    }

    respuesta(true, array_map(function (array $f) use ($abastecidaPor): array {
        $u = normalizarUbicacion($f);
        $u['abastecida_por'] = $abastecidaPor[$u['id_tienda']] ?? [];

        return $u;
    }, $ubicaciones));
}

function accionObtener(array $in): void
{
    exigirAcceso();

    $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Ubicación inválida.', 400);
    }

    $ubicacion = cargarUbicacion($id);
    if (!$ubicacion) {
        respuesta(false, null, 'Ubicación no encontrada.', 404);
    }

    respuesta(true, $ubicacion);
}

function accionCrear(array $in): void
{
    exigirAcceso();
    exigirPost();

    $nombre = campoTexto($in, 'nombre', 100, true, 'El nombre');
    $tipo   = strtoupper(trim((string)($in['tipo'] ?? '')));

    if (!in_array($tipo, ['TIENDA', 'BODEGA'], true)) {
        respuesta(false, null, 'El tipo debe ser Tienda o Bodega.', 400);
    }

    $estado        = !array_key_exists('estado', $in) || aBool($in['estado']);
    $esPrincipal   = aBool($in['es_principal'] ?? false);
    $observaciones = campoTexto($in, 'observaciones', 500, false, 'Las observaciones');
    $idAbastecedora = (int)($in['id_abastecedora'] ?? 0);

    $st = db()->prepare('SELECT 1 FROM tienda WHERE LOWER(nombre) = LOWER(:nombre) LIMIT 1');
    $st->execute([':nombre' => $nombre]);

    if ($st->fetchColumn()) {
        respuesta(false, null, 'Ya existe una ubicación registrada con ese nombre.', 409);
    }

    $abastecedora = null;

    if ($idAbastecedora > 0) {
        if ($tipo === 'BODEGA') {
            respuesta(false, null, 'Una bodega no puede ser destino de abastecimiento.', 409);
        }

        if (!$estado) {
            respuesta(false, null, 'Para asignarle una ubicación que la abastezca, la ubicación debe estar activa.', 409);
        }

        $abastecedora = cargarUbicacion($idAbastecedora);

        if (!$abastecedora) {
            respuesta(false, null, 'La ubicación que abastece no existe.', 400);
        }

        if (!$abastecedora['estado']) {
            respuesta(false, null, 'La ubicación que abastece está inactiva.', 409);
        }
    }

    $nueva = enTransaccion(function () use ($nombre, $tipo, $estado, $esPrincipal, $observaciones, $abastecedora) {
        $st = db()->prepare("
            INSERT INTO tienda (nombre, ubicacion, tipo, estado, es_principal, observaciones)
            VALUES (:nombre, :ubicacion, :tipo, :estado, :principal, :observaciones)
            RETURNING id_tienda
        ");
        $st->bindValue(':nombre', $nombre);
        $st->bindValue(':ubicacion', $nombre);
        $st->bindValue(':tipo', $tipo);
        $st->bindValue(':estado', $estado, PDO::PARAM_BOOL);
        $st->bindValue(':principal', $esPrincipal, PDO::PARAM_BOOL);
        $st->bindValue(':observaciones', $observaciones);
        $st->execute();

        $id   = (int)$st->fetchColumn();
        $foto = fotoUbicacion($id);

        auditar(ENTIDAD_UBICACION, $id, 'CREAR', null, $foto);

        if ($abastecedora !== null) {
            $rel = db()->prepare("
                INSERT INTO relacion_abastecimiento (id_tienda_origen, id_tienda_destino, estado)
                VALUES (:origen, :destino, TRUE)
                RETURNING id_relacion
            ");
            $rel->execute([':origen' => $abastecedora['id_tienda'], ':destino' => $id]);

            auditar(ENTIDAD_RELACION, $abastecedora['id_tienda'], 'CREAR', null, [
                'id_relacion'       => (int)$rel->fetchColumn(),
                'id_tienda_origen'  => $abastecedora['id_tienda'],
                'id_tienda_destino' => $id,
                'estado'            => true,
                'origen_nombre'     => $abastecedora['nombre'],
                'destino_nombre'    => $nombre,
                'destino_tipo'      => 'UBICACION',
                'tipo_relacion'     => etiquetaTipo($abastecedora['tipo']) . ' → Tienda',
            ]);
        }

        return $foto;
    }, 'No se pudo registrar la ubicación.');

    respuesta(true, $nueva, 'La ubicación fue registrada correctamente.', 201);
}

/**
 * Edita nombre, tipo, principal y observaciones. El estado se cambia con
 * cambiar_estado y las relaciones de abastecimiento en api/abastecimiento.
 */
function accionEditar(array $in): void
{
    exigirAcceso();
    exigirPost();

    $id = (int)($in['id_tienda'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Ubicación inválida.', 400);
    }

    $actual = cargarUbicacion($id);
    if (!$actual) {
        respuesta(false, null, 'Ubicación no encontrada.', 404);
    }

    $nombre = campoTexto($in, 'nombre', 100, true, 'El nombre');
    $tipo   = strtoupper(trim((string)($in['tipo'] ?? '')));

    if (!in_array($tipo, ['TIENDA', 'BODEGA'], true)) {
        respuesta(false, null, 'El tipo debe ser Tienda o Bodega.', 400);
    }

    $esPrincipal   = aBool($in['es_principal'] ?? false);
    $observaciones = campoTexto($in, 'observaciones', 500, false, 'Las observaciones');

    $st = db()->prepare('SELECT 1 FROM tienda WHERE LOWER(nombre) = LOWER(:nombre) AND id_tienda <> :id LIMIT 1');
    $st->execute([':nombre' => $nombre, ':id' => $id]);

    if ($st->fetchColumn()) {
        respuesta(false, null, 'Ya existe una ubicación registrada con ese nombre.', 409);
    }

    // Una bodega no puede ser destino de abastecimiento (HU-18).
    if ($tipo === 'BODEGA' && $actual['tipo'] !== 'BODEGA') {
        $st = db()->prepare('SELECT 1 FROM relacion_abastecimiento WHERE id_tienda_destino = :id LIMIT 1');
        $st->execute([':id' => $id]);

        if ($st->fetchColumn()) {
            respuesta(
                false,
                null,
                'No se puede cambiar a Bodega: la ubicación es destino de una relación de abastecimiento.',
                409
            );
        }
    }

    $sinCambios = $nombre === $actual['nombre']
        && $tipo === $actual['tipo']
        && $esPrincipal === $actual['es_principal']
        && ($observaciones ?? '') === (string)($actual['observaciones'] ?? '');

    if ($sinCambios) {
        respuesta(false, null, 'No hay cambios para guardar.', 409);
    }

    $nueva = enTransaccion(function () use ($id, $actual, $nombre, $tipo, $esPrincipal, $observaciones) {
        $antes = fotoUbicacion($id);

        // La columna ubicacion repite el nombre: se mantiene igual si así estaba.
        $st = db()->prepare("
            UPDATE tienda SET
                nombre        = :nombre,
                ubicacion     = CASE WHEN ubicacion IS NULL OR ubicacion = :anterior THEN :nombre_ubicacion ELSE ubicacion END,
                tipo          = :tipo,
                es_principal  = :principal,
                observaciones = :observaciones
            WHERE id_tienda = :id
        ");
        $st->bindValue(':nombre', $nombre);
        $st->bindValue(':anterior', $actual['nombre']);
        $st->bindValue(':nombre_ubicacion', $nombre);
        $st->bindValue(':tipo', $tipo);
        $st->bindValue(':principal', $esPrincipal, PDO::PARAM_BOOL);
        $st->bindValue(':observaciones', $observaciones);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = fotoUbicacion($id);
        auditar(ENTIDAD_UBICACION, $id, 'EDITAR', $antes, $despues);

        return $despues;
    }, 'No se pudo actualizar la ubicación.');

    respuesta(true, $nueva, 'La ubicación fue actualizada correctamente.');
}

/** Activar o inactivar. La ubicación y su historial no se eliminan. */
function accionCambiarEstado(array $in): void
{
    exigirAcceso();
    exigirPost();

    $id = (int)($in['id_tienda'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Ubicación inválida.', 400);
    }

    if (!array_key_exists('estado', $in)) {
        respuesta(false, null, 'Debe indicar el nuevo estado.', 400);
    }

    $activar = aBool($in['estado']);
    $actual  = cargarUbicacion($id);

    if (!$actual) {
        respuesta(false, null, 'Ubicación no encontrada.', 404);
    }

    if ($actual['estado'] === $activar) {
        respuesta(false, null, $activar ? 'La ubicación ya está activa.' : 'La ubicación ya está inactiva.', 409);
    }

    $nueva = enTransaccion(function () use ($id, $activar) {
        $antes = fotoUbicacion($id);

        $st = db()->prepare('UPDATE tienda SET estado = :estado WHERE id_tienda = :id');
        $st->bindValue(':estado', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = fotoUbicacion($id);
        auditar(ENTIDAD_UBICACION, $id, $activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    }, 'No se pudo cambiar el estado de la ubicación.');

    respuesta(
        true,
        $nueva,
        $activar
            ? 'La ubicación se activó correctamente.'
            : 'La ubicación se inactivó correctamente. Su historial se conserva.'
    );
}


/* ==========================================================
   HISTORIAL DE UNA UBICACIÓN
========================================================== */

/** Historial de una ubicación: sus cambios y las relaciones de abastecimiento donde participa. */
function accionHistorialUbicacion(array $in): void
{
    exigirAcceso();

    $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Ubicación inválida.', 400);
    }

    if (!cargarUbicacion($id)) {
        respuesta(false, null, 'Ubicación no encontrada.', 404);
    }

    $st = db()->prepare("
        SELECT b.id_evento, b.entidad, b.accion, b.valor_anterior, b.valor_nuevo, b.fecha,
               TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario
        FROM bitacora_auditoria b
        LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
        WHERE (b.entidad = :ubicacion
               AND COALESCE(b.valor_nuevo->>'id_tienda', b.valor_anterior->>'id_tienda') = :id)
           OR (b.entidad = :relacion
               AND (COALESCE(b.valor_nuevo->>'id_tienda_destino', b.valor_anterior->>'id_tienda_destino') = :id
                    OR COALESCE(b.valor_nuevo->>'id_tienda_origen', b.valor_anterior->>'id_tienda_origen') = :id))
        ORDER BY b.fecha DESC, b.id_evento DESC
        LIMIT 100
    ");
    $st->execute([':ubicacion' => ENTIDAD_UBICACION, ':relacion' => ENTIDAD_RELACION, ':id' => (string)$id]);

    respuesta(true, array_map(function (array $f): array {
        return [
            'id_evento' => (int)$f['id_evento'],
            'entidad'   => $f['entidad'],
            'accion'    => $f['accion'],
            'antes'     => $f['valor_anterior'] !== null ? json_decode((string)$f['valor_anterior'], true) : null,
            'despues'   => $f['valor_nuevo'] !== null ? json_decode((string)$f['valor_nuevo'], true) : null,
            'fecha'     => $f['fecha'],
            'usuario'   => $f['usuario'] !== '' ? $f['usuario'] : null,
        ];
    }, $st->fetchAll()));
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? '');

$acciones = [
    'listar'              => 'accionListar',
    'obtener'             => 'accionObtener',
    'historial_ubicacion' => 'accionHistorialUbicacion',
    'crear'               => 'accionCrear',
    'editar'              => 'accionEditar',
    'cambiar_estado'      => 'accionCambiarEstado',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[tienda] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
