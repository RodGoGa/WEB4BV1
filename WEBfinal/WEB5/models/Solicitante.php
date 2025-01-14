<?php
class Solicitante {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // Métodos anteriores se mantienen igual

    public function agregarHabilidad($solicitante_id, $habilidad_id, $nivel = 'intermedio') {
        // Verificar si la combinación ya existe para evitar duplicados
        $sqlVerificar = "SELECT COUNT(*) as count FROM solicitante_habilidades 
                         WHERE solicitante_id = ? AND habilidad_id = ?";
        $stmtVerificar = $this->db->prepare($sqlVerificar);
        $stmtVerificar->bind_param("ii", $solicitante_id, $habilidad_id);
        $stmtVerificar->execute();
        $resultVerificar = $stmtVerificar->get_result()->fetch_assoc();

        // Si ya existe, no insertar
        if ($resultVerificar['count'] > 0) {
            return true;
        }

        // Insertar nueva habilidad
        $sql = "INSERT INTO solicitante_habilidades 
                (solicitante_id, habilidad_id, nivel) 
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iis", $solicitante_id, $habilidad_id, $nivel);

        return $stmt->execute();
    }

    public function obtenerHabilidadesSolicitante($solicitante_id) {
        $sql = "SELECT h.id, h.nombre, sh.nivel 
                FROM habilidades h
                JOIN solicitante_habilidades sh ON h.id = sh.habilidad_id
                WHERE sh.solicitante_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $solicitante_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function actualizarNivelHabilidad($solicitante_id, $habilidad_id, $nuevoNivel) {
        $sql = "UPDATE solicitante_habilidades 
                SET nivel = ? 
                WHERE solicitante_id = ? AND habilidad_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $nuevoNivel, $solicitante_id, $habilidad_id);
        return $stmt->execute();
    }

    public function eliminarHabilidad($solicitante_id, $habilidad_id) {
        $sql = "DELETE FROM solicitante_habilidades 
                WHERE solicitante_id = ? AND habilidad_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $solicitante_id, $habilidad_id);
        return $stmt->execute();
    }

    public function crearSolicitante($usuario_id, $nombre_completo) {
        // Modificar para devolver el ID del solicitante
        $sql = "INSERT INTO solicitantes (usuario_id, nombre_completo) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $nombre_completo);

        if ($stmt->execute()) {
            return $stmt->insert_id; // Devolver el ID del solicitante recién creado
        }

        return false;
    }

    // Método para obtener solicitante por ID de usuario
    public function obtenerSolicitantePorUsuarioId($usuarioId) {
        $sql = "SELECT s.* 
                FROM solicitantes s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE u.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Nuevo método para obtener solicitante por ID de solicitante
    public function obtenerSolicitantePorId($solicitanteId) {
        $sql = "SELECT s.*, u.email
                FROM solicitantes s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $solicitanteId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>