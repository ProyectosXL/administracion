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

        $sql = "SELECT
                    A.ID,
                    A.FECHA_MOV,
                    A.FECHA_DESP_ADU,
                    A.CONTENEDOR,
                    A.COD_PROVEE,
                    A.PROVEEDOR,
                    A.DESPACHO,
                    A.ORDEN_COMPRA,
                    A.VALOR_FOB_PESO,
                    (COSTO_NAC * 100) AS COSTO_NAC,
                    A.ID_PADRE,
                    (SELECT P.ORDEN_COMPRA
                     FROM RO_T_IMPORTACIONES_ENCABEZADO P
                     WHERE P.ID = A.ID_PADRE) AS ORDEN_COMPRA_PADRE
                FROM RO_T_IMPORTACIONES_ENCABEZADO A
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
     * Inserta/actualiza RO_COSTOS_NACIONALIZACION para TODAS las OCs del grupo.
     * Si el encabezado recibido es una OC hija, primero resuelve al principal
     * y luego replica el mismo COSTO_NAC a cada OC del grupo.
     */
    public function insertarCostoNacionalizacion($nroOrden, $idEncabezado, $costoNac) {

        require_once __DIR__ . '/encabezado.php';
        $encabezadoClass = new Encabezado();

        $idPrincipal  = $encabezadoClass->resolverIdPrincipal($idEncabezado);
        $ordenesGrupo = $encabezadoClass->obtenerOrdenesDelGrupo($idEncabezado);

        if (empty($ordenesGrupo)) {
            error_log('[insertarCostoNacionalizacion] No se encontraron OCs para idEncabezado=' . $idEncabezado);
            return ['success' => false, 'message' => 'No se encontró el encabezado ID=' . $idEncabezado];
        }

        $costoNacVal = floatval($costoNac);

        foreach ($ordenesGrupo as $ocReal) {
            $ocStr = is_array($ocReal) ? $ocReal['ORDEN_COMPRA'] : $ocReal;
            $ocEsc = str_replace("'", "''", $ocStr);

            // DELETE previo para esta OC
            $sqlDelete = "DELETE FROM RO_COSTOS_NACIONALIZACION WHERE N_ORDEN_CO = '$ocEsc'";
            $stmtDelete = sqlsrv_query($this->cid_central, $sqlDelete);
            if ($stmtDelete === false) {
                $errors = sqlsrv_errors();
                error_log('[insertarCostoNacionalizacion] Error DELETE OC=' . $ocStr . ': ' . print_r($errors, true));
                return ['success' => false, 'message' => 'Error al eliminar registro previo para OC: ' . $ocStr];
            }

            // Obtener FECHA_DESP_ADU del encabezado que corresponda a esta OC
            $sqlFecha = "SELECT FECHA_DESP_ADU FROM RO_T_IMPORTACIONES_ENCABEZADO
                         WHERE ORDEN_COMPRA = '$ocEsc'";
            $stmtFecha = sqlsrv_query($this->cid_central, $sqlFecha);
            $rowFecha = ($stmtFecha !== false) ? sqlsrv_fetch_array($stmtFecha, SQLSRV_FETCH_ASSOC) : null;

            $fechaDespStr = ($rowFecha && $rowFecha['FECHA_DESP_ADU'])
                ? "'" . $rowFecha['FECHA_DESP_ADU']->format('Y-m-d') . "'"
                : 'NULL';

            $sqlInsert = "INSERT INTO RO_COSTOS_NACIONALIZACION (FECHA_DESP, N_ORDEN_CO, COSTO_NAC, FECHA_MODIF)
                          VALUES ($fechaDespStr, '$ocEsc', $costoNacVal, GETDATE())";

            $stmtInsert = sqlsrv_query($this->cid_central, $sqlInsert);
            if ($stmtInsert === false) {
                $errors = sqlsrv_errors();
                error_log('[insertarCostoNacionalizacion] Error INSERT OC=' . $ocReal . ': ' . print_r($errors, true));
                return ['success' => false, 'message' => 'Error al insertar costo para OC: ' . $ocReal];
            }
        }

        return [
            'success' => true,
            'message' => 'Costo de nacionalización actualizado en ' . count($ordenesGrupo) . ' OC(s)'
        ];
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