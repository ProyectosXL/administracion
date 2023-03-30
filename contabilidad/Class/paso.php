<?php


class Paso
{

    private function ejecutarQuery($sqlEnviado)
    {
        try {
            require_once __DIR__.'/../../class/conexion.php';

            $cid = new Conexion();
            $cid_central = $cid->conectar('central');
            $sql = $sqlEnviado;

            $stmt = sqlsrv_query($cid_central, $sql);
            sqlsrv_execute($stmt);
        }

        /*  print_r($stmt); */
        /* sqlsrv_execute($stmt); */
        /*  $dato = sqlsrv_fetch_array($stmt);
            var_dump($dato); */ catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function consultarPasos($periodo){

        require_once __DIR__.'/../../class/conexion.php';
        $cid = new Conexion();
        $cid_central = $cid->conectar('central');

        $sql = "SELECT * FROM RO_T_CONTROL_INFORME_ECONOMICO WHERE PERIODO = '$periodo'";
        $stmt = sqlsrv_query($cid_central, $sql);
        $salida=array();

        while ($row = sqlsrv_fetch_array($stmt)) {
            $salida[] = $row;
        }


        return ($salida);

    }

    public function ejecutarPaso1($desde, $hasta)
    {
 
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_ARTICULOS_SIN_COSTO_NAC '$desde', '$hasta';
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);
            $salida=array();

            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
          
             echo json_encode($salida);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


    public function ejecutarPaso2($desde, $hasta)
    {  
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_ARTICULOS_SIN_PRECIO_COSTO '$desde', '$hasta';
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);

            $salida=array();

            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
             
            $data = [];
       

            for ($i=0; $i < (count($salida)-1) ; $i++) {

              
                $data[$i]['COD_ARTICU'] = $salida[$i]['COD_ARTICU'];
                $data[$i]['RUBRO'] = $salida[$i]['RUBRO'];
 
            }

             echo json_encode($data);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function ejecutarPaso3($desde, $hasta)
    {  
        try {
            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_RENTABILIDAD_BRUTA '$desde', '$hasta' ;
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);
            $salida=array();

            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                    $salida[] = $row;
                }
            } while (sqlsrv_next_result($stmt)); 
            
       
             echo json_encode($salida);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


    public function ejecutarPaso4($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = [LAKERBIS].LOCALES_LAKERS.DBO.RO_SP_VENTAS_VS_COBRANZA_TOTALES '$desde', '$hasta' ;
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);
            $salida=array();
            /* $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt);
            $next_result = sqlsrv_next_result($stmt); */
            /* $salida['resultado']=sqlsrv_rows_affected($stmt); */
            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
          
             echo json_encode($salida);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


    public function ejecutarPaso5($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_INSERTAR_MET_PRORRATEO_TODOS '$desde', '$hasta' ;
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);
            $salida=array();

            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
          
             echo json_encode($salida);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function ejecutarPaso6($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_INTEGRAL '$desde', '$hasta' ;
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);
            $salida=array();

            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
          
             echo json_encode($salida);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function ejecutarPaso7($desde, $hasta)
    {  
        try {

            require_once __DIR__.'/../../class/conexion.php';
            $cid = new Conexion();
            $cid_central = $cid->conectar('central');

            $sql = "DECLARE @ResultForPos int;
            EXEC @ResultForPos = RO_SP_APLICAR_COEF_AJUSTE '$desde', '$hasta' ;
            SELECT @ResultForPos as valor";

            $stmt = sqlsrv_query($cid_central, $sql);
            $salida=array();

            do {
                while ($row = sqlsrv_fetch_array($stmt)) {
                   $salida[] = $row;
                }
             } while (sqlsrv_next_result($stmt)); 
          
             echo json_encode($salida);
          
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }


}




?>