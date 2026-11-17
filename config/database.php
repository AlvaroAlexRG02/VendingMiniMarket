<?php

$host = getenv('DB_HOST');

if (!$host) {
    // Conexión local que ya funciona.
    require __DIR__ . '/database.local.php';
} else {
    // Conexión de DigitalOcean a Supabase.
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'postgres';
    $user = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');

    if (!$user || !$password) {
        http_response_code(500);
        die('Falta configurar la conexión a la base de datos.');
    }

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
        error_log($e->getMessage());
        http_response_code(500);
        die('Error de conexión a Supabase.');
    }
}