
<?php

class OrdenDeCompra
{

    private function retornarArray($sqlEnviado)
    {

        require_once 'conexion.php';

        $cid = new Conexion();
        $cid_central = $cid->conectar();
        $sql = $sqlEnviado;

        $stmt = sqlsrv_query($cid_central, $sql);

        $rows = array();

        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }


        return $rows;
    }


    public function traerOrdenDeCompra()
    {

        $sql = "SELECT COD_PROVEE, N_ORDEN_CO FROM CPA35 WHERE COD_PROVEE LIKE '%' AND COD_PROVEE LIKE 'Z%' AND
                N_ORDEN_CO NOT IN
                (
                --INGRESOS DE COMPRAS CON PRECIO FOB--
                SELECT DISTINCT(N_ORDEN_CO) N_ORDEN_CO FROM
                    (
                    SELECT B.COD_PRO_CL, E.NOM_PROVEE, A.N_ORDEN_CO, CAST(A.FECHA_MOV AS DATE) FECHA, B.N_COMP, A.COD_ARTICU, CAST(A.CANTIDAD AS FLOAT) CANTIDAD, CAST(D.PRECIO_NET AS FLOAT) PRECIO_UNI, CAST(D.IMP_NETO_P AS FLOAT) IMPORTE FROM STA20 A
                    INNER JOIN STA14 B ON A.NCOMP_IN_S = B.NCOMP_IN_S AND A.TCOMP_IN_S = B.TCOMP_IN_S
                    LEFT JOIN CPA48 C ON A.TCOMP_IN_S = C.TCOMP_IN_S AND A.NCOMP_IN_S = C.NCOMP_IN_S AND A.N_RENGL_S = C.N_RENGL_S
                    LEFT JOIN CPA46 D ON C.NCOMP_IN_C = D.NCOMP_IN_C AND C.TCOMP_IN_C = D.TCOMP_IN_C AND C.N_RENGL_C = D.N_RENGL_C
                    LEFT JOIN CPA01 E ON B.COD_PRO_CL = E.COD_PROVEE
                    INNER JOIN (SELECT COD_ARTICU, USA_PARTID FROM STA11 WHERE COD_ARTICU LIKE 'X%' AND USA_ESC != 'B' AND USA_PARTID = '1') H ON A.COD_ARTICU = H.COD_ARTICU
                    WHERE A.FECHA_MOV >=GETDATE()-365 AND A.TCOMP_IN_S = 'RP' AND B.COD_PRO_CL LIKE 'Z%'
                    ) A 
                )
                AND FEC_EMISIO >=GETDATE()-365 AND COD_PROVEE LIKE '%'
        ";

        $rows = $this->retornarArray($sql);
        
        $myJSON = json_encode($rows);

        return $myJSON;

    }

}