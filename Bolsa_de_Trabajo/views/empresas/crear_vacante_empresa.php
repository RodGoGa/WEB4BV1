<?php
// Configuración y verificación de sesión
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../models/Empresa.php';
require_once '../../models/Vacante.php';

// Iniciar sesión y verificar
$authController = new AuthController($conn);
$authController->verificarSesion();

// Obtener ID de empresa
$usuarioId = $_SESSION['user_id'];
$empresaModel = new Empresa($conn);
$empresa = $empresaModel->obtenerEmpresaPorUsuarioId($usuarioId);

if (!$empresa) {
    die("Empresa no encontrada");
}
$empresaId = $empresa['id'];

// Manejar creación de vacante
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $requisitos = $_POST['requisitos'] ?? '';
    $habilidades = [];

    // Procesar habilidades
    if (isset($_POST['habilidades']) && is_array($_POST['habilidades'])) {
        foreach ($_POST['habilidades'] as $index => $habilidad) {
            // Verificar si es una habilidad nueva o existente
            $habilidadId = is_numeric($habilidad) ? $habilidad : null;
            $nivel = $_POST['niveles'][$index] ?? 'basico';

            if ($habilidadId === null) {
                // Verificar si la habilidad ya existe
                $sqlVerificar = "SELECT id FROM habilidades WHERE nombre = ?";
                $stmtVerificar = $conn->prepare($sqlVerificar);
                $stmtVerificar->bind_param("s", $habilidad);
                $stmtVerificar->execute();
                $resultVerificar = $stmtVerificar->get_result();

                if ($resultVerificar->num_rows > 0) {
                    // Si la habilidad ya existe, obtener su ID
                    $filaHabilidad = $resultVerificar->fetch_assoc();
                    $habilidadId = $filaHabilidad['id'];
                } else {
                    // Insertar nueva habilidad
                    $sqlHabilidad = "INSERT INTO habilidades (nombre, tipo) VALUES (?, 'personalizada')";
                    $stmtHabilidad = $conn->prepare($sqlHabilidad);
                    $stmtHabilidad->bind_param("s", $habilidad);
                    $stmtHabilidad->execute();
                    $habilidadId = $conn->insert_id;
                }
            }

            $habilidades[] = [
                'habilidad_id' => $habilidadId,
                'nivel_requerido' => $nivel
            ];
        }
    }

    // Crear vacante
    $vacanteModel = new Vacante($conn);
    $resultado = $vacanteModel->crearVacante($empresaId, $titulo, $descripcion, $requisitos, $habilidades);

    if ($resultado) {
        $success = "Vacante creada exitosamente";
        // Limpiar campos después de crear
        $titulo = $descripcion = $requisitos = '';
        $habilidades = [];
    } else {
        $error = "Error al crear la vacante";
    }
}

// Obtener habilidades existentes para el selector
$sqlHabilidades = "SELECT id, nombre FROM habilidades";
$resultHabilidades = $conn->query($sqlHabilidades);
$habilidadesExistentes = $resultHabilidades ? $resultHabilidades->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Vacante</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Bolsa de Trabajo</div>
        <div class="nav-links">
            <a href="dashboard_empresa.php">Volver al Dashboard</a>
        </div>
    </nav>

    <main>
        <div class="form-container">
            <h1>Crear Nueva Vacante</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="titulo">Título de la Vacante</label>
                <div class="input-container">
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        value="<?php echo htmlspecialchars($titulo ?? ''); ?>"
                        required
                        placeholder="Ingresa el título de la vacante"
                    >
                </div>

                <label for="descripcion">Descripción</label>
                <div class="input-container">
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        required
                    ><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
                </div>

                <label for="requisitos">Requisitos</label>
                <div class="input-container">
                    <textarea
                        id="requisitos"
                        name="requisitos"
                    ><?php echo htmlspecialchars($requisitos ?? ''); ?></textarea>
                </div>

                <div id="habilidades-container">
                    <label>Habilidades Requeridas</label>
                    

                    <div class="add-habilidad-container">
                        <div class="flex-container">
                            <select class="select" id="habilidad-existente">
                                <option value="">Seleccionar habilidad existente</option>
                                <?php foreach ($habilidadesExistentes as $habilidad): ?>
                                    <option value="<?php echo $habilidad['id']; ?>">
                                        <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select class="select" id="nivel-habilidad">
                                <option value="basico">Básico</option>
                                <option value="intermedio">Intermedio</option>
                                <option value="avanzado">Avanzado</option>
                            </select>
                        </div>

                        <div class="input-container">
                            <input
                            type="text"
                            id="habilidad-nueva"
                            placeholder="O agregar nueva habilidad"
                            >
                        </div>
                        <button type="button" class="button" id="agregar-habilidad">Agregar Habilidad</button>
                    </div>

                    <div id="lista-habilidades" class="skills-container">
                        <!-- Contenedor de habilidades -->
                    </div>
                </div>

                <div class="form-buttons-container">
                    <button type="submit" class="button-y">Crear Vacante</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('agregar-habilidad').addEventListener('click', function() {
            const habilidadExistente = document.getElementById('habilidad-existente');
            const habilidadNueva = document.getElementById('habilidad-nueva');
            const nivelHabilidad = document.getElementById('nivel-habilidad');
            const listaHabilidades = document.getElementById('lista-habilidades');

            let habilidadId = habilidadExistente.value;
            let habilidadNombre = habilidadExistente.options[habilidadExistente.selectedIndex].text;
            let nivel = nivelHabilidad.value;

            if (habilidadNueva.value) {
                habilidadNombre = habilidadNueva.value;
                habilidadId = null; // Nueva habilidad no tiene ID
            }

            if (habilidadNombre) {
                const div = document.createElement('div');
                div.classList.add('skill-item');
                div.innerHTML = `
                    <label>${habilidadNombre} <span class="text-enphasis-y">(${nivel})</span></label>
                    <input type="hidden" name="habilidades[]" value="${habilidadId || habilidadNombre}">
                    <input type="hidden" name="niveles[]" value="${nivel}">
                    <button type="button" class="remove-btn">X</button>
                `;
                listaHabilidades.appendChild(div);

                // Limpiar campos
                habilidadNueva.value = '';
                habilidadExistente.selectedIndex = 0;
                nivelHabilidad.selectedIndex = 0;

                // Agregar evento para eliminar habilidad
                div.querySelector('.remove-btn').addEventListener('click', function() {
                    listaHabilidades.removeChild(div);
                });
            }
        });
    </script>
</body>
</html>