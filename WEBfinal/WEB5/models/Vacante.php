<?php
class Vacante {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // Método para crear una nueva vacante
    public function crearVacante($empresaId, $titulo, $descripcion, $requisitos = null, $habilidades = []) {
        try {
            // Iniciar transacción
            $this->db->begin_transaction();

            // Insertar vacante
            $sql = "INSERT INTO vacantes 
                    (empresa_id, titulo, descripcion, requisitos, estado) 
                    VALUES (?, ?, ?, ?, 'disponible')";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isss", $empresaId, $titulo, $descripcion, $requisitos);

            if (!$stmt->execute()) {
                throw new Exception("Error al crear vacante");
            }

            $vacanteId = $stmt->insert_id;

            // Insertar habilidades requeridas
            if (!empty($habilidades)) {
                $this->agregarHabilidadesVacante($vacanteId, $habilidades);
            }

            // Confirmar transacción
            $this->db->commit();

            return $vacanteId;
        } catch (Exception $e) {
            // Revertir transacción
            $this->db->rollback();
            error_log("Error al crear vacante: " . $e->getMessage());
            return false;
        }
    }

    // Método para agregar habilidades a una vacante
    private function agregarHabilidadesVacante($vacanteId, $habilidades) {
        $sql = "INSERT INTO vacante_habilidades 
                (vacante_id, habilidad_id, nivel_requerido) 
                VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        foreach ($habilidades as $habilidad) {
            $stmt->bind_param("iis",
                $vacanteId,
                $habilidad['habilidad_id'],
                $habilidad['nivel_requerido']
            );
            $stmt->execute();
        }
    }

    // Método para obtener vacantes disponibles
    public function obtenerVacantesDisponibles($filtros = [], $postulanteId = '') {
        $sql = "SELECT v.*, e.nombre_empresa 
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.id
                LEFT OUTER JOIN postulaciones p ON p.vacante_id = v.id AND p.estado <> 'rechazada'
                WHERE v.estado = 'disponible'";

        if (!empty($postulanteId)) {
            $sql .= " AND p.solicitante_id = ". $postulanteId;
        }
				  

        // Aplicar filtros si existen
        if (!empty($filtros['titulo'])) {
            $titulo = $this->db->real_escape_string($filtros['titulo']);
            $sql .= " AND v.titulo LIKE '%$titulo%'";
        }

        // Preparar y ejecutar la consulta
        $stmt = $this->db->prepare($sql);

        if ($stmt === false) {
            error_log("Error preparando consulta: " . $this->db->error);
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            error_log("Error ejecutando consulta: " . $stmt->error);
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Método para obtener una vacante por ID
    public function obtenerVacantePorId($vacanteId) {
        $sql = "SELECT v.*, e.nombre_empresa 
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.id
                WHERE v.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $vacanteId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Método para obtener habilidades de una vacante
    public function obtenerHabilidadesVacante($vacanteId) {
        $sql = "SELECT h.id, h.nombre, vh.nivel_requerido 
                FROM vacante_habilidades vh
                JOIN habilidades h ON vh.habilidad_id = h.id
                WHERE vh.vacante_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $vacanteId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Método para actualizar una vacante
    public function actualizarVacante($vacanteId, $datos, $habilidades = null) {
        try {
            // Iniciar transacción
            $this->db->begin_transaction();
    
            // Preparar campos para actualizar
            $campos = [];
            $tipos = '';
            $valores = [];
    
            // Verificar y agregar cada campo
            $camposPermitidos = ['titulo', 'descripcion', 'requisitos', 'estado'];
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $campos[] = "$campo = ?";
                    $tipos .= 's';
                    $valores[] = $datos[$campo];
                }
            }
    
            // Si hay campos para actualizar
            if (!empty($campos)) {
                $tipos .= 'i';
                $valores[] = $vacanteId;
    
                $sql = "UPDATE vacantes SET " . implode(', ', $campos) . " WHERE id = ?";
                $stmt = $this->db->prepare($sql);
    
                // Bind dinámico de parámetros
                $bindParams = array_merge([$tipos], $valores);
                call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
    
                if (!$stmt->execute()) {
                    // Agregar log de error
                    error_log("Error al actualizar vacante: " . $stmt->error);
                    throw new Exception("Error al actualizar vacante");
                }
            }
    
            // Actualizar habilidades si se proporcionan
            if ($habilidades !== null) {
                // Eliminar habilidades existentes
                $sqlEliminar = "DELETE FROM vacante_habilidades WHERE vacante_id = ?";
                $stmtEliminar = $this->db->prepare($sqlEliminar);
                $stmtEliminar->bind_param("i", $vacanteId);
                $stmtEliminar->execute();
    
                // Agregar nuevas habilidades
                $this->agregarHabilidadesVacante($vacanteId, $habilidades);
            }
    
            // Confirmar transacción
            $this->db->commit();
    
            return true;
        } catch (Exception $e) {
            // Revertir transacción
            $this->db->rollback();
            error_log("Error al actualizar vacante: " . $e->getMessage());
            return false;
        }
    }

    // Método para eliminar una vacante
    public function eliminarVacante($vacanteId) {
        try {
            // Iniciar transacción
            $this->db->begin_transaction();

            // Eliminar habilidades de la vacante
            $sqlHabilidades = "DELETE FROM vacante_habilidades WHERE vacante_id = ?";
            $stmtHabilidades = $this->db->prepare($sqlHabilidades);
            $stmtHabilidades->bind_param("i", $vacanteId);
            $stmtHabilidades->execute();

            // // Eliminar la vacante
            $sqlVacante = "DELETE FROM vacantes WHERE id = ?";
            $stmtVacante = $this->db->prepare($sqlVacante);
            $stmtVacante->bind_param("i", $vacanteId);
            $stmtVacante->execute();

            // Confirmar transacción
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Revertir transacción
            $this->db->rollback();
            error_log("Error al eliminar vacante: " . $e->getMessage());
            return false;
        }
    }

    // Método para obtener todas las vacantes de una empresa
    public function obtenerVacantesPorEmpresa($empresaId) {
        $sql = "SELECT v.*, e.nombre_empresa 
                FROM vacantes v
                JOIN empresas e ON v.empresa_id = e.id
                WHERE v.empresa_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $empresaId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Método para referenciar valores para bind_param
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
}
?>