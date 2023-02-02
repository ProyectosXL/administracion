
<?php

class OrdenDeCompra
{

    function __construct(){

        require_once __DIR__.'/../../class/conexion.php';

        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    }

    private function retornarArray($sql)
    {

        try {
            $stmt = sqlsrv_query($this->cid_central, $sql);

            $rows = array();

            while ($v = sqlsrv_fetch_array($stmt)) {
             $rows[] = $v;
            }

        } catch (PDOException $e) {

            echo $e->getMessage();

        }

        return $rows;
    }

    public function traerOrdenDeCompra($proveedor)
    {
        try{
            
            $sql = "SELECT COD_PROVEE, N_ORDEN_CO FROM CPA35 WHERE COD_PROVEE LIKE '%' AND COD_PROVEE LIKE 'Z%'
                    --AND N_ORDEN_CO NOT IN
                    --     (
                    --     --INGRESOS DE COMPRAS CON PRECIO FOB--
                    --     SELECT DISTINCT(N_ORDEN_CO) N_ORDEN_CO FROM
                    --         (
                    --          SELECT B.COD_PRO_CL, E.NOM_PROVEE, A.N_ORDEN_CO, CAST(A.FECHA_MOV AS DATE) FECHA, B.N_COMP, A.COD_ARTICU, CAST(A.CANTIDAD AS FLOAT) CANTIDAD, CAST(D.PRECIO_NET AS FLOAT) PRECIO_UNI, CAST(D.IMP_NETO_P AS FLOAT) IMPORTE FROM STA20 A
                    --          INNER JOIN STA14 B ON A.NCOMP_IN_S = B.NCOMP_IN_S AND A.TCOMP_IN_S = B.TCOMP_IN_S
                    --          LEFT JOIN CPA48 C ON A.TCOMP_IN_S = C.TCOMP_IN_S AND A.NCOMP_IN_S = C.NCOMP_IN_S AND A.N_RENGL_S = C.N_RENGL_S
                    --          LEFT JOIN CPA46 D ON C.NCOMP_IN_C = D.NCOMP_IN_C AND C.TCOMP_IN_C = D.TCOMP_IN_C AND C.N_RENGL_C = D.N_RENGL_C
                    --          LEFT JOIN CPA01 E ON B.COD_PRO_CL = E.COD_PROVEE
                    --          INNER JOIN (SELECT COD_ARTICU, USA_PARTID FROM STA11 WHERE COD_ARTICU LIKE 'X%' AND USA_ESC != 'B' AND USA_PARTID = '1') H ON A.COD_ARTICU = H.COD_ARTICU
                    --          WHERE A.FECHA_MOV >=GETDATE()-365 AND A.TCOMP_IN_S = 'RP' AND B.COD_PRO_CL LIKE 'Z%'
                    --          ) A 
                    --        )
                        AND FEC_EMISIO >=GETDATE()-365 
                        AND COD_PROVEE like '%$proveedor'
                        ";
    
            $rows = $this->retornarArray($sql);
    
            $myJSON = json_encode($rows);
    
            echo $myJSON;

        } catch (\Throwable $th) {

            print_r($th);

        }

    }

    public function verificarOrdenCompra($ordenDeCompra)
    {
        $sql="SELECT * FROM RO_T_IMPORTACIONES_ENCABEZADO WHERE ORDEN_COMPRA LIKE '%$ordenDeCompra'";

        $rows = $this->retornarArray($sql);
        if(empty($rows))
        {
            echo 'ok';
        }else{
            echo 'El comprobante existe';
        }
    }

    public function deleteDetalle($idEncabezado){

        $sql = "DELETE RO_T_IMPORTACIONES_DETALLE WHERE ID_MG ='".$idEncabezado."'";

        try {
                
            $stmt = sqlsrv_query($this->cid_central, $sql);

        } catch (Exception $e) {
            
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
    
        }
    }

    public function insertDetalle($datosDetalle, $idCabezera){   

        foreach ($datosDetalle as $dato) {

            $sql = "
            INSERT INTO RO_T_IMPORTACIONES_DETALLE(ID_MG, IMPORTE_U\$S, TIPO_CAMBIO, IMPORTE_$, PORCENTAJE, OBSERVACIONES, FECHA_MOD,GASTOS)
            VALUES (".$idCabezera.", ".$dato['importeEnDolares'].", ".$dato['tipoCambio'].", ".$dato['importeEnPesos'].", ".$dato['sobreFob'].", '".$dato['observaciones']."',GETDATE(),'".$dato['gastos']."')
            ;";

            try {
                
                $stmt = sqlsrv_query($this->cid_central, $sql);

            } catch (Exception $e) {
    
                echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        
            }
        }

    }  


}

$cuenta = new OrdenDeCompra();

if (isset($_GET['proveedor'])) {
    $cuenta->traerOrdenDeCompra($_GET['proveedor']);
}else
{
    if(isset($_GET['OrdenCompra']))
    {
        $cuenta->verificarOrdenCompra($_GET['OrdenCompra']);
    }
}
