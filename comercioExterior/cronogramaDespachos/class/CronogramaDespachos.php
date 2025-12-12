<?php
class CronogramaDespachos {
    private $cid_central;

    function __construct() {
        require_once __DIR__ . '/../../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);
    }

    /**
     * Obtiene todos los despachos para el cronograma
     */
    public function obtenerDespachos() {
        try {
            $sql = "SELECT A.ORDEN_COMPRA, A.COD_PROVEE, A.PROVEEDOR, A.CONTENEDOR, 
                    CAST(C.FECHA_INGRESO AS DATE) FECHA_ING_OC, A.FECHA_EST_EMB,
                    A.FECHA_EMB, A.FECHA_ARR, A.FECHA_DESP_ADU, D.FECHA_REC
                    FROM RO_T_IMPORTACIONES_ENCABEZADO A
                    LEFT JOIN RO_T_IMPORTACIONES_DETALLE B ON A.ID = B.ID_MG
                    LEFT JOIN CPA35 C ON A.ORDEN_COMPRA = C.N_ORDEN_CO
                    LEFT JOIN 
                    (
                        SELECT CAST(FECHA_MOV AS DATE) FECHA_REC, N_ORDEN_CO 
                        FROM STA20 
                        WHERE TCOMP_IN_S = 'RP' AND FECHA_MOV >= GETDATE()-360 
                        GROUP BY FECHA_MOV, N_ORDEN_CO
                    ) D ON A.ORDEN_COMPRA = D.N_ORDEN_CO
                    WHERE B.ID_MG IS NULL AND A.FECHA_MOV >= GETDATE()-360
                    ORDER BY A.FECHA_MOV DESC";
            
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error en la consulta: " . print_r($errors, true));
            }
            
            $despachos = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Formatear fechas
                if ($row['FECHA_ING_OC'] && is_object($row['FECHA_ING_OC'])) {
                    $row['FECHA_ING_OC'] = $row['FECHA_ING_OC']->format('Y-m-d');
                }
                if ($row['FECHA_EST_EMB'] && is_object($row['FECHA_EST_EMB'])) {
                    $row['FECHA_EST_EMB'] = $row['FECHA_EST_EMB']->format('Y-m-d');
                }
                if ($row['FECHA_EMB'] && is_object($row['FECHA_EMB'])) {
                    $row['FECHA_EMB'] = $row['FECHA_EMB']->format('Y-m-d');
                }
                if ($row['FECHA_ARR'] && is_object($row['FECHA_ARR'])) {
                    $row['FECHA_ARR'] = $row['FECHA_ARR']->format('Y-m-d');
                }
                if ($row['FECHA_DESP_ADU'] && is_object($row['FECHA_DESP_ADU'])) {
                    $row['FECHA_DESP_ADU'] = $row['FECHA_DESP_ADU']->format('Y-m-d');
                }
                if ($row['FECHA_REC'] && is_object($row['FECHA_REC'])) {
                    $row['FECHA_REC'] = $row['FECHA_REC']->format('Y-m-d');
                }
                
                $despachos[] = $row;
            }
            
            return $despachos;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Determina el estado actual del despacho
     */
    public static function determinarEstado($despacho) {
        if (!empty($despacho['FECHA_REC'])) {
            return 'recibido';
        } else if (!empty($despacho['FECHA_DESP_ADU'])) {
            return 'despachado';
        } else if (!empty($despacho['FECHA_ARR'])) {
            return 'arribado';
        } else if (!empty($despacho['FECHA_EMB'])) {
            return 'embarcado';
        } else {
            // Si solo existe FECHA_EST_EMB, está en origen
            return 'origen';
        }
    }
}
?>
