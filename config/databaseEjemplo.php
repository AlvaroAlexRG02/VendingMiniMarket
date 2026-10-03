<?php

$host = "aws-0-us-east-1.pooler.supabase.com";
$port = "5432";
$dbname = "postgres";
$user = "postgres.bsdgxhkqjdxcydrqztwu";
$password = "CONTRASENIAAAAA";

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
//tieen que crear en config un archivo "database.php" con este mismo contenido pero con la contraseña 