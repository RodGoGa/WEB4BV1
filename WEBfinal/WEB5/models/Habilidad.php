<?php
class Habilidad {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function crearHabilidad($nombre, $tipo) {
        $sql = "INSERT INTO habilidades (nombre, tipo) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $nombre, $tipo);
        return $stmt->execute() ? $this->db->insert_id : false;
    }

    public function obtenerHabilidadPorNombre($nombre) {
        $sql = "SELECT * FROM habilidades WHERE nombre = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function obtenerTodasLasHabilidades() {
        $sql = "SELECT * FROM habilidades";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Método para actualizar el nivel de una habilidad
    public function actualizarNivelHabilidad($habilidadId, $nuevoNivel) {
        $query = "UPDATE habilidades SET nivel = :nivel WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':nivel', $nuevoNivel, PDO::PARAM_INT);
        $stmt->bindParam(':id', $habilidadId, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
?>