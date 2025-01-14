<?php
// Obtener la ruta raíz del proyecto de manera dinámica
$rootPath = realpath(dirname(__FILE__) . '/../');

// Inclusión de archivos con rutas absolutas
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/models/Usuario.php';
require_once $rootPath . '/models/Solicitante.php';
require_once $rootPath . '/models/Empresa.php';
require_once $rootPath . '/models/Habilidad.php';

class AuthController {
    private $db;
    private $usuarioModel;
    private $solicitanteModel;
    private $empresaModel;
    private $habilidadModel;

    public function __construct($database) {
        $this->db = $database;
        $this->usuarioModel = new Usuario($this->db);
        $this->solicitanteModel = new Solicitante($this->db);
        $this->empresaModel = new Empresa($this->db);
        $this->habilidadModel = new Habilidad($this->db);
    }

    public function login($email, $password) {
        try {
            $usuario = $this->usuarioModel->obtenerUsuarioPorEmail($email);

            if (!$usuario) {
                return false; // Usuario no encontrado
            }

            // Verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                session_start();
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

                return true;
            }

            return false;
        } catch (Exception $e) {
            // Registrar error
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }

    public function registerSolicitante($nombre, $email, $password, $habilidades = []) {
        try {
            // Iniciar transacción
            $this->db->begin_transaction();

            // Verificar si el email ya existe
            if ($this->usuarioModel->obtenerUsuarioPorEmail($email)) {
                throw new Exception("El correo electrónico ya está registrado");
            }

            // Validar datos de entrada
            $this->validarDatosSolicitante($nombre, $email, $password, $habilidades);

            // Hashear contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Crear usuario
            $usuarioId = $this->crearUsuarioSolicitante($email, $hashedPassword);

            // Crear perfil de solicitante
            $solicitanteId = $this->solicitanteModel->crearSolicitante($usuarioId, $nombre);

            if (!$solicitanteId) {
                throw new Exception("Error al crear perfil de solicitante");
            }

            // Procesar y agregar habilidades
            $this->procesarHabilidades($solicitanteId, $habilidades);

            // Confirmar transacción
            $this->db->commit();

            return true;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollback();
            error_log("Error en registro de solicitante: " . $e->getMessage());
            return false;
        }
    }

    public function registerEmpresa($nombre_empresa, $email, $password) {
        try {
            // Iniciar transacción
            $this->db->begin_transaction();

            // Verificar si el email ya existe
            if ($this->usuarioModel->obtenerUsuarioPorEmail($email)) {
                throw new Exception("El correo electrónico ya está registrado");
            }

            // Hashear contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Crear usuario
            $usuarioId = $this->crearUsuarioEmpresa($email, $hashedPassword);

            // Crear perfil de empresa
            $empresaId = $this->empresaModel->crearEmpresa($usuarioId, $nombre_empresa, '');

            if (!$empresaId) {
                throw new Exception("Error al crear perfil de empresa");
            }

            // Confirmar transacción
            $this->db->commit();

            return true;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollback();
            error_log("Error en registro de empresa: " . $e->getMessage());
            return false;
        }
    }

    private function crearUsuarioSolicitante($email, $hashedPassword) {
        // Crear usuario de tipo solicitante
        if ($this->usuarioModel->crearUsuario($email, $hashedPassword, 'solicitante')) {
            $usuario = $this->usuarioModel->obtenerUsuarioPorEmail($email);
            return $usuario['id'];
        }
        throw new Exception("No se pudo crear el usuario");
    }

    private function crearUsuarioEmpresa($email, $hashedPassword) {
        // Crear usuario de tipo empresa
        if ($this->usuarioModel->crearUsuario($email, $hashedPassword, 'empresa')) {
            $usuario = $this->usuarioModel->obtenerUsuarioPorEmail($email);
            return $usuario['id'];
        }
        throw new Exception("No se pudo crear el usuario");
    }

    private function validarDatosSolicitante($nombre, $email, $password, $habilidades) {
        // Validaciones de datos de entrada
        if (empty($nombre) || strlen($nombre) > 100) {
            throw new Exception("Nombre inválido");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Correo electrónico inválido");
        }

        if (strlen($password) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }

        if (count($habilidades) < 5) {
            throw new Exception("Debe seleccionar al menos 5 habilidades");
        }
    }

    private function procesarHabilidades($solicitanteId, $habilidades) {
        foreach ($habilidades as $habilidad) {
            // Verificar si es una habilidad nueva (prefijo 'nueva_')
            if (strpos($habilidad, 'nueva_') === 0) {
                $nombreHabilidad = substr($habilidad, 6);

                // Verificar si la habilidad ya existe
                $habilidadExistente = $this->habilidadModel->obtenerHabilidadPorNombre($nombreHabilidad);

                if (!$habilidadExistente) {
                    // Crear nueva habilidad
                    $this->habilidadModel->crearHabilidad($nombreHabilidad, 'personalizada');
                    $habilidadId = $this->db->insert_id;
                } else {
                    $habilidadId = $habilidadExistente['id'];
                }
            } else {
                $habilidadId = $habilidad;
            }

            // Asociar habilidad al solicitante
            $this->solicitanteModel->agregarHabilidad($solicitanteId, $habilidadId);
        }
    }

    // Método para cerrar sesión
    public function logout() {
    // Iniciar sesión si no está iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Destruir todas las variables de sesión
    $_SESSION = array();

    // Destruir la sesión
    session_destroy();

    // Borrar la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Redirigir a la página de inicio de sesión
    header("Location: ../auth/login.php");
    exit();
}

    // Método para verificar si hay una sesión activa
    public function verificarSesion() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }
    }

}
?>