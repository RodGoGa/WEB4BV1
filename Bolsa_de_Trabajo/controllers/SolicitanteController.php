<?php
// Incluir modelos necesarios
require_once __DIR__ . '/../models/Solicitante.php';
require_once __DIR__ . '/../models/Vacante.php';
require_once __DIR__ . '/../models/Habilidad.php';

class SolicitanteController {
    private $db;
    private $solicitanteModel;
    private $habilidadModel;
    private $vacanteModel;

    public function __construct($database) {
        $this->db = $database;
        $this->solicitanteModel = new Solicitante($database);
        $this->habilidadModel = new Habilidad($database);
        $this->vacanteModel = new Vacante($database);
    }

    // Método para obtener vacantes recomendadas
    public function obtenerVacantesRecomendadas($solicitanteId) {
        // Obtener todas las vacantes disponibles EXCEPTO las ya postuladas
        $sql = "SELECT v.*, e.nombre_empresa 
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.id
                WHERE v.estado = 'disponible' 
                AND v.id NOT IN (
                    SELECT vacante_id 
                    FROM postulaciones 
                    WHERE solicitante_id = ?
                )";
    
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $solicitanteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $vacantes = $result->fetch_all(MYSQLI_ASSOC);
    
        // Obtener habilidades del solicitante
        $habilidadesSolicitante = $this->solicitanteModel->obtenerHabilidadesSolicitante($solicitanteId);
    
        // Calcular recomendaciones
        $vacantesRecomendadas = [];
    
        foreach ($vacantes as $vacante) {
            // Obtener habilidades de la vacante
            $habilidadesVacante = $this->vacanteModel->obtenerHabilidadesVacante($vacante['id']);
    
            // Calcular coincidencia
            $puntuacion = $this->calcularPorcentajeCoincidencia($habilidadesSolicitante, $habilidadesVacante);
    
            // Agregar solo si hay coincidencia
            if ($puntuacion > 0) {
                $vacantesRecomendadas[] = [
                    'vacante' => $vacante,
                    'puntuacion' => $puntuacion
                ];
            }
        }
    
        // Ordenar por puntuación descendente
        usort($vacantesRecomendadas, function($a, $b) {
            return $b['puntuacion'] - $a['puntuacion'];
        });
    
        // Limitar a las 5 mejores recomendaciones
        return array_slice($vacantesRecomendadas, 0, 5);
    }
    
    private function calcularPorcentajeCoincidencia($habilidadesSolicitante, $habilidadesVacante) {
        if (empty($habilidadesVacante)) return 0;
    
        $coincidencias = 0;
        $totalHabilidadesVacante = count($habilidadesVacante);
    
        $niveles = [
            'basico' => 1,
            'intermedio' => 2,
            'avanzado' => 3
        ];
    
        foreach ($habilidadesVacante as $habilidadVacante) {
            foreach ($habilidadesSolicitante as $habilidadSolicitante) {
                if ($habilidadVacante['id'] == $habilidadSolicitante['id']) {
                    $nivelVacante = $niveles[$habilidadVacante['nivel_requerido']] ?? 1;
                    $nivelSolicitante = $niveles[$habilidadSolicitante['nivel']] ?? 1;
    
                    // Incrementar coincidencias si el nivel del solicitante es igual o superior
                    if ($nivelSolicitante >= $nivelVacante) {
                        $coincidencias++;
                    }
                    break;
                }
            }
        }
    
        // Calcular porcentaje de coincidencia
        return round(($coincidencias / $totalHabilidadesVacante) * 100, 2);
    }

    // Método para postular a una vacante
    public function postularVacante($solicitanteId, $vacanteId) {
        try {
            // Verificar si ya existe la postulación
            $sqlVerificar = "SELECT COUNT(*) as count 
                             FROM postulaciones 
                             WHERE solicitante_id = ? AND vacante_id = ?";
            $stmtVerificar = $this->db->prepare($sqlVerificar);
            $stmtVerificar->bind_param("ii", $solicitanteId, $vacanteId);
            $stmtVerificar->execute();
            $resultVerificar = $stmtVerificar->get_result()->fetch_assoc();

            // Si ya existe la postulación, no permitir
            if ($resultVerificar['count'] > 0) {
                return false;
            }

            // Insertar nueva postulación
            $sqlPostular = "INSERT INTO postulaciones 
                            (solicitante_id, vacante_id, estado) 
                            VALUES (?, ?, 'pendiente')";
            $stmtPostular = $this->db->prepare($sqlPostular);
            $stmtPostular->bind_param("ii", $solicitanteId, $vacanteId);

            return $stmtPostular->execute();
        } catch (Exception $e) {
            error_log("Error al postular: " . $e->getMessage());
            return false;
        }
    }

    // Método para obtener postulaciones del solicitante
    public function obtenerPostulacionesSolicitante($solicitanteId) {
        $sql = "SELECT p.*, v.titulo AS titulo_vacante, e.nombre_empresa 
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                JOIN empresas e ON v.empresa_id = e.id
                WHERE p.solicitante_id = ?
                ORDER BY p.fecha_postulacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $solicitanteId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Método para obtener detalles de una postulación
    public function obtenerDetallePostulacion($postulacionId, $solicitanteId) {
        $sql = "SELECT p.*, v.titulo, v.descripcion, e.nombre_empresa 
                FROM postulaciones p
                JOIN vacantes v ON p.vacante_id = v.id
                JOIN empresas e ON v.empresa_id = e.id
                WHERE p.id = ? AND p.solicitante_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $postulacionId, $solicitanteId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Método para cancelar una postulación

    public function cancelarPostulacion($solicitanteId, $vacanteId) {
        try {
            $sql = "DELETE FROM postulaciones 
                    WHERE solicitante_id = ? AND vacante_id = ? 
                    AND estado = 'pendiente'";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $solicitanteId, $vacanteId);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al cancelar postulación: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarNivelHabilidad($solicitante_id, $habilidad_id, $nuevoNivel) {
        $sql = "UPDATE solicitante_habilidades 
                SET nivel = ? 
                WHERE solicitante_id = ? AND habilidad_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $nuevoNivel, $solicitante_id, $habilidad_id);
        return $stmt->execute();
    }

    // public function procesarHabilidades($solicitanteId, $habilidadesNuevas, $nivelesExistentes, $habilidadesRemovidas) {
    //     try {
    //         // Registro de datos entrantes
    //         error_log("Habilidades Nuevas: " . print_r($habilidadesNuevas, true));
    //         error_log("Niveles Existentes: " . print_r($nivelesExistentes, true));
    //         error_log("Habilidades Removidas: " . print_r($habilidadesRemovidas, true));

    //         // Procesar habilidades nuevas
    //         foreach ($habilidadesNuevas as $habilidadNueva) {
    //             $habilidadNombre = $habilidadNueva['nombre'] ?? '';
    //             $nivel = $habilidadNueva['nivel'] ?? 'basico';

    //             if (!empty($habilidadNombre)) {
    //                 $habilidad = $this->habilidadModel->obtenerHabilidadPorNombre($habilidadNombre);
    //                 $habilidadId = $habilidad ? $habilidad['id'] : null;

    //                 if (!$habilidadId) {
    //                     // Crear habilidad si no existe
    //                     $habilidadId = $this->habilidadModel->crearHabilidad($habilidadNombre, 'personalizada');

    //                     if (!$habilidadId) {
    //                         throw new Exception("No se pudo crear la habilidad: $habilidadNombre.");
    //                     }
    //                 }

    //                 // Agregar relación solicitante-habilidad con el nivel
    //                 $resultado = $this->solicitanteModel->agregarHabilidad($solicitanteId, $habilidadId, $nivel);
    //                 error_log("Resultado de agregarHabilidad: " . ($resultado ? 'éxito' : 'fallo'));
    //             }
    //         }

    //         // Procesar actualización de niveles de habilidades existentes
    //         foreach ($nivelesExistentes as $habilidadId => $nivel) {
    //             $resultado = $this->solicitanteModel->actualizarNivelHabilidad($solicitanteId, $habilidadId, $nivel);
    //             error_log("Resultado de actualizarNivelHabilidad (Habilidad ID $habilidadId): " . ($resultado ? 'éxito' : 'fallo'));
    //             if (!$resultado) {
    //                 throw new Exception("No se pudo actualizar el nivel de la habilidad con ID: $habilidadId.");
    //             }
    //         }

    //         // Eliminar relaciones de habilidades removidas
    //         foreach ($habilidadesRemovidas as $habilidadId) {
    //             $resultado = $this->solicitanteModel->eliminarHabilidad($solicitanteId, $habilidadId);
    //             error_log("Resultado de eliminarRelacionHabilidad (Habilidad ID $habilidadId): " . ($resultado ? 'éxito' : 'fallo'));
    //             if (!$resultado) {
    //                 throw new Exception("No se pudo eliminar la relación con la habilidad ID: $habilidadId.");
    //             }
    //         }

    //         // Redireccionar al dashboard después de procesar habilidades
    //         header("Location: dashboard_solicitante.php");
    //         exit();
    //     } catch (Exception $e) {
    //         error_log("Error al procesar habilidades: " . $e->getMessage());
    //         return false;
    //     }
    // }

    

    public function actualizarPerfil($solicitanteId, $nombreCompleto, $habilidades) {
    try {
        // Iniciar transacción
        $this->db->begin_transaction();

        // Actualizar nombre del solicitante
        $sqlNombre = "UPDATE solicitantes SET nombre_completo = ? WHERE id = ?";
        $stmtNombre = $this->db->prepare($sqlNombre);
        $stmtNombre->bind_param("si", $nombreCompleto, $solicitanteId);

        if (!$stmtNombre->execute()) {
            throw new Exception("Error al actualizar nombre");
        }

        // Eliminar habilidades existentes
        $sqlEliminarHabilidades = "DELETE FROM solicitante_habilidades WHERE solicitante_id = ?";
        $stmtEliminarHabilidades = $this->db->prepare($sqlEliminarHabilidades);
        $stmtEliminarHabilidades->bind_param("i", $solicitanteId);

        if (!$stmtEliminarHabilidades->execute()) {
            throw new Exception("Error al eliminar habilidades existentes");
        }

        // Preparar statement para insertar habilidades
        $sqlInsertHabilidad = "INSERT INTO solicitante_habilidades (solicitante_id, habilidad_id, nivel) VALUES (?, ?, ?)";
        $stmtInsertHabilidad = $this->db->prepare($sqlInsertHabilidad);

        // Procesar cada habilidad
        foreach ($habilidades as $habilidad => $nivel) {
            // Verificar si es una habilidad nueva o existente
            if (is_numeric($habilidad)) {
                // Es un ID de habilidad existente
                $habilidadId = $habilidad;
            } else {
                // Es una nueva habilidad, insertarla primero
                $sqlNuevaHabilidad = "INSERT INTO habilidades (nombre, tipo) VALUES (?, 'personalizada')";
                $stmtNuevaHabilidad = $this->db->prepare($sqlNuevaHabilidad);
                $stmtNuevaHabilidad->bind_param("s", $habilidad);
                $stmtNuevaHabilidad->execute();
                $habilidadId = $this->db->insert_id;
            }

            // Insertar relación solicitante-habilidad
            $stmtInsertHabilidad->bind_param("iis", $solicitanteId, $habilidadId, $nivel);

            if (!$stmtInsertHabilidad->execute()) {
                throw new Exception("Error al insertar habilidad");
            }
        }

        // Confirmar transacción
        $this->db->commit();
        return true;

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $this->db->rollback();
        error_log("Error en actualizarPerfil: " . $e->getMessage());
        return false;
    }
}
}
?>