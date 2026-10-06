<?php

require_once __DIR__ . "/../../config/session.php";
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/permisos.php";

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["usuario"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Sesión no válida."
    ]);

    exit;
}

if (!esAdministradorOGerente()) {
    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Acceso no autorizado."
    ]);

    exit;
}

try {

    /*
     * Usuarios que aparecen en la bitácora.
     */
    $consultaUsuarios = $pdo->query("
        SELECT DISTINCT
            b.id_usuario,
            CONCAT(
                u.nombre,
                ' ',
                COALESCE(u.apellido, '')
            ) AS usuario
        FROM bitacora_auditoria b
        INNER JOIN usuario u
            ON u.id_usuario = b.id_usuario
        WHERE b.id_usuario IS NOT NULL
        ORDER BY usuario ASC
    ");

    $usuarios = $consultaUsuarios->fetchAll();


    /*
     * Módulos/entidades registrados.
     */
    $consultaEntidades = $pdo->query("
        SELECT DISTINCT entidad
        FROM bitacora_auditoria
        WHERE entidad IS NOT NULL
          AND entidad <> ''
        ORDER BY entidad ASC
    ");

    $entidades = $consultaEntidades->fetchAll();


    /*
     * Acciones registradas.
     */
    $consultaAcciones = $pdo->query("
        SELECT DISTINCT accion
        FROM bitacora_auditoria
        WHERE accion IS NOT NULL
          AND accion <> ''
        ORDER BY accion ASC
    ");

    $acciones = $consultaAcciones->fetchAll();


    echo json_encode([
        "success" => true,
        "usuarios" => $usuarios,
        "entidades" => $entidades,
        "acciones" => $acciones
    ]);

} catch (PDOException $e) {

    error_log(
        "Error en api/bitacora/opciones.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "No fue posible cargar las opciones de los filtros."
    ]);
}