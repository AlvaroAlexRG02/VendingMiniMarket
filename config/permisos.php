<?php
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/ubicaciones.php";

/*
|--------------------------------------------------------------------------
| Permisos por rol
|--------------------------------------------------------------------------
|
| Roles:
| 1 = Administrador
| 2 = Gerente General
| 3 = Encargado
| 4 = Dependiente
|
*/


/*
|--------------------------------------------------------------------------
| Verificar si el usuario tiene uno de los roles indicados
|--------------------------------------------------------------------------
*/

function usuarioTieneRol(array $rolesPermitidos): bool
{
    if (!isset($_SESSION["usuario"]["id_rol"])) {
        return false;
    }

    $idRolUsuario = (int) $_SESSION["usuario"]["id_rol"];

    return in_array($idRolUsuario, $rolesPermitidos, true);
}


/*
|--------------------------------------------------------------------------
| Verificar si el usuario es Administrador
|--------------------------------------------------------------------------
*/

function esAdministrador(): bool
{
    return usuarioTieneRol([1]);
}


/*
|--------------------------------------------------------------------------
| Verificar si el usuario es Gerente General
|--------------------------------------------------------------------------
*/

function esGerenteGeneral(): bool
{
    return usuarioTieneRol([2]);
}


/*
|--------------------------------------------------------------------------
| Verificar si el usuario es Encargado
|--------------------------------------------------------------------------
*/

function esEncargado(): bool
{
    return usuarioTieneRol([3]);
}


/*
|--------------------------------------------------------------------------
| Verificar si el usuario es Dependiente
|--------------------------------------------------------------------------
*/

function esDependiente(): bool
{
    return usuarioTieneRol([4]);
}


/*
|--------------------------------------------------------------------------
| Verificar si es Administrador o Gerente General
|--------------------------------------------------------------------------
*/

function esAdministradorOGerente(): bool
{
    return usuarioTieneRol([1, 2]);
}

/*
|--------------------------------------------------------------------------
| Verificar acceso a una tienda
|--------------------------------------------------------------------------
*/

function usuarioTieneAccesoTienda(int $idTienda): bool
{
    global $pdo;
    if (!isset($_SESSION["usuario"]["id_usuario"])) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Administrador y Gerente General
    |--------------------------------------------------------------------------
    |
    | Estos roles tienen acceso general a las tiendas.
    |
    */

    if (esAdministradorOGerente()) {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Consultar tiendas asignadas al usuario
    |--------------------------------------------------------------------------
    */

   

    $consulta = $pdo->prepare("
        SELECT 1
        FROM usuario_tienda
        WHERE id_usuario = :id_usuario
          AND id_tienda = :id_tienda
          AND estado = TRUE
        LIMIT 1
    ");

    $consulta->execute([
        ":id_usuario" =>
            $_SESSION["usuario"]["id_usuario"],

        ":id_tienda" =>
            $idTienda
    ]);

    return (bool) $consulta->fetchColumn();
}