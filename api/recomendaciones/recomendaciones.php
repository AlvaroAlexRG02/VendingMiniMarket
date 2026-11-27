<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

ob_start();
register_shutdown_function(static function (): void {
    $salida = ob_get_contents();
    $error = error_get_last();
    $tiposFatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    $esFatal = $error !== null && in_array($error['type'], $tiposFatales, true);
    $esJsonValido = is_string($salida)
        && json_decode(trim($salida), true) !== null
        && json_last_error() === JSON_ERROR_NONE;

    if ($esFatal || (is_string($salida) && trim($salida) !== '' && !$esJsonValido)) {
        if ($esFatal) {
            error_log('[recomendaciones] Error fatal: ' . $error['message']);
        } else {
            error_log('[recomendaciones] La API terminó con una salida que no es JSON.');
        }
        ob_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'data' => null,
            'message' => 'La API de recomendaciones encontró un error interno. Revise el registro de errores de PHP.',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        return;
    }

    ob_end_flush();
});

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';
require_once __DIR__ . '/../../config/reportes_lib.php';

date_default_timezone_set(ZONA_REPORTES);

function responderRecomendaciones(bool $success, $data = null, string $message = '', int $status = 200): void
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

final class ErrorGroqRecomendacion extends RuntimeException
{
}

function exigirAdministradorRecomendaciones(): int
{
    if (empty($_SESSION['usuario'])) {
        responderRecomendaciones(false, null, 'Debe iniciar sesión.', 401);
    }

    if (!esAdministradorOGerente()) {
        responderRecomendaciones(false, null, 'Solo Administración o Gerencia pueden gestionar recomendaciones.', 403);
    }

    return (int)$_SESSION['usuario']['id_usuario'];
}

function auditarRecomendacion(
    PDO $pdo,
    int $idUsuario,
    ?int $idTienda,
    string $accion,
    array $anterior,
    array $nuevo
): void {
    $consulta = $pdo->prepare("
        INSERT INTO public.bitacora_auditoria (
            id_usuario, id_tienda, entidad, accion, valor_anterior,
            valor_nuevo, resultado, ip_origen, fecha
        )
        VALUES (
            :id_usuario, :id_tienda, 'RECOMENDACION_PRODUCTO', :accion,
            CAST(:anterior AS JSONB), CAST(:nuevo AS JSONB), 'EXITOSO',
            CAST(:ip AS INET), NOW()
        )
    ");
    $consulta->execute([
        ':id_usuario' => $idUsuario,
        ':id_tienda' => $idTienda,
        ':accion' => $accion,
        ':anterior' => json_encode($anterior, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ':nuevo' => json_encode($nuevo, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function leerConfiguracionRecomendaciones(PDO $pdo): array
{
    $fila = $pdo->query("
        SELECT umbral_unidades, fecha_actualizacion
        FROM public.recomendacion_producto_config
        WHERE id_configuracion = 1
    ")->fetch();

    if (!$fila) {
        throw new RuntimeException('No existe la configuración de recomendaciones. Ejecute RecomendacionesProductos.sql.');
    }

    return $fila;
}

function contarRecomendaciones(PDO $pdo): array
{
    $fila = $pdo->query("
        SELECT
            COUNT(*) AS total,
            COUNT(*) FILTER (WHERE estado = 'PENDIENTE') AS pendientes,
            COUNT(*) FILTER (WHERE estado = 'REVISADA') AS revisadas,
            COUNT(*) FILTER (WHERE estado = 'ACEPTADA') AS aceptadas,
            COUNT(*) FILTER (WHERE estado = 'DESCARTADA') AS descartadas
        FROM public.recomendacion_producto
    ")->fetch();

    return array_map('intval', $fila ?: [
        'total' => 0,
        'pendientes' => 0,
        'revisadas' => 0,
        'aceptadas' => 0,
        'descartadas' => 0,
    ]);
}

function listarRecomendaciones(PDO $pdo, ?string $estado = null): array
{
    $sql = "
        SELECT
            r.id_recomendacion,
            r.id_tienda,
            t.nombre AS tienda,
            r.id_producto,
            p.nombre AS producto,
            c.nombre AS categoria,
            r.periodo_inicio,
            r.periodo_fin,
            r.unidades_vendidas,
            r.umbral_unidades,
            r.ventas_por_dia,
            r.stock_actual,
            r.ubicaciones,
            r.ia_explicacion,
            r.ia_modelo,
            r.ia_fecha_generacion,
            r.estado,
            r.fecha_generacion,
            r.fecha_actualizacion
        FROM public.recomendacion_producto r
        JOIN public.tienda t ON t.id_tienda = r.id_tienda
        JOIN public.producto p ON p.id_producto = r.id_producto
        LEFT JOIN public.categoria c ON c.id_categoria = p.id_categoria
    ";
    if ($estado !== null) {
        $sql .= " WHERE r.estado = :estado";
    }
    $sql .= "
        ORDER BY
            CASE r.estado WHEN 'PENDIENTE' THEN 0 WHEN 'REVISADA' THEN 1 ELSE 2 END,
            r.unidades_vendidas ASC,
            r.fecha_generacion DESC,
            r.id_recomendacion DESC
        LIMIT 500
    ";

    $consultaRecomendaciones = $pdo->prepare($sql);
    if ($estado !== null) {
        $consultaRecomendaciones->execute([':estado' => $estado]);
    } else {
        $consultaRecomendaciones->execute();
    }
    $recomendaciones = $consultaRecomendaciones->fetchAll();

    if (!$recomendaciones) {
        return [];
    }

    $ids = array_map(static fn(array $fila): int => (int)$fila['id_recomendacion'], $recomendaciones);
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $historial = $pdo->prepare("
        SELECT
            h.id_recomendacion,
            h.estado_anterior,
            h.estado_nuevo,
            h.comentario,
            h.fecha,
            COALESCE(NULLIF(BTRIM(CONCAT_WS(' ', u.nombre, u.apellido)), ''), 'Sistema') AS responsable
        FROM public.recomendacion_producto_historial h
        LEFT JOIN public.usuario u ON u.id_usuario = h.id_usuario
        WHERE h.id_recomendacion IN ($marcadores)
        ORDER BY h.fecha, h.id_historial
    ");
    $historial->execute($ids);

    $porRecomendacion = [];
    foreach ($historial->fetchAll() as $fila) {
        $porRecomendacion[(int)$fila['id_recomendacion']][] = $fila;
    }

    $alternativas = $pdo->prepare("
        SELECT
            a.id_alternativa,
            a.id_recomendacion,
            a.id_producto_sugerido,
            ps.nombre AS producto_sugerido,
            a.unidades_vendidas_original,
            a.unidades_vendidas_sugerido,
            a.stock_actual_sugerido,
            a.score,
            a.justificacion,
            a.estado,
            a.comentario_decision,
            a.fecha_generacion,
            a.fecha_actualizacion
        FROM public.recomendacion_producto_alternativa a
        JOIN public.producto ps ON ps.id_producto = a.id_producto_sugerido
        WHERE a.id_recomendacion IN ($marcadores)
        ORDER BY a.id_recomendacion, a.score DESC, a.id_alternativa
    ");
    $alternativas->execute($ids);

    $alternativasPorRecomendacion = [];
    foreach ($alternativas->fetchAll() as $fila) {
        $fila['id_alternativa'] = (int)$fila['id_alternativa'];
        $fila['id_recomendacion'] = (int)$fila['id_recomendacion'];
        $fila['id_producto_sugerido'] = (int)$fila['id_producto_sugerido'];
        $fila['unidades_vendidas_original'] = (int)$fila['unidades_vendidas_original'];
        $fila['unidades_vendidas_sugerido'] = (int)$fila['unidades_vendidas_sugerido'];
        $fila['stock_actual_sugerido'] = (int)$fila['stock_actual_sugerido'];
        $fila['score'] = (float)$fila['score'];
        $alternativasPorRecomendacion[$fila['id_recomendacion']][] = $fila;
    }

    foreach ($recomendaciones as &$fila) {
        $fila['id_recomendacion'] = (int)$fila['id_recomendacion'];
        $fila['id_tienda'] = (int)$fila['id_tienda'];
        $fila['id_producto'] = (int)$fila['id_producto'];
        $fila['unidades_vendidas'] = (int)$fila['unidades_vendidas'];
        $fila['umbral_unidades'] = (int)$fila['umbral_unidades'];
        $fila['stock_actual'] = (int)$fila['stock_actual'];
        $fila['ventas_por_dia'] = json_decode((string)$fila['ventas_por_dia'], true) ?: [];
        $fila['historial'] = $porRecomendacion[$fila['id_recomendacion']] ?? [];
        $fila['alternativas'] = $alternativasPorRecomendacion[$fila['id_recomendacion']] ?? [];
    }
    unset($fila);

    return $recomendaciones;
}

function generarExplicacionIA(PDO $pdo, int $idUsuario, array $entrada): array
{
    $idRecomendacion = filter_var(
        $entrada['id_recomendacion'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($idRecomendacion === false) {
        responderRecomendaciones(false, null, 'La recomendación indicada no es válida.', 400);
    }

    $apiKey = getenv('GROQ_API_KEY');
    if (!is_string($apiKey) || trim($apiKey) === '') {
        responderRecomendaciones(
            false,
            null,
            'Groq no está configurado. Defina GROQ_API_KEY en el entorno de PHP y reinicie Apache.',
            503
        );
    }
    if (!function_exists('curl_init')) {
        throw new ErrorGroqRecomendacion('La extensión cURL de PHP es necesaria para conectar con Groq.');
    }

    $consulta = $pdo->prepare("
        SELECT
            r.id_recomendacion,
            r.id_tienda,
            t.nombre AS tienda,
            p.nombre AS producto,
            c.nombre AS categoria,
            r.periodo_inicio,
            r.periodo_fin,
            r.unidades_vendidas,
            r.umbral_unidades,
            r.ventas_por_dia,
            r.stock_actual,
            r.ubicaciones
        FROM public.recomendacion_producto r
        JOIN public.tienda t ON t.id_tienda = r.id_tienda
        JOIN public.producto p ON p.id_producto = r.id_producto
        LEFT JOIN public.categoria c ON c.id_categoria = p.id_categoria
        WHERE r.id_recomendacion = :id
    ");
    $consulta->execute([':id' => $idRecomendacion]);
    $recomendacion = $consulta->fetch();
    if (!$recomendacion) {
        responderRecomendaciones(false, null, 'No se encontró la recomendación.', 404);
    }

    $modelo = trim((string)(getenv('GROQ_MODEL') ?: ''));
    if ($modelo === '') {
        // qwen3.8-27b no razona internamente por defecto (a diferencia de los
        // modelos openai/gpt-oss-*), así que no agota max_output_tokens antes
        // de producir la explicación visible.
        $modelo = 'qwen/qwen3.8-27b';
    }
    $datos = [
        'producto' => $recomendacion['producto'],
        'categoria' => $recomendacion['categoria'],
        'tienda' => $recomendacion['tienda'],
        'periodo' => [
            'inicio' => $recomendacion['periodo_inicio'],
            'fin' => $recomendacion['periodo_fin'],
        ],
        'unidades_vendidas' => (int)$recomendacion['unidades_vendidas'],
        'umbral_unidades' => (int)$recomendacion['umbral_unidades'],
        'stock_actual' => (int)$recomendacion['stock_actual'],
        'ubicaciones' => $recomendacion['ubicaciones'],
        'ventas_por_dia' => json_decode((string)$recomendacion['ventas_por_dia'], true) ?: [],
    ];
    $payload = json_encode([
        'model' => $modelo,
        'store' => false,
        'instructions' => 'Eres un asistente que explica datos de inventario. Redacta en español una explicación breve, neutral y basada exclusivamente en los datos recibidos. Indica por qué el producto quedó bajo el umbral y menciona el stock solo si es relevante. No inventes causas, tendencias ni datos faltantes. No sugieras productos sustitutos, compras, cambios de precio ni acciones automáticas; aclara que la decisión corresponde al administrador o gerente.',
        'input' => json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        'max_output_tokens' => 180,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($payload === false) {
        throw new ErrorGroqRecomendacion('No fue posible preparar los datos para Groq.');
    }

    $curl = curl_init('https://api.groq.com/openai/v1/responses');
    if ($curl === false) {
        throw new ErrorGroqRecomendacion('No fue posible inicializar la conexión segura con Groq.');
    }
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . trim($apiKey),
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
    ]);

    $respuesta = curl_exec($curl);
    $errorCurl = curl_error($curl);
    $estadoHttp = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($respuesta === false) {
        error_log('[recomendaciones] Error de conexión Groq: ' . $errorCurl);
        throw new ErrorGroqRecomendacion('No se pudo conectar con Groq. Revise la conexión e inténtelo de nuevo.');
    }
    if ($estadoHttp < 200 || $estadoHttp >= 300) {
        error_log('[recomendaciones] Groq respondió HTTP ' . $estadoHttp . ': ' . $respuesta);
        throw new ErrorGroqRecomendacion(
            $estadoHttp === 401 || $estadoHttp === 403
                ? 'Groq rechazó la clave configurada. Revise GROQ_API_KEY.'
                : 'Groq no pudo generar la explicación (HTTP ' . $estadoHttp . ').'
        );
    }

    $respuestaJson = json_decode($respuesta, true);
    if (!is_array($respuestaJson)) {
        throw new ErrorGroqRecomendacion('Groq no devolvió una respuesta válida. Inténtelo de nuevo.');
    }
    $fragmentos = [];
    foreach (($respuestaJson['output'] ?? []) as $salida) {
        foreach (($salida['content'] ?? []) as $contenido) {
            if (($contenido['type'] ?? '') === 'output_text' && is_string($contenido['text'] ?? null)) {
                $fragmentos[] = trim($contenido['text']);
            }
        }
    }
    $explicacion = trim(implode("\n", $fragmentos));
    if (json_last_error() !== JSON_ERROR_NONE || $explicacion === '') {
        throw new ErrorGroqRecomendacion('Groq no devolvió una explicación válida. Inténtelo de nuevo.');
    }
    if (mb_strlen($explicacion, 'UTF-8') > 2000) {
        $explicacion = mb_substr($explicacion, 0, 2000, 'UTF-8');
    }

    $pdo->beginTransaction();
    try {
        $guardar = $pdo->prepare("
            UPDATE public.recomendacion_producto
            SET ia_explicacion = :explicacion,
                ia_modelo = :modelo,
                ia_fecha_generacion = NOW(),
                ia_id_usuario = :usuario
            WHERE id_recomendacion = :id
        ");
        $guardar->execute([
            ':explicacion' => $explicacion,
            ':modelo' => $modelo,
            ':usuario' => $idUsuario,
            ':id' => $idRecomendacion,
        ]);
        auditarRecomendacion(
            $pdo,
            $idUsuario,
            (int)$recomendacion['id_tienda'],
            'EXPLICACION_IA',
            [],
            [
                'id_recomendacion' => (int)$idRecomendacion,
                'modelo' => $modelo,
                'fecha_generacion' => date(DATE_ATOM),
            ]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'id_recomendacion' => (int)$idRecomendacion,
        'explicacion' => $explicacion,
        'modelo' => $modelo,
        'fecha_generacion' => date(DATE_ATOM),
    ];
}

function generarRecomendaciones(PDO $pdo, int $idUsuario): array
{
    $config = leerConfiguracionRecomendaciones($pdo);
    $hoy = hoyCostaRica();
    $inicio = (new DateTimeImmutable($hoy, new DateTimeZone(ZONA_REPORTES)))
        ->modify('-29 days')
        ->format('Y-m-d');
    $tiendas = array_map(
        'intval',
        $pdo->query('SELECT id_tienda FROM public.tienda WHERE estado = TRUE')->fetchAll(PDO::FETCH_COLUMN)
    );
    if (!$tiendas) {
        return ['creadas' => 0, 'umbral_unidades' => (int)$config['umbral_unidades']];
    }

    $idsTiendas = listaEnteros($tiendas);
    $desdeManual = "(CAST(:desde_manual AS DATE)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
    $hastaManual = "((CAST(:hasta_manual AS DATE) + 1)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
    $ventas = "
        SELECT
            v.id_tienda,
            d.id_producto,
            (v.fecha AT TIME ZONE 'America/Costa_Rica')::DATE AS fecha,
            SUM(d.cantidad)::INTEGER AS unidades
        FROM public.venta_manual v
        JOIN public.venta_manual_detalle d ON d.id_venta = v.id_venta
        JOIN public.movimiento_inventario m
          ON m.id_movimiento = v.id_movimiento
         AND m.estado = 'CONFIRMADO'
        WHERE v.id_tienda IN ($idsTiendas)
          AND v.fecha >= $desdeManual
          AND v.fecha < $hastaManual
        GROUP BY v.id_tienda, d.id_producto, (v.fecha AT TIME ZONE 'America/Costa_Rica')::DATE
    ";
    $parametrosVentas = [
        ':desde_manual' => $inicio,
        ':hasta_manual' => $hoy,
    ];
    if (tablaNayaxExiste($pdo)) {
        $desdeNayax = "(CAST(:desde_nayax AS DATE)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
        $hastaNayax = "((CAST(:hasta_nayax AS DATE) + 1)::TIMESTAMP AT TIME ZONE 'America/Costa_Rica')";
        $ventas .= "
            UNION ALL
            SELECT
                n.id_tienda,
                n.id_producto,
                (n.fecha AT TIME ZONE 'America/Costa_Rica')::DATE AS fecha,
                SUM(n.cantidad)::INTEGER AS unidades
            FROM public.venta_nayax n
            WHERE n.estado = 'CONFIRMADA'
              AND n.id_tienda IN ($idsTiendas)
              AND n.fecha >= $desdeNayax
              AND n.fecha < $hastaNayax
            GROUP BY n.id_tienda, n.id_producto, (n.fecha AT TIME ZONE 'America/Costa_Rica')::DATE
        ";
        $parametrosVentas[':desde_nayax'] = $inicio;
        $parametrosVentas[':hasta_nayax'] = $hoy;
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            WITH ventas_fuente AS ($ventas),
            ventas_dia AS (
                SELECT id_tienda, id_producto, fecha, SUM(unidades)::INTEGER AS unidades
                FROM ventas_fuente
                GROUP BY id_tienda, id_producto, fecha
            ),
            ventas AS (
                SELECT
                    id_tienda,
                    id_producto,
                    SUM(unidades)::INTEGER AS unidades_vendidas,
                    JSONB_AGG(
                        JSONB_BUILD_OBJECT('fecha', fecha, 'unidades', unidades)
                        ORDER BY fecha
                    ) AS ventas_por_dia
                FROM ventas_dia
                GROUP BY id_tienda, id_producto
            ),
            inventario AS (
                SELECT
                    u.id_tienda,
                    e.id_producto,
                    SUM(e.cantidad)::INTEGER AS stock_actual,
                    STRING_AGG(DISTINCT u.nombre, ', ' ORDER BY u.nombre) AS ubicaciones
                FROM public.existencia e
                JOIN public.ubicacion_inventario u ON u.id_ubicacion = e.id_ubicacion
                WHERE u.estado = TRUE
                GROUP BY u.id_tienda, e.id_producto
            ),
            candidatos AS (
                SELECT id_tienda, id_producto FROM inventario
                UNION
                SELECT id_tienda, id_producto FROM ventas
            ),
            insertadas AS (
                INSERT INTO public.recomendacion_producto (
                    id_tienda, id_producto, periodo_inicio, periodo_fin,
                    unidades_vendidas, umbral_unidades, ventas_por_dia,
                    stock_actual, ubicaciones
                )
                SELECT
                    c.id_tienda,
                    p.id_producto,
                    CAST(:periodo_inicio AS DATE),
                    CAST(:periodo_fin AS DATE),
                    COALESCE(v.unidades_vendidas, 0),
                    CAST(:umbral AS INTEGER),
                    COALESCE(v.ventas_por_dia, '[]'::JSONB),
                    COALESCE(i.stock_actual, 0),
                    i.ubicaciones
                FROM candidatos c
                JOIN public.tienda t
                  ON t.id_tienda = c.id_tienda AND t.estado = TRUE
                JOIN public.producto p
                  ON p.id_producto = c.id_producto AND p.estado = TRUE
                LEFT JOIN ventas v
                  ON v.id_tienda = c.id_tienda AND v.id_producto = c.id_producto
                LEFT JOIN inventario i
                  ON i.id_tienda = c.id_tienda AND i.id_producto = c.id_producto
                WHERE COALESCE(v.unidades_vendidas, 0) < CAST(:umbral_filtro AS INTEGER)
                ON CONFLICT (id_tienda, id_producto)
                    WHERE estado IN ('PENDIENTE', 'REVISADA')
                DO NOTHING
                RETURNING id_recomendacion, id_tienda, id_producto,
                          unidades_vendidas, umbral_unidades
            ),
            historiales AS (
                INSERT INTO public.recomendacion_producto_historial (
                    id_recomendacion, id_usuario, estado_anterior, estado_nuevo, comentario
                )
                SELECT
                    id_recomendacion,
                    NULL,
                    NULL,
                    'PENDIENTE',
                    'Generada automáticamente: ' || unidades_vendidas
                        || ' unidades en 30 días; umbral ' || umbral_unidades || '.'
                FROM insertadas
                RETURNING id_recomendacion
            ),
            auditorias AS (
                INSERT INTO public.bitacora_auditoria (
                    id_usuario, id_tienda, entidad, accion, valor_anterior,
                    valor_nuevo, resultado, ip_origen, fecha
                )
                SELECT
                    CAST(:id_usuario AS BIGINT),
                    id_tienda,
                    'RECOMENDACION_PRODUCTO',
                    'GENERACION',
                    NULL,
                    JSONB_BUILD_OBJECT(
                        'id_recomendacion', id_recomendacion,
                        'id_producto', id_producto,
                        'unidades_vendidas', unidades_vendidas,
                        'umbral_unidades', umbral_unidades,
                        'estado', 'PENDIENTE'
                    ),
                    'EXITOSO',
                    NULL,
                    NOW()
                FROM insertadas
                RETURNING id_evento
            ),
            candidatos_alternativa AS (
                SELECT
                    ins.id_recomendacion,
                    ins.id_tienda,
                    ins.unidades_vendidas AS unidades_original,
                    pc.id_producto AS id_producto_candidato,
                    COALESCE(vc.unidades_vendidas, 0) AS unidades_candidato,
                    COALESCE(ic.stock_actual, 0) AS stock_candidato,
                    ROW_NUMBER() OVER (
                        PARTITION BY ins.id_recomendacion
                        ORDER BY (COALESCE(vc.unidades_vendidas, 0) - ins.unidades_vendidas) DESC,
                                 COALESCE(ic.stock_actual, 0) DESC
                    ) AS orden
                FROM insertadas ins
                JOIN public.producto po ON po.id_producto = ins.id_producto
                JOIN public.producto pc
                  ON pc.id_categoria = po.id_categoria
                 AND pc.id_producto <> ins.id_producto
                 AND pc.estado = TRUE
                LEFT JOIN ventas vc
                  ON vc.id_tienda = ins.id_tienda AND vc.id_producto = pc.id_producto
                LEFT JOIN inventario ic
                  ON ic.id_tienda = ins.id_tienda AND ic.id_producto = pc.id_producto
                WHERE COALESCE(ic.stock_actual, 0) > 0
                  AND COALESCE(vc.unidades_vendidas, 0) >= ins.umbral_unidades
                  AND COALESCE(vc.unidades_vendidas, 0) > ins.unidades_vendidas
            ),
            mejor_alternativa AS (
                SELECT * FROM candidatos_alternativa WHERE orden = 1
            ),
            alternativas_insertadas AS (
                INSERT INTO public.recomendacion_producto_alternativa (
                    id_recomendacion, id_producto_sugerido, unidades_vendidas_original,
                    unidades_vendidas_sugerido, stock_actual_sugerido, score, justificacion
                )
                SELECT
                    id_recomendacion,
                    id_producto_candidato,
                    unidades_original,
                    unidades_candidato,
                    stock_candidato,
                    (unidades_candidato - unidades_original)::NUMERIC,
                    'Vendió ' || unidades_candidato || ' unidades frente a ' || unidades_original
                        || ' del producto original en los últimos 30 días, con ' || stock_candidato
                        || ' unidades disponibles en la misma tienda.'
                FROM mejor_alternativa
                ON CONFLICT (id_recomendacion, id_producto_sugerido) WHERE estado = 'PENDIENTE' DO NOTHING
                RETURNING id_alternativa, id_recomendacion
            ),
            alternativas_historial AS (
                INSERT INTO public.recomendacion_producto_alternativa_historial (
                    id_alternativa, id_usuario, estado_anterior, estado_nuevo, comentario
                )
                SELECT id_alternativa, NULL, NULL, 'PENDIENTE',
                       'Generada automáticamente junto con la recomendación de baja rotación.'
                FROM alternativas_insertadas
                RETURNING id_alternativa
            ),
            alternativas_auditoria AS (
                INSERT INTO public.bitacora_auditoria (
                    id_usuario, id_tienda, entidad, accion, valor_anterior,
                    valor_nuevo, resultado, ip_origen, fecha
                )
                SELECT
                    CAST(:id_usuario AS BIGINT),
                    ma.id_tienda,
                    'RECOMENDACION_PRODUCTO_ALTERNATIVA',
                    'GENERACION',
                    NULL,
                    JSONB_BUILD_OBJECT(
                        'id_alternativa', ai.id_alternativa,
                        'id_recomendacion', ai.id_recomendacion,
                        'id_producto_sugerido', ma.id_producto_candidato,
                        'estado', 'PENDIENTE'
                    ),
                    'EXITOSO',
                    NULL,
                    NOW()
                FROM alternativas_insertadas ai
                JOIN mejor_alternativa ma ON ma.id_recomendacion = ai.id_recomendacion
                RETURNING id_evento
            )
            SELECT
                (SELECT COUNT(*) FROM insertadas)::INTEGER AS creadas,
                (SELECT COUNT(*) FROM alternativas_historial)::INTEGER AS alternativas_creadas
        ");
        $consulta->execute([
            ...$parametrosVentas,
            ':periodo_inicio' => $inicio,
            ':periodo_fin' => $hoy,
            ':umbral' => (int)$config['umbral_unidades'],
            ':umbral_filtro' => (int)$config['umbral_unidades'],
            ':id_usuario' => $idUsuario,
        ]);
        $resultado = $consulta->fetch();
        $creadas = (int)($resultado['creadas'] ?? 0);
        $alternativasCreadas = (int)($resultado['alternativas_creadas'] ?? 0);

        $pdo->commit();
        return [
            'creadas' => $creadas,
            'alternativas_creadas' => $alternativasCreadas,
            'umbral_unidades' => (int)$config['umbral_unidades'],
            'periodo_inicio' => $inicio,
            'periodo_fin' => $hoy,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function cambiarEstadoRecomendacion(PDO $pdo, int $idUsuario, array $entrada): void
{
    $idRecomendacion = filter_var(
        $entrada['id_recomendacion'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $estadoNuevo = strtoupper(trim((string)($entrada['estado'] ?? '')));
    $comentario = trim((string)($entrada['comentario'] ?? ''));
    if ($idRecomendacion === false || !in_array($estadoNuevo, ['REVISADA', 'ACEPTADA', 'DESCARTADA'], true)) {
        responderRecomendaciones(false, null, 'La recomendación o el estado indicados no son válidos.', 400);
    }
    if (mb_strlen($comentario, 'UTF-8') > 1000) {
        responderRecomendaciones(false, null, 'El comentario no puede exceder 1000 caracteres.', 400);
    }
    if ($estadoNuevo === 'REVISADA' && $comentario === '') {
        responderRecomendaciones(false, null, 'Agregue una observación para registrar la revisión.', 400);
    }
    if ($estadoNuevo === 'DESCARTADA' && $comentario === '') {
        responderRecomendaciones(false, null, 'Agregue un comentario para explicar por qué descarta la recomendación.', 400);
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            SELECT id_tienda, estado
            FROM public.recomendacion_producto
            WHERE id_recomendacion = :id
            FOR UPDATE
        ");
        $consulta->execute([':id' => $idRecomendacion]);
        $actual = $consulta->fetch();
        if (!$actual) {
            $pdo->rollBack();
            responderRecomendaciones(false, null, 'No se encontró la recomendación.', 404);
        }

        $estadoAnterior = (string)$actual['estado'];
        $transiciones = [
            'PENDIENTE' => ['REVISADA'],
            'REVISADA' => ['ACEPTADA', 'DESCARTADA'],
        ];
        if (!in_array($estadoNuevo, $transiciones[$estadoAnterior] ?? [], true)) {
            $pdo->rollBack();
            responderRecomendaciones(
                false,
                null,
                'La recomendación debe revisarse antes de aceptarla o descartarla, y los estados finales no se pueden cambiar.',
                409
            );
        }

        $actualizar = $pdo->prepare("
            UPDATE public.recomendacion_producto
            SET estado = :estado, fecha_actualizacion = NOW()
            WHERE id_recomendacion = :id
        ");
        $actualizar->execute([':estado' => $estadoNuevo, ':id' => $idRecomendacion]);

        $guardarHistorial = $pdo->prepare("
            INSERT INTO public.recomendacion_producto_historial (
                id_recomendacion, id_usuario, estado_anterior, estado_nuevo, comentario
            )
            VALUES (:id, :usuario, :anterior, :nuevo, :comentario)
        ");
        $guardarHistorial->execute([
            ':id' => $idRecomendacion,
            ':usuario' => $idUsuario,
            ':anterior' => $estadoAnterior,
            ':nuevo' => $estadoNuevo,
            ':comentario' => $comentario !== '' ? $comentario : null,
        ]);
        auditarRecomendacion(
            $pdo,
            $idUsuario,
            (int)$actual['id_tienda'],
            'CAMBIO_ESTADO',
            ['estado' => $estadoAnterior],
            [
                'id_recomendacion' => (int)$idRecomendacion,
                'estado' => $estadoNuevo,
                'comentario' => $comentario !== '' ? $comentario : null,
            ]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function decidirAlternativa(PDO $pdo, int $idUsuario, array $entrada): void
{
    $idAlternativa = filter_var(
        $entrada['id_alternativa'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $estadoNuevo = strtoupper(trim((string)($entrada['estado'] ?? '')));
    $comentario = trim((string)($entrada['comentario'] ?? ''));
    if ($idAlternativa === false || !in_array($estadoNuevo, ['ACEPTADA', 'DESCARTADA'], true)) {
        responderRecomendaciones(false, null, 'La alternativa o el estado indicados no son válidos.', 400);
    }
    if (mb_strlen($comentario, 'UTF-8') > 1000) {
        responderRecomendaciones(false, null, 'El comentario no puede exceder 1000 caracteres.', 400);
    }
    if ($estadoNuevo === 'DESCARTADA' && $comentario === '') {
        responderRecomendaciones(false, null, 'Agregue un comentario para explicar por qué descarta la alternativa.', 400);
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare("
            SELECT a.estado, r.id_tienda
            FROM public.recomendacion_producto_alternativa a
            JOIN public.recomendacion_producto r ON r.id_recomendacion = a.id_recomendacion
            WHERE a.id_alternativa = :id
            FOR UPDATE OF a
        ");
        $consulta->execute([':id' => $idAlternativa]);
        $actual = $consulta->fetch();
        if (!$actual) {
            $pdo->rollBack();
            responderRecomendaciones(false, null, 'No se encontró la alternativa sugerida.', 404);
        }

        $estadoAnterior = (string)$actual['estado'];
        if ($estadoAnterior !== 'PENDIENTE') {
            $pdo->rollBack();
            responderRecomendaciones(false, null, 'Esta alternativa ya tiene una decisión registrada y no se puede cambiar.', 409);
        }

        $actualizar = $pdo->prepare("
            UPDATE public.recomendacion_producto_alternativa
            SET estado = :estado,
                id_usuario_decision = :usuario,
                comentario_decision = :comentario,
                fecha_actualizacion = NOW()
            WHERE id_alternativa = :id
        ");
        $actualizar->execute([
            ':estado' => $estadoNuevo,
            ':usuario' => $idUsuario,
            ':comentario' => $comentario !== '' ? $comentario : null,
            ':id' => $idAlternativa,
        ]);

        $guardarHistorial = $pdo->prepare("
            INSERT INTO public.recomendacion_producto_alternativa_historial (
                id_alternativa, id_usuario, estado_anterior, estado_nuevo, comentario
            )
            VALUES (:id, :usuario, :anterior, :nuevo, :comentario)
        ");
        $guardarHistorial->execute([
            ':id' => $idAlternativa,
            ':usuario' => $idUsuario,
            ':anterior' => $estadoAnterior,
            ':nuevo' => $estadoNuevo,
            ':comentario' => $comentario !== '' ? $comentario : null,
        ]);
        auditarRecomendacion(
            $pdo,
            $idUsuario,
            (int)$actual['id_tienda'],
            'ALTERNATIVA_DECISION',
            ['estado' => $estadoAnterior],
            [
                'id_alternativa' => (int)$idAlternativa,
                'estado' => $estadoNuevo,
                'comentario' => $comentario !== '' ? $comentario : null,
            ]
        );
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

$idUsuario = exigirAdministradorRecomendaciones();
$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

try {
    if ($metodo === 'GET') {
        $estado = strtoupper(trim((string)($_GET['estado'] ?? '')));
        if ($estado !== '' && !in_array($estado, ['PENDIENTE', 'REVISADA', 'ACEPTADA', 'DESCARTADA'], true)) {
            responderRecomendaciones(false, null, 'El filtro de estado no es válido.', 400);
        }
        responderRecomendaciones(true, [
            'configuracion' => leerConfiguracionRecomendaciones($pdo),
            'conteos' => contarRecomendaciones($pdo),
            'recomendaciones' => listarRecomendaciones($pdo, $estado !== '' ? $estado : null),
        ]);
    }

    if ($metodo !== 'POST') {
        responderRecomendaciones(false, null, 'Método no permitido.', 405);
    }

    $entrada = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responderRecomendaciones(false, null, 'La solicitud debe contener JSON válido.', 400);
    }

    $accion = (string)($entrada['accion'] ?? '');
    if ($accion === 'generar') {
        $resultado = generarRecomendaciones($pdo, $idUsuario);
        responderRecomendaciones(true, $resultado, 'Análisis completado.');
    }

    if ($accion === 'umbral') {
        $umbral = filter_var(
            $entrada['umbral_unidades'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100000]]
        );
        if ($umbral === false) {
            responderRecomendaciones(false, null, 'El umbral debe ser un entero entre 1 y 100000.', 400);
        }

        $pdo->beginTransaction();
        try {
            $consulta = $pdo->prepare("
                SELECT umbral_unidades
                FROM public.recomendacion_producto_config
                WHERE id_configuracion = 1
                FOR UPDATE
            ");
            $consulta->execute();
            $anterior = (int)$consulta->fetchColumn();
            if ($anterior !== $umbral) {
                $actualizar = $pdo->prepare("
                    UPDATE public.recomendacion_producto_config
                    SET umbral_unidades = :umbral,
                        id_usuario_actualizacion = :usuario,
                        fecha_actualizacion = NOW()
                    WHERE id_configuracion = 1
                ");
                $actualizar->execute([':umbral' => $umbral, ':usuario' => $idUsuario]);
                auditarRecomendacion(
                    $pdo,
                    $idUsuario,
                    null,
                    'CAMBIO_UMBRAL',
                    ['umbral_unidades' => $anterior],
                    ['umbral_unidades' => $umbral]
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        responderRecomendaciones(true, ['umbral_unidades' => $umbral], 'Umbral guardado.');
    }

    if ($accion === 'estado') {
        cambiarEstadoRecomendacion($pdo, $idUsuario, $entrada);
        responderRecomendaciones(true, null, 'Estado de la recomendación actualizado.');
    }

    if ($accion === 'alternativa_decision') {
        decidirAlternativa($pdo, $idUsuario, $entrada);
        responderRecomendaciones(true, null, 'Decisión sobre la alternativa sugerida registrada.');
    }

    if ($accion === 'explicar_ia') {
        $resultado = generarExplicacionIA($pdo, $idUsuario, $entrada);
        responderRecomendaciones(true, $resultado, 'Explicación generada con IA (Groq).');
    }

    responderRecomendaciones(false, null, 'La acción solicitada no es válida.', 400);
} catch (Throwable $e) {
    error_log('[recomendaciones] ' . $e->getMessage());
    if ($e instanceof ErrorGroqRecomendacion) {
        responderRecomendaciones(false, null, $e->getMessage(), 502);
    }
    responderRecomendaciones(false, null, 'No fue posible procesar las recomendaciones. Revise que la migración esté aplicada.', 500);
}
