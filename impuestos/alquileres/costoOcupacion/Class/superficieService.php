<?php

/**
 * Servicio para gestión de superficies de sucursales
 * Obtiene datos de metros cuadrados desde FP_T_SUCURSALES_MEDIDAS
 */
class SuperficieService
{
    private $cid_central;
    
    public function __construct()
    {
        // Session should already be started by controller
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        require_once $_SERVER['DOCUMENT_ROOT'].'/administracion/class/conexion.php';
        
        $cid = new Conexion();
        
        // Determinar entorno antes de conectar
        $entorno = isset($_SESSION['entorno']) ? $_SESSION['entorno'] : 'central';
        
        if ($entorno == 'uy') {
            $this->cid_central = $cid->conectar('uy');
        } else {
            $this->cid_central = $cid->conectar('central');
        }
    }
    
    /**
     * Obtiene la superficie total (M2_VENTA + M2_DEPOSITO + M2_BAULERA) de una sucursal
     * @param int $nroSucursal Número de sucursal
     * @return float|null Superficie total en M2, null si no se encuentra
     */
    public function obtenerSuperficie($nroSucursal)
    {
        try {
            $sql = "SELECT 
                        NRO_SUCURS, 
                        (ISNULL(M2_VENTA, 0) + ISNULL(M2_DEPOSITO, 0) + ISNULL(M2_BAULERA, 0)) AS SUPERFICIE 
                    FROM [XL-APPS].sistemas.dbo.FP_T_SUCURSALES_MEDIDAS
                    WHERE NRO_SUCURS = ?";
            
            $params = array($nroSucursal);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en query obtenerSuperficie: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row && isset($row['SUPERFICIE'])) {
                return floatval($row['SUPERFICIE']);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error en obtenerSuperficie: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene las superficies de todas las sucursales
     * @param array $nrosSucursales Array de números de sucursales (opcional)
     * @return array Array asociativo [nro_sucursal => superficie]
     */
    public function obtenerSuperficies($nrosSucursales = null)
    {
        try {
            $sql = "SELECT 
                        NRO_SUCURS, 
                        (ISNULL(M2_VENTA, 0) + ISNULL(M2_DEPOSITO, 0) + ISNULL(M2_BAULERA, 0)) AS SUPERFICIE 
                    FROM [XL-APPS].sistemas.dbo.FP_T_SUCURSALES_MEDIDAS";
            
            $params = array();
            
            if ($nrosSucursales !== null && is_array($nrosSucursales) && count($nrosSucursales) > 0) {
                $placeholders = implode(',', array_fill(0, count($nrosSucursales), '?'));
                $sql .= " WHERE NRO_SUCURS IN ($placeholders)";
                $params = $nrosSucursales;
            }
            
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en query obtenerSuperficies: " . print_r(sqlsrv_errors(), true));
                return array();
            }
            
            $superficies = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $superficies[$row['NRO_SUCURS']] = floatval($row['SUPERFICIE']);
            }
            
            sqlsrv_free_stmt($stmt);
            
            return $superficies;
            
        } catch (Exception $e) {
            error_log("Error en obtenerSuperficies: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Obtiene información detallada de superficies (desglosada)
     * @param int $nroSucursal Número de sucursal
     * @return array|null Array con M2_VENTA, M2_DEPOSITO, M2_BAULERA y SUPERFICIE, null si no se encuentra
     */
    public function obtenerSuperficieDetallada($nroSucursal)
    {
        try {
            $sql = "SELECT 
                        NRO_SUCURS,
                        ISNULL(M2_VENTA, 0) AS M2_VENTA,
                        ISNULL(M2_DEPOSITO, 0) AS M2_DEPOSITO,
                        ISNULL(M2_BAULERA, 0) AS M2_BAULERA,
                        (ISNULL(M2_VENTA, 0) + ISNULL(M2_DEPOSITO, 0) + ISNULL(M2_BAULERA, 0)) AS SUPERFICIE 
                    FROM [XL-APPS].sistemas.dbo.FP_T_SUCURSALES_MEDIDAS
                    WHERE NRO_SUCURS = ?";
            
            $params = array($nroSucursal);
            $stmt = sqlsrv_query($this->cid_central, $sql, $params);
            
            if ($stmt === false) {
                error_log("Error en query obtenerSuperficieDetallada: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            
            if ($row) {
                return array(
                    'nro_sucursal' => $row['NRO_SUCURS'],
                    'm2_venta' => floatval($row['M2_VENTA']),
                    'm2_deposito' => floatval($row['M2_DEPOSITO']),
                    'm2_baulera' => floatval($row['M2_BAULERA']),
                    'superficie_total' => floatval($row['SUPERFICIE'])
                );
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Error en obtenerSuperficieDetallada: " . $e->getMessage());
            return null;
        }
    }
}
