<?php
require_once __DIR__ . '/../../../class/conexion.php';

/**
 * Clase MotivoPagoServicio
 * Gestiona los motivos de pago de servicios desde la tabla RO_T_MOTIVOS_PAGO_SERVICIOS
 */
class MotivoPagoServicio {
    private $db;
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
        $this->db = $this->conexion->conectar('apps');
        
        if ($this->db === false) {
            throw new Exception("Error al conectar con la base de datos APPS en MotivoPagoServicio");
        }
    }
    
    /**
     * Obtiene todos los motivos activos ordenados
     */
    public function obtenerMotivosActivos(): array {
        try {
            $sql = "SELECT ID_MOTIVO, NOMBRE, DESCRIPCION, ORDEN 
                    FROM RO_T_MOTIVOS_PAGO_SERVICIOS 
                    WHERE ACTIVO = 1 
                    ORDER BY ORDEN, NOMBRE";
            
            $stmt = sqlsrv_query($this->db, $sql);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener motivos: " . print_r(sqlsrv_errors(), true));
            }
            
            $motivos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $motivos[] = [
                    'id' => $row['ID_MOTIVO'],
                    'nombre' => $row['NOMBRE'],
                    'descripcion' => $row['DESCRIPCION'],
                    'orden' => $row['ORDEN']
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            return $motivos;
        } catch (Exception $e) {
            error_log("Error en obtenerMotivosActivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un motivo por ID
     */
    public function obtenerMotivoPorId(int $id): ?array {
        try {
            $sql = "SELECT ID_MOTIVO, NOMBRE, DESCRIPCION, ACTIVO, ORDEN 
                    FROM RO_T_MOTIVOS_PAGO_SERVICIOS 
                    WHERE ID_MOTIVO = ?";
            
            $stmt = sqlsrv_query($this->db, $sql, [$id]);
            
            if ($stmt === false) {
                throw new Exception("Error al obtener motivo: " . print_r(sqlsrv_errors(), true));
            }
            
            $motivo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($motivo) {
                return [
                    'id' => $motivo['ID_MOTIVO'],
                    'nombre' => $motivo['NOMBRE'],
                    'descripcion' => $motivo['DESCRIPCION'],
                    'activo' => $motivo['ACTIVO'],
                    'orden' => $motivo['ORDEN']
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error en obtenerMotivoPorId: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica si un nombre de motivo existe (para validación)
     */
    public function existeMotivo(string $nombre): bool {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM RO_T_MOTIVOS_PAGO_SERVICIOS 
                    WHERE NOMBRE = ? AND ACTIVO = 1";
            
            $stmt = sqlsrv_query($this->db, $sql, [$nombre]);
            
            if ($stmt === false) {
                return false;
            }
            
            $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error en existeMotivo: " . $e->getMessage());
            return false;
        }
    }
}
