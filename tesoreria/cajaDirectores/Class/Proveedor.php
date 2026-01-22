<?php
require_once __DIR__ . '/../../../class/conexion.php';

/**
 * Clase Proveedor
 * Gestiona consultas de proveedores desde la tabla CPA01 en base CENTRAL (LAKER_SA)
 */
class Proveedor {
    private $dbCentral;
    private $conexion;
    
    public function __construct() {
        $this->conexion = new Conexion();
        $this->dbCentral = $this->conexion->conectar('central');
        
        if ($this->dbCentral === false) {
            throw new Exception("Error al conectar con la base de datos CENTRAL en Proveedor");
        }
    }
    
    /**
     * Busca proveedores por nombre o CUIT
     * @param string $termino Término de búsqueda
     * @param int $limite Cantidad máxima de resultados
     * @return array Array de proveedores encontrados
     */
    public function buscar($termino = '', $limite = 50) {
        try {
            // Consulta a la tabla CPA01 que está en LAKER_SA (servidor CENTRAL)
            $sql = "SELECT 
                        NOM_PROVEE, 
                        N_CUIT, 
                        ISNULL(CBU, '') as CBU, 
                        ISNULL(DESCRIPCION_CBU, '') as DESCRIPCION_CBU 
                    FROM CPA01 
                    WHERE NOM_PROVEE != ''";
            
            $params = [];
            
            // Si hay término de búsqueda, filtrar
            if (!empty($termino)) {
                $sql .= " AND (
                    NOM_PROVEE LIKE ? OR
                    N_CUIT LIKE ?
                )";
                $terminoBusqueda = '%' . $termino . '%';
                $params[] = $terminoBusqueda;
                $params[] = $terminoBusqueda;
            }
            
            $sql .= " ORDER BY NOM_PROVEE";
            
            // Agregar límite si SQL Server lo soporta con TOP
            if ($limite > 0) {
                $sql = str_replace('SELECT', "SELECT TOP {$limite}", $sql);
            }
            
            $stmt = sqlsrv_query($this->dbCentral, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en buscar proveedores SQL: " . print_r(sqlsrv_errors(), true));
                throw new Exception("Error al buscar proveedores");
            }
            
            $proveedores = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Limpiar y formatear datos
                $cbu = trim($row['CBU']);
                $descripcionCbu = trim($row['DESCRIPCION_CBU']);
                
                $proveedores[] = [
                    'nombre' => trim($row['NOM_PROVEE']),
                    'cuit' => trim($row['N_CUIT']),
                    'cbu' => $cbu,
                    'descripcion_cbu' => $descripcionCbu,
                    'tiene_cbu' => !empty($cbu),
                    'text' => trim($row['NOM_PROVEE']) . ' (' . trim($row['N_CUIT']) . ')'
                ];
            }
            
            sqlsrv_free_stmt($stmt);
            return $proveedores;
        } catch (Exception $e) {
            error_log("Error en buscar proveedores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un proveedor específico por nombre exacto
     * @param string $nombreProvee Nombre del proveedor
     * @return array|null Datos del proveedor o null si no existe
     */
    public function obtenerPorNombre($nombreProvee) {
        try {
            $sql = "SELECT 
                        NOM_PROVEE, 
                        N_CUIT, 
                        ISNULL(CBU, '') as CBU, 
                        ISNULL(DESCRIPCION_CBU, '') as DESCRIPCION_CBU 
                    FROM CPA01 
                    WHERE NOM_PROVEE = ?";
            
            $stmt = sqlsrv_query($this->dbCentral, $sql, [$nombreProvee]);
            
            if ($stmt === false) {
                error_log("Error en obtenerPorNombre SQL: " . print_r(sqlsrv_errors(), true));
                throw new Exception("Error al obtener proveedor");
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row) {
                $cbu = trim($row['CBU']);
                return [
                    'nombre' => trim($row['NOM_PROVEE']),
                    'cuit' => trim($row['N_CUIT']),
                    'cbu' => $cbu,
                    'descripcion_cbu' => trim($row['DESCRIPCION_CBU']),
                    'tiene_cbu' => !empty($cbu)
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error al obtener proveedor por nombre: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene todos los proveedores (con límite para no sobrecargar)
     * @param int $limite Cantidad máxima de resultados
     * @return array Array de proveedores
     */
    public function obtenerTodos($limite = 100) {
        return $this->buscar('', $limite);
    }
    
    /**
     * Verifica si un CBU tiene formato válido (22 dígitos)
     * @param string $cbu CBU a validar
     * @return bool True si es válido
     */
    public static function validarCBU($cbu) {
        // CBU debe tener exactamente 22 dígitos
        $cbu = preg_replace('/\s/', '', $cbu); // Remover espacios
        return preg_match('/^\d{22}$/', $cbu) === 1;
    }
    
    /**
     * Formatea un CUIT con guiones (XX-XXXXXXXX-X)
     * @param string $cuit CUIT sin formato
     * @return string CUIT formateado
     */
    public static function formatearCUIT($cuit) {
        // Remover caracteres no numéricos
        $cuit = preg_replace('/\D/', '', $cuit);
        
        // Si tiene 11 dígitos, formatear con guiones
        if (strlen($cuit) === 11) {
            return substr($cuit, 0, 2) . '-' . substr($cuit, 2, 8) . '-' . substr($cuit, 10, 1);
        }
        
        return $cuit;
    }
}
