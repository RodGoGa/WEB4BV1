<?php
// Mostrar todos los errores de PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "2001Form@77404";
$dbname = "WEB2";

// Intentar establecer la conexión con información detallada
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Detalles de conexión:" .
            "\nServidor: $servername" .
            "\nUsuario: $username" .
            "\nBase de datos: $dbname" .
            "\nError: " . $conn->connect_error
        );
    }

    // echo "Conexión establecida correctamente";
} catch (Exception $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}
?>