<?php
// Incluir la configuración de la base de datos y el controlador
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

// Crear una instancia del controlador
$authController = new AuthController($conn);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_empresa = $_POST['nombre_empresa'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Llamar al método de registro de empresa del controlador
    if ($authController->registerEmpresa($nombre_empresa, $email, $password)) {
        header("Location: login.php"); // Redirigir a la página de inicio de sesión
        exit();
    } else {
        $error = "Error al registrar la empresa.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Empresa</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">Bolsa de Trabajo</div>
            <div class="nav-links">
                <a href="../../index.php">Inicio</a>
                <a href="login.php">Iniciar Sesión</a>
                <a href="register_solicitante.php">Registro Solicitante</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h1>Registro de <span class="text-enphasis">Empresa</span></h1>
            <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
            <form action="" method="post">
                <label for="nombre_empresa">Nombre de la Empresa:</label>
                <div class="input-container">
                    <input type="text" id="nombre_empresa" name="nombre_empresa" required placeholder="Ingresa el nombre de la empresa">
                </div>
                <label for="email">Correo Electrónico:</label>
                <div class="input-container">
                    <input type="email" id="email" name="email" required placeholder="ejemplo@example.com">
                </div>
                <label for="password">Contraseña:</label>
                <div class="input-container">
                    <input type="password" id="password" name="password" required placeholder="Crea una contraseña">
                </div>
                <div class="form-buttons-container">
                    <button class="button-y" type="submit">Registrar</button>
                </div>
            </form>
            <p>¿Ya tienes una cuenta? <a href="login.php" class="text-enphasis">Inicia sesión aquí</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados.</p>
    </footer>
</body>
</html>