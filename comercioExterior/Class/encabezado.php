
<?php

class Encabezado
{

    function __construct(){

        require_once './../../Class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function insertarEncabezado($datosDeCabezera){     

        $sql = "INSERT INTO RO_T_IMPORTACIONES_ENCABEZADO(FECHA_MOV, COD_PROVEE, PROVEEDOR, CONTENEDOR, DESPACHO, MATERIAL, ORIGEN, FECHA_EMB, FACTURA, FECHA_FACT, 
            ORDEN_COMPRA, FORMA_PAGO, NUMERO_BL, TIPO_CAMBIO, VALOR_FOB_DOLAR, VALOR_FOB_PESO, FECHA_ARR, FECHA_DESP_ADU)
            VALUES (GETDATE(),'".$datosDeCabezera['cod_proveedor']."','".$datosDeCabezera['proveedor']."','".$datosDeCabezera['contenedor']."','".$datosDeCabezera['despacho']."','".$datosDeCabezera['material']."','".$datosDeCabezera['origen']."','".$datosDeCabezera['fechaEmbarque']."','".$datosDeCabezera['facturaProveedor']."','".$datosDeCabezera['fechaFactura']."',
            '".$datosDeCabezera['ordenCompra']."','".$datosDeCabezera['formaPago']."','".$datosDeCabezera['numeroBl']."','".$datosDeCabezera['tipoCambio']."','".$datosDeCabezera['valorFobDolar']."','".$datosDeCabezera['valorFobPeso']."','".$datosDeCabezera['fechaArribo']."','".$datosDeCabezera['fechaDespacho']."')
        ";

        try {

            $stmt = sqlsrv_query($this->cid_central, $sql);
            
        } catch (Exception $e) {

            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
    
        }

    }  

}