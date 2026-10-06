<?php

/**
 * API de máquinas expendedoras — VendingMiniMarket
 *
 * Tabla: maquina (relacionada con tienda por id_tienda).
 *
 * HU-17  Registro de máquinas          crear_maquina
 *        Asignación de ubicación       id_tienda (Ultrapark 1, Ultrapark 2, UltraLag)
 *        Inactivación de máquinas      inactivar_maquina / activar_maquina (estado = FALSE, no se elimina)
 *        Identificador único           codigo UNIQUE, validado antes de guardar
 *
 * Usa PDO ($pdo), igual que api/catalogo/catalogo.php.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';   // define $pdo
require_once __DIR__ . '/../../config/session.php';    // inicia la sesión


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

function esAdministrador(): bool
{
    $rol = mb_strtolower(trim((string)($_SESSION['usuario']['rol'] ?? '')), 'UTF-8');

    return in_array($rol, ['administrador', 'admin'], true);
}

function exigirAdministrador(): array
{
    $usuario = exigirSesion();

    if (!esAdministrador()) {
        respuesta(false, null, 'No tiene permisos de administrador.', 403);
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

function mensajeCodigoDuplicado(): string
{
    return 'El identificador de la máquina debe ser único. Ya existe una máquina con ese código.';
}

function manejarErrorBD(PDOException $e, string $mensajeGeneral): void
{
    $codigo = (string)$e->getCode();

    if ($codigo === '23505') {
        respuesta(false, null, mensajeCodigoDuplicado(), 409);
    }

    if ($codigo === '23503') {
        respuesta(false, null, 'La operación se relaciona con un registro que no existe.', 409);
    }

    error_log('[maquinas] ' . $e->getMessage());
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


/* ==========================================================
   BITÁCORA (historial) — tabla bitacora_auditoria
========================================================== */

function auditar(string $accion, ?array $antes, ?array $despues): void
{
    $st = db()->prepare("
        INSERT INTO bitacora_auditoria
            (id_usuario, id_tienda, entidad, accion, valor_anterior, valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:id_usuario, :id_tienda, 'MAQUINA', :accion, CAST(:anterior AS jsonb), CAST(:nuevo AS jsonb), 'EXITOSO', :ip, NOW())
    ");

    $referencia = $despues ?? $antes;

    $st->execute([
        ':id_usuario' => (int)($_SESSION['usuario']['id_usuario'] ?? 0) ?: null,
        ':id_tienda'  => isset($referencia['id_tienda']) ? (int)$referencia['id_tienda'] : null,
        ':accion'     => $accion,
        ':anterior'   => $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
        ':nuevo'      => $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}


/* ==========================================================
   VALIDACIÓN DE CAMPOS
========================================================== */

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

/** "  vmm   ul 001 " => "VMM UL 001": mismo identificador, sin importar mayúsculas ni espacios. */
function normalizarCodigo(string $codigo): string
{
    $codigo = preg_replace('/\s+/u', ' ', trim($codigo)) ?? '';

    return mb_strtoupper($codigo, 'UTF-8');
}


/* ==========================================================
   MÁQUINAS — LECTURA
========================================================== */

const SQL_MAQUINA_BASE = "
    SELECT
        m.id_maquina,
        m.id_tienda,
        m.codigo,
        m.nombre,
        m.ubicacion,
        m.modelo,
        m.tipo,
        m.estado,
        m.observaciones,
        m.fecha_registro,
        t.nombre AS tienda_nombre,
        t.estado AS tienda_activa
    FROM maquina m
    JOIN tienda t ON t.id_tienda = m.id_tienda
";

function normalizarFilaMaquina(array $f): array
{
    $f['id_maquina']    = (int)$f['id_maquina'];
    $f['id_tienda']     = (int)$f['id_tienda'];
    $f['estado']        = aBool($f['estado']);
    $f['tienda_activa'] = aBool($f['tienda_activa']);

    return $f;
}

function obtenerMaquina(int $id): ?array
{
    $st = db()->prepare(SQL_MAQUINA_BASE . ' WHERE m.id_maquina = :id');
    $st->execute([':id' => $id]);
    $fila = $st->fetch();

    return $fila ? normalizarFilaMaquina($fila) : null;
}

/** Estado completo de una máquina, usado como valor anterior/nuevo en la bitácora. */
function fotoMaquina(int $id): array
{
    $m = obtenerMaquina($id);

    return $m ?? ['id_maquina' => $id];
}

function accionListarTiendas(array $in): void
{
    exigirSesion();

    $filas = db()->query("
        SELECT id_tienda, nombre, estado
        FROM tienda
        ORDER BY id_tienda
    ")->fetchAll();

    respuesta(true, array_map(function (array $f): array {
        return [
            'id_tienda' => (int)$f['id_tienda'],
            'nombre'    => $f['nombre'],
            'estado'    => aBool($f['estado']),
        ];
    }, $filas));
}

function accionListarMaquinas(array $in): void
{
    exigirSesion();

    $where  = [];
    $params = [];

    $busqueda = trim((string)($_GET['busqueda'] ?? $in['busqueda'] ?? ''));
    if ($busqueda !== '') {
        $where[] = "(m.codigo ILIKE :b OR m.nombre ILIKE :b OR COALESCE(m.ubicacion, '') ILIKE :b OR COALESCE(m.modelo, '') ILIKE :b)";
        $params[':b'] = '%' . addcslashes($busqueda, '%_\\') . '%';
    }

    $idTienda = (int)($_GET['id_tienda'] ?? $in['id_tienda'] ?? 0);
    if ($idTienda > 0) {
        $where[] = 'm.id_tienda = :id_tienda';
        $params[':id_tienda'] = $idTienda;
    }

    $estado = (string)($_GET['estado'] ?? $in['estado'] ?? '');
    if ($estado === 'activas') {
        $where[] = 'm.estado = TRUE';
    } elseif ($estado === 'inactivas') {
        $where[] = 'm.estado = FALSE';
    }

    $sql = SQL_MAQUINA_BASE
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY t.id_tienda, m.codigo';

    $st = db()->prepare($sql);
    $st->execute($params);

    respuesta(true, array_map('normalizarFilaMaquina', $st->fetchAll()));
}

function accionObtenerMaquina(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id'] ?? $in['id'] ?? $in['id_maquina'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Máquina inválida.', 400);
    }

    $maquina = obtenerMaquina($id);
    if (!$maquina) {
        respuesta(false, null, 'Máquina no encontrada.', 404);
    }

    respuesta(true, $maquina);
}

function accionResumenMaquinas(array $in): void
{
    exigirSesion();

    $fila = db()->query("
        SELECT
            COUNT(*)                                  AS total,
            COUNT(*) FILTER (WHERE estado = TRUE)     AS activas,
            COUNT(*) FILTER (WHERE estado = FALSE)    AS inactivas,
            COUNT(DISTINCT id_tienda)                 AS tiendas_con_maquinas
        FROM maquina
    ")->fetch();

    respuesta(true, [
        'total'                => (int)$fila['total'],
        'activas'              => (int)$fila['activas'],
        'inactivas'            => (int)$fila['inactivas'],
        'tiendas_con_maquinas' => (int)$fila['tiendas_con_maquinas'],
    ]);
}

function accionHistorialMaquina(array $in): void
{
    exigirSesion();

    $id = (int)($_GET['id'] ?? $in['id'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Máquina inválida.', 400);
    }

    $st = db()->prepare("
        SELECT b.id_evento, b.accion, b.valor_anterior, b.valor_nuevo, b.fecha,
               TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario
        FROM bitacora_auditoria b
        LEFT JOIN usuario u ON u.id_usuario = b.id_usuario
        WHERE b.entidad = 'MAQUINA'
          AND COALESCE(b.valor_nuevo->>'id_maquina', b.valor_anterior->>'id_maquina') = :id
        ORDER BY b.fecha DESC, b.id_evento DESC
        LIMIT 100
    ");
    $st->execute([':id' => (string)$id]);

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
   MÁQUINAS — ESCRITURA (HU-17)
========================================================== */

/** Lee y valida los datos de una máquina. */
function leerMaquina(array $in): array
{
    $codigo = campoTexto($in, 'codigo', 100, true, 'El identificador (código)');
    $nombre = campoTexto($in, 'nombre', 150, true, 'El nombre');

    $idTienda = (int)($in['id_tienda'] ?? 0);
    if ($idTienda <= 0) {
        respuesta(false, null, 'Debe seleccionar la ubicación (tienda) de la máquina.', 400);
    }

    return [
        'codigo'        => normalizarCodigo((string)$codigo),
        'nombre'        => $nombre,
        'id_tienda'     => $idTienda,
        'ubicacion'     => campoTexto($in, 'ubicacion', 200, false, 'El punto operativo'),
        'modelo'        => campoTexto($in, 'modelo', 100, false, 'El modelo'),
        'tipo'          => campoTexto($in, 'tipo', 100, false, 'El tipo'),
        'observaciones' => campoTexto($in, 'observaciones', 500, false, 'Las observaciones'),
    ];
}

/** La ubicación asignada debe existir y estar activa. */
function validarTienda(int $idTienda, ?int $tiendaActual = null): void
{
    $st = db()->prepare('SELECT estado FROM tienda WHERE id_tienda = :id');
    $st->execute([':id' => $idTienda]);
    $fila = $st->fetch();

    if (!$fila) {
        respuesta(false, null, 'La ubicación seleccionada no existe.', 400);
    }

    // Una máquina ya asignada a una tienda inactiva puede conservar su asignación al editarla.
    if (!aBool($fila['estado']) && $idTienda !== $tiendaActual) {
        respuesta(false, null, 'La ubicación seleccionada está inactiva.', 409);
    }
}

/** Identificador único: ninguna otra máquina puede tener el mismo código. */
function verificarCodigoUnico(string $codigo, int $idExcluir = 0): void
{
    $st = db()->prepare("
        SELECT 1
        FROM maquina
        WHERE UPPER(REGEXP_REPLACE(TRIM(codigo), '\s+', ' ', 'g')) = :codigo
          AND id_maquina <> :id
        LIMIT 1
    ");
    $st->execute([':codigo' => $codigo, ':id' => $idExcluir]);

    if ($st->fetchColumn()) {
        respuesta(false, null, mensajeCodigoDuplicado(), 409);
    }
}

function accionCrearMaquina(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $m = leerMaquina($in);

    // El estado inicial es opcional: si no se indica, la máquina queda activa.
    $estado = array_key_exists('estado', $in) ? aBool($in['estado']) : true;

    validarTienda($m['id_tienda']);
    verificarCodigoUnico($m['codigo']);

    $nueva = enTransaccion(function () use ($m, $estado) {
        $st = db()->prepare("
            INSERT INTO maquina
                (id_tienda, codigo, nombre, ubicacion, modelo, tipo, estado, observaciones)
            VALUES
                (:id_tienda, :codigo, :nombre, :ubicacion, :modelo, :tipo, CAST(:estado AS BOOLEAN), :observaciones)
            RETURNING id_maquina
        ");
        $st->execute([
            ':estado'        => $estado ? 'true' : 'false',
            ':id_tienda'     => $m['id_tienda'],
            ':codigo'        => $m['codigo'],
            ':nombre'        => $m['nombre'],
            ':ubicacion'     => $m['ubicacion'],
            ':modelo'        => $m['modelo'],
            ':tipo'          => $m['tipo'],
            ':observaciones' => $m['observaciones'],
        ]);

        $id   = (int)$st->fetchColumn();
        $foto = fotoMaquina($id);

        auditar('CREAR', null, $foto);

        return $foto;
    }, 'No se pudo registrar la máquina.');

    respuesta(true, $nueva, 'Máquina registrada correctamente.', 201);
}

function accionEditarMaquina(array $in): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_maquina'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Máquina inválida.', 400);
    }

    $actual = obtenerMaquina($id);
    if (!$actual) {
        respuesta(false, null, 'Máquina no encontrada.', 404);
    }

    $m = leerMaquina($in);

    validarTienda($m['id_tienda'], $actual['id_tienda']);
    verificarCodigoUnico($m['codigo'], $id);

    $nueva = enTransaccion(function () use ($id, $m) {
        $antes = fotoMaquina($id);

        $st = db()->prepare("
            UPDATE maquina SET
                id_tienda     = :id_tienda,
                codigo        = :codigo,
                nombre        = :nombre,
                ubicacion     = :ubicacion,
                modelo        = :modelo,
                tipo          = :tipo,
                observaciones = :observaciones
            WHERE id_maquina = :id
        ");
        $st->execute([
            ':id_tienda'     => $m['id_tienda'],
            ':codigo'        => $m['codigo'],
            ':nombre'        => $m['nombre'],
            ':ubicacion'     => $m['ubicacion'],
            ':modelo'        => $m['modelo'],
            ':tipo'          => $m['tipo'],
            ':observaciones' => $m['observaciones'],
            ':id'            => $id,
        ]);

        $despues = fotoMaquina($id);
        auditar('EDITAR', $antes, $despues);

        return $despues;
    }, 'No se pudo actualizar la máquina.');

    respuesta(true, $nueva, 'Máquina actualizada correctamente.');
}

/** Inactivar = estado FALSE. El registro y su historial no se eliminan. */
function cambiarEstadoMaquina(array $in, bool $activar): void
{
    exigirAdministrador();
    exigirPost();

    $id = (int)($in['id_maquina'] ?? 0);
    if ($id <= 0) {
        respuesta(false, null, 'Máquina inválida.', 400);
    }

    $actual = obtenerMaquina($id);
    if (!$actual) {
        respuesta(false, null, 'Máquina no encontrada.', 404);
    }

    if ($actual['estado'] === $activar) {
        respuesta(false, null, $activar ? 'La máquina ya está activa.' : 'La máquina ya está inactiva.', 409);
    }

    $nueva = enTransaccion(function () use ($id, $activar) {
        $antes = fotoMaquina($id);

        $st = db()->prepare('UPDATE maquina SET estado = :estado WHERE id_maquina = :id');
        $st->bindValue(':estado', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = fotoMaquina($id);
        auditar($activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    }, 'No se pudo cambiar el estado de la máquina.');

    respuesta(
        true,
        $nueva,
        $activar
            ? 'Máquina activada correctamente.'
            : 'Máquina inactivada correctamente. No aceptará nuevos movimientos y su historial se conserva.'
    );
}

function accionInactivarMaquina(array $in): void
{
    cambiarEstadoMaquina($in, false);
}

function accionActivarMaquina(array $in): void
{
    cambiarEstadoMaquina($in, true);
}


/* ==========================================================
   SESIÓN ACTUAL (mostrar acciones solo al administrador)
========================================================== */

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


/* ==========================================================
   ENRUTADOR
========================================================== */

$entrada = leerEntrada();
$accion  = (string)($_GET['accion'] ?? $entrada['accion'] ?? '');

$acciones = [
    'sesion'            => 'accionSesion',

    'listar_tiendas'    => 'accionListarTiendas',

    'listar_maquinas'   => 'accionListarMaquinas',
    'obtener_maquina'   => 'accionObtenerMaquina',
    'resumen_maquinas'  => 'accionResumenMaquinas',
    'historial_maquina' => 'accionHistorialMaquina',

    'crear_maquina'     => 'accionCrearMaquina',
    'editar_maquina'    => 'accionEditarMaquina',
    'inactivar_maquina' => 'accionInactivarMaquina',
    'activar_maquina'   => 'accionActivarMaquina',
];

try {
    if (!isset($acciones[$accion])) {
        respuesta(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]($entrada);
} catch (Throwable $e) {
    error_log('[maquinas] ' . $e->getMessage());
    respuesta(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
