<?php

require_once __DIR__ . "/../config/database.php";

$carpetaRespaldos = __DIR__ . "/../respaldo/archivos";

if (!is_dir($carpetaRespaldos)) {
    if (!mkdir($carpetaRespaldos, 0777, true) && !is_dir($carpetaRespaldos)) {
        return [
            "success" => false,
            "archivo" => null,
            "ruta" => null,
            "tamano" => 0,
            "error" => "No fue posible crear la carpeta de respaldos."
        ];
    }
}

if (!is_writable($carpetaRespaldos)) {
    return [
        "success" => false,
        "archivo" => null,
        "ruta" => null,
        "tamano" => 0,
        "error" => "El servidor no tiene permiso para escribir en la carpeta de respaldos."
    ];
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

$salida = [];
$codigoSalida = 0;
exec($comando, $salida, $codigoSalida);

if ($codigoSalida === 0 && file_exists($rutaArchivo) && filesize($rutaArchivo) > 0) {

    return [
        "success" => true,
        "archivo" => $nombreArchivo,
        "ruta" => "respaldo/archivos/" . $nombreArchivo,
        "tamano" => filesize($rutaArchivo)
    ];

}

$detalleError = trim(implode(PHP_EOL, $salida));
if ($password !== "") {
    $detalleError = str_replace($password, "[oculto]", $detalleError);
}
if ($detalleError === "") {
    $detalleError = "Docker terminó con código {$codigoSalida} y no devolvió detalles.";
}

return [
    "success" => false,
    "archivo" => null,
    "ruta" => null,
    "tamano" => 0,
    "error" => substr($detalleError, 0, 1000)
];