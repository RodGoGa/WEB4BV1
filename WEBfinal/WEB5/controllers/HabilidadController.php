<?php
// Obtener la ruta raíz del proyecto de manera dinámica
$rootPath = realpath(dirname(__FILE__) . '/../');

// Inclusión de archivos con rutas absolutas
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/models/Habilidad.php';

class HabilidadController {
    private $db;
    private $habilidadModel;

    public function __construct($database) {
        $this->db = $database;
        $this->habilidadModel = new Habilidad($this->db);
    }

    public function crearHabilidad($nombre, $tipo = 'predefinida') {
        // Verificar si la habilidad ya existe
        $existente = $this->obtenerHabilidadPorNombre($nombre);
        if ($existente) {
            return $existente['id'];
        }

        return $this->habilidadModel->crearHabilidad($nombre, $tipo);
    }

    public function obtenerHabilidadPorNombre($nombre) {
        return $this->habilidadModel->obtenerHabilidadPorNombre($nombre);
    }

    public function obtenerTodasLasHabilidades() {
        return $this->habilidadModel->obtenerTodasLasHabilidades();
    }

    public function obtenerHabilidadesPredefinidas() {
        // Obtener todas las habilidades
        $todasHabilidades = $this->obtenerTodasLasHabilidades();

        // Filtrar solo habilidades predefinidas
        return array_filter($todasHabilidades, function($habilidad) {
            return $habilidad['tipo'] == 'predefinida';
        });
    }

    // Otros métodos según sea necesario
}
?>