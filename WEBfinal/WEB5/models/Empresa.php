<?php
class Empresa {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function crearEmpresa($usuario_id, $nombre_empresa, $descripcion) {
        $sql = "INSERT INTO empresas (usuario_id, nombre_empresa, descripcion) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $usuario_id, $nombre_empresa, $descripcion);
        return $stmt->execute();
    }

    public function obtenerEmpresaPorId($id) {
        $sql = "SELECT * FROM empresas WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerEmpresaPorUsuarioId($usuario_id) {
        $sql = "SELECT * FROM empresas WHERE usuario_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>