<?php
// Obtener la ruta raíz del proyecto de manera dinámica
$rootPath = realpath(dirname(__FILE__) . '/../../');

// Inclusión de archivos con rutas absolutas
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/controllers/AuthController.php';
require_once $rootPath . '/controllers/HabilidadController.php';

// Crear instancias de controladores
$authController = new AuthController($conn);
$habilidadController = new HabilidadController($conn);

// Variables de control
$error = '';
$nombre = '';
$email = '';

// Obtener habilidades predefinidas
$habilidadesPredefinidas = $habilidadController->obtenerHabilidadesPredefinidas();

// Procesar formulario de registro
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitizar y validar inputs
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $habilidades = $_POST['habilidades'] ?? [];

    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre es obligatorio.";
    } elseif (empty($email)) {
        $error = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (count($habilidades) < 5) {
        $error = "Debes seleccionar al menos 5 habilidades.";
    } else {
        // Intentar registrar solicitante
        if ($authController->registerSolicitante($nombre, $email, $password, $habilidades)) {
            // Redirigir a página de inicio de sesión
            header("Location: login.php");
            exit();
        } else {
            $error = "Error al registrar el usuario. Puede que el correo ya esté en uso.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Solicitante</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">Bolsa de Trabajo</div>
            <div class="nav-links">
                <a href="../../index.php">Inicio</a>
                <a href="login.php">Iniciar Sesión</a>
                <a href="register_empresa.php">Registro Empresa</a>
            </div>
        </nav>
    </header>

    <main>
        <div id="register-solicitante" class="form-container">
            <h1>Registro de <span class="text-enphasis">Solicitante</span></h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" onsubmit="return validarFormulario()">
                <label for="nombre">Nombre completo:</label>
                <div class="input-container">
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?php echo htmlspecialchars($nombre); ?>"
                        required
                        maxlength="100"
                        placeholder="Ingresa tu nombre"
                    >
                </div>

                <label for="email">Correo Electrónico:</label>
                <div class="input-container">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                        maxlength="100"
                        placeholder="ejemplo@example.com"
                    >
                </div>

                <label for="password">Contraseña:</label>
                <div class="input-container">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        placeholder="Crea una contraseña"
                    >
                </div>

                <label>Habilidades <span class="skills-message">(Al menos 5)</span></label>
                <div class="skills-container">
                    <p id="skills-message" class="skills-message">¡Anímate! Selecciona algunas habilidades predefinidas aquí. O selecciona <span class="text-enphasis-y">Agregar habilidad</span> y crea habilidades personalizadas.</p>
                    <?php foreach ($habilidadesPredefinidas as $habilidad): ?>
                        <div class="skill-item">
                            <label for="habilidad_<?php echo $habilidad['id']; ?>">
                                <?php echo htmlspecialchars($habilidad['nombre']); ?>
                            </label>
                            <input
                                type="checkbox"
                                id="habilidad_<?php echo $habilidad['id']; ?>"
                                name="habilidades[]"
                                value="<?php echo $habilidad['id']; ?>"
                            >
                        </div>  
                    <?php endforeach; ?>
                </div>

                <div class="skills-custom-container">
                    <div class="input-container">
                        <input
                            type="text"
                            id="nueva_habilidad"
                            name="nueva_habilidad"
                            maxlength="50"
                            placeholder="Ingresa una habilidad personalizada"
                        >
                    </div>
                    <button type="button" class="button" onclick="agregarHabilidadPersonalizada()">
                            Agregar Habilidad
                    </button>
                </div>

                <div id="habilidades-personalizadas" class="skills-container"></div>

                <div class="form-buttons-container">
                    <button type="submit" class="button-y">Registrar</button>
                </div>
            </form>

            <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados.</p>
    </footer>

    <script>
    function validarFormulario() {
        const checkboxes = document.querySelectorAll('input[name="habilidades[]"]:checked');

        if (checkboxes.length < 5) {
            alert('Debes seleccionar al menos 5 habilidades');
            return false;
        }

        return true;
    }

    function agregarHabilidadPersonalizada() {
        const input = document.getElementById('nueva_habilidad');
        const contenedor = document.getElementById('habilidades-personalizadas');
        const habilidad = input.value.trim();

        if (habilidad === '') {
            alert('Ingresa un nombre para la habilidad');
            return;
        }

        // Crear nuevo elemento
        const div = document.createElement('div');
        div.className = 'skill-item';
        div.innerHTML = `
            <label>${habilidad} (Personalizada)</label>
            <input
                type="checkbox"
                name="habilidades[]"
                value="nueva_${habilidad}"
                checked
            >
        `;

        // Agregar al contenedor
        contenedor.appendChild(div);

        // Limpiar input
        input.value = '';
    }
    </script>
</body>
</html>