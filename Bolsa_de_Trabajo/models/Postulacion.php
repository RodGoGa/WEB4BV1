<?php
class Postulacion {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function crearPostulacion($solicitanteId, $vacanteId) {
        try {
            // Verificar si ya existe la postulación
            if ($this->verificarPostulacion($solicitanteId, $vacanteId)) {
                return false;
            }

            // Insertar nueva postulación
            $sql = "INSERT INTO postulaciones 
                    (solicitante_id, vacante_id, estado, fecha_postulacion) 
                    VALUES (?, ?, 'pendiente', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $solicitanteId, $vacanteId);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al crear postulación: " . $e->getMessage());
            return false;
        }
    }

    public function verificarPostulacion($solicitanteId, $vacanteId) {
        $sql = "SELECT COUNT(*) as count 
                FROM postulaciones 
                WHERE solicitante_id = ? AND vacante_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $solicitanteId, $vacanteId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }

    public function obtenerPostulacionesSolicitante($solicitanteId) {
        $sql = "SELECT p.*, v.titulo AS titulo_vacante, e.nombre AS nombre_empresa, p.estado
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                JOIN empresas e ON v.empresa_id = e.id
                WHERE p.solicitante_id = ?
                ORDER BY p.fecha_postulacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $solicitanteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function obtenerPostulacionesVacante($vacanteId) {
        $sql = "SELECT p.*, s.nombre_completo, s.email
                FROM postulaciones p
                JOIN solicitantes s ON p.solicitante_id = s.id
                WHERE p.vacante_id = ?
                ORDER BY p.fecha_postulacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $vacanteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function actualizarEstadoPostulacion($postulacionId, $nuevoEstado) {
        $estadosValidos = ['pendiente', 'revisado', 'aceptado', 'rechazado'];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            return false;
        }

        $sql = "UPDATE postulaciones SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $nuevoEstado, $postulacionId);

        return $stmt->execute();
    }

    public function eliminarPostulacion($postulacionId) {
        $sql = "DELETE FROM postulaciones WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $postulacionId);

        return $stmt->execute();
    }
}
?>