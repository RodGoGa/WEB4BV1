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

// Obtener ID de vacante
$vacanteId = isset($_GET['vacante_id']) ? intval($_GET['vacante_id']) : 0;

// Obtener ID de empresa
$usuarioId = $_SESSION['user_id'];
$empresaModel = new Empresa($conn);
$empresaController = new EmpresaController($conn);

// Obtener empresa
$empresa = $empresaModel->obtenerEmpresaPorUsuarioId($usuarioId);
$empresaId = $empresa['id'];

// Obtener vacante
$vacanteModel = new Vacante($conn);
$vacante = $vacanteModel->obtenerVacantePorId($vacanteId);

// Verificar que la vacante pertenezca a la empresa
if ($vacante['empresa_id'] != $empresaId) {
    die("No tienes permiso para ver estas postulaciones.");
}

// Obtener postulaciones
$postulaciones = $empresaController->obtenerPostulacionesPorVacante($vacanteId);

// Manejar cambio de estado de postulación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postulacionId = $_POST['postulacion_id'] ?? 0;
    $nuevoEstado = $_POST['estado'] ?? '';

    $resultado = $empresaController->cambiarEstadoPostulacion($postulacionId, $nuevoEstado);

    if ($resultado) {
        header("Location: ver_postulaciones.php?vacante_id=$vacanteId&success=1");
        exit();
    } else {
        $error = "Error al actualizar el estado de la postulación.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postulaciones de Vacante</title>
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
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Estado de postulación actualizado correctamente.
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <h1>Postulaciones para: <?php echo htmlspecialchars($vacante['titulo']); ?></h1>

            <div class="postulaciones-container">
                <?php if (!empty($postulaciones)): ?>
                    <?php foreach ($postulaciones as $postulacion): ?>
                        <div class="postulacion-card">
                            <div class="postulacion-info">
                                <h3>
                                    <p class="btn-estado">
                                        <?php echo htmlspecialchars($postulacion['nombre_completo']); ?>
                    </p>
                                </h3>
                                <p>Fecha de postulación: <?php echo date('d/m/Y H:i', strtotime($postulacion['fecha_postulacion'])); ?></p>

                                <div class="estado-postulacion
                                    <?php
                                    switch($postulacion['estado']) {
                                        case 'pendiente': echo 'estado-pendiente'; break;
                                        case 'aceptada': echo 'estado-aceptada'; break;
                                        case 'rechazada': echo 'estado-rechazada'; break;
                                    }
                                    ?>">
                                    Estado: <?php
                                    switch($postulacion['estado']) {
                                        case 'pendiente': echo ' Pendiente'; break;
                                        case 'aceptada': echo 'Aceptada'; break;
                                        case 'rechazada': echo 'Rechazada'; break;
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="postulacion-acciones">
                                <?php if ($postulacion['estado'] === 'pendiente'): ?>
                                    <form method="POST" class="form-acciones">
                                        <input type="hidden" name="postulacion_id" value="<?php echo $postulacion['id']; ?>">
                                        <button
                                            type="submit"
                                            name="estado"
                                            value="aceptada"
                                            class="btn-estado btn-aceptar"
                                        >
                                            Aceptar Postulación
                                        </button>
                                        <button
                                            type="submit"
                                            name="estado"
                                            value="rechazada"
                                            class="btn-estado btn-rechazar"
                                        >
                                            Rechazar Postulación
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <p>
                                        <strong>Estado Actual:</strong> <?php echo ucfirst($postulacion['estado']); ?>
                                    </p>
                                    <form method="POST" class="form-acciones">
                                        <input type="hidden" name="postulacion_id" value="<?php echo $postulacion['id']; ?>">
                                        <?php if ($postulacion['estado'] === 'aceptada'): ?>
                                            <button
                                                type="submit"
                                                name="estado"
                                                value="rechazada"
                                                class="btn-estado btn-rechazar"
                                            >
                                                Cambiar a Rechazada
                                            </button>
                                        <?php elseif ($postulacion['estado'] === 'rechazada'): ?>
                                            <button
                                                type="submit"
                                                name="estado"
                                                value="aceptada"
                                                class="btn-estado btn-aceptar"
                                            >
                                                Cambiar a Aceptada
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                                
                                <a 
                                    href="ver_perfil_postulado.php?solicitante_id=<?php echo $postulacion['solicitante_id']; ?>&vacante_id=<?php echo $vacanteId; ?>" 
                                    class="btn-ver-perfil"
                                >
                                    Ver Perfil Completo
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No hay postulaciones para esta vacante.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>