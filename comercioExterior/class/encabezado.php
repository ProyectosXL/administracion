
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
  
        $datosDeCabezera['valorFobPeso'] = $datosDeCabezera['valorFobPeso']+0.15;
        
        $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, DESPACHO, MATERIAL, ORIGEN, FACTURA, 
        ORDEN_COMPRA, FORMA_PAGO, NUMERO_BL, TIPO_CAMBIO, VALOR_FOB_DOLAR, VALOR_FOB_PESO, FECHA_DESP_ADU, OCM)
        VALUES (GETDATE(),'".$codProv."','".$datosDeCabezera['proveedor']."','".$datosDeCabezera['contenedor']."','".$datosDeCabezera['despacho']."','".$datosDeCabezera['material']."','".$datosDeCabezera['origen']."','".$datosDeCabezera['facturaProveedor']."',
        '".$datosDeCabezera['ordenCompra']."','".$datosDeCabezera['formaPago']."','".$datosDeCabezera['numeroBl']."','".$datosDeCabezera['tipoCambio']."','".$datosDeCabezera['valorFobDolar']."','".(float)$datosDeCabezera['valorFobPeso']."','".$datosDeCabezera['fechaDespacho']."' , '".$datosDeCabezera['ocm']."');
        ";

  
        
        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            
            $queryIdentity = "select @@identity";
            $stmtIdentity = sqlsrv_query($this->cid_central, $queryIdentity);
        
            $id = null;
            
            while ($v = sqlsrv_fetch_array($stmtIdentity)) {
                $id = $v;
            }
    
            return $id[0];

        } catch (Exception $e) {

            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
    
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

}