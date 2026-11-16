<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('America/Costa_Rica');
require_once __DIR__ . '/../config/database.php';

function registrarRespaldoAutomatico(
    PDO $pdo,
    DateTimeImmutable $inicio,
    string $estado,
    ?string $referencia,
    string $observaciones,
    array $datosAuditoria
): void {
    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            INSERT INTO public.respaldo (
                id_usuario,
                tipo,
                fecha_inicio,
                fecha_fin,
                estado,
                cobertura,
                referencia,
                observaciones
            )
            VALUES (
                NULL,
                'AUTOMATICO',
                :fecha_inicio,
                NOW(),
                :estado,
                :cobertura,
                :referencia,
                :observaciones
            )
            RETURNING id_respaldo
        ");
        $consulta->execute([
            ':fecha_inicio' => $inicio->format('Y-m-d H:i:sP'),
            ':estado' => $estado,
            ':cobertura' => 'Base de datos y información crítica',
            ':referencia' => $referencia,
            ':observaciones' => $observaciones,
        ]);
        $idRespaldo = (int)$consulta->fetchColumn();

        $auditoria = $pdo->prepare("
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
                NULL,
                NULL,
                'RESPALDO',
                'GENERACION_RESPALDO',
                NULL,
                CAST(:valor_nuevo AS JSONB),
                :resultado,
                NULL,
                NOW()
            )
        ");
        $datosAuditoria['id_respaldo'] = $idRespaldo;
        $auditoria->execute([
            ':valor_nuevo' => json_encode(
                $datosAuditoria,
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ),
            ':resultado' => $estado,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$bloqueoAdquirido = false;
$codigoSalida = 0;
try {
    $consultaBloqueo = $pdo->query('SELECT pg_try_advisory_lock(731547, 2)');
    $bloqueoAdquirido = filter_var(
        $consultaBloqueo->fetchColumn(),
        FILTER_VALIDATE_BOOLEAN
    );
    $consultaBloqueo->closeCursor();
    if (!$bloqueoAdquirido) {
        fwrite(STDOUT, "Ya hay un respaldo automático en ejecución; no se inició otro.\n");
    } else {
        $inicio = new DateTimeImmutable('now', new DateTimeZone('America/Costa_Rica'));
        try {
            $resultado = include __DIR__ . '/generar_respaldo.php';
        } catch (Throwable $e) {
            error_log('Error al generar respaldo automático: ' . $e->getMessage());
            $resultado = [
                'success' => false,
                'error' => 'El generador produjo un error. Revise el registro de PHP.',
            ];
        }

        if (!is_array($resultado) || ($resultado['success'] ?? false) !== true) {
            $error = is_array($resultado)
                ? (string)($resultado['error'] ?? 'No se pudo generar el archivo de respaldo.')
                : 'El generador no devolvió un resultado válido.';
            registrarRespaldoAutomatico(
                $pdo,
                $inicio,
                'FALLIDO',
                null,
                $error,
                ['tipo' => 'AUTOMATICO', 'estado' => 'FALLIDO', 'error' => $error]
            );
            fwrite(STDERR, "Respaldo automático fallido: {$error}\n");
            $codigoSalida = 1;
        } else {
            $archivo = (string)$resultado['archivo'];
            $tamano = (int)$resultado['tamano'];
            $referencia = 'respaldo/archivos/' . $archivo;
            $observaciones = "Respaldo generado mediante pg_dump. Tamaño: {$tamano} bytes.";
            registrarRespaldoAutomatico(
                $pdo,
                $inicio,
                'EXITOSO',
                $referencia,
                $observaciones,
                [
                    'tipo' => 'AUTOMATICO',
                    'estado' => 'EXITOSO',
                    'archivo' => $archivo,
                    'tamano' => $tamano,
                ]
            );

            fwrite(STDOUT, "Respaldo automático creado: {$archivo} ({$tamano} bytes).\n");
        }
    }
} catch (Throwable $e) {
    error_log('Error en scripts/ejecutar_respaldo_automatico.php: ' . $e->getMessage());
    fwrite(STDERR, "Error al ejecutar el respaldo automático. Revise el registro de PHP.\n");
    $codigoSalida = 1;
} finally {
    if ($bloqueoAdquirido) {
        try {
            $pdo->query('SELECT pg_advisory_unlock(731547, 2)');
        } catch (Throwable $e) {
            error_log('No se pudo liberar el bloqueo de respaldo automático: ' . $e->getMessage());
            $codigoSalida = 1;
        }
    }
}

exit($codigoSalida);
