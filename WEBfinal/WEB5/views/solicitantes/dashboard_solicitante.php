<?php
// Configuración y verificación de sesión
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SolicitanteController.php';
require_once '../../models/Solicitante.php';
require_once '../../models/Vacante.php';
require_once '../../models/Postulacion.php';

// Iniciar sesión y verificar
$authController = new AuthController($conn);
$authController->verificarSesion();

// Manejar logout si es necesario
if (isset($_GET['logout'])) {
    $authController->logout();
}

// Obtener ID de solicitante
$usuarioId = $_SESSION['user_id'];
$solicitanteController = new SolicitanteController($conn);
$solicitanteModel = new Solicitante($conn);

// Obtener solicitante
$solicitante = $solicitanteModel->obtenerSolicitantePorUsuarioId($usuarioId);

// Verificar si se encontró el solicitante
if (!$solicitante) {
    die("Solicitante no encontrado");
}

$solicitanteId = $solicitante['id'];

// Obtener vacantes recomendadas
$vacantesRecomendadas = $solicitanteController->obtenerVacantesRecomendadas($solicitanteId);

// Obtener habilidades del solicitante
$habilidadesSolicitante = $solicitanteModel->obtenerHabilidadesSolicitante($solicitanteId);

// Obtener postulaciones del solicitante
$postulacionesSolicitante = $solicitanteController->obtenerPostulacionesSolicitante($solicitanteId);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Solicitante</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>

<body>
    <nav class="navbar">
        <div class="logo">Bolsa de Trabajo</div>
        <div class="nav-links">
            <a href="editar_perfil_solicitante.php">Editar Perfil</a>
            <a href="?logout=true" class="logout-btn">Cerrar Sesión</a>
        </div>
    </nav>

    <main id="solicitante-dashboard">

        <div class="flex-container vacantes-dash-container">
            <div class="form-container">
                <h1>Bienvenido, <?php echo htmlspecialchars($solicitante['nombre_completo']); ?></h1>

                <section class="vacantes-recomendadas">
                    <h2>Vacantes Recomendadas</h2>
                    <?php if (!empty($vacantesRecomendadas)): ?>
                        <div class="vacantes-grid">
                            <?php foreach ($vacantesRecomendadas as $index => $vacante): ?>
                                <div class="vacante-card">
                                    <div class="vacante-info">
                                        <h3><?php echo htmlspecialchars($vacante['vacante']['titulo']); ?></h3>
                                        <p>Empresa: <?php echo htmlspecialchars($vacante['vacante']['nombre_empresa']); ?></p>
                                        <span class="puntuacion">
                                            Coincidencia: <?php echo number_format($vacante['puntuacion'], 2); ?>%
                                        </span>
                                    </div>
                                    <a href="ver_vacante.php?id=<?php echo $vacante['vacante']['id']; ?>" class="btn-ver-detalle">
                                        Ver Detalles
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No hay vacantes recomendadas en este momento.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <div class="form-container info-container">
            <div class="form-container habilidades-container">
                <h2>Mis Habilidades</h2>
                <section class="mis-habilidades">
                    <?php if (!empty($habilidadesSolicitante)): ?>
                        <ul class="habilidades-lista">
                            <?php foreach ($habilidadesSolicitante as $habilidad): ?>
                                <li>
                                    <?php echo htmlspecialchars($habilidad['nombre']); ?>
                                    <span class="nivel-habilidad">
                                        <?php echo htmlspecialchars($habilidad['nivel'] ?? 'Sin nivel'); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Aún no has agregado habilidades.</p>
                    <?php endif; ?>
                </section>
            </div>

            <div class="form-container">
                <h2>Mis Postulaciones</h2>
                <section class="mis-postulaciones postulaciones-container">
                    <?php if (!empty($postulacionesSolicitante)): ?>
                        <div class="postulaciones-grid">
                            <?php foreach ($postulacionesSolicitante as $postulacion): ?>
                                <div class="postulacion-card">
                                    <div class="postulacion-info vacante-info">
                                        <h3><?php echo htmlspecialchars($postulacion['titulo_vacante']); ?></h3>
                                        <p>Empresa: <?php echo htmlspecialchars($postulacion['nombre_empresa']); ?></p>
                                        <p>Fecha de postulacion: <?php echo htmlspecialchars($postulacion['fecha_postulacion']); ?></p>
                                        <span class="puntuacion">
                                            Esatus: <?php echo htmlspecialchars($postulacion['estado']) ?>
                                        </span>
                                    </div>
                                    <a href="ver_vacante.php?id=<?php echo $postulacion['vacante_id']; ?>" class="btn-ver-detalle">
                                        Ver Detalles
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Aún no te has postulado a alguna vacante.</p>
                    <?php endif; ?>
                </section>
            </div>
        </div>

    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados</p>
    </footer>
</body>

</html>