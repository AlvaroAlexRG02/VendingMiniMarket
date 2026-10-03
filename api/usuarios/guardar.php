<?php

require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/session.php";

header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| Validar sesión
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["usuario"])) {

    http_response_code(401);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Sesión no válida."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validar método
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Método no permitido."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Obtener datos
|--------------------------------------------------------------------------
*/

$datos = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($datos)) {

    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Datos inválidos."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Datos recibidos
|--------------------------------------------------------------------------
*/

$idUsuario = $datos["id_usuario"] ?? null;

$nombre = trim($datos["nombre"] ?? "");

$apellido = trim($datos["apellido"] ?? "");

$correo = trim($datos["correo"] ?? "");

$contrasena = $datos["contrasena"] ?? "";

$idRol = $datos["id_rol"] ?? null;

$idTienda = $datos["id_tienda"] ?? null;

$estado = $datos["estado"] ?? true;


/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
*/

if (
    $nombre === "" ||
    $apellido === "" ||
    $correo === "" ||
    !$idRol ||
    !$idTienda
) {

    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "Debe completar todos los campos obligatorios."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validar correo
|--------------------------------------------------------------------------
*/

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "El correo electrónico no es válido."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validar contraseña
|--------------------------------------------------------------------------
*/

if ($contrasena !== "" && strlen($contrasena) < 8) {

    http_response_code(400);

    echo json_encode([
        "exito" => false,
        "mensaje" => "La contraseña debe tener al menos 8 caracteres."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Procesamiento
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Validar rol
    |--------------------------------------------------------------------------
    */

    $consultaRol = $pdo->prepare("
        SELECT
            id_rol,
            nombre
        FROM rol
        WHERE id_rol = :id_rol
          AND estado = TRUE
        LIMIT 1
    ");

    $consultaRol->execute([
        ":id_rol" => $idRol
    ]);

    $rol = $consultaRol->fetch();

    if (!$rol) {

        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            "exito" => false,
            "mensaje" => "El rol seleccionado no es válido."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validar tienda
    |--------------------------------------------------------------------------
    */

    $consultaTienda = $pdo->prepare("
        SELECT
            id_tienda,
            nombre
        FROM tienda
        WHERE id_tienda = :id_tienda
          AND estado = TRUE
        LIMIT 1
    ");

    $consultaTienda->execute([
        ":id_tienda" => $idTienda
    ]);

    $tienda = $consultaTienda->fetch();

    if (!$tienda) {

        $pdo->rollBack();

        http_response_code(400);

        echo json_encode([
            "exito" => false,
            "mensaje" => "La tienda seleccionada no es válida."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR USUARIO
    |--------------------------------------------------------------------------
    */

    if (!$idUsuario) {

        /*
        |--------------------------------------------------------------------------
        | Verificar correo duplicado
        |--------------------------------------------------------------------------
        */

        $consultaCorreo = $pdo->prepare("
            SELECT
                id_usuario
            FROM usuario
            WHERE LOWER(correo) = LOWER(:correo)
            LIMIT 1
        ");

        $consultaCorreo->execute([
            ":correo" => $correo
        ]);

        if ($consultaCorreo->fetch()) {

            $pdo->rollBack();

            http_response_code(409);

            echo json_encode([
                "exito" => false,
                "mensaje" => "Ya existe un usuario con ese correo."
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Validar contraseña para creación
        |--------------------------------------------------------------------------
        */

        if ($contrasena === "") {

            $pdo->rollBack();

            http_response_code(400);

            echo json_encode([
                "exito" => false,
                "mensaje" => "Debe ingresar una contraseña."
            ]);

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Generar hash de contraseña
        |--------------------------------------------------------------------------
        */

        $passwordHash = password_hash(
            $contrasena,
            PASSWORD_DEFAULT
        );


        /*
        |--------------------------------------------------------------------------
        | Insertar usuario
        |--------------------------------------------------------------------------
        */

        $insertar = $pdo->prepare("
            INSERT INTO usuario (
                id_rol,
                nombre,
                apellido,
                correo,
                password_hash,
                estado,
                intentos_fallidos,
                bloqueado_hasta
            )
            VALUES (
                :id_rol,
                :nombre,
                :apellido,
                :correo,
                :password_hash,
                :estado,
                0,
                NULL
            )
            RETURNING id_usuario
        ");

        $insertar->execute([
            ":id_rol" => $idRol,
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":correo" => $correo,
            ":password_hash" => $passwordHash,
            ":estado" => (bool)$estado
        ]);

        $idUsuarioNuevo = $insertar->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | Asignar tienda al usuario
        |--------------------------------------------------------------------------
        */

        $insertarTienda = $pdo->prepare("
            INSERT INTO usuario_tienda (
                id_usuario,
                id_tienda,
                estado
            )
            VALUES (
                :id_usuario,
                :id_tienda,
                TRUE
            )
        ");

        $insertarTienda->execute([
            ":id_usuario" => $idUsuarioNuevo,
            ":id_tienda" => $idTienda
        ]);


        /*
        |--------------------------------------------------------------------------
        | Auditoría - creación
        |--------------------------------------------------------------------------
        */

        $auditoria = $pdo->prepare("
            INSERT INTO bitacora_auditoria (
                id_usuario,
                entidad,
                accion,
                valor_anterior,
                valor_nuevo,
                resultado,
                ip_origen,
                fecha
            )
            VALUES (
                :id_usuario,
                'USUARIO',
                'CREACION',
                NULL,
                :valor_nuevo,
                'EXITOSO',
                :ip_origen,
                NOW()
            )
        ");

        $valorNuevo = json_encode([
            "id_usuario" => $idUsuarioNuevo,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "correo" => $correo,
            "id_rol" => $idRol,
            "id_tienda" => $idTienda,
            "estado" => (bool)$estado
        ]);

        $auditoria->execute([
            ":id_usuario" =>
                $_SESSION["usuario"]["id_usuario"],

            ":valor_nuevo" =>
                $valorNuevo,

            ":ip_origen" =>
                $_SERVER["REMOTE_ADDR"] ?? null
        ]);


        /*
        |--------------------------------------------------------------------------
        | Confirmar transacción
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        echo json_encode([
            "exito" => true,
            "mensaje" => "Usuario creado correctamente.",
            "id_usuario" => $idUsuarioNuevo
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | EDITAR USUARIO
    |--------------------------------------------------------------------------
    */

    $consultaUsuario = $pdo->prepare("
        SELECT
            id_usuario,
            nombre,
            apellido,
            correo,
            id_rol,
            estado
        FROM usuario
        WHERE id_usuario = :id_usuario
        FOR UPDATE
    ");

    $consultaUsuario->execute([
        ":id_usuario" => $idUsuario
    ]);

    $usuarioAnterior = $consultaUsuario->fetch();

    if (!$usuarioAnterior) {

        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            "exito" => false,
            "mensaje" => "No se encontró el usuario."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validar correo duplicado
    |--------------------------------------------------------------------------
    */

    $consultaCorreo = $pdo->prepare("
        SELECT
            id_usuario
        FROM usuario
        WHERE LOWER(correo) = LOWER(:correo)
          AND id_usuario <> :id_usuario
        LIMIT 1
    ");

    $consultaCorreo->execute([
        ":correo" => $correo,
        ":id_usuario" => $idUsuario
    ]);

    if ($consultaCorreo->fetch()) {

        $pdo->rollBack();

        http_response_code(409);

        echo json_encode([
            "exito" => false,
            "mensaje" => "Ya existe otro usuario con ese correo."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar usuario sin cambiar contraseña
    |--------------------------------------------------------------------------
    */

    if ($contrasena === "") {

        $actualizar = $pdo->prepare("
            UPDATE usuario
            SET
                id_rol = :id_rol,
                nombre = :nombre,
                apellido = :apellido,
                correo = :correo,
                estado = :estado
            WHERE id_usuario = :id_usuario
        ");

        $actualizar->execute([
            ":id_rol" => $idRol,
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":correo" => $correo,
            ":estado" => (bool)$estado,
            ":id_usuario" => $idUsuario
        ]);

    } else {

        /*
        |--------------------------------------------------------------------------
        | Actualizar usuario cambiando contraseña
        |--------------------------------------------------------------------------
        */

        $passwordHash = password_hash(
            $contrasena,
            PASSWORD_DEFAULT
        );

        $actualizar = $pdo->prepare("
            UPDATE usuario
            SET
                id_rol = :id_rol,
                nombre = :nombre,
                apellido = :apellido,
                correo = :correo,
                password_hash = :password_hash,
                estado = :estado
            WHERE id_usuario = :id_usuario
        ");

        $actualizar->execute([
            ":id_rol" => $idRol,
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":correo" => $correo,
            ":password_hash" => $passwordHash,
            ":estado" => (bool)$estado,
            ":id_usuario" => $idUsuario
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar tienda del usuario
    |--------------------------------------------------------------------------
    */

    $consultaRelacionTienda = $pdo->prepare("
        SELECT
            id_usuario_tienda
        FROM usuario_tienda
        WHERE id_usuario = :id_usuario
        LIMIT 1
    ");

    $consultaRelacionTienda->execute([
        ":id_usuario" => $idUsuario
    ]);

    $relacionTienda = $consultaRelacionTienda->fetch();


    if ($relacionTienda) {

        /*
        |--------------------------------------------------------------------------
        | Actualizar relación existente
        |--------------------------------------------------------------------------
        */

        $actualizarTienda = $pdo->prepare("
            UPDATE usuario_tienda
            SET
                id_tienda = :id_tienda,
                estado = TRUE
            WHERE id_usuario_tienda = :id_usuario_tienda
        ");

        $actualizarTienda->execute([
            ":id_tienda" => (int)$idTienda,
            ":id_usuario_tienda" =>
                $relacionTienda["id_usuario_tienda"]
        ]);

    } else {

        /*
        |--------------------------------------------------------------------------
        | Crear relación si no existe
        |--------------------------------------------------------------------------
        */

        $insertarTienda = $pdo->prepare("
            INSERT INTO usuario_tienda (
                id_usuario,
                id_tienda,
                estado
            )
            VALUES (
                :id_usuario,
                :id_tienda,
                TRUE
            )
        ");

        $insertarTienda->execute([
            ":id_usuario" => $idUsuario,
            ":id_tienda" => $idTienda
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Auditoría - edición
    |--------------------------------------------------------------------------
    */

    $auditoria = $pdo->prepare("
        INSERT INTO bitacora_auditoria (
            id_usuario,
            entidad,
            accion,
            valor_anterior,
            valor_nuevo,
            resultado,
            ip_origen,
            fecha
        )
        VALUES (
            :id_usuario,
            'USUARIO',
            'EDICION',
            :valor_anterior,
            :valor_nuevo,
            'EXITOSO',
            :ip_origen,
            NOW()
        )
    ");


    $valorAnterior = json_encode([
        "id_usuario" =>
            $usuarioAnterior["id_usuario"],

        "nombre" =>
            $usuarioAnterior["nombre"],

        "apellido" =>
            $usuarioAnterior["apellido"],

        "correo" =>
            $usuarioAnterior["correo"],

        "id_rol" =>
            $usuarioAnterior["id_rol"],

        "estado" =>
            $usuarioAnterior["estado"]
    ]);


    $valorNuevo = json_encode([
        "id_usuario" => $idUsuario,
        "nombre" => $nombre,
        "apellido" => $apellido,
        "correo" => $correo,
        "id_rol" => $idRol,
        "id_tienda" => $idTienda,
        "estado" => (bool)$estado
    ]);


    $auditoria->execute([
        ":id_usuario" =>
            $_SESSION["usuario"]["id_usuario"],

        ":valor_anterior" =>
            $valorAnterior,

        ":valor_nuevo" =>
            $valorNuevo,

        ":ip_origen" =>
            $_SERVER["REMOTE_ADDR"] ?? null
    ]);


    /*
    |--------------------------------------------------------------------------
    | Confirmar transacción
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    echo json_encode([
        "exito" => true,
        "mensaje" => "Usuario actualizado correctamente.",
        "id_usuario" => $idUsuario
    ]);


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Revertir transacción si existe
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | Registrar error
    |--------------------------------------------------------------------------
    */

    error_log(
        "Error al guardar usuario: " .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "exito" => false,
        "mensaje" => "No fue posible guardar el usuario."
    ]);
}