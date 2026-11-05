<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function crearCorreoVending(): PHPMailer
{
    $config = require __DIR__ . '/correo.php';
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPSecure = $config['encryption'];
    $mail->Port = $config['port'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], $config['from_name']);

    return $mail;
}

function enviarEnlaceRecuperacion(string $correo, string $enlace): void
{
    $mail = crearCorreoVending();
    $mail->addAddress($correo);
    $mail->isHTML(true);
    $mail->Subject = 'Validación de identidad - Vending Mini Market';
    $mail->Body = "
        <div style='font-family: Arial, sans-serif;'>
            <h2>Vending Mini Market</h2>
            <p>Para confirmar su identidad y recibir una contraseña temporal, use este enlace:</p>
            <p><a href='" . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8') . "'>Validar identidad</a></p>
            <p>El enlace vence en 15 minutos. Si usted no inició este proceso, ignore este correo.</p>
        </div>
    ";
    $mail->AltBody = 'Para confirmar su identidad y recibir una contraseña temporal, abra este enlace: ' . $enlace
        . '. El enlace vence en 15 minutos.';
    $mail->send();
}

function enviarContrasenaTemporal(string $correo, string $nombre, string $contrasena): void
{
    $mail = crearCorreoVending();
    $mail->addAddress($correo, $nombre);
    $mail->isHTML(true);
    $mail->Subject = 'Contraseña temporal - Vending Mini Market';
    $mail->Body = "
        <div style='font-family: Arial, sans-serif;'>
            <h2>Vending Mini Market</h2>
            <p>Su identidad fue validada. Use esta contraseña temporal para iniciar sesión:</p>
            <p style='font-size: 20px; font-weight: bold;'>" .
                htmlspecialchars($contrasena, ENT_QUOTES, 'UTF-8') .
            "</p>
            <p>Por seguridad, el sistema le pedirá crear una contraseña nueva al ingresar.</p>
            <p>Si usted no solicitó este cambio, comuníquese con el Administrador.</p>
        </div>
    ";
    $mail->AltBody = 'Su contraseña temporal es: ' . $contrasena
        . '. Inicie sesión y cree una contraseña nueva. Si no solicitó este cambio, comuníquese con el Administrador.';
    $mail->send();
}
