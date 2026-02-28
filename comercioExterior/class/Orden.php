<?php
class Orden{

    function __construct() {

        require_once __DIR__ . '/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    }

    public function listarOrden($idEncabezado = null){
        
        $date = date('Y-m-d', strtotime("-90 days"));
        $sql = "SELECT ID,FECHA_MOV,COD_PROVEE,PROVEEDOR,DESPACHO,ORDEN_COMPRA,VALOR_FOB_PESO FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE FECHA_MOV > '$date' ORDER BY FECHA_MOV DESC";
        if($idEncabezado != null){
            $sql = " SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = $idEncabezado ";
        }
     
        try{
        $stmt = sqlsrv_query( $this->cid_central, $sql );

        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt) ) {
            $rows[] = $v;
        }
        return ($rows);
        

        } catch (\Throwable $th) {

        print_r($th);

        }
    }

    public function traerPorOrdenCompra($id) {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG =".$id.";";
        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
            $rows = array();
            
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }

            foreach ($rows as $key => &$value) {
                foreach ($value as $k => &$v) {
                    if((gettype($v)== 'string') && substr($v, 0, 1) == '.'){
                        $v = '0'.$v;
                    }
                }
            }

            // print_r($rows);
            // die();

            return ($rows);
            
    
            } catch (\Throwable $th) {
    
            print_r($th);
    
            }
    }
    public function traerOrdenPorFecha($desde, $hasta) {

        $sql = "SELECT ID, FECHA_MOV, A.FECHA_DESP_ADU,  CONTENEDOR, COD_PROVEE, PROVEEDOR, DESPACHO, A.ORDEN_COMPRA, VALOR_FOB_PESO, (COSTO_NAC*100) COSTO_NAC FROM RO_T_IMPORTACIONES_ENCABEZADO A
                LEFT JOIN RO_W_COSTO_NACIONALIZACION B ON A.ORDEN_COMPRA = B.ORDEN_COMPRA
                WHERE A.FECHA_DESP_ADU BETWEEN '$desde' AND '$hasta' 
                ORDER BY A.FECHA_DESP_ADU DESC;";
        
        try{
            $stmt = sqlsrv_query( $this->cid_central, $sql );
    
            $rows = array();
    
            while( $v = sqlsrv_fetch_array( $stmt) ) {
                $rows[] = $v;
            }
            return ($rows);
            
    
            } catch (\Throwable $th) {
    
            print_r($th);
    
            }
    }

    /**
     * Inserta/actualiza RO_COSTOS_NACIONALIZACION directamente,
     * sin depender de la vista RO_W_COSTO_NACIONALIZACION ni del SP.
     * Obtiene FECHA_DESP_ADU desde el encabezado usando idEncabezado.
     */
    public function insertarCostoNacionalizacion($nroOrden, $idEncabezado, $costoNac) {

        $nroOrden = trim($nroOrden);
        if (strlen($nroOrden) == 13) {
            $nroOrden = ' ' . $nroOrden;
        }

        // Paso 1: Obtener FECHA_DESP_ADU y ORDEN_COMPRA del encabezado
        $sqlEnc = "SELECT ORDEN_COMPRA, FECHA_DESP_ADU FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = " . intval($idEncabezado);
        $stmtEnc = sqlsrv_query($this->cid_central, $sqlEnc);
        if ($stmtEnc === false) {
            $errors = sqlsrv_errors();
            error_log('[insertarCostoNacionalizacion] Error buscando encabezado ID=' . $idEncabezado . ': ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al buscar el encabezado ID=' . $idEncabezado];
        }
        $enc = sqlsrv_fetch_array($stmtEnc, SQLSRV_FETCH_ASSOC);
        if (!$enc) {
            error_log('[insertarCostoNacionalizacion] No se encontró encabezado ID=' . $idEncabezado);
            return ['success' => false, 'message' => 'No se encontró el encabezado ID=' . $idEncabezado];
        }

        $ordenCompraReal = $enc['ORDEN_COMPRA']; // Usar el valor exacto de la BD
        $fechaDesp       = $enc['FECHA_DESP_ADU']; // DateTime o null

        error_log('[insertarCostoNacionalizacion] Encabezado encontrado. ORDEN_COMPRA="' . $ordenCompraReal . '" FECHA_DESP_ADU=' . ($fechaDesp ? $fechaDesp->format('Y-m-d') : 'NULL'));

        // Paso 2: DELETE del registro previo usando el valor exacto de la BD
        $sqlDelete = "DELETE FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = '" . str_replace("'", "''", $ordenCompraReal) . "'";
        $stmtDelete = sqlsrv_query($this->cid_central, $sqlDelete);
        if ($stmtDelete === false) {
            $errors = sqlsrv_errors();
            error_log('[insertarCostoNacionalizacion] Error DELETE: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al eliminar registro previo'];
        }

        // Paso 3: INSERT directo con valores concretos
        $fechaDespStr = $fechaDesp ? "'" . $fechaDesp->format('Y-m-d') . "'" : 'NULL';
        $costoNacVal  = floatval($costoNac);
        $ordenEsc     = str_replace("'", "''", $ordenCompraReal);

        $sqlInsert = "INSERT INTO RO_COSTOS_NACIONALIZACION (FECHA_DESP, N_ORDEN_CO, COSTO_NAC, FECHA_MODIF)
                      VALUES ($fechaDespStr, '$ordenEsc', $costoNacVal, GETDATE())";

        error_log('[insertarCostoNacionalizacion] SQL INSERT: ' . $sqlInsert);

        $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert);
        if ($stmtInsert === false) {
            $errors = sqlsrv_errors();
            error_log('[insertarCostoNacionalizacion] Error INSERT: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al insertar: ' . print_r($errors, true)];
        }

        return ['success' => true, 'message' => 'Costo de nacionalización actualizado correctamente'];
    }

    public function updateCostoNacionalizacion($nroOrden){

        $nroOrden = trim($nroOrden);
        if(strlen($nroOrden) == 13){
            $nroOrden = ' '.$nroOrden;
        }

        // DELETE previo
        $sqlDelete = "DELETE FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = ?";
        $stmtDelete = sqlsrv_prepare($this->cid_central, $sqlDelete, array(&$nroOrden));
        if ($stmtDelete === false || sqlsrv_execute($stmtDelete) === false) {
            $errors = sqlsrv_errors();
            error_log('[updateCostoNacionalizacion] Error DELETE: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al eliminar registro previo: ' . print_r($errors, true)];
        }

        // Ejecutar SP
        $sqlExec = "EXEC RO_SP_INSERTAR_COSTO_NACIONALIZACION ?";
        ini_set('max_execution_time', 300);
        $stmtExec = sqlsrv_prepare($this->cid_central, $sqlExec, array(&$nroOrden));
        if ($stmtExec === false || sqlsrv_execute($stmtExec) === false) {
            $errors = sqlsrv_errors();
            error_log('[updateCostoNacionalizacion] Error EXEC SP: ' . print_r($errors, true));
            return ['success' => false, 'message' => 'Error al ejecutar SP: ' . print_r($errors, true)];
        }

        // Verificar cuántas filas insertó
        $sqlCheck = "SELECT COUNT(*) AS total FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = ?";
        $stmtCheck = sqlsrv_prepare($this->cid_central, $sqlCheck, array(&$nroOrden));
        sqlsrv_execute($stmtCheck);
        $row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
        $filasInsertadas = $row ? intval($row['total']) : 0;

        if ($filasInsertadas === 0) {
            error_log('[updateCostoNacionalizacion] SP ejecutado pero no insertó filas. N_ORDEN_CO: ' . $nroOrden);
            return ['success' => false, 'message' => 'El SP no encontró datos en RO_W_COSTO_NACIONALIZACION para la orden: ' . trim($nroOrden)];
        }

        return ['success' => true, 'message' => 'Costo de nacionalización actualizado correctamente'];

    }

    public function eliminarDespacho($id) {
        try {
            // Primero eliminar los detalles de estimación
            $sqlEstimacionDetalle = "DELETE FROM RO_T_IMPORTACIONES_ESTIMACION_DETALLE WHERE ID_MG = ?";
            $stmtEstimacionDetalle = sqlsrv_prepare($this->cid_central, $sqlEstimacionDetalle, array(&$id));
            
            if ($stmtEstimacionDetalle === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar eliminación de detalles de estimación: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmtEstimacionDetalle)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al eliminar detalles de estimación: " . print_r($errors, true));
            }
            
            // Luego eliminar los detalles del despacho
            $sqlDetalle = "DELETE FROM RO_T_IMPORTACIONES_DETALLE WHERE ID_MG = ?";
            $stmtDetalle = sqlsrv_prepare($this->cid_central, $sqlDetalle, array(&$id));
            
            if ($stmtDetalle === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar eliminación de detalles: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmtDetalle)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al eliminar detalles del despacho: " . print_r($errors, true));
            }
            
            // Finalmente eliminar el encabezado
            $sqlEncabezado = "DELETE FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?";
            $stmtEncabezado = sqlsrv_prepare($this->cid_central, $sqlEncabezado, array(&$id));
            
            if ($stmtEncabezado === false) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al preparar eliminación de encabezado: " . print_r($errors, true));
            }
            
            if (!sqlsrv_execute($stmtEncabezado)) {
                $errors = sqlsrv_errors();
                throw new Exception("Error al eliminar encabezado del despacho: " . print_r($errors, true));
            }
            
            return true;
            
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

}