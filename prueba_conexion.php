<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP está funcionando.<br>";

require_once 'config/database.php';

echo "Conexión exitosa con Supabase.";