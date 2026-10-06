<?php

$host = "TU_HOST_SUPABASE";
$port = "5432";
$dbname = "postgres";
$user = "TU_USUARIO_SUPABASE";
$password = "TU_PASSWORD_SUPABASE";

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    die("Error de conexión a Supabase.");

}

/*
 * Cree config/database.php localmente con estos mismos datos,
 * completando las credenciales reales de su entorno.
 */