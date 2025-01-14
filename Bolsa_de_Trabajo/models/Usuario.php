<?php
class Usuario {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function crearUsuario($email, $password, $tipo_usuario) {
        $sql = "INSERT INTO usuarios (email, password, tipo_usuario) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sss", $email, $password, $tipo_usuario);
        return $stmt->execute();
    }

    public function obtenerUsuarioPorEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function obtenerUsuarioPorId($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>