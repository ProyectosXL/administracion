<?php 
    // Incluir el controlador donde están definidas las funciones
    require_once "Controller/AlquilerController.php";
    
    for ($i=0; $i < 13; $i++)
    {
         $meses[] = date('M', strtotime("-$i month"));
    }
    $meses = array_reverse($meses);
    

    foreach ($meses as $key => &$mesName) {

        switch ($mesName) {
            case 'Jan': $mesName = 'Enero'; break;
            case 'Feb': $mesName = 'Febrero'; break;
            case 'Mar': $mesName = 'Marzo'; break;
            case 'Apr': $mesName = 'Abril'; break;
            case 'May': $mesName = 'Mayo'; break;
            case 'Jun': $mesName = 'Junio'; break;
            case 'Jul': $mesName = 'Julio'; break;
            case 'Aug': $mesName = 'Agosto'; break;
            case 'Sep': $mesName = 'Septiembre'; break;
            case 'Oct': $mesName = 'Octubre'; break;
            case 'Nov': $mesName = 'Noviembre'; break;
            case 'Dec': $mesName = 'Diciembre'; break;
            
            default: $mesName = ''; break;
        }
    }

    $anio = date('Y',  strtotime( date("Y-m-d")));
    $conceptos = traerConceptos();
    $mes = date('m',  strtotime( date("Y-m-d")));
    
    $mesReverse = strrev($mes);
    $periodoPasado = $mesReverse.((int)$anio-1);
    $now = $mesReverse.$anio;
    
    $periodoPasadoReverse =strrev($periodoPasado);
    $nowReverse = strrev($now);

    $data = consultarMesesDetalle($periodoPasadoReverse, $nowReverse);
 
    $dataDetalladaPorSucursal = traerDetalleHaceUnAño($periodoPasadoReverse, $nowReverse);

    $traerArrayPeriodo = traerArrayPeriodo();
    $traerArrayPeriodo = array_reverse($traerArrayPeriodo, true);

    $todosLosLocales = traerLocales();

    $fechaParaMostrar = $meses[0].' '.((int)$anio-1).' a '.$meses[12].' '.$anio;

?>