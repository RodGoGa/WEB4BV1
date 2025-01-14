<?php
// Configuración y verificación de sesión
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/EmpresaController.php';
require_once '../../models/Solicitante.php';
require_once '../../models/Vacante.php';

// Iniciar sesión y verificar
$authController = new AuthController($conn);
$authController->verificarSesion();

// Verificar que sea una empresa
if ($_SESSION['tipo_usuario'] !== 'empresa') {
    die("Acceso denegado");
}

// Obtener ID del solicitante
$solicitanteId = isset($_GET['solicitante_id']) ? intval($_GET['solicitante_id']) : 0;
$vacanteId = isset($_GET['vacante_id']) ? intval($_GET['vacante_id']) : 0;

// Modelos
$solicitanteModel = new Solicitante($conn);
$vacanteModel = new Vacante($conn);
$empresaController = new EmpresaController($conn);

// Obtener datos del solicitante
$solicitante = $solicitanteModel->obtenerSolicitantePorId($solicitanteId);

// Obtener habilidades del solicitante
$habilidades = $solicitanteModel->obtenerHabilidadesSolicitante($solicitanteId);

// Obtener datos de la vacante
$vacante = $vacanteModel->obtenerVacantePorId($vacanteId);

// Obtener datos de la postulación
$postulacion = $empresaController->obtenerDetallePostulacion($solicitanteId, $vacanteId);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Postulante</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Bolsa de Trabajo</div>
        <div class="nav-links">
            <a href="ver_postulaciones.php?vacante_id=<?php echo $vacanteId; ?>">Volver a Postulaciones</a>
        </div>
    </nav>

    <main class="perfil-postulante-container">
        <div class="form-container">
            <h1>Perfil del Postulante</h1>

            <section class="informacion-personal">
                <h2>Información Personal</h2>
                <div class="perfil-detalle">
                    <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($solicitante['nombre_completo']); ?></p>
                    
                    <?php 
                    // Obtener correo del usuario
                    $sqlEmail = "SELECT email FROM usuarios WHERE id = ?";
                    $stmtEmail = $conn->prepare($sqlEmail);
                    $stmtEmail->bind_param("i", $solicitante['usuario_id']);
                    $stmtEmail->execute();
                    $resultEmail = $stmtEmail->get_result()->fetch_assoc();
                    ?>
                    <p><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($resultEmail['email']); ?></p>
                </div>
            </section>

            <section class="habilidades-postulante">
                <h2>Habilidades</h2>
                <?php if (!empty($habilidades)): ?>
                    <ul class="habilidades-lista">
                        <?php foreach ($habilidades as $habilidad): ?>
                            <li>
                                <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                <span class="nivel-habilidad">
                                    Nivel: <?php echo htmlspecialchars($habilidad['nivel']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>El postulante no ha registrado habilidades.</p>
                <?php endif; ?>
            </section>

            <section class="detalle-postulacion">
                <h2>Detalles de la Postulación</h2>
                <div class="postulacion-info">
                    <p><strong>Vacante:</strong> <?php echo htmlspecialchars($vacante['titulo']); ?></p>
                    <p><strong>Fecha de Postulación:</strong> <?php echo htmlspecialchars($postulacion['fecha_postulacion']); ?></p>
                    <p><strong>Estado de Postulación:</strong> <?php echo htmlspecialchars($postulacion['estado']); ?></p>
                </div>
            </section>

            <div class="acciones-postulacion">
                <form method="POST" action="procesar_postulacion.php">
                    <input type="hidden" name="postulacion_id" value="<?php echo $postulacion['id']; ?>">
                    <input type="hidden" name="vacante_id" value="<?php echo $vacanteId; ?>">
                    
                    <?php if ($postulacion['estado'] == 'pendiente'): ?>
                        <button type="submit" name="accion" value="aceptar" class="button-y">
                            Aceptar Postulación
                        </button>
                        <button type="submit" name="accion" value="rechazar" class="button-r">
                            Rechazar Postulación
                        </button>
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