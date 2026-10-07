<?php

require_once __DIR__ . "/../config/database.php";

$carpetaRespaldos = __DIR__ . "/../respaldo/archivos";

if (!is_dir($carpetaRespaldos)) {
    mkdir($carpetaRespaldos, 0777, true);
}

$nombreArchivo = "respaldo_" . date("Y-m-d_H-i-s") . ".sql";
$rutaArchivo = $carpetaRespaldos . "/" . $nombreArchivo;

$comando = sprintf(
    'docker run --rm -e PGPASSWORD=%s -e PGSSLMODE=require -v %s:/backup public.ecr.aws/supabase/postgres:17.6.1.166 pg_dump --host=%s --port=%s --username=%s --dbname=%s --format=plain --file=/backup/%s 2>&1',
    escapeshellarg($password),
    escapeshellarg($carpetaRespaldos),
    escapeshellarg($host),
    escapeshellarg($port),
    escapeshellarg($user),
    escapeshellarg($dbname),
    escapeshellarg($nombreArchivo)
);

$resultado = shell_exec($comando);

if (file_exists($rutaArchivo) && filesize($rutaArchivo) > 0) {

    return [
        "success" => true,
        "archivo" => $nombreArchivo,
        "ruta" => "respaldo/archivos/" . $nombreArchivo,
        "tamano" => filesize($rutaArchivo)
    ];

}

return [
    "success" => false,
    "archivo" => null,
    "ruta" => null,
    "tamano" => 0
];