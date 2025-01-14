<?php
// Configuración y verificación de sesión
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/EmpresaController.php';
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

// Manejar edición de vacante
$error = '';
$success = '';
$vacanteId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$vacanteModel = new Vacante($conn);
$vacante = $vacanteModel->obtenerVacantePorId($vacanteId);

// Verificar que la vacante pertenezca a la empresa
if (!$vacante || $vacante['empresa_id'] != $empresaId) {
    die("Vacante no encontrada o no pertenece a la empresa");
}

// Obtener habilidades de la vacante
$vacante['habilidades'] = $vacanteModel->obtenerHabilidadesVacante($vacanteId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $requisitos = $_POST['requisitos'] ?? '';
    $habilidades = [];

    // Procesar habilidades
    if (isset($_POST['habilidades']) && is_array($_POST['habilidades'])) {
        foreach ($_POST['habilidades'] as $index => $habilidad) {
            $habilidadId = is_numeric($habilidad) ? $habilidad : null;
            $nivel = $_POST['niveles'][$index] ?? 'basico';

            if ($habilidadId === null) {
                // Insertar nueva habilidad
                $sqlHabilidad = "INSERT INTO habilidades (nombre, tipo) VALUES (?, 'personalizada')";
                $stmtHabilidad = $conn->prepare($sqlHabilidad);
                $stmtHabilidad->bind_param("s", $habilidad);
                $stmtHabilidad->execute();
                $habilidadId = $conn->insert_id;
            }

            $habilidades[] = [
                'habilidad_id' => $habilidadId,
                'nivel_requerido' => $nivel
            ];
        }
    }

    // Actualizar vacante
    $resultado = $vacanteModel->actualizarVacante($vacanteId, [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'requisitos' => $requisitos,
        'estado' => $vacante['estado'] // Mantener el estado actual
    ], $habilidades);

    if ($resultado) {
        $success = "Vacante actualizada exitosamente";

        // Recargar la vacante actualizada
        $vacante = $vacanteModel->obtenerVacantePorId($vacanteId);
        $vacante['habilidades'] = $vacanteModel->obtenerHabilidadesVacante($vacanteId);
    } else {
        $error = "Error al actualizar la vacante";
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
    <title>Editar Vacante</title>
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
        <div class="form-container" id="editar-vacante">
            <h1>Editar Vacante</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars ($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="titulo">Título de la Vacante</label>
                <div class="input-container">
                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        value="<?php echo htmlspecialchars($vacante['titulo']); ?>"
                        required
                    >
                </div>

                <label for="descripcion">Descripción</label>
                <div class="input-container">
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        required
                    ><?php echo htmlspecialchars($vacante['descripcion']); ?></textarea>
                </div>

                <label for="requisitos">Requisitos</label>
                <div class="input-container">
                    <textarea
                        id="requisitos"
                        name="requisitos"
                    ><?php echo htmlspecialchars($vacante['requisitos']); ?></textarea>
                </div>

                <label>Habilidades Requeridas</label>
                <div id="habilidades-container" class="add-habilidad-container">
                    <div id="lista-habilidades" class="skills-container">
                        <?php foreach ($vacante['habilidades'] as $habilidad): ?>
                            <div class="skill-item">
                                <label><?php echo htmlspecialchars($habilidad['nombre']); ?> <span class="text-enphasis-y">(<?php echo htmlspecialchars($habilidad['nivel_requerido']); ?>)</span></label>
                                <input type="hidden" name="habilidades[]" value="<?php echo $habilidad['id']; ?>">
                                <input type="hidden" name="niveles[]" value="<?php echo $habilidad['nivel_requerido']; ?>">
                                <button type="button" class="remove-btn">X</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="add-habilidad-container">
                        <select id="habilidad-existente" class="select">
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

                        <div class="input-container">
                            <input
                                type="text"
                                id="habilidad-nueva"
                                placeholder="O agregar nueva habilidad"
                            >
                        </div>

                        <button type="button" class="button" id="agregar-habilidad">Agregar Habilidad</button>
                    </div>
                </div>

                <div class="form-buttons-container">
                    <button type="submit" class="button-y">Actualizar Vacante</button>
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
            let habilidadNombre = habilidadExistente.value
                ? habilidadExistente.options[habilidadExistente.selectedIndex].text
                : habilidadNueva.value.trim();
            let nivel = nivelHabilidad.value;

            if (!habilidadNombre) return;

            const habilidadDiv = document.createElement('div');
            habilidadDiv.className = 'skill-item';
            habilidadDiv.innerHTML = `
                <label>${habilidadNombre} <span class="text-enphasis-y">(${nivel})</span></label>
                <input type="hidden" name="habilidades[]" value="${habilidadId || habilidadNueva.value.trim()}">
                <input type="hidden" name="niveles[]" value="${nivel}">
                <button type="button" class="remove-btn">X</button>
            `;
            listaHabilidades.appendChild(habilidadDiv);

            habilidadDiv.querySelector('.remove-btn').addEventListener('click', function() {
                listaHabilidades.removeChild(habilidadDiv);
            });

            habilidadNueva.value = '';
            habilidadExistente.selectedIndex = 0;
        });
    </script>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados</p>
    </footer>
</body>
</html>