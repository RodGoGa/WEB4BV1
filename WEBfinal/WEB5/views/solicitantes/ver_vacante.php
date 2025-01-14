<?php
// Configuración y verificación de sesión
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SolicitanteController.php';
require_once '../../models/Vacante.php';
require_once '../../models/Solicitante.php';

// Iniciar sesión y verificar
$authController = new AuthController($conn);
$authController->verificarSesion();

// Obtener ID de vacante
$vacanteId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener ID de solicitante
$usuarioId = $_SESSION['user_id'];
$solicitanteModel = new Solicitante($conn);
$solicitante = $solicitanteModel->obtenerSolicitantePorUsuarioId($usuarioId);
$solicitanteId = $solicitante['id'];

// Controladores
$solicitanteController = new SolicitanteController($conn);
$vacanteModel = new Vacante($conn);

// Obtener detalles de la vacante
$vacante = $vacanteModel->obtenerVacantePorId($vacanteId);

// Obtener habilidades de la vacante
$habilidadesVacante = $vacanteModel->obtenerHabilidadesVacante($vacanteId);

// Verificar si ya está postulado
$sqlPostulacion = "SELECT id, estado
                   FROM postulaciones 
                   WHERE solicitante_id = ? AND vacante_id = ?";
$stmtPostulacion = $conn->prepare($sqlPostulacion);
$stmtPostulacion->bind_param("ii", $solicitanteId, $vacanteId);
$stmtPostulacion->execute();
$resultPostulacion = $stmtPostulacion->get_result()->fetch_assoc();
$yaPostulado = $resultPostulacion ? $resultPostulacion['estado'] : null;
$postulacionId = $resultPostulacion ? $resultPostulacion['id'] : null;

// Manejar postulación y despostulación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['postular'])) {
        // Lógica de postulación
        $resultado = $solicitanteController->postularVacante($solicitanteId, $vacanteId);
        if ($resultado) {
            header("Location: ver_vacante.php?id=$vacanteId&postulacion_exitosa=1");
            exit();
        } else {
            $error = "Error al postular a la vacante.";
        }
    } elseif (isset($_POST['cancelar_postulacion'])) {
        // Lógica de cancelación de postulación
        $resultado = $solicitanteController->cancelarPostulacion($solicitanteId, $vacanteId);
        if ($resultado) {
            header("Location: ver_vacante.php?id=$vacanteId&cancelacion_exitosa=1");
            exit();
        } else {
            $error = "Error al cancelar la postulación.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Vacante</title>
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
            <?php if (isset($_GET['postulacion_exitosa'])): ?>
                <div class="alert alert-success">
                    ¡Postulación realizada con éxito!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['cancelacion_exitosa'])): ?>
                <div class="alert alert-success">
                    ¡Postulación cancelada con éxito!
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($vacante['titulo']); ?></h1>

            <div class="vacante-detalle">
                <h2>Detalles de la Vacante</h2>
                <p><strong>Empresa:</strong> <?php echo htmlspecialchars($vacante['nombre_empresa']); ?></p>
                <p><strong>Descripción:</strong> <?php echo htmlspecialchars($vacante['descripcion']); ?></p>

                <?php if (!empty($vacante['requisitos'])): ?>
                    <p><strong>Requisitos:</strong> <?php echo htmlspecialchars($vacante['requisitos']); ?></p>
                <?php endif; ?>
            </div>

            <div class="vacante-habilidades">
                <h2>Habilidades Requeridas</h2>
                <ul class="habilidades-lista">
                    <?php foreach ($habilidadesVacante as $habilidad): ?>
                        <li>
                            <?php echo htmlspecialchars($habilidad['nombre']); ?>
                            <span class="nivel-habilidad">
                                <?php echo htmlspecialchars($habilidad['nivel_requerido']); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="form-buttons-container">
                <form method="POST">
                    <?php if ($yaPostulado == '' || $yaPostulado == null): ?>
                        <button
                            type="submit"
                            name="postular"
                            class="button-y"
                        >
                            Postular a la Vacante
                        </button>
                    <?php else: ?>
                        <div class="postulacion-info">
                            <p class="text-enphasis">Ya estás postulado a esta vacante</p>
                            <p>Estatus: <span class="text-enphasis"><?php echo htmlspecialchars($yaPostulado)?></span></p>
                            
                            <?php if ($yaPostulado == 'pendiente'): ?>
                                <button
                                    type="submit"
                                    name="cancelar_postulacion"
                                    class="button-r"
                                >
                                    Cancelar Postulación
                                </button>
                            <?php endif; ?>
                        </div>
                 <?php endif; ?>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados</p>
    </footer>
</body>
</html>