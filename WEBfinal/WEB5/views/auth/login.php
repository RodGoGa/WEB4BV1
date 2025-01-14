<?php
// Definir la ruta raíz del proyecto de manera dinámica
$rootPath = realpath(dirname(__FILE__) . '/../../');

// Incluir archivos de configuración y controladores de manera segura
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/controllers/AuthController.php';

// Verificar la conexión a la base de datos
if (!isset($conn) || $conn === null) {
    try {
        // Crear conexión si no existe
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Verificar errores de conexión
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }
    } catch (Exception $e) {
        // Manejo de errores de conexión
        error_log($e->getMessage());
        die("Lo sentimos, hay un problema con la conexión. Intente más tarde.");
    }
}

// Inicializar variables
$error = '';

// Procesar inicio de sesión
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanear y validar entrada
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $tipo_usuario = $_POST['tipo_usuario'] ?? '';

        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de correo electrónico inválido.");
        }

        // Validar tipo de usuario
        if (empty($tipo_usuario)) {
            throw new Exception("Debe seleccionar un tipo de usuario.");
        }

        // Crear instancia del controlador de autenticación
        $authController = new AuthController($conn);

        // Intentar inicio de sesión
        if ($authController->login($email, $password)) {
            // Obtener tipo de usuario de la sesión
            session_start();
            $tipo_usuario_sesion = $_SESSION['tipo_usuario'];

            // Validar que el tipo de usuario coincida
            if ($tipo_usuario != $tipo_usuario_sesion) {
                // Destruir sesión si no coincide
                session_destroy();
                throw new Exception("El tipo de usuario no coincide.");
            }

            // Redirigir según el tipo de usuario
            if ($tipo_usuario == 'solicitante') {
                header("Location: ../solicitantes/dashboard_solicitante.php");
            } elseif ($tipo_usuario == 'empresa') {
                header("Location: ../empresas/dashboard_empresa.php");
            }
            exit();
        } else {
            $error = "Correo electrónico o contraseña incorrectos.";
        }
    } catch (Exception $e) {
        // Capturar y mostrar errores
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">Bolsa de Trabajo</div>
            <div class="nav-links">
                <a href="../../index.php">Inicio</a>
                <a href="register_solicitante.php">Registro Solicitante</a>
                <a href="register_empresa.php">Registro Empresa</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h1>Iniciando sesion como...</h1>
            <form action="" method="post" novalidate>
                <div class="tipo-usuario-container">
                    <input
                        type="radio"
                        id="solicitante"
                        name="tipo_usuario"
                        value="solicitante"
                        required
                    >
                    <label for="solicitante">Solicitante</label>

                    <input
                        type="radio"
                        id="empresa"
                        name="tipo_usuario"
                        value="empresa"
                        required
                    >
                    <label for="empresa">Empresa</label>
                </div>

                <label for="email">Correo electrónico:</label>
                <div class="input-container">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="ejemplo@correo.com"
                        required
                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                    >
                </div>
                <label for="password">Contraseña:</label>
                <div class="input-container">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        required
                        minlength="6"
                    >
                </div>
                <div class="form-buttons-container">
                    <button class="button-y" type="submit">Iniciar Sesión</button>
                </div>
            </form>
            <?php
            // Mostrar errores de manera segura
            if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados.</p>
    </footer>

    <script>
    // Validación adicional de selección de tipo de usuario
    document.querySelector('form').addEventListener('submit', function(e) {
        const tipoUsuarioSeleccionado = document.querySelector('input[name="tipo_usuario"]:checked');
        if (!tipoUsuarioSeleccionado) {
            e.preventDefault();
            alert('Por favor, seleccione un tipo de usuario');
        }
    });
    </script>
</body>
</html>