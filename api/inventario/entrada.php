<?php

/**
 * HU-21 — Entrada de mercadería.
 *
 * GET:
 *   Devuelve ubicaciones, productos, proveedores y pedidos disponibles.
 *
 * POST:
 *   Registra una entrada de inventario.
 *
 * Flujo:
 * movimiento_inventario
 *      ↓
 * detalle_movimiento
 *      ↓
 * existencia
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/permisos.php';


/* ==========================================================
   RESPUESTA JSON
========================================================== */

function responderEntrada(
    bool $success,
    $data = null,
    string $message = '',
    int $status = 200
): void {

    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    header('Cache-Control: no-store');

    echo json_encode(
        [
            'success' => $success,
            'data' => $data,
            'message' => $message
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}


/* ==========================================================
   SESIÓN
========================================================== */

function usuarioEntrada(): array
{
    if (empty($_SESSION['usuario'])) {
        responderEntrada(
            false,
            null,
            'Debe iniciar sesión.',
            401
        );
    }

    return $_SESSION['usuario'];
}


/* ==========================================================
   TIENDAS PERMITIDAS
========================================================== */

/**
 * null = Administrador/Gerente puede trabajar
 *        con todas las tiendas.
 *
 * [] = usuario sin tiendas asignadas.
 *
 * [1,2...] = tiendas asignadas.
 */
function tiendasPermitidasEntrada(
    PDO $pdo
): ?array {

    $idRol = (int)(
        $_SESSION['usuario']['id_rol'] ?? 0
    );

    if (
        in_array(
            $idRol,
            UBICACION_ROLES_VEN_TODAS,
            true
        )
    ) {
        return null;
    }

    $idUsuario = (int)(
        $_SESSION['usuario']['id_usuario'] ?? 0
    );

    $asignadas = ubicacionesAsignadas(
        $pdo,
        $idUsuario
    );

    return array_map(
        static fn(array $tienda): int =>
            (int)$tienda['id_tienda'],
        $asignadas
    );
}


/* ==========================================================
   GET — OPCIONES DEL FORMULARIO
========================================================== */

function consultarOpcionesEntrada(
    PDO $pdo
): void {

    $tiendasPermitidas =
        tiendasPermitidasEntrada($pdo);


    /*
     * Ubicaciones
     */
    $condiciones = [
        'ui.estado = TRUE'
    ];

    $parametros = [];


    if ($tiendasPermitidas !== null) {

        if (!$tiendasPermitidas) {

            $condiciones[] = 'FALSE';

        } else {

            $marcadores = [];

            foreach (
                $tiendasPermitidas as
                $indice => $idTienda
            ) {

                $clave =
                    ':tienda_' . $indice;

                $marcadores[] = $clave;

                $parametros[$clave] =
                    $idTienda;
            }

            $condiciones[] =
                'ui.id_tienda IN (' .
                implode(', ', $marcadores) .
                ')';
        }
    }


    $sqlUbicaciones = '
        SELECT
            ui.id_ubicacion,
            ui.id_tienda,
            ui.id_maquina,
            ui.nombre,
            ui.tipo,
            t.nombre AS tienda

        FROM ubicacion_inventario ui

        JOIN tienda t
          ON t.id_tienda = ui.id_tienda

        WHERE ' .
        implode(' AND ', $condiciones) . '

        ORDER BY
            t.nombre,
            ui.tipo,
            ui.nombre
    ';


    $consultaUbicaciones =
        $pdo->prepare($sqlUbicaciones);


    foreach (
        $parametros as
        $clave => $valor
    ) {
        $consultaUbicaciones->bindValue(
            $clave,
            $valor,
            PDO::PARAM_INT
        );
    }


    $consultaUbicaciones->execute();

    $ubicaciones =
        $consultaUbicaciones->fetchAll();


    /*
     * Productos activos
     */
    $consultaProductos =
        $pdo->query(
            '
            SELECT
                id_producto,
                nombre,
                codigo_articulo_retail,
                codigo_barras

            FROM producto

            WHERE estado = TRUE

            ORDER BY nombre
            '
        );


    $productos =
        $consultaProductos->fetchAll();


    /*
     * Proveedores
     */
    $consultaProveedores =
        $pdo->query(
            '
            SELECT
                id_proveedor,
                nombre

            FROM proveedor

            ORDER BY nombre
            '
        );


    $proveedores =
        $consultaProveedores->fetchAll();

    /* Asociaciones activas de producto y proveedor para el selector. */
    $consultaRelaciones = $pdo->query(<<<'SQL'
        SELECT DISTINCT pp.id_producto, pp.id_proveedor
        FROM producto_proveedor pp
        JOIN producto p ON p.id_producto = pp.id_producto AND p.estado = TRUE
        JOIN proveedor pr ON pr.id_proveedor = pp.id_proveedor AND pr.estado = TRUE
        WHERE pp.estado = TRUE
        ORDER BY pp.id_producto, pp.id_proveedor
    SQL);
    $proveedoresProducto = $consultaRelaciones->fetchAll();


    /*
     * Productos de pedidos con recepción habilitada, por destino.
     * Se descuentan también las recepciones registradas que aún
     * están pendientes de confirmación, para no ofrecer unidades
     * que ya se encuentran reservadas para una recepción.
     */
    $sqlPedidos = <<<'SQL'
        SELECT
            p.id_pedido,
            p.id_proveedor,
            p.id_ubicacion,
            p.fecha_pedido,
            p.estado,
            pr.nombre AS proveedor,
            pd.id_producto,
            pd.cantidad_solicitada,
            pd.cantidad_recibida,
            (
                pd.cantidad_solicitada - pd.cantidad_recibida
                - COALESCE(reservas.cantidad_reservada, 0)
            ) AS cantidad_pendiente
        FROM pedido p
        JOIN proveedor pr ON pr.id_proveedor = p.id_proveedor
        JOIN ubicacion_inventario ui ON ui.id_ubicacion = p.id_ubicacion
        JOIN pedido_detalle pd ON pd.id_pedido = p.id_pedido
        LEFT JOIN (
            SELECT m.id_pedido, dm.id_producto,
                   SUM(dm.cantidad) AS cantidad_reservada
            FROM movimiento_inventario m
            JOIN detalle_movimiento dm ON dm.id_movimiento = m.id_movimiento
            WHERE m.tipo_movimiento = 'ENTRADA'
              AND m.estado = 'PENDIENTE'
              AND m.id_pedido IS NOT NULL
            GROUP BY m.id_pedido, dm.id_producto
        ) reservas
          ON reservas.id_pedido = pd.id_pedido
         AND reservas.id_producto = pd.id_producto
        WHERE UPPER(TRIM(p.estado)) IN ('PENDIENTE', 'EN_CAMINO', 'PARCIAL')
          AND p.requiere_aprobacion = FALSE
          AND ui.estado = TRUE
          AND (
              pd.cantidad_solicitada - pd.cantidad_recibida
              - COALESCE(reservas.cantidad_reservada, 0)
          ) > 0
    SQL;

    $parametrosPedidos = [];
    if ($tiendasPermitidas !== null) {
        if (!$tiendasPermitidas) {
            $sqlPedidos .= ' AND FALSE';
        } else {
            $marcadoresPedidos = [];
            foreach ($tiendasPermitidas as $indice => $idTienda) {
                $clave = ':pedido_tienda_' . $indice;
                $marcadoresPedidos[] = $clave;
                $parametrosPedidos[$clave] = $idTienda;
            }
            $sqlPedidos .= ' AND ui.id_tienda IN (' .
                implode(', ', $marcadoresPedidos) . ')';
        }
    }

    $sqlPedidos .= ' ORDER BY p.fecha_pedido DESC, p.id_pedido DESC';
    $consultaPedidos = $pdo->prepare($sqlPedidos);
    foreach ($parametrosPedidos as $clave => $valor) {
        $consultaPedidos->bindValue($clave, $valor, PDO::PARAM_INT);
    }
    $consultaPedidos->execute();
    $pedidos = $consultaPedidos->fetchAll();


    responderEntrada(
        true,
        [
            'ubicaciones' => $ubicaciones,
            'productos' => $productos,
            'proveedores' => $proveedores,
            'proveedores_producto' => $proveedoresProducto,
            'pedidos' => $pedidos
        ],
        'Opciones cargadas correctamente.'
    );
}


/* ==========================================================
   POST — REGISTRAR ENTRADA
========================================================== */

function registrarEntrada(
    PDO $pdo,
    array $usuario
): void {

    $contenido =
        file_get_contents('php://input');

    $datos =
        json_decode(
            $contenido ?: '',
            true
        );


    if (!is_array($datos)) {

        responderEntrada(
            false,
            null,
            'Los datos enviados no son válidos.',
            400
        );
    }


    $idUsuario =
        (int)($usuario['id_usuario'] ?? 0);

    $idUbicacion =
        (int)($datos['id_ubicacion'] ?? 0);

    $idProducto =
        (int)($datos['id_producto'] ?? 0);

    $cantidad =
        (int)($datos['cantidad'] ?? 0);

    $origen =
        trim(
            (string)($datos['origen'] ?? '')
        );

    $referencia =
        trim(
            (string)($datos['referencia'] ?? '')
        );

    $observaciones =
        trim(
            (string)($datos['observaciones'] ?? '')
        );

    $idPedido =
        isset($datos['id_pedido']) &&
        $datos['id_pedido'] !== ''
            ? (int)$datos['id_pedido']
            : null;


    $idProveedor =
        isset($datos['id_proveedor']) &&
        $datos['id_proveedor'] !== ''
            ? (int)$datos['id_proveedor']
            : null;


    /* ======================================================
       VALIDACIONES
    ====================================================== */

    if ($idUsuario <= 0) {

        responderEntrada(
            false,
            null,
            'No fue posible identificar al usuario.',
            401
        );
    }


    if ($idUbicacion <= 0) {

        responderEntrada(
            false,
            null,
            'Debe seleccionar una ubicación.',
            400
        );
    }


    if ($idProducto <= 0) {

        responderEntrada(
            false,
            null,
            'Debe seleccionar un producto.',
            400
        );
    }


    if ($cantidad <= 0) {

        responderEntrada(
            false,
            null,
            'La cantidad debe ser mayor que cero.',
            400
        );
    }


    $origenesPermitidos = [
        'Proveedor',
        'Pedido de compra',
        'Devolución'
    ];


    if (
        $origen === '' ||
        !in_array(
            $origen,
            $origenesPermitidos,
            true
        )
    ) {

        responderEntrada(
            false,
            null,
            'Debe indicar un origen válido para la mercadería.',
            400
        );
    }


    if (
        $origen === 'Proveedor' &&
        (
            $idProveedor === null ||
            $idProveedor <= 0
        )
    ) {

        responderEntrada(
            false,
            null,
            'Debe seleccionar un proveedor.',
            400
        );
    }


    if (
        $origen === 'Proveedor' &&
        $referencia === ''
    ) {

        responderEntrada(
            false,
            null,
            'Debe indicar el documento o referencia del proveedor.',
            400
        );
    }


    if (
        $origen === 'Pedido de compra' &&
        (
            $idPedido === null ||
            $idPedido <= 0
        )
    ) {

        responderEntrada(
            false,
            null,
            'Debe indicar un número de pedido válido.',
            400
        );
    }


    if (
        $origen === 'Devolución' &&
        $observaciones === ''
    ) {

        responderEntrada(
            false,
            null,
            'Debe indicar el motivo de la devolución.',
            400
        );
    }


    /*
     * La recepción asociada a un pedido siempre debe pasar por
     * la API de Compras (HU-32). Evita un alta de existencias
     * que no actualice pedido_detalle y permita duplicados.
     */
    if ($origen === 'Pedido de compra') {
        responderEntrada(
            false,
            null,
            'Las entradas por pedido se registran mediante la recepción de Compras.',
            409
        );
    }

    /*
     * Normalizar relaciones según el origen.
     */
    if ($origen !== 'Pedido de compra') {
        $idPedido = null;
    }

    if ($origen !== 'Proveedor') {
        $idProveedor = null;
    }


    /* ======================================================
       VALIDAR UBICACIÓN
    ====================================================== */

    $consultaUbicacion =
        $pdo->prepare(
            '
            SELECT
                ui.id_ubicacion,
                ui.id_tienda,
                ui.nombre,
                ui.tipo,
                t.nombre AS tienda

            FROM ubicacion_inventario ui

            JOIN tienda t
              ON t.id_tienda = ui.id_tienda

            WHERE ui.id_ubicacion = :id_ubicacion
              AND ui.estado = TRUE

            LIMIT 1
            '
        );


    $consultaUbicacion->execute([
        ':id_ubicacion' => $idUbicacion
    ]);


    $ubicacion =
        $consultaUbicacion->fetch();


    if (!$ubicacion) {

        responderEntrada(
            false,
            null,
            'La ubicación seleccionada no existe o está inactiva.',
            404
        );
    }


    /*
     * Verificar que el usuario tenga acceso
     * a la tienda de esa ubicación.
     */
    $tiendasPermitidas =
        tiendasPermitidasEntrada($pdo);


    if (
        $tiendasPermitidas !== null &&
        !in_array(
            (int)$ubicacion['id_tienda'],
            $tiendasPermitidas,
            true
        )
    ) {

        responderEntrada(
            false,
            null,
            'No tiene permiso para registrar movimientos en esta ubicación.',
            403
        );
    }


    /* ======================================================
       VALIDAR PRODUCTO
    ====================================================== */

    $consultaProducto =
        $pdo->prepare(
            '
            SELECT
                id_producto,
                nombre,
                codigo_articulo_retail,
                codigo_barras

            FROM producto

            WHERE id_producto = :id_producto
              AND estado = TRUE

            LIMIT 1
            '
        );


    $consultaProducto->execute([
        ':id_producto' => $idProducto
    ]);


    $producto =
        $consultaProducto->fetch();


    if (!$producto) {

        responderEntrada(
            false,
            null,
            'El producto seleccionado no existe o está inactivo.',
            404
        );
    }


    /* ======================================================
       VALIDAR PROVEEDOR DIRECTO
    ====================================================== */

    $proveedorSeleccionado = null;


    if ($idProveedor !== null) {

        $consultaProveedor =
            $pdo->prepare(
                '
                SELECT
                    id_proveedor,
                    nombre

                FROM proveedor

                WHERE id_proveedor = :id_proveedor

                LIMIT 1
                '
            );


        $consultaProveedor->execute([
            ':id_proveedor' => $idProveedor
        ]);


        $proveedorSeleccionado =
            $consultaProveedor->fetch();


        if (!$proveedorSeleccionado) {

            responderEntrada(
                false,
                null,
                'El proveedor seleccionado no existe.',
                400
            );
        }
    }


    /* Asociación proveedor-producto válida también para llamadas directas. */
    if ($origen === 'Proveedor' && $idProveedor !== null) {
        $consultaAsociacion = $pdo->prepare(<<<'SQL'
            SELECT 1
            FROM producto_proveedor pp
            JOIN proveedor pr ON pr.id_proveedor = pp.id_proveedor
            WHERE pp.id_producto = :producto
              AND pp.id_proveedor = :proveedor
              AND pp.estado = TRUE
              AND pr.estado = TRUE
            LIMIT 1
        SQL);
        $consultaAsociacion->execute([
            ':producto' => $idProducto,
            ':proveedor' => $idProveedor,
        ]);
        if (!$consultaAsociacion->fetchColumn()) {
            responderEntrada(false, null,
                'El proveedor no está asociado a este producto.', 400);
        }
    }

    /* ======================================================
       VALIDAR PEDIDO RELACIONADO
    ====================================================== */

    $pedidoSeleccionado = null;


    if ($idPedido !== null) {

        $consultaPedido =
            $pdo->prepare(
                '
                SELECT
                    p.id_pedido,
                    p.id_proveedor,
                    p.fecha_pedido,
                    p.estado,
                    pr.nombre AS proveedor

                FROM pedido p

                JOIN proveedor pr
                  ON pr.id_proveedor = p.id_proveedor

                WHERE p.id_pedido = :id_pedido

                LIMIT 1
                '
            );


        $consultaPedido->execute([
            ':id_pedido' => $idPedido
        ]);


        $pedidoSeleccionado =
            $consultaPedido->fetch();


        if (!$pedidoSeleccionado) {

            responderEntrada(
                false,
                null,
                'El pedido indicado no existe.',
                400
            );
        }


        $estadoPedido =
            strtoupper(
                trim(
                    (string)$pedidoSeleccionado['estado']
                )
            );


        if (
            !in_array(
                $estadoPedido,
                [
                    'PENDIENTE',
                    'APROBADO'
                ],
                true
            )
        ) {

            responderEntrada(
                false,
                null,
                'El pedido seleccionado ya no está disponible para recepción.',
                400
            );
        }


        /*
         * El proveedor se obtiene directamente del pedido.
         * No se confía en un proveedor enviado por el navegador.
         */
        $idProveedor =
            (int)$pedidoSeleccionado['id_proveedor'];


        $proveedorSeleccionado = [
            'id_proveedor' =>
                $idProveedor,

            'nombre' =>
                $pedidoSeleccionado['proveedor']
        ];

    } else {

        $idPedido = null;
    }


    /* ======================================================
       PREPARAR OBSERVACIONES
    ====================================================== */

    $textoObservaciones = [];


    if ($proveedorSeleccionado) {

        $textoObservaciones[] =
            'Proveedor: ' .
            $proveedorSeleccionado['nombre'] .
            ' (#' .
            (int)$proveedorSeleccionado['id_proveedor'] .
            ')';
    }


    if ($idPedido !== null) {

        $textoObservaciones[] =
            'Pedido: #' . $idPedido;
    }


    if ($referencia !== '') {

        $textoObservaciones[] =
            'Referencia: ' . $referencia;
    }


    if ($observaciones !== '') {

        $textoObservaciones[] =
            $observaciones;
    }


    $observacionesFinales =
        $textoObservaciones
            ? implode(
                ' | ',
                $textoObservaciones
            )
            : null;


    /* ======================================================
       TRANSACCIÓN
    ====================================================== */

    try {

        $pdo->beginTransaction();


        /* --------------------------------------------------
           EXISTENCIA ACTUAL
        -------------------------------------------------- */

        $consultaExistencia =
            $pdo->prepare(
                '
                SELECT
                    id_existencia,
                    cantidad,
                    stock_minimo

                FROM existencia

                WHERE id_ubicacion = :id_ubicacion
                  AND id_producto = :id_producto

                FOR UPDATE
                '
            );


        $consultaExistencia->execute([
            ':id_ubicacion' => $idUbicacion,
            ':id_producto' => $idProducto
        ]);


        $existencia =
            $consultaExistencia->fetch();


        if ($existencia) {

            $saldoAnterior =
                (int)$existencia['cantidad'];

            $saldoResultante =
                $saldoAnterior + $cantidad;


            $actualizarExistencia =
                $pdo->prepare(
                    '
                    UPDATE existencia

                    SET
                        cantidad = :cantidad,
                        fecha_actualizacion = NOW()

                    WHERE id_existencia = :id_existencia
                    '
                );


            $actualizarExistencia->execute([
                ':cantidad' =>
                    $saldoResultante,

                ':id_existencia' =>
                    (int)$existencia[
                        'id_existencia'
                    ]
            ]);

        } else {

            /*
             * El producto todavía no existía
             * en esta ubicación.
             */
            $saldoAnterior = 0;

            $saldoResultante =
                $cantidad;


            $insertarExistencia =
                $pdo->prepare(
                    '
                    INSERT INTO existencia (
                        id_ubicacion,
                        id_producto,
                        cantidad,
                        stock_minimo,
                        fecha_actualizacion
                    )
                    VALUES (
                        :id_ubicacion,
                        :id_producto,
                        :cantidad,
                        0,
                        NOW()
                    )
                    '
                );


            $insertarExistencia->execute([
                ':id_ubicacion' =>
                    $idUbicacion,

                ':id_producto' =>
                    $idProducto,

                ':cantidad' =>
                    $saldoResultante
            ]);
        }


        /* --------------------------------------------------
           MOVIMIENTO
        -------------------------------------------------- */

        $insertarMovimiento =
            $pdo->prepare(
                '
                INSERT INTO movimiento_inventario (
                    id_usuario,
                    id_ubicacion_origen,
                    id_ubicacion_destino,
                    id_relacion_abastecimiento,
                    id_usuario_autoriza,
                    tipo_movimiento,
                    motivo,
                    observaciones,
                    evidencia_url,
                    estado,
                    fecha_movimiento,
                    fecha_confirmacion,
                    id_pedido
                )
                VALUES (
                    :id_usuario,
                    NULL,
                    :id_ubicacion_destino,
                    NULL,
                    NULL,
                    \'ENTRADA\',
                    :motivo,
                    :observaciones,
                    NULL,
                    \'CONFIRMADO\',
                    NOW(),
                    NOW(),
                    :id_pedido
                )

                RETURNING id_movimiento
                '
            );


        $insertarMovimiento->bindValue(
            ':id_usuario',
            $idUsuario,
            PDO::PARAM_INT
        );


        $insertarMovimiento->bindValue(
            ':id_ubicacion_destino',
            $idUbicacion,
            PDO::PARAM_INT
        );


        $insertarMovimiento->bindValue(
            ':motivo',
            $origen,
            PDO::PARAM_STR
        );


        if ($observacionesFinales !== null) {

            $insertarMovimiento->bindValue(
                ':observaciones',
                $observacionesFinales,
                PDO::PARAM_STR
            );

        } else {

            $insertarMovimiento->bindValue(
                ':observaciones',
                null,
                PDO::PARAM_NULL
            );
        }


        if ($idPedido !== null) {

            $insertarMovimiento->bindValue(
                ':id_pedido',
                $idPedido,
                PDO::PARAM_INT
            );

        } else {

            $insertarMovimiento->bindValue(
                ':id_pedido',
                null,
                PDO::PARAM_NULL
            );
        }


        $insertarMovimiento->execute();


        $idMovimiento =
            (int)$insertarMovimiento
                ->fetchColumn();


        if ($idMovimiento <= 0) {

            throw new RuntimeException(
                'No fue posible generar el movimiento.'
            );
        }


        /* --------------------------------------------------
           DETALLE DEL MOVIMIENTO
        -------------------------------------------------- */

        $insertarDetalle =
            $pdo->prepare(
                '
                INSERT INTO detalle_movimiento (
                    id_movimiento,
                    id_producto,
                    cantidad,
                    saldo_origen_anterior,
                    saldo_origen_resultante,
                    saldo_destino_anterior,
                    saldo_destino_resultante
                )
                VALUES (
                    :id_movimiento,
                    :id_producto,
                    :cantidad,
                    NULL,
                    NULL,
                    :saldo_anterior,
                    :saldo_resultante
                )
                '
            );


        $insertarDetalle->execute([
            ':id_movimiento' =>
                $idMovimiento,

            ':id_producto' =>
                $idProducto,

            ':cantidad' =>
                $cantidad,

            ':saldo_anterior' =>
                $saldoAnterior,

            ':saldo_resultante' =>
                $saldoResultante
        ]);


        /* --------------------------------------------------
           CONFIRMAR TRANSACCIÓN
        -------------------------------------------------- */

        $pdo->commit();


        responderEntrada(
            true,
            [
                'id_movimiento' =>
                    $idMovimiento,

                'producto' => [
                    'id_producto' =>
                        $idProducto,

                    'nombre' =>
                        $producto['nombre']
                ],

                'ubicacion' => [
                    'id_ubicacion' =>
                        $idUbicacion,

                    'tienda' =>
                        $ubicacion['tienda'],

                    'nombre' =>
                        $ubicacion['nombre']
                ],

                'proveedor' =>
                    $proveedorSeleccionado
                        ? [
                            'id_proveedor' =>
                                (int)$proveedorSeleccionado['id_proveedor'],

                            'nombre' =>
                                $proveedorSeleccionado['nombre']
                        ]
                        : null,

                'pedido' =>
                    $pedidoSeleccionado
                        ? [
                            'id_pedido' =>
                                (int)$pedidoSeleccionado['id_pedido'],

                            'estado' =>
                                $pedidoSeleccionado['estado']
                        ]
                        : null,

                'cantidad_entrada' =>
                    $cantidad,

                'saldo_anterior' =>
                    $saldoAnterior,

                'saldo_resultante' =>
                    $saldoResultante
            ],
            'Entrada de mercadería registrada correctamente.',
            201
        );


    } catch (Throwable $error) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        error_log(
            'HU-21 entrada inventario: ' .
            $error->getMessage()
        );


        responderEntrada(
            false,
            null,
            'No fue posible registrar la entrada de mercadería.',
            500
        );
    }
}


/* ==========================================================
   EJECUCIÓN
========================================================== */

$usuario = usuarioEntrada();

$metodo =
    $_SERVER['REQUEST_METHOD'] ?? 'GET';


if ($metodo === 'GET') {

    consultarOpcionesEntrada($pdo);

}


if ($metodo === 'POST') {

    registrarEntrada(
        $pdo,
        $usuario
    );

}


responderEntrada(
    false,
    null,
    'Método no permitido.',
    405
);