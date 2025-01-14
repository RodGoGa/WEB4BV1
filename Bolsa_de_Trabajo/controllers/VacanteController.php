<?php
require_once '../config/database.php';
require_once '../models/Vacante.php';

class VacanteController {
    private $db;
    private $vacanteModel;

    public function __construct($database) {
        $this->db = $database;
        $this->vacanteModel = new Vacante($this->db);
    }

    public function crearVacante($empresa_id, $titulo, $descripcion, $requisitos) {
        return $this->vacanteModel->crearVacante($empresa_id, $titulo, $descripcion, $requisitos);
    }

    public function obtenerVacante($id) {
        return $this->vacanteModel->obtenerVacantePorId($id);
    }

    public function obtenerVacantesPorEmpresa($empresa_id) {
        return $this->vacanteModel->obtenerVacantesPorEmpresaId($empresa_id);
    }
}
?>