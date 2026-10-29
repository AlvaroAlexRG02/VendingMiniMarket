<?php
/**
 * HU-22 / HU-23 — Salidas y traslados de inventario.
 * Reutiliza: ubicacion_inventario, existencia, relacion_abastecimiento,
 * movimiento_inventario, detalle_movimiento, bitacora_auditoria.
 * No altera el registro de entradas de HU-21.
 */
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';

function responderMovimiento2322(bool $success, $data = null, string $message = '', int $http = 200): void
{
    http_response_code($http);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(compact('success', 'data', 'message'), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function numeroPositivo2322($valor): int
{
    if (is_bool($valor) || !is_scalar($valor)) return 0;
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : (int)$id;
}

function cargarUbicacion2322(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT ui.id_ubicacion, ui.id_tienda, ui.id_maquina, ui.tipo,
                              ui.nombre, ui.estado, t.estado AS tienda_activa
                         FROM ubicacion_inventario ui
                         JOIN tienda t ON t.id_tienda = ui.id_tienda
                        WHERE ui.id_ubicacion = :id LIMIT 1');
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function activo2322($dato): bool
{
    return in_array($dato, [true, 1, '1', 't', 'true'], true);
}

/**
 * Retorna ID de relación activa para un traslado entre tiendas o hacia máquina.
 * Entre ubicaciones distintas de la misma tienda sin máquina se trata como
 * transferencia interna (HU-22), por lo que no requiere esta relación.
 * Una máquina de destino sí requiere una relación explícita.
 */
function relacionDestino2322(PDO $pdo, array $origen, array $destino): ?int
{
    if ($origen['id_maquina'] !== null && (int)$origen['id_tienda'] !== (int)$destino['id_tienda']) {
        throw new DomainException('El abastecimiento entre tiendas desde una máquina no está configurado.');
    }

    if ($destino['id_maquina'] === null && (int)$origen['id_tienda'] === (int)$destino['id_tienda']) {
        return null;
    }

    if ($destino['id_maquina'] !== null) {
        $st = $pdo->prepare('SELECT id_relacion FROM relacion_abastecimiento
                             WHERE id_tienda_origen = :origen AND id_maquina_destino = :maquina
                               AND estado = TRUE LIMIT 1');
        $st->execute([':origen' => (int)$origen['id_tienda'], ':maquina' => (int)$destino['id_maquina']]);
    } else {
        $st = $pdo->prepare('SELECT id_relacion FROM relacion_abastecimiento
                             WHERE id_tienda_origen = :origen AND id_tienda_destino = :destino
                               AND estado = TRUE LIMIT 1');
        $st->execute([':origen' => (int)$origen['id_tienda'], ':destino' => (int)$destino['id_tienda']]);
    }
    $id = $st->fetchColumn();
    if ($id === false) throw new DomainException('No existe una relación de abastecimiento activa entre origen y destino.');
    return (int)$id;
}

$usuario = $_SESSION['usuario'] ?? null;
if (!is_array($usuario) || numeroPositivo2322($usuario['id_usuario'] ?? null) === 0) {
    responderMovimiento2322(false, null, 'Debe iniciar sesión.', 401);
}

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($metodo, ['GET', 'POST'], true)) {
    responderMovimiento2322(false, null, 'Método no permitido.', 405);
}

try {
    if ($metodo === 'GET') {
        $idOrigen = numeroPositivo2322($_GET['id_ubicacion_origen'] ?? null);
        if ($idOrigen <= 0) throw new DomainException('Seleccione la ubicación de origen.');
        $origen = cargarUbicacion2322($pdo, $idOrigen);
        if (!$origen || !activo2322($origen['estado']) || !activo2322($origen['tienda_activa'])) {
            throw new DomainException('Ubicación de origen inexistente o inactiva.');
        }
        exigirOperarEnUbicacion($pdo, (int)$origen['id_tienda']);
        $st = $pdo->prepare('SELECT id_producto, cantidad FROM existencia
                             WHERE id_ubicacion = :origen AND cantidad > 0 ORDER BY id_producto');
        $st->execute([':origen' => $idOrigen]);
        $stock = array_map(static fn(array $r): array => [
            'id_producto' => (int)$r['id_producto'],
            'cantidad' => (int)$r['cantidad'],
        ], $st->fetchAll(PDO::FETCH_ASSOC));
        // Consulta opcional del destino para la vista previa (sin modificar existencias).
        // La comprobación definitiva se repite dentro de la transacción POST.
        $idDestino = numeroPositivo2322($_GET['id_ubicacion_destino'] ?? null);
        $vistaDestino = null;
        if ($idDestino > 0) {
            $vistaDestino = [
                'id_ubicacion' => $idDestino,
                'permitido' => false,
                'tipo' => null,
                'mensaje' => '',
                'stock' => [],
            ];
            if ($idDestino === $idOrigen) {
                $vistaDestino['mensaje'] = 'Origen y destino deben ser diferentes.';
            } else {
                $destino = cargarUbicacion2322($pdo, $idDestino);
                if (!$destino || !activo2322($destino['estado']) || !activo2322($destino['tienda_activa'])) {
                    $vistaDestino['mensaje'] = 'La ubicación de destino no existe o está inactiva.';
                } else {
                    // No revelar stock de ubicaciones sin autorización.
                    exigirOperarEnUbicacion($pdo, (int)$destino['id_tienda']);
                    try {
                        $idRelacion = relacionDestino2322($pdo, $origen, $destino);
                        if ($idRelacion !== null && !usuarioTieneRol([1, 2, 3])) {
                            $vistaDestino['mensaje'] = 'Este rol no puede hacer traslados entre tiendas o hacia máquinas.';
                        } else {
                            $vistaDestino['permitido'] = true;
                            $vistaDestino['tipo'] = $idRelacion === null ? 'INTERNA' : 'ABASTECIMIENTO';
                            $vistaDestino['mensaje'] = $idRelacion === null
                                ? 'Transferencia interna autorizada.'
                                : 'Relación de abastecimiento activa.';
                        }
                    } catch (DomainException $errorRelacion) {
                        $vistaDestino['mensaje'] = $errorRelacion->getMessage();
                    }
                    if ($vistaDestino['permitido']) {
                        $stDestino = $pdo->prepare('SELECT id_producto, cantidad FROM existencia
                            WHERE id_ubicacion = :destino ORDER BY id_producto');
                        $stDestino->execute([':destino' => $idDestino]);
                        $vistaDestino['stock'] = array_map(static fn(array $r): array => [
                            'id_producto' => (int)$r['id_producto'],
                            'cantidad' => (int)$r['cantidad'],
                        ], $stDestino->fetchAll(PDO::FETCH_ASSOC));
                    }
                }
            }
        }
        responderMovimiento2322(true, ['stock' => $stock, 'destino' => $vistaDestino], 'Existencias consultadas correctamente.');
    }

    $datos = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($datos)) throw new DomainException('La solicitud no tiene el formato correcto.');

    $tipo = strtolower(trim((string)($datos['tipo'] ?? '')));
    if (!in_array($tipo, ['salida', 'traslado'], true)) {
        throw new DomainException('El tipo de movimiento debe ser salida o traslado.');
    }
    // HU-22: encargado y dependiente pueden mover mercadería dentro de la tienda.
    // HU-23: abastecimiento entre tiendas o hacia máquinas, 1/2/3 solamente.
    if (!usuarioTieneRol([1, 2, 3, 4])) responderMovimiento2322(false, null, 'Rol no autorizado.', 403);

    $idOrigen = numeroPositivo2322($datos['id_ubicacion_origen'] ?? null);
    $idDestino = $tipo === 'traslado' ? numeroPositivo2322($datos['id_ubicacion_destino'] ?? null) : 0;
    if ($idOrigen === 0 || ($tipo === 'traslado' && $idDestino === 0)) {
        throw new DomainException('Seleccione ubicación de origen y, para traslado, destino.');
    }
    if ($idDestino > 0 && $idDestino === $idOrigen) {
        throw new DomainException('Origen y destino deben ser diferentes.');
    }
    $motivo = trim((string)($datos['motivo'] ?? ''));
    $observaciones = trim((string)($datos['observaciones'] ?? ''));
    if ($motivo === '' || mb_strlen($motivo, 'UTF-8') > 150 || mb_strlen($observaciones, 'UTF-8') > 500) {
        throw new DomainException('Debe indicar un motivo válido (máximo 150 caracteres).');
    }
    $items = $datos['items'] ?? null;
    if (!is_array($items) || count($items) < 1 || count($items) > 200) {
        throw new DomainException('Agregue entre 1 y 200 productos.');
    }
    $cantidades = [];
    foreach ($items as $item) {
        if (!is_array($item)) throw new DomainException('Hay un producto inválido.');
        $idProducto = numeroPositivo2322($item['id_producto'] ?? null);
        $cantidad = numeroPositivo2322($item['cantidad'] ?? null);
        if (!$idProducto || !$cantidad || isset($cantidades[$idProducto])) {
            throw new DomainException('Indique productos distintos y cantidades enteras positivas.');
        }
        $cantidades[$idProducto] = $cantidad;
    }
    ksort($cantidades, SORT_NUMERIC);

    $pdo->beginTransaction();
    try {
        $origen = cargarUbicacion2322($pdo, $idOrigen);
        $destino = $idDestino > 0 ? cargarUbicacion2322($pdo, $idDestino) : null;
        if (!$origen || !activo2322($origen['estado']) || !activo2322($origen['tienda_activa']) ||
            ($tipo === 'traslado' && (!$destino || !activo2322($destino['estado']) || !activo2322($destino['tienda_activa'])))) {
            throw new DomainException('Alguna ubicación no existe o se encuentra inactiva.');
        }
        // Regla HU-19: comprobar asignación actual, no la sesión antigua.
        exigirOperarEnUbicacion($pdo, (int)$origen['id_tienda']);
        if ($destino) exigirOperarEnUbicacion($pdo, (int)$destino['id_tienda']);

        $idRelacion = null;
        if ($destino) {
            $idRelacion = relacionDestino2322($pdo, $origen, $destino);
            if ($idRelacion !== null && !usuarioTieneRol([1, 2, 3])) {
                throw new DomainException('Un dependiente no puede realizar abastecimientos entre tiendas o hacia máquinas.');
            }
        }

        $claves = array_keys($cantidades);
        $marcadores = implode(',', array_fill(0, count($claves), '?'));
        $val = $pdo->prepare("SELECT id_producto FROM producto WHERE estado = TRUE AND id_producto IN ($marcadores)");
        $val->execute($claves);
        if (count($val->fetchAll()) !== count($claves)) throw new DomainException('Hay productos inexistentes o inactivos.');

        $insertMov = $pdo->prepare('INSERT INTO movimiento_inventario
            (id_usuario, id_ubicacion_origen, id_ubicacion_destino, id_relacion_abastecimiento,
             id_usuario_autoriza, tipo_movimiento, motivo, observaciones, estado,
             fecha_movimiento, fecha_confirmacion, fecha_autorizacion, id_pedido)
            VALUES (:usuario, :origen, :destino, :relacion, :autoriza, :tipo, :motivo, :observaciones,
                    \'CONFIRMADO\', NOW(), NOW(), NOW(), NULL) RETURNING id_movimiento');
        $insertMov->execute([
            ':usuario' => (int)$usuario['id_usuario'], ':origen' => $idOrigen,
            ':destino' => $idDestino ?: null, ':relacion' => $idRelacion,
            ':autoriza' => (int)$usuario['id_usuario'], ':tipo' => strtoupper($tipo),
            ':motivo' => $motivo, ':observaciones' => $observaciones ?: null,
        ]);
        $idMovimiento = (int)$insertMov->fetchColumn();
        if (!$idMovimiento) throw new RuntimeException('No se pudo crear el movimiento.');

        $bloqueo = $pdo->prepare('SELECT pg_advisory_xact_lock(CAST(:ubi AS integer), CAST(:prod AS integer))');
        $leer = $pdo->prepare('SELECT id_existencia, cantidad FROM existencia
            WHERE id_ubicacion = :ubicacion AND id_producto = :producto FOR UPDATE');
        $actualizar = $pdo->prepare('UPDATE existencia SET cantidad = :cantidad,
            fecha_actualizacion = NOW() WHERE id_existencia = :id');
        $crear = $pdo->prepare('INSERT INTO existencia
            (id_ubicacion, id_producto, cantidad, stock_minimo, fecha_actualizacion)
            VALUES (:ubicacion, :producto, :cantidad, 0, NOW())');
        $detalle = $pdo->prepare('INSERT INTO detalle_movimiento
            (id_movimiento, id_producto, cantidad, saldo_origen_anterior, saldo_origen_resultante,
             saldo_destino_anterior, saldo_destino_resultante)
            VALUES (:movimiento, :producto, :cantidad, :origen_antes, :origen_despues,
                    :destino_antes, :destino_despues)');

        foreach ($cantidades as $idProducto => $cantidad) {
            // Orden fijo de bloqueos: evita conflictos concurrentes entre traslados inversos.
            $idsBloqueo = $idDestino ? [$idOrigen, $idDestino] : [$idOrigen];
            sort($idsBloqueo, SORT_NUMERIC);
            foreach ($idsBloqueo as $idUbicacion) {
                $bloqueo->execute([':ubi' => $idUbicacion, ':prod' => $idProducto]);
            }
            $leer->execute([':ubicacion' => $idOrigen, ':producto' => $idProducto]);
            $stockOrigen = $leer->fetch(PDO::FETCH_ASSOC);
            $antesOrigen = $stockOrigen ? (int)$stockOrigen['cantidad'] : 0;
            if ($antesOrigen < $cantidad) {
                throw new DomainException("Stock insuficiente para producto #$idProducto. Disponible: $antesOrigen; solicitado: $cantidad. No se permiten saldos negativos sin autorización.");
            }
            $despuesOrigen = $antesOrigen - $cantidad;
            $actualizar->execute([':cantidad' => $despuesOrigen, ':id' => (int)$stockOrigen['id_existencia']]);
            $antesDestino = $despuesDestino = null;
            if ($destino) {
                $leer->execute([':ubicacion' => $idDestino, ':producto' => $idProducto]);
                $stockDestino = $leer->fetch(PDO::FETCH_ASSOC);
                $antesDestino = $stockDestino ? (int)$stockDestino['cantidad'] : 0;
                $despuesDestino = $antesDestino + $cantidad;
                if ($stockDestino) {
                    $actualizar->execute([':cantidad' => $despuesDestino, ':id' => (int)$stockDestino['id_existencia']]);
                } else {
                    $crear->execute([':ubicacion' => $idDestino, ':producto' => $idProducto, ':cantidad' => $despuesDestino]);
                }
            }
            $detalle->execute([
                ':movimiento' => $idMovimiento, ':producto' => $idProducto, ':cantidad' => $cantidad,
                ':origen_antes' => $antesOrigen, ':origen_despues' => $despuesOrigen,
                ':destino_antes' => $antesDestino, ':destino_despues' => $despuesDestino,
            ]);
        }

        $resumen = [
            'id_movimiento' => $idMovimiento,
            'tipo' => strtoupper($tipo),
            'productos' => count($cantidades),
            'unidades' => array_sum($cantidades),
            'origen' => $idOrigen,
            'destino' => $idDestino ?: null,
            'relacion' => $idRelacion,
        ];
        registrarOperacion($pdo, 'MOVIMIENTO_INVENTARIO', strtoupper($tipo), (int)$origen['id_tienda'], null, $resumen);
        if ($destino && (int)$origen['id_tienda'] !== (int)$destino['id_tienda']) {
            registrarOperacion($pdo, 'MOVIMIENTO_INVENTARIO', strtoupper($tipo), (int)$destino['id_tienda'], null, $resumen);
        }
        $pdo->commit();
        responderMovimiento2322(true, $resumen, 'Movimiento registrado correctamente.', 201);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
} catch (DomainException | JsonException $e) {
    responderMovimiento2322(false, null, $e instanceof JsonException ? 'El JSON no es válido.' : $e->getMessage(), 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[inventario salidas-traslados] ' . $e->getMessage());
    responderMovimiento2322(false, null, 'No fue posible registrar el movimiento. Revise el registro del servidor.', 500);
}
