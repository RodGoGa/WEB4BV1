<?php
// controllers/EmpresaController.php

// Requiere los modelos necesarios
require_once __DIR__ . '/../models/Empresa.php';
require_once __DIR__ . '/../models/Vacante.php';

class EmpresaController {
    private $db;
    private $empresaModel;
    private $vacanteModel;

    public function __construct($database) {
        $this->db = $database;
        $this->empresaModel = new Empresa($database);
        $this->vacanteModel = new Vacante($database);
    }

    public function obtenerVacantesPorEmpresa($empresaId) {
        return $this->vacanteModel->obtenerVacantesPorEmpresa($empresaId);
    }

    public function obtenerPostulacionesPorVacante($vacanteId) {
        try {
            $sql = "SELECT 
                        p.id, 
                        p.estado, 
                        p.fecha_postulacion, 
                        p.solicitante_id,
                        s.nombre_completo
                    FROM postulaciones p
                    JOIN solicitantes s ON p.solicitante_id = s.id
                    WHERE p.vacante_id = ?
                    ORDER BY p.fecha_postulacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $vacanteId);
            $stmt->execute();

            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener postulaciones: " . $e->getMessage());
            return [];
        }
    }

    public function cambiarEstadoPostulacion($postulacionId, $nuevoEstado) {
        try {
            // Validar estados permitidos
            $estadosPermitidos = ['pendiente', 'aceptada', 'rechazada'];
            if (!in_array($nuevoEstado, $estadosPermitidos)) {
                throw new Exception("Estado no válido");
            }

            // Iniciar transacción
            $this->db->begin_transaction();

            // Obtener información de la postulación
            $sqlPostulacion = "SELECT vacante_id FROM postulaciones WHERE id = ?";
            $stmtPostulacion = $this->db->prepare($sqlPostulacion);
            $stmtPostulacion->bind_param("i", $postulacionId);
            $stmtPostulacion->execute();
            $resultPostulacion = $stmtPostulacion->get_result()->fetch_assoc();
            $vacanteId = $resultPostulacion['vacante_id'];

            // Actualizar estado de postulación
            $sql = "UPDATE postulaciones SET estado = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $nuevoEstado, $postulacionId);

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar estado de postulación");
            }

            // Si el nuevo estado es aceptada, actualizar vacante a ocupada
            if ($nuevoEstado === 'aceptada') {
                // Actualizar estado de la vacante
                $sqlVacante = "UPDATE vacantes SET estado = 'ocupada' WHERE id = ?";
                $stmtVacante = $this->db->prepare($sqlVacante);
                $stmtVacante->bind_param("i", $vacanteId);

                if (!$stmtVacante->execute()) {
                    throw new Exception("Error al actualizar estado de vacante");
                }

                // Rechazar otras postulaciones para esta vacante
                $sqlRechazarOtras = "UPDATE postulaciones SET estado = 'rechazada' 
                                     WHERE vacante_id = ? AND id != ?";
                $stmtRechazarOtras = $this->db->prepare($sqlRechazarOtras);
                $stmtRechazarOtras->bind_param("ii", $vacanteId, $postulacionId);

                if (!$stmtRechazarOtras->execute()) {
                    throw new Exception("Error al rechazar otras postulaciones");
                }
            }

            // Confirmar transacción
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Revertir transacción
            $this->db->rollback();
            error_log("Error al cambiar estado de postulación: " . $e->getMessage());
            return false;
        }
    }
    public function obtenerDetallePostulacion($solicitanteId, $vacanteId) {
        $sql = "SELECT p.* 
                FROM postulaciones p
                WHERE p.solicitante_id = ? AND p.vacante_id = ?";
    
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $solicitanteId, $vacanteId);
        $stmt->execute();
    
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>