<?php
// Configuración y verificación de sesión
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SolicitanteController.php';
require_once '../../controllers/HabilidadController.php';

// Iniciar sesión y verificar
$authController = new AuthController($conn);
$authController->verificarSesion();

// Obtener ID de solicitante
$usuarioId = $_SESSION['user_id'];
$solicitanteController = new SolicitanteController($conn);
$solicitanteModel = new Solicitante($conn);

// Habilidad 
$habilidadesController = new HabilidadController($conn);
$habilidadesModel = new Habilidad($conn);

// Obtener solicitante
$solicitante = $solicitanteModel->obtenerSolicitantePorUsuarioId($usuarioId);
$solicitanteId = $solicitante['id'];

// Obtener habilidades existentes
$habilidadesExistentes = $habilidadesModel->obtenerTodasLasHabilidades();

// Obtener habilidades del solicitante
$habilidadesSolicitante = $solicitanteModel->obtenerHabilidadesSolicitante($solicitanteId);

// Manejo de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    // $habilidadesNuevas = $_POST['habilidades_nuevas'] ?? [];
    $nivelesExistentes = $_POST['niveles_existentes'] ?? [];
    // $habilidadesRemovidas = $_POST['habilidades_removidas'] ?? [];


    // if (!empty($habilidadesNuevas) || !empty($nivelesExistentes)) {
    //     $solicitanteController->procesarHabilidades($solicitanteId, $habilidadesNuevas, $nivelesExistentes, $habilidadesRemovidas);
    // } else {
    //     error_log("No se procesaron habilidades porque no hay datos.");
    // }

    // Actualizar el perfil
    if (!empty($nombre) && !empty($nivelesExistentes)) {
        $resultadoPerfil = $solicitanteController->actualizarPerfil($solicitanteId, $nombre, $nivelesExistentes);
        if (!$resultadoPerfil) {
            error_log("Error al actualizar el perfil del solicitante.");
        }
    }

    if (!isset($error)) {
        header("Location: dashboard_solicitante.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil Solicitante</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Bolsa de Trabajo</div>
        <div class="nav-links">
            <a href="dashboard_solicitante.php">Volver al Dashboard</a>
        </div>
    </nav>
    
    <main>
        <div class="form-container">
            <h1>Editar Perfil</h1>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label for="nombre">Nombre Completo:</label>
                <div class="input-container">
                    <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($solicitante['nombre_completo']) ?>" required>
                </div>


                <label>Habilidades</label>
                <div id="lista-habilidades" class="skills-container">
                    <?php foreach ($habilidadesSolicitante as $habilidad): ?>
                        <div class="skill-item">
                            <label for="habilidad_<?php echo $habilidad['id']; ?>">
                                <?php echo htmlspecialchars($habilidad['nombre']); ?>
                            </label>
                            <select class="select" name="niveles_existentes[<?= $habilidad['id'] ?>]" required>
                                <option value="basico" <?= $habilidad['nivel'] === 'basico' ? 'selected' : '' ?>>Básico</option>
                                <option value="intermedio" <?= $habilidad['nivel'] === 'intermedio' ? 'selected' : '' ?>>Intermedio</option>
                                <option value="avanzado" <?= $habilidad['nivel'] === 'avanzado' ? 'selected' : '' ?>>Avanzado</option>
                            </select>
                            <button type="button" class="remove-btn" onclick="eliminarHabilidad(this)">X</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex-container">
                    <select class="select select-input" id="habilidades_existentes">
                        <option value="">Seleccionar habilidad existente</option>
                        <?php foreach ($habilidadesExistentes as $habilidad): ?>
                            <option value="<?php echo $habilidad['id']; ?>">
                                <?php echo htmlspecialchars($habilidad['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="add-skill-container">
                    <div class="flex-container">
                        <div class="input-container">
                            <input type="text" id="nueva-habilidad" placeholder="Agregar habilidad">
                        </div>
                        <select id="nivel-nueva-habilidad" class="select">
                            <option value="basico">Básico</option>
                            <option value="intermedio">Intermedio</option>
                            <option value="avanzado">Avanzado</option>
                        </select>
                    </div>
                    <button type="button" class="button" onclick="agregarHabilidad()">Agregar</button>
                </div>

                <div class="form-buttons-container">
                    <button type="submit" class="button-y">Actualizar Perfil</button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados</p>
    </footer>

    <script>
        function agregarHabilidad() {
            const inputHabilidad = document.getElementById('nueva-habilidad');
            const inputHabilidadExistente = document.getElementById('habilidades_existentes');
            const nivel = document.getElementById('nivel-nueva-habilidad').value;

            var habilidadId = null;
            var habilidadNombre = null;

            if (inputHabilidadExistente.value !== '') {
                habilidadId = inputHabilidadExistente.value;
                habilidadNombre = inputHabilidadExistente.options[inputHabilidadExistente.selectedIndex].text;
            } else {
                habilidadId = inputHabilidad.value.trim();  
                habilidadNombre = inputHabilidad.value.trim();

                if (habilidadId === '') return;
            }

            if (habilidadId === null || habilidadNombre === null) return;

            const contenedor = document.getElementById('lista-habilidades');
            const skillTag = document.createElement('div');
            skillTag.className = 'skill-item';

            skillTag.innerHTML = `
                <input type="hidden" name="habilidad_${habilidadId}" value="${habilidadNombre}">
                <label>${habilidadNombre}</label>
                <select class="select" name="niveles_existentes[${habilidadId}]" required>
                    <option value="basico" ${nivel === 'basico' ? 'selected' : ''}>Básico</option>
                    <option value="intermedio" ${nivel === 'intermedio' ? 'selected' : ''}>Intermedio</option>
                    <option value="avanzado" ${nivel === 'avanzado' ? 'selected' : ''}>Avanzado</option>
                </select>
                <button type="button" class="remove-btn" onclick="eliminarHabilidad(this)">X</button>
            `;

            contenedor.appendChild(skillTag);
            inputHabilidad.value = '';
            inputHabilidadExistente.selectedIndex = 0;
        }

        function eliminarHabilidad(elemento) {
            elemento.closest('.skill-item').remove();
        }
    </script>

</body>
</html>