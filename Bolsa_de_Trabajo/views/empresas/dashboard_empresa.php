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

// Manejar logout si es necesario
if (isset($_GET['logout'])) {
    $authController->logout();
}

// Manejar cambio de estado de vacante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $vacanteId = $_POST['vacante_id'] ?? 0;
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';

    $vacanteModel = new Vacante($conn);
    $resultado = $vacanteModel->actualizarVacante($vacanteId, ['estado' => $nuevoEstado]);

    if ($resultado) {
        header("Location: dashboard_empresa.php?success=1");
        exit();
    } else {
        $error = "Error al cambiar el estado de la vacante.";
    }
}

// Obtener ID de empresa
$usuarioId = $_SESSION['user_id'];
$empresaModel = new Empresa($conn);
$empresaController = new EmpresaController($conn);

// Obtener empresa
$empresa = $empresaModel->obtenerEmpresaPorUsuarioId($usuarioId);

// Verificar si se encontró la empresa
if (!$empresa) {
    die("Empresa no encontrada");
}

$empresaId = $empresa['id'];

// Obtener vacantes de la empresa
$vacantes = $empresaController->obtenerVacantesPorEmpresa($empresaId);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Empresa</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Bolsa de Trabajo</div>
        <div class="nav-links">
            <a href="crear_vacante_empresa.php">Crear Vacante</a>
            <a href="?logout=true" class="logout-btn">Cerrar Sesión</a>
        </div>
    </nav>

    <main>
        <div id="dashboard-empresa" class="form-container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Estado de vacante actualizado correctamente.
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <h1>Bienvenido, <?php echo htmlspecialchars($empresa['nombre_empresa']); ?></h1>

            <h2>Mis Vacantes</h2>
            <section id="vacantes-empresa" class="mis-vacantes">
                <?php if (!empty($vacantes)): ?>
                    <div class="vacantes-grid">
                        <?php foreach ($vacantes as $vacante): ?>
                            <div class="vacante-card
                                <?php
                                    echo $vacante['estado'] == 'ocupada' ? 'vacante-ocupada' :
                                    ($vacante['estado'] == 'despublicada' ? 'vacante-despublicada' : '');
                                ?>">
                                    <h3><?php echo htmlspecialchars($vacante['titulo']); ?></h3>
                                    <div class="vacante-info">
                                        <p><?php echo htmlspecialchars($vacante['descripcion']); ?></p>
                                    </div>

                                    <div class="vacante-estado">
                                        <span class="estado">
                                            Estado: <?php
                                                switch($vacante['estado']) {
                                                    case 'disponible':
                                                        echo 'Disponible';
                                                        break;
                                                    case 'ocupada':
                                                        echo 'Ocupada';
                                                        break;
                                                    case 'despublicada':
                                                        echo 'Despublicada';
                                                        break;
                                                }
                                            ?>
                                        </span>
                                    </div>

                                    <div class="vacante-acciones">
                                        <a
                                            href="ver_postulaciones.php?vacante_id=<?php echo $vacante['id'];?>"
                                            class="btn-ver-detalle"
                                        >
                                            Ver Postulaciones
                                        </a>
                                        <a
                                            href="editar_vacante_empresa.php?id=<?php echo $vacante['id'];?>"
                                            class="btn-ver-detalle"
                                        >
                                            Editar
                                        </a>
                                        <?php if ($vacante['estado'] == 'disponible'): ?>
                                            <form method="POST" class="form-cambiar-estado">
                                                <input type="hidden" name="cambiar_estado" value="1">
                                                <input type="hidden" name="vacante_id" value="<?php echo $vacante['id']; ?>">
                                                <select name="nuevo_estado" class="select select-estado">
                                                    <option value="disponible">Disponible</option>
                                                    <option value="ocupada">Marcar como Ocupada</option>
                                                    <option value="despublicada">Despublicar</option>
                                                </select>
                                                <button class="button-y" type="submit" class="btn-cambiar-estado">Cambiar Estado</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Aún no has publicado ninguna vacante.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Bolsa de Trabajo - Todos los derechos reservados</p>
    </footer>
</body>
</html>