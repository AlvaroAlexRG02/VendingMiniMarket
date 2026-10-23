<?php
declare(strict_types=1);

/**
 * API de precios — VendingMiniMarket
 *
 * HU-37  Configurar IVA y márgenes   listar_margenes, guardar_margen, estado_margen,
 *                                    configurar_iva, margen_masivo
 * HU-38  Calcular precio de venta    calcular, aplicar
 * HU-39  Precio distinto por tienda  listar, aplicar (un precio activo por producto y tienda)
 * HU-40  Simular e historial         simular, aplicar_simulacion, simulaciones, historial
 *
 * Consultar y simular: cualquier usuario con sesión. Configurar y aplicar: administrador.
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/precios_lib.php';


/* ==========================================================
   LECTURA
========================================================== */

function accionCatalogos(): void
{
    libUsuario();
    $db = libDb();

    $productos = $db->query('
        SELECT p.id_producto, p.nombre, p.codigo_articulo_retail, p.id_impuesto,
               i.nombre AS impuesto_nombre, i.porcentaje AS impuesto_porcentaje
        FROM public.producto p
        JOIN public.impuesto i ON i.id_impuesto = p.id_impuesto
        WHERE p.estado = TRUE
        ORDER BY LOWER(p.nombre), p.id_producto
    ')->fetchAll(PDO::FETCH_ASSOC);

    $tiendas = $db->query('SELECT * FROM public.tienda ORDER BY id_tienda')->fetchAll(PDO::FETCH_ASSOC);

    $margenes = $db->query('
        SELECT id_margen, nombre, porcentaje
        FROM public.margen_ganancia WHERE estado = TRUE ORDER BY porcentaje, nombre
    ')->fetchAll(PDO::FETCH_ASSOC);

    $impuestos = $db->query('
        SELECT id_impuesto, nombre, porcentaje
        FROM public.impuesto WHERE estado = TRUE ORDER BY porcentaje DESC, nombre
    ')->fetchAll(PDO::FETCH_ASSOC);

    libJson(true, [
        'productos' => libNumericos($productos, ['impuesto_porcentaje']),
        'tiendas'   => $tiendas,
        'margenes'  => libNumericos($margenes, ['porcentaje']),
        'impuestos' => libNumericos($impuestos, ['porcentaje']),
    ]);
}

/** Datos de apoyo al elegir producto y tienda: IVA, precio activo y costos de proveedor. */
function accionContexto(): void
{
    libUsuario();
    $db = libDb();

    $idProducto = (int)($_GET['id_producto'] ?? 0);
    $idTienda   = (int)($_GET['id_tienda'] ?? 0);

    if ($idProducto <= 0) {
        throw new PrecioException('Producto inválido.');
    }

    $st = $db->prepare('
        SELECT i.nombre, i.porcentaje
        FROM public.producto p
        JOIN public.impuesto i ON i.id_impuesto = p.id_impuesto
        WHERE p.id_producto = :id
    ');
    $st->execute([':id' => $idProducto]);
    $iva = $st->fetch(PDO::FETCH_ASSOC);

    if (!$iva) {
        throw new PrecioException('Producto no encontrado.', 404);
    }

    $activo = $idTienda > 0 ? plPrecioActivo($db, $idProducto, $idTienda) : null;

    $st = $db->prepare('
        SELECT pr.nombre, pp.costo
        FROM public.producto_proveedor pp
        JOIN public.proveedor pr ON pr.id_proveedor = pp.id_proveedor
        WHERE pp.id_producto = :id AND pp.estado = TRUE
        ORDER BY pr.nombre
    ');
    $st->execute([':id' => $idProducto]);
    $costos = libNumericos($st->fetchAll(PDO::FETCH_ASSOC), ['costo']);

    libJson(true, [
        'impuesto_nombre'     => $iva['nombre'],
        'impuesto_porcentaje' => (float)$iva['porcentaje'],
        'precio_activo'       => $activo
            ? libNumericos([$activo], ['costo', 'costo_con_impuesto', 'precio_venta'])[0]
            : null,
        'costos_proveedor'    => $costos,
    ]);
}

/** Precios activos por tienda (HU-39). Filtros: id_tienda, busqueda. */
function accionListar(): void
{
    libUsuario();
    $db = libDb();

    $where  = ['pt.estado = TRUE'];
    $params = [];

    $tienda = (int)($_GET['id_tienda'] ?? 0);
    if ($tienda > 0) {
        $where[] = 'pt.id_tienda = :tienda';
        $params[':tienda'] = $tienda;
    }

    $busqueda = trim((string)($_GET['busqueda'] ?? ''));
    if ($busqueda !== '') {
        $partes = [];

        $normalizada = libNormalizar($busqueda);
        if ($normalizada !== '') {
            $partes[] = libSqlNormalizado('p.nombre') . ' LIKE :b_nombre';
            $params[':b_nombre'] = '%' . $normalizada . '%';
        }

        // '!' como carácter de escape (el analizador de PDO no se lleva bien con '\').
        $partes[] = "COALESCE(p.codigo_articulo_retail, '') ILIKE :b_codigo ESCAPE '!'";
        $params[':b_codigo'] = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda) . '%';

        $where[] = '(' . implode(' OR ', $partes) . ')';
    }

    $st = $db->prepare('
        SELECT p.id_producto, p.nombre, p.codigo_articulo_retail,
               pt.id_tienda, pt.id_precio, pt.costo, pt.precio_con_impuesto AS costo_con_impuesto, pt.precio_venta,
               pt.id_margen, mg.nombre AS margen_nombre, mg.porcentaje AS margen_porcentaje,
               i.porcentaje AS iva_actual, pt.fecha_inicio
        FROM public.precio_tienda pt
        JOIN public.producto p ON p.id_producto = pt.id_producto
        JOIN public.margen_ganancia mg ON mg.id_margen = pt.id_margen
        JOIN public.impuesto i ON i.id_impuesto = p.id_impuesto
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY LOWER(p.nombre), pt.id_tienda
        LIMIT 500
    ');
    $st->execute($params);

    libJson(true, libNumericos($st->fetchAll(PDO::FETCH_ASSOC), [
        'costo', 'costo_con_impuesto', 'precio_venta', 'margen_porcentaje', 'iva_actual',
    ]));
}

function accionHistorial(): void
{
    libUsuario();
    $db = libDb();

    $producto = (int)($_GET['id_producto'] ?? 0);
    $tienda   = (int)($_GET['id_tienda'] ?? 0);

    if ($producto <= 0) {
        throw new PrecioException('Indique el producto.');
    }

    $sql = "
        SELECT h.id_historial, h.id_producto, h.id_tienda,
               h.costo_anterior, h.costo_con_impuesto_anterior, h.precio_venta_anterior,
               h.costo_nuevo, h.costo_con_impuesto_nuevo, h.precio_venta_nuevo,
               h.porcentaje_iva, h.fecha_cambio,
               mg.nombre AS margen_nombre, mg.porcentaje AS margen_porcentaje,
               TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario
        FROM public.historial_precio h
        JOIN public.margen_ganancia mg ON mg.id_margen = h.id_margen
        LEFT JOIN public.usuario u ON u.id_usuario = h.id_usuario
        WHERE h.id_producto = :p
    ";
    $params = [':p' => $producto];

    if ($tienda > 0) {
        $sql .= ' AND h.id_tienda = :t';
        $params[':t'] = $tienda;
    }

    $st = $db->prepare($sql . ' ORDER BY h.fecha_cambio DESC, h.id_historial DESC LIMIT 200');
    $st->execute($params);

    libJson(true, libNumericos($st->fetchAll(PDO::FETCH_ASSOC), [
        'costo_anterior', 'costo_con_impuesto_anterior', 'precio_venta_anterior',
        'costo_nuevo', 'costo_con_impuesto_nuevo', 'precio_venta_nuevo',
        'porcentaje_iva', 'margen_porcentaje',
    ]));
}

function accionSimulaciones(): void
{
    libUsuario();
    $db = libDb();

    $producto = (int)($_GET['id_producto'] ?? 0);
    $tienda   = (int)($_GET['id_tienda'] ?? 0);

    $sql = '
        SELECT s.id_simulacion, s.id_producto, s.id_tienda, s.costo, s.porcentaje_iva,
               s.costo_con_impuesto, s.precio_calculado, s.precio_venta, s.aplicado, s.fecha_creacion,
               p.nombre AS producto_nombre,
               mg.nombre AS margen_nombre, mg.porcentaje AS margen_porcentaje
        FROM public.simulacion_precio s
        JOIN public.producto p ON p.id_producto = s.id_producto
        JOIN public.margen_ganancia mg ON mg.id_margen = s.id_margen
        WHERE 1 = 1
    ';
    $params = [];

    if ($producto > 0) {
        $sql .= ' AND s.id_producto = :p';
        $params[':p'] = $producto;
    }

    if ($tienda > 0) {
        $sql .= ' AND s.id_tienda = :t';
        $params[':t'] = $tienda;
    }

    $st = $db->prepare($sql . ' ORDER BY s.fecha_creacion DESC, s.id_simulacion DESC LIMIT 100');
    $st->execute($params);

    $filas = libNumericos($st->fetchAll(PDO::FETCH_ASSOC), [
        'costo', 'porcentaje_iva', 'costo_con_impuesto', 'precio_calculado', 'precio_venta', 'margen_porcentaje',
    ]);

    foreach ($filas as &$f) {
        $f['aplicado'] = libBool($f['aplicado']);
    }
    unset($f);

    libJson(true, $filas);
}


/* ==========================================================
   MÁRGENES (HU-37)
========================================================== */

function obtenerMargen(PDO $db, int $id): ?array
{
    $st = $db->prepare('SELECT id_margen, nombre, porcentaje, descripcion, estado FROM public.margen_ganancia WHERE id_margen = :id');
    $st->execute([':id' => $id]);
    $f = $st->fetch(PDO::FETCH_ASSOC);

    if (!$f) {
        return null;
    }

    $f['id_margen']  = (int)$f['id_margen'];
    $f['porcentaje'] = (float)$f['porcentaje'];
    $f['estado']     = libBool($f['estado']);

    return $f;
}

function accionListarMargenes(): void
{
    libUsuario();
    $db = libDb();

    $filas = $db->query('
        SELECT m.id_margen, m.nombre, m.porcentaje, m.descripcion, m.estado,
               (SELECT COUNT(*) FROM public.precio_tienda pt
                 WHERE pt.id_margen = m.id_margen AND pt.estado = TRUE) AS precios_activos
        FROM public.margen_ganancia m
        ORDER BY m.porcentaje, LOWER(m.nombre)
    ')->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filas as &$f) {
        $f['id_margen']       = (int)$f['id_margen'];
        $f['porcentaje']      = (float)$f['porcentaje'];
        $f['estado']          = libBool($f['estado']);
        $f['precios_activos'] = (int)$f['precios_activos'];
    }
    unset($f);

    libJson(true, $filas);
}

function accionGuardarMargen(): void
{
    libAdmin();
    libPost();
    $in = libEntrada();
    $db = libDb();

    $id          = (int)($in['id_margen'] ?? 0);
    $nombre      = trim((string)($in['nombre'] ?? ''));
    $descripcion = trim((string)($in['descripcion'] ?? ''));

    if ($nombre === '') {
        throw new PrecioException('El nombre del margen es obligatorio.');
    }

    if (mb_strlen($nombre, 'UTF-8') > 100) {
        throw new PrecioException('El nombre del margen no puede superar 100 caracteres.');
    }

    if (mb_strlen($descripcion, 'UTF-8') > 250) {
        throw new PrecioException('La descripción no puede superar 250 caracteres.');
    }

    $porcentaje = plNumero($in['porcentaje'] ?? null, 'El porcentaje de margen', 0, 999.99);

    $st = $db->prepare('SELECT 1 FROM public.margen_ganancia WHERE LOWER(TRIM(nombre)) = LOWER(:n) AND id_margen <> :id LIMIT 1');
    $st->execute([':n' => $nombre, ':id' => $id]);

    if ($st->fetchColumn()) {
        throw new PrecioException('Ya existe un margen con ese nombre.', 409);
    }

    $fila = plTransaccion($db, function () use ($db, $id, $nombre, $porcentaje, $descripcion) {
        if ($id > 0) {
            $antes = obtenerMargen($db, $id);

            if (!$antes) {
                throw new PrecioException('Margen no encontrado.', 404);
            }

            $st = $db->prepare('UPDATE public.margen_ganancia SET nombre = :n, porcentaje = :p, descripcion = :d WHERE id_margen = :id');
            $st->execute([':n' => $nombre, ':p' => $porcentaje, ':d' => $descripcion !== '' ? $descripcion : null, ':id' => $id]);

            $despues = obtenerMargen($db, $id);
            libAuditar($db, 'MARGEN', 'EDITAR', $antes, $despues);

            return $despues;
        }

        $st = $db->prepare('INSERT INTO public.margen_ganancia (nombre, porcentaje, descripcion, estado) VALUES (:n, :p, :d, TRUE) RETURNING id_margen');
        $st->execute([':n' => $nombre, ':p' => $porcentaje, ':d' => $descripcion !== '' ? $descripcion : null]);

        $nuevo = obtenerMargen($db, (int)$st->fetchColumn());
        libAuditar($db, 'MARGEN', 'CREAR', null, $nuevo);

        return $nuevo;
    });

    libJson(true, $fila, $id > 0 ? 'Margen actualizado correctamente.' : 'Margen creado correctamente.', $id > 0 ? 200 : 201);
}

function accionEstadoMargen(): void
{
    libAdmin();
    libPost();
    $in = libEntrada();
    $db = libDb();

    $id      = (int)($in['id_margen'] ?? 0);
    $activar = libBool($in['activar'] ?? false);
    $antes   = $id > 0 ? obtenerMargen($db, $id) : null;

    if (!$antes) {
        throw new PrecioException('Margen no encontrado.', 404);
    }

    $fila = plTransaccion($db, function () use ($db, $id, $activar, $antes) {
        $st = $db->prepare('UPDATE public.margen_ganancia SET estado = :e WHERE id_margen = :id');
        $st->bindValue(':e', $activar, PDO::PARAM_BOOL);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();

        $despues = obtenerMargen($db, $id);
        libAuditar($db, 'MARGEN', $activar ? 'ACTIVAR' : 'INACTIVAR', $antes, $despues);

        return $despues;
    });

    libJson(
        true,
        $fila,
        $activar
            ? 'Margen activado correctamente.'
            : 'Margen desactivado. Los precios que ya lo usan se conservan.'
    );
}


/* ==========================================================
   IVA POR PRODUCTO (HU-37)
========================================================== */

function accionConfigurarIva(): void
{
    libAdmin();
    libPost();
    $in = libEntrada();
    $db = libDb();

    $idProducto = (int)($in['id_producto'] ?? 0);
    $idImpuesto = (int)($in['id_impuesto'] ?? 0);

    if ($idProducto <= 0 || $idImpuesto <= 0) {
        throw new PrecioException('Producto e impuesto son obligatorios.');
    }

    $st = $db->prepare('
        SELECT p.id_producto, p.nombre, p.id_impuesto, i.nombre AS impuesto_nombre
        FROM public.producto p
        JOIN public.impuesto i ON i.id_impuesto = p.id_impuesto
        WHERE p.id_producto = :id
    ');
    $st->execute([':id' => $idProducto]);
    $producto = $st->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new PrecioException('Producto no encontrado.', 404);
    }

    $st = $db->prepare('SELECT id_impuesto, nombre, porcentaje FROM public.impuesto WHERE id_impuesto = :id AND estado = TRUE');
    $st->execute([':id' => $idImpuesto]);
    $impuesto = $st->fetch(PDO::FETCH_ASSOC);

    if (!$impuesto) {
        throw new PrecioException('Impuesto inexistente o inactivo.');
    }

    if ((int)$producto['id_impuesto'] === $idImpuesto) {
        throw new PrecioException('El producto ya tiene ese impuesto.', 409);
    }

    plTransaccion($db, function () use ($db, $idProducto, $idImpuesto, $producto, $impuesto) {
        $st = $db->prepare('UPDATE public.producto SET id_impuesto = :i WHERE id_producto = :p');
        $st->execute([':i' => $idImpuesto, ':p' => $idProducto]);

        // Misma forma que usa el historial del producto (productos.js).
        libAuditar(
            $db,
            'PRODUCTO',
            'EDITAR',
            ['id_producto' => $idProducto, 'nombre' => $producto['nombre'], 'impuesto_nombre' => $producto['impuesto_nombre']],
            ['id_producto' => $idProducto, 'nombre' => $producto['nombre'], 'impuesto_nombre' => $impuesto['nombre']]
        );
    });

    libJson(true, [
        'id_producto' => $idProducto,
        'id_impuesto' => $idImpuesto,
        'nombre'      => $impuesto['nombre'],
        'porcentaje'  => (float)$impuesto['porcentaje'],
    ], 'IVA del producto actualizado. Los precios activos no se recalculan solos: use «Recalcular» en la lista de precios.');
}


/* ==========================================================
   CALCULAR, SIMULAR Y APLICAR (HU-38, HU-39, HU-40)
========================================================== */

function accionCalcular(): void
{
    libUsuario();
    libPost();
    $in = libEntrada();
    $db = libDb();

    [$producto, $tienda, $margen] = plIds($in);

    $refs  = plReferencias($db, $producto, $tienda, $margen);
    $costo = plNumero($in['costo'] ?? null, 'El costo');
    $calc  = plCalcular($costo, (float)$refs['margen']['porcentaje'], $refs['iva']);
    $activo = plPrecioActivo($db, $producto, $tienda);

    libJson(true, [
        'id_producto'       => $producto,
        'id_tienda'         => $tienda,
        'id_margen'         => $margen,
        'costo'             => $costo,
        'margen_nombre'     => $refs['margen']['nombre'],
        'margen_porcentaje' => (float)$refs['margen']['porcentaje'],
        'porcentaje_iva'    => $refs['iva'],
        'precio_actual'     => $activo ? (float)$activo['precio_venta'] : null,
    ] + $calc, 'Precio calculado; todavía no se ha aplicado.');
}

function accionAplicar(): void
{
    libAdmin();
    libPost();
    $in = libEntrada();
    $db = libDb();

    [$producto, $tienda, $margen] = plIds($in);

    $refs  = plReferencias($db, $producto, $tienda, $margen);
    $costo = plNumero($in['costo'] ?? null, 'El costo');

    $datos = plTransaccion($db, function () use ($db, $refs, $tienda, $costo) {
        return plAplicar($db, $refs, $tienda, $costo, libUsuarioId());
    });

    libJson(true, $datos, 'Precio aplicado. El anterior quedó en el historial.');
}

/** Simula uno o varios márgenes. No toca precio_tienda. */
function accionSimular(): void
{
    libUsuario();
    libPost();
    $in = libEntrada();
    $db = libDb();

    [$producto, $tienda] = plIds($in, false);

    $ids = $in['id_margenes'] ?? (isset($in['id_margen']) ? [$in['id_margen']] : []);
    $ids = is_array($ids) ? $ids : [$ids];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($x) => $x > 0)));

    if (!$ids) {
        throw new PrecioException('Seleccione al menos un margen para simular.');
    }

    if (count($ids) > 12) {
        throw new PrecioException('Puede simular como máximo 12 márgenes a la vez.');
    }

    $activo = plPrecioActivo($db, $producto, $tienda);
    $costoTexto = $in['costo'] ?? '';

    if ($costoTexto === '' || $costoTexto === null) {
        if (!$activo) {
            throw new PrecioException('Ingrese un costo: este producto aún no tiene precio activo en la tienda.');
        }

        $costo = (float)$activo['costo'];
    } else {
        $costo = plNumero($costoTexto, 'El costo');
    }

    $guardar = !array_key_exists('guardar', $in) || libBool($in['guardar']);
    $actual  = $activo ? (float)$activo['precio_venta'] : null;

    $escenarios = plTransaccion($db, function () use ($db, $ids, $producto, $tienda, $costo, $guardar, $actual) {
        $lista = [];

        foreach ($ids as $idMargen) {
            $refs = plReferencias($db, $producto, $tienda, $idMargen);
            $calc = plCalcular($costo, (float)$refs['margen']['porcentaje'], $refs['iva']);
            $idSimulacion = null;

            if ($guardar) {
                $st = $db->prepare('
                    INSERT INTO public.simulacion_precio
                        (id_producto, id_tienda, id_margen, costo, porcentaje_iva, costo_con_impuesto, precio_calculado, precio_venta, aplicado, id_usuario)
                    VALUES
                        (:p, :t, :m, :c, :iva, :s, :pc, :v, FALSE, :u)
                    RETURNING id_simulacion
                ');
                $st->execute([
                    ':p'   => $producto,
                    ':t'   => $tienda,
                    ':m'   => $idMargen,
                    ':c'   => $costo,
                    ':iva' => $refs['iva'],
                    ':s'   => $calc['costo_con_impuesto'],
                    ':pc'  => $calc['precio_calculado'],
                    ':v'   => $calc['precio_venta'],
                    ':u'   => libUsuarioId(),
                ]);
                $idSimulacion = (int)$st->fetchColumn();
            }

            $lista[] = [
                'id_simulacion'     => $idSimulacion,
                'id_margen'         => $idMargen,
                'margen_nombre'     => $refs['margen']['nombre'],
                'margen_porcentaje' => (float)$refs['margen']['porcentaje'],
                'porcentaje_iva'    => $refs['iva'],
                'ganancia_unitaria' => round($calc['precio_venta'] - $calc['costo_con_impuesto'], 2),
                'diferencia_actual' => $actual !== null ? round($calc['precio_venta'] - $actual, 2) : null,
            ] + $calc;
        }

        return $lista;
    });

    libJson(true, [
        'costo'         => $costo,
        'precio_actual' => $actual,
        'escenarios'    => $escenarios,
    ], 'Simulación terminada; el precio activo no se modificó.');
}

/** Aplica una simulación guardada (recalcula con el IVA actual del producto). */
function accionAplicarSimulacion(): void
{
    libAdmin();
    libPost();
    $in = libEntrada();
    $db = libDb();

    $id = (int)($in['id_simulacion'] ?? 0);

    if ($id <= 0) {
        throw new PrecioException('Simulación inválida.');
    }

    $datos = plTransaccion($db, function () use ($db, $id) {
        $st = $db->prepare('SELECT * FROM public.simulacion_precio WHERE id_simulacion = :id FOR UPDATE');
        $st->execute([':id' => $id]);
        $sim = $st->fetch(PDO::FETCH_ASSOC);

        if (!$sim) {
            throw new PrecioException('Simulación no encontrada.', 404);
        }

        if (libBool($sim['aplicado'])) {
            throw new PrecioException('Esta simulación ya se aplicó.', 409);
        }

        $refs = plReferencias($db, (int)$sim['id_producto'], (int)$sim['id_tienda'], (int)$sim['id_margen']);
        $resultado = plAplicar($db, $refs, (int)$sim['id_tienda'], (float)$sim['costo'], libUsuarioId());

        $st = $db->prepare('UPDATE public.simulacion_precio SET aplicado = TRUE WHERE id_simulacion = :id');
        $st->execute([':id' => $id]);

        return $resultado;
    });

    libJson(true, $datos, 'Simulación aplicada como precio activo. El anterior quedó en el historial.');
}

/**
 * Recalcula con otro margen todos los precios activos de una tienda
 * (opcionalmente solo una categoría), conservando el costo de cada uno.
 * Con vista_previa = true solo muestra el resultado.
 */
function accionMargenMasivo(): void
{
    libAdmin();
    libPost();
    $in = libEntrada();
    $db = libDb();

    $tienda    = (int)($in['id_tienda'] ?? 0);
    $margen    = (int)($in['id_margen'] ?? 0);
    $categoria = (int)($in['id_categoria'] ?? 0);
    $vista     = libBool($in['vista_previa'] ?? false);

    if ($tienda <= 0 || $margen <= 0) {
        throw new PrecioException('Tienda y margen son obligatorios.');
    }

    $sql = '
        SELECT pt.id_producto, pt.costo, pt.precio_venta, p.nombre
        FROM public.precio_tienda pt
        JOIN public.producto p ON p.id_producto = pt.id_producto
        WHERE pt.id_tienda = :t AND pt.estado = TRUE AND p.estado = TRUE
    ';
    $params = [':t' => $tienda];

    if ($categoria > 0) {
        $sql .= ' AND p.id_categoria = :c';
        $params[':c'] = $categoria;
    }

    $st = $db->prepare($sql . ' ORDER BY LOWER(p.nombre)');
    $st->execute($params);
    $filas = $st->fetchAll(PDO::FETCH_ASSOC);

    if (!$filas) {
        throw new PrecioException('No hay precios activos que coincidan con la tienda y la categoría elegidas.', 404);
    }

    $items = [];

    foreach ($filas as $f) {
        $refs  = plReferencias($db, (int)$f['id_producto'], $tienda, $margen);
        $costo = (float)$f['costo'];
        $calc  = plCalcular($costo, (float)$refs['margen']['porcentaje'], $refs['iva']);

        $items[] = [
            'id_producto'   => (int)$f['id_producto'],
            'nombre'        => $f['nombre'],
            'costo'         => $costo,
            'precio_actual' => (float)$f['precio_venta'],
            'precio_nuevo'  => $calc['precio_venta'],
        ];
    }

    if ($vista) {
        libJson(true, ['total' => count($items), 'items' => $items], 'Vista previa: no se cambió ningún precio.');
    }

    plTransaccion($db, function () use ($db, $items, $tienda, $margen) {
        foreach ($items as $item) {
            $refs = plReferencias($db, $item['id_producto'], $tienda, $margen);
            plAplicar($db, $refs, $tienda, $item['costo'], libUsuarioId());
        }
    });

    libJson(true, ['total' => count($items), 'items' => $items], count($items) . ' precio(s) actualizado(s). Los anteriores quedaron en el historial.');
}


/* ==========================================================
   ENRUTADOR
========================================================== */

$accion = (string)($_GET['accion'] ?? '');

$acciones = [
    'catalogos'           => 'accionCatalogos',
    'contexto'            => 'accionContexto',
    'listar'              => 'accionListar',
    'historial'           => 'accionHistorial',
    'simulaciones'        => 'accionSimulaciones',

    'listar_margenes'     => 'accionListarMargenes',
    'guardar_margen'      => 'accionGuardarMargen',
    'estado_margen'       => 'accionEstadoMargen',
    'configurar_iva'      => 'accionConfigurarIva',

    'calcular'            => 'accionCalcular',
    'aplicar'             => 'accionAplicar',
    'simular'             => 'accionSimular',
    'aplicar_simulacion'  => 'accionAplicarSimulacion',
    'margen_masivo'       => 'accionMargenMasivo',
];

try {
    if (!isset($acciones[$accion])) {
        libJson(false, null, 'Acción no reconocida.', 404);
    }

    $acciones[$accion]();
} catch (PrecioException $e) {
    libJson(false, null, $e->getMessage(), $e->estadoHttp());
} catch (PDOException $e) {
    error_log('[precios] ' . $e->getMessage());

    $codigo = (string)$e->getCode();

    if ($codigo === '23505') {
        libJson(false, null, 'Ya existe un registro con esos datos (por ejemplo, otro precio activo para el mismo producto y tienda). Intente de nuevo.', 409);
    }

    if ($codigo === '23503') {
        libJson(false, null, 'La referencia a producto, tienda, impuesto o margen no existe.', 409);
    }

    if ($codigo === '22003') {
        libJson(false, null, 'Un valor numérico está fuera del rango permitido.', 400);
    }

    if ($codigo === '42P01') {
        libJson(false, null, 'Faltan tablas en la base de datos. Ejecute basededatos/NuevasHU.sql en Supabase.', 500);
    }

    libJson(false, null, 'Error de base de datos al gestionar precios. Revise el registro de errores de PHP.', 500);
} catch (Throwable $e) {
    error_log('[precios] ' . $e->getMessage());
    libJson(false, null, 'Ocurrió un error interno. Intente nuevamente.', 500);
}
