<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('America/Costa_Rica');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/resumen_inventario.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function escaparResumen(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function siguienteEjecucionConfigurada(array $config): DateTimeImmutable
{
    return proximaEjecucionResumenInventario(
        (string)$config['frecuencia'],
        (int)$config['dia_semana'],
        substr((string)$config['hora'], 0, 5)
    );
}

function auditoriaResumenInventario(PDO $pdo, ?int $idUsuario, string $accion, array $valores): void
{
    $consulta = $pdo->prepare("
        INSERT INTO public.bitacora_auditoria
            (id_usuario, id_tienda, entidad, accion, valor_anterior,
             valor_nuevo, resultado, ip_origen, fecha)
        VALUES
            (:usuario, NULL, 'RESUMEN_INVENTARIO', :accion, NULL,
             CAST(:nuevo AS JSONB), :resultado, NULL, NOW())
    ");
    $consulta->execute([
        ':usuario' => $idUsuario,
        ':accion' => $accion,
        ':nuevo' => json_encode($valores, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ':resultado' => $valores['estado_envio'] === 'ENVIADO' ? 'EXITOSO' : 'FALLIDO',
    ]);
}

function recuperarEjecucionesInterrumpidas(PDO $pdo): void
{
    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            UPDATE public.resumen_inventario_ejecucion
            SET estado_envio = 'ERROR',
                error = 'La ejecución anterior se interrumpió antes de registrar su resultado.',
                fecha_finalizacion = NOW()
            WHERE estado_envio = 'PROCESANDO'
              AND fecha_ejecucion < NOW() - INTERVAL '15 minutes'
            RETURNING id_ejecucion, id_usuario_configuracion, correo_destino
        ");
        $consulta->execute();
        $interrumpidas = $consulta->fetchAll();
        $consulta->closeCursor();
        foreach ($interrumpidas as $fila) {
            auditoriaResumenInventario($pdo, $fila['id_usuario_configuracion'] === null
                ? null
                : (int)$fila['id_usuario_configuracion'], 'EJECUCION', [
                    'id_ejecucion' => (int)$fila['id_ejecucion'],
                    'correo_destino' => $fila['correo_destino'],
                    'estado_envio' => 'ERROR',
                    'error' => 'La ejecución anterior se interrumpió antes de registrar su resultado.',
                ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function generarDatosResumen(PDO $pdo, string $inicio, string $fin): array
{
    $existencias = $pdo->prepare("
        SELECT
            CASE
                WHEN e.cantidad < 0 THEN 'NEGATIVO'
                WHEN e.cantidad = 0 THEN 'AGOTADO'
                ELSE 'BAJO'
            END AS tipo,
            p.nombre AS producto,
            t.nombre AS tienda,
            u.nombre AS ubicacion,
            e.cantidad AS stock_actual,
            COALESCE(e.stock_minimo, 0) AS stock_minimo
        FROM public.existencia e
        JOIN public.producto p ON p.id_producto = e.id_producto
        JOIN public.ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion
        JOIN public.tienda t ON t.id_tienda = u.id_tienda
        WHERE p.estado = TRUE
          AND t.estado = TRUE
          AND (
              e.cantidad < 0
              OR e.cantidad = 0
              OR (e.cantidad > 0 AND e.cantidad <= COALESCE(e.stock_minimo, 0))
          )
        ORDER BY
            CASE WHEN e.cantidad < 0 THEN 0 WHEN e.cantidad = 0 THEN 1 ELSE 2 END,
            t.nombre, u.nombre, p.nombre
    ");
    $existencias->execute();

    $alertas = $pdo->prepare("
        SELECT
            p.nombre AS producto,
            t.nombre AS tienda,
            u.nombre AS ubicacion,
            a.tipo_alerta,
            a.estado,
            e.cantidad AS stock_actual,
            COALESCE(e.stock_minimo, a.stock_minimo_generacion) AS stock_minimo,
            a.fecha_generacion
        FROM public.alerta_inventario a
        JOIN public.producto p ON p.id_producto = a.id_producto
        JOIN public.ubicacion_inventario u ON u.id_ubicacion = a.id_ubicacion
        JOIN public.tienda t ON t.id_tienda = u.id_tienda
        LEFT JOIN public.existencia e
          ON e.id_producto = a.id_producto
         AND e.id_ubicacion = a.id_ubicacion
        WHERE a.estado = 'PENDIENTE'
        ORDER BY
            CASE a.tipo_alerta WHEN 'negativo' THEN 0 WHEN 'cero' THEN 1 ELSE 2 END,
            a.fecha_generacion, t.nombre, p.nombre
    ");
    $alertas->execute();

    $movimientos = $pdo->prepare("
        SELECT
            COALESCE(m.fecha_confirmacion, m.fecha_movimiento) AS fecha,
            m.tipo_movimiento,
            m.motivo,
            p.nombre AS producto,
            d.cantidad,
            origen.nombre AS origen,
            destino.nombre AS destino,
            concat_ws(' ', usuario.nombre, usuario.apellido) AS responsable
        FROM public.movimiento_inventario m
        JOIN public.detalle_movimiento d ON d.id_movimiento = m.id_movimiento
        JOIN public.producto p ON p.id_producto = d.id_producto
        LEFT JOIN public.ubicacion_inventario origen
          ON origen.id_ubicacion = m.id_ubicacion_origen
        LEFT JOIN public.ubicacion_inventario destino
          ON destino.id_ubicacion = m.id_ubicacion_destino
        LEFT JOIN public.usuario usuario ON usuario.id_usuario = m.id_usuario
        WHERE m.estado = 'CONFIRMADO'
          AND COALESCE(m.fecha_confirmacion, m.fecha_movimiento)
              >= CAST(:inicio AS DATE)
          AND COALESCE(m.fecha_confirmacion, m.fecha_movimiento)
              < CAST(:fin AS DATE) + INTERVAL '1 day'
        ORDER BY COALESCE(m.fecha_confirmacion, m.fecha_movimiento) DESC,
                 m.id_movimiento DESC, p.nombre
        LIMIT 500
    ");
    $movimientos->execute([':inicio' => $inicio, ':fin' => $fin]);

    return [
        'existencias' => $existencias->fetchAll(),
        'alertas' => $alertas->fetchAll(),
        'movimientos' => $movimientos->fetchAll(),
    ];
}

function tablaResumen(string $encabezado, array $filas): string
{
    if (!$filas) {
        return '<p>No hay registros para este periodo.</p>';
    }
    $html = '<table style="border-collapse:collapse;width:100%;margin:12px 0 24px"><thead><tr>';
    foreach ($encabezado as $titulo) {
        $html .= '<th style="background:#edf2f7;border:1px solid #d5dde5;padding:8px;text-align:left">'
            . escaparResumen($titulo) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($filas as $fila) {
        $html .= '<tr>';
        foreach ($fila as $celda) {
            $html .= '<td style="border:1px solid #d5dde5;padding:8px">'
                . escaparResumen((string)($celda ?? '')) . '</td>';
        }
        $html .= '</tr>';
    }
    return $html . '</tbody></table>';
}

function cuerpoResumenInventario(array $datos, string $inicio, string $fin): string
{
    $conteos = ['AGOTADO' => 0, 'BAJO' => 0, 'NEGATIVO' => 0];
    foreach ($datos['existencias'] as $fila) {
        $conteos[$fila['tipo']]++;
    }
    $filasStock = array_map(static fn(array $fila): array => [
        $fila['tipo'], $fila['producto'], $fila['tienda'], $fila['ubicacion'],
        $fila['stock_actual'], $fila['stock_minimo'],
    ], $datos['existencias']);
    $filasAlertas = array_map(static fn(array $fila): array => [
        $fila['producto'], $fila['tienda'], $fila['ubicacion'],
        $fila['tipo_alerta'], $fila['stock_actual'] ?? 'No disponible',
        $fila['stock_minimo'], $fila['fecha_generacion'],
    ], $datos['alertas']);
    $filasMovimientos = array_map(static fn(array $fila): array => [
        $fila['fecha'], $fila['tipo_movimiento'], $fila['motivo'], $fila['producto'],
        $fila['cantidad'], $fila['origen'] ?? '-', $fila['destino'] ?? '-',
        $fila['responsable'] ?: 'No disponible',
    ], $datos['movimientos']);
    $masMovimientos = count($datos['movimientos']) === 500
        ? '<p>Se muestran los 500 movimientos más recientes del periodo.</p>'
        : '';

    return '<div style="font-family:Arial,sans-serif;color:#1f2937;max-width:1100px">'
        . '<h1>Resumen periódico del inventario</h1>'
        . '<p>Periodo: <strong>' . escaparResumen($inicio) . ' al ' . escaparResumen($fin) . '</strong></p>'
        . '<h2>Estado del stock</h2>'
        . '<p>Agotados: ' . $conteos['AGOTADO'] . ' · Bajo mínimo: ' . $conteos['BAJO']
        . ' · Negativo: ' . $conteos['NEGATIVO'] . '</p>'
        . tablaResumen(['Condición', 'Producto', 'Tienda', 'Ubicación', 'Stock', 'Mínimo'], $filasStock)
        . '<h2>Alertas pendientes (' . count($filasAlertas) . ')</h2>'
        . tablaResumen(['Producto', 'Tienda', 'Ubicación', 'Tipo', 'Stock', 'Mínimo', 'Generada'], $filasAlertas)
        . '<h2>Movimientos relevantes (' . count($filasMovimientos) . ')</h2>'
        . $masMovimientos
        . tablaResumen(['Fecha', 'Movimiento', 'Motivo', 'Producto', 'Cantidad', 'Origen', 'Destino', 'Responsable'], $filasMovimientos)
        . '<p>Vending Mini Market</p></div>';
}

function registrarResultadoResumen(
    PDO $pdo,
    int $idEjecucion,
    ?int $idUsuario,
    string $correo,
    string $estado,
    ?string $error
): void {
    $pdo->beginTransaction();
    try {
        $actualizar = $pdo->prepare("
            UPDATE public.resumen_inventario_ejecucion
            SET estado_envio = :estado,
                error = :error,
                fecha_finalizacion = NOW()
            WHERE id_ejecucion = :id
        ");
        $actualizar->execute([
            ':estado' => $estado,
            ':error' => $error,
            ':id' => $idEjecucion,
        ]);
        auditoriaResumenInventario($pdo, $idUsuario, 'EJECUCION', [
            'id_ejecucion' => $idEjecucion,
            'correo_destino' => $correo,
            'estado_envio' => $estado,
            'error' => $error,
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$bloqueo = false;
try {
    $consultaBloqueo = $pdo->query('SELECT pg_try_advisory_lock(731547, 1)');
    $bloqueo = (bool)$consultaBloqueo->fetchColumn();
    $consultaBloqueo->closeCursor();
    if (!$bloqueo) {
        exit(0);
    }

    recuperarEjecucionesInterrumpidas($pdo);
    $pdo->beginTransaction();
    $consultaConfig = $pdo->query("
        SELECT id_configuracion, habilitado, frecuencia, dia_semana,
               to_char(hora, 'HH24:MI') AS hora, correo_destino,
               proxima_ejecucion, id_usuario_configuracion
        FROM public.resumen_inventario_config
        WHERE id_configuracion = 1
        FOR UPDATE
    ");
    $config = $consultaConfig->fetch();
    $consultaConfig->closeCursor();
    if (!$config) {
        $pdo->commit();
        exit(0);
    }
    $habilitado = in_array($config['habilitado'], [true, 1, '1', 't', 'true'], true);
    if (!$habilitado
        || $config['proxima_ejecucion'] === null
        || new DateTimeImmutable((string)$config['proxima_ejecucion']) > new DateTimeImmutable('now', new DateTimeZone('America/Costa_Rica'))) {
        $pdo->commit();
        exit(0);
    }

    $zona = new DateTimeZone('America/Costa_Rica');
    $ayer = (new DateTimeImmutable('yesterday', $zona))->format('Y-m-d');
    $inicio = $config['frecuencia'] === 'SEMANAL'
        ? (new DateTimeImmutable($ayer, $zona))->modify('-6 days')->format('Y-m-d')
        : $ayer;
    $idUsuario = $config['id_usuario_configuracion'] === null
        ? null
        : (int)$config['id_usuario_configuracion'];
    $correo = (string)$config['correo_destino'];

    $crearEjecucion = $pdo->prepare("
        INSERT INTO public.resumen_inventario_ejecucion
            (id_usuario_configuracion, correo_destino, periodo_inicio, periodo_fin, estado_envio)
        VALUES (:usuario, :correo, :inicio, :fin, 'PROCESANDO')
        RETURNING id_ejecucion
    ");
    $crearEjecucion->execute([
        ':usuario' => $idUsuario,
        ':correo' => $correo,
        ':inicio' => $inicio,
        ':fin' => $ayer,
    ]);
    $idEjecucion = (int)$crearEjecucion->fetchColumn();
    $crearEjecucion->closeCursor();
    $proxima = siguienteEjecucionConfigurada($config);
    $actualizarConfig = $pdo->prepare("
        UPDATE public.resumen_inventario_config
        SET proxima_ejecucion = :proxima
        WHERE id_configuracion = 1
    ");
    $actualizarConfig->execute([':proxima' => $proxima->format(DateTimeInterface::ATOM)]);
    $pdo->commit();

    $error = null;
    $estado = 'ENVIADO';
    try {
        $datos = generarDatosResumen($pdo, $inicio, $ayer);
        $configCorreo = require __DIR__ . '/../config/correo.php';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $configCorreo['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $configCorreo['username'];
        $mail->Password = $configCorreo['password'];
        $mail->SMTPSecure = $configCorreo['encryption'];
        $mail->Port = (int)$configCorreo['port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($configCorreo['from_email'], $configCorreo['from_name']);
        $mail->addAddress($correo);
        $mail->isHTML(true);
        $mail->Subject = "Resumen de inventario ({$inicio} - {$ayer})";
        $mail->Body = cuerpoResumenInventario($datos, $inicio, $ayer);
        $mail->AltBody = "Resumen de inventario del {$inicio} al {$ayer}. "
            . 'Agotados: ' . count(array_filter($datos['existencias'], static fn(array $fila): bool => $fila['tipo'] === 'AGOTADO'))
            . ', bajo mínimo: ' . count(array_filter($datos['existencias'], static fn(array $fila): bool => $fila['tipo'] === 'BAJO'))
            . ', negativos: ' . count(array_filter($datos['existencias'], static fn(array $fila): bool => $fila['tipo'] === 'NEGATIVO'))
            . ', alertas pendientes: ' . count($datos['alertas'])
            . ', movimientos: ' . count($datos['movimientos']) . '.';
        $mail->send();
    } catch (Throwable $e) {
        $estado = 'ERROR';
        $error = mb_substr($e->getMessage(), 0, 4000, 'UTF-8');
    }

    registrarResultadoResumen($pdo, $idEjecucion, $idUsuario, $correo, $estado, $error);
    if ($estado === 'ERROR') {
        fwrite(STDERR, $error . PHP_EOL);
        exit(1);
    }
    fwrite(STDOUT, "Resumen de inventario enviado: {$correo}; periodo {$inicio} - {$ayer}." . PHP_EOL);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[resumen-inventario-cli] ' . $e->getMessage());
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($bloqueo) {
        try {
            $pdo->query('SELECT pg_advisory_unlock(731547, 1)');
        } catch (Throwable $e) {
            error_log('[resumen-inventario-cli] No se pudo liberar el bloqueo: ' . $e->getMessage());
        }
    }
}
