
<?php

class Encabezado
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $db = (isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'uy') ? 'uy' : 'central';
        $this->cid_central = $cid->conectar($db);

    } 

    public function insertarEncabezado($datosDeCabezera){   
        
        $codProv = substr($datosDeCabezera['cod_proveedor'], 0, 6);
        
        // Sección 1 - Datos Iniciales (campos obligatorios)
        $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(
            FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, MATERIAL, ORIGEN, 
            VALOR_FOB_DOLAR, FECHA_EST_EMB, ORDEN_COMPRA, OCM";
        
        $values = "VALUES (
            GETDATE(),
            '".$codProv."',
            '".$datosDeCabezera['proveedor']."',
            '".$datosDeCabezera['contenedor']."',
            '".$datosDeCabezera['material']."',
            '".$datosDeCabezera['origen']."',
            '".$datosDeCabezera['valorFobDolar']."',
            '".$datosDeCabezera['fechaEstEmb']."',
            '".$datosDeCabezera['ordenCompra']."',
            '".$datosDeCabezera['ocm']."'";
        
        // Campos calculados automáticamente que se guardan desde la Sección 1
        if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
            $sql .= ", FECHA_ARR";
            $values .= ", '".$datosDeCabezera['fechaArr']."'";
        }
        
        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $sql .= ", FECHA_PAGO";
            $values .= ", '".$datosDeCabezera['fechaPago']."'";
        }
        
        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $sql .= ", FECHA_DESP_ADU";
            $values .= ", '".$datosDeCabezera['fechaDespAdu']."'";
        }
        
        // Campos opcionales de secciones 2 y 3 (solo si vienen en el array)
        // FECHA_EMB es el ETD (Estimated Time of Departure - fecha de salida real)
        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $sql .= ", FECHA_EMB";
            $values .= ", '".$datosDeCabezera['fechaEmb']."'";
        }
        
        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $sql .= ", NUMERO_BL";
            $values .= ", '".$datosDeCabezera['numeroBl']."'";
        }
        
        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $sql .= ", FACTURA";
            $values .= ", '".$datosDeCabezera['facturaProveedor']."'";
        }
        
        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $sql .= ", TIPO_CAMBIO";
            $values .= ", '".$datosDeCabezera['tipoCambio']."'";
        }
        
        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $sql .= ", VALOR_FOB_PESO";
            $values .= ", '".(float)($datosDeCabezera['valorFobPeso'] + 0.15)."'";
        }
        
        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $sql .= ", FORMA_PAGO";
            $values .= ", '".$datosDeCabezera['formaPago']."'";
        }
        
        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $sql .= ", DESPACHO";
            $values .= ", '".$datosDeCabezera['despacho']."'";
        }
        
        $sql .= ") " . $values . ");";

        // Log del SQL para debugging
        error_log("SQL a ejecutar: " . $sql);
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                $errors = sqlsrv_errors();
                error_log("Error en INSERT: " . print_r($errors, true));
                throw new Exception("Error al insertar: " . $errors[0]['message']);
            }
            
            $queryIdentity = "SELECT @@IDENTITY as ID";
            $stmtIdentity = sqlsrv_query($this->cid_central, $queryIdentity);
        
            if ($stmtIdentity === false) {
                error_log("Error al obtener ID: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmtIdentity, SQLSRV_FETCH_ASSOC);
            $id = $row ? $row['ID'] : null;
            
            error_log("ID insertado: " . $id);
            return $id;

        } catch (Exception $e) {
            error_log('Excepción en insertarEncabezado: ' . $e->getMessage());
            throw $e;
        }

    }  
    
    public function traerOrdenManual (){
        $sql = "SELECT MAX(cast (RIGHT(ORDEN_COMPRA,'8')as INT)+1 ) AS nroOrden FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE OCM = 1;";

        $stmt = sqlsrv_query($this->cid_central, $sql);
        
        $rows = array();

        while( $v = sqlsrv_fetch_array( $stmt,SQLSRV_FETCH_ASSOC) ) {

            $rows = $v;
        }
        return ($rows);

    }
    
    public function listarTodosLosDespachos() {
        $sql = "SELECT TOP 100
                A.ID,
                FECHA_MOV,
                COD_PROVEE,
                PROVEEDOR,
                CONTENEDOR,
                MATERIAL,
                ORDEN_COMPRA,
                FECHA_EST_EMB,
                FECHA_EMB,
                NUMERO_BL,
                FACTURA,
                A.TIPO_CAMBIO,
                VALOR_FOB_DOLAR
            FROM RO_T_IMPORTACIONES_ENCABEZADO A
            LEFT JOIN RO_T_IMPORTACIONES_DETALLE B ON A.ID = B.ID_MG
            WHERE DESPACHO IS NULL OR B.ID_MG IS NULL
            AND A.FECHA_MOV >= GETDATE()-90
            ORDER BY ID DESC";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                error_log("Error en listarTodosLosDespachos: " . print_r(sqlsrv_errors(), true));
                return [];
            }
            
            $despachos = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                // Convertir objetos DateTime a strings
                if (isset($row['FECHA_MOV']) && is_object($row['FECHA_MOV'])) {
                    $row['FECHA_MOV'] = $row['FECHA_MOV']->format('Y-m-d');
                }
                if (isset($row['FECHA_EST_EMB']) && is_object($row['FECHA_EST_EMB'])) {
                    $row['FECHA_EST_EMB'] = $row['FECHA_EST_EMB']->format('Y-m-d');
                }
                if (isset($row['FECHA_EMB']) && is_object($row['FECHA_EMB'])) {
                    $row['FECHA_EMB'] = $row['FECHA_EMB']->format('Y-m-d');
                }
                
                $despachos[] = $row;
            }
            
            return $despachos;
            
        } catch (Exception $e) {
            error_log('Error en listarTodosLosDespachos: ' . $e->getMessage());
            return [];
        }
    }

    public function obtenerDespachoPorId($id) {
        $sql = "SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ID = ?";
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql, array($id));
            
            if ($stmt === false) {
                error_log("Error en obtenerDespachoPorId: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            if ($row) {
                // Convertir objetos DateTime a strings en formato DD/MM/YYYY
                $dateFields = ['FECHA_MOV', 'FECHA_EST_EMB', 'FECHA_EMB', 'FECHA_ARR', 
                              'FECHA_PAGO', 'FECHA_DESP_ADU'];
                
                foreach ($dateFields as $field) {
                    if (isset($row[$field]) && is_object($row[$field])) {
                        $row[$field] = $row[$field]->format('d/m/Y');
                    }
                }
                
                return $row;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('Error en obtenerDespachoPorId: ' . $e->getMessage());
            return null;
        }
    }

    public function actualizarEncabezado($id, $datosDeCabezera) {
        $codProv = substr($datosDeCabezera['cod_proveedor'], 0, 6);
        
        // Construir UPDATE dinámico
        $sql = "UPDATE RO_T_IMPORTACIONES_ENCABEZADO SET ";
        $updates = [];
        
        // Campos que siempre se actualizan
        $updates[] = "COD_PROVEE = '".$codProv."'";
        $updates[] = "PROVEEDOR = '".$datosDeCabezera['proveedor']."'";
        $updates[] = "CONTENEDOR = '".$datosDeCabezera['contenedor']."'";
        $updates[] = "MATERIAL = '".$datosDeCabezera['material']."'";
        $updates[] = "ORIGEN = '".$datosDeCabezera['origen']."'";
        $updates[] = "VALOR_FOB_DOLAR = '".$datosDeCabezera['valorFobDolar']."'";
        $updates[] = "FECHA_EST_EMB = '".$datosDeCabezera['fechaEstEmb']."'";
        $updates[] = "ORDEN_COMPRA = '".$datosDeCabezera['ordenCompra']."'";
        $updates[] = "OCM = '".$datosDeCabezera['ocm']."'";
        
        // Campos opcionales
        if (isset($datosDeCabezera['fechaArr']) && !empty($datosDeCabezera['fechaArr'])) {
            $updates[] = "FECHA_ARR = '".$datosDeCabezera['fechaArr']."'";
        }
        
        if (isset($datosDeCabezera['fechaPago']) && !empty($datosDeCabezera['fechaPago'])) {
            $updates[] = "FECHA_PAGO = '".$datosDeCabezera['fechaPago']."'";
        }
        
        if (isset($datosDeCabezera['fechaDespAdu']) && !empty($datosDeCabezera['fechaDespAdu'])) {
            $updates[] = "FECHA_DESP_ADU = '".$datosDeCabezera['fechaDespAdu']."'";
        }
        
        if (isset($datosDeCabezera['fechaEmb']) && !empty($datosDeCabezera['fechaEmb'])) {
            $updates[] = "FECHA_EMB = '".$datosDeCabezera['fechaEmb']."'";
        }
        
        if (isset($datosDeCabezera['numeroBl']) && !empty($datosDeCabezera['numeroBl'])) {
            $updates[] = "NUMERO_BL = '".$datosDeCabezera['numeroBl']."'";
        }
        
        if (isset($datosDeCabezera['facturaProveedor']) && !empty($datosDeCabezera['facturaProveedor'])) {
            $updates[] = "FACTURA = '".$datosDeCabezera['facturaProveedor']."'";
        }
        
        if (isset($datosDeCabezera['tipoCambio']) && !empty($datosDeCabezera['tipoCambio'])) {
            $updates[] = "TIPO_CAMBIO = '".$datosDeCabezera['tipoCambio']."'";
        }
        
        if (isset($datosDeCabezera['valorFobPeso']) && !empty($datosDeCabezera['valorFobPeso'])) {
            $updates[] = "VALOR_FOB_PESO = '".$datosDeCabezera['valorFobPeso']."'";
        }
        
        if (isset($datosDeCabezera['formaPago']) && !empty($datosDeCabezera['formaPago'])) {
            $updates[] = "FORMA_PAGO = '".$datosDeCabezera['formaPago']."'";
        }
        
        if (isset($datosDeCabezera['despacho']) && !empty($datosDeCabezera['despacho'])) {
            $updates[] = "DESPACHO = '".$datosDeCabezera['despacho']."'";
        }
        
        $sql .= implode(", ", $updates);
        $sql .= " WHERE ID = ".$id;
        
        error_log("SQL UPDATE: " . $sql);
        
        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            if ($stmt === false) {
                error_log("Error en actualizarEncabezado: " . print_r(sqlsrv_errors(), true));
                return false;
            }
            
            return $id;
            
        } catch (Exception $e) {
            error_log('Error en actualizarEncabezado: ' . $e->getMessage());
            return false;
        }
    }

}