<?php
require_once '../config/database.php';
require_once '../models/Postulacion.php';

class PostulacionController {
    private $db;
    private $postulacionModel;

    public function __construct($database) {
        $this->db = $database;
        $this->postulacionModel = new Postulacion($this->db);
    }

    public function crearPostulacion($solicitante_id, $vacante_id) {
        return $this->postulacionModel->crearPostulacion($solicitante_id, $vacante_id);
    }

    public function obtenerPostulacion($id) {
        return $this->postulacionModel->obtenerPostulacionPorId($id);
    }

    public function obtenerPostulacionesPorSolicitante($solicitante_id) {
        return $this->postulacionModel->obtenerPostulacionesPorSolicitanteId($solicitante_id);
    }

    public function actualizarEstadoPostulacion($id, $estado) {
        return $this->postulacionModel->actualizarEstadoPostulacion($id, $estado);
    }
}
?>