
<?php

class Periodo
{
    function __construct(){
        require_once __DIR__.'/../../../Class/conexion.php';
        $cid = new Conexion();
        $this->cid_central = $cid->conectar('central');

    } 

    public function hacerPeriodo ($fecha, $mesesARestar)
    { 
        // Convierte la fecha en un objeto DateTime
        $fechaObj = new DateTime($fecha);
 
        // Resta la cantidad de meses especificada
        $fechaObj->modify("-$mesesARestar month");

        // Obtiene el mes y el año de la fecha
        $mes = $fechaObj->format('n');
        $anio = $fechaObj->format('Y');

        // Crea el período en el formato deseado
        $periodo = $mes . '-' . $anio;

        return $periodo;

    }


    public function desHacerPeriodo($periodo)
    {
        // Divide el período en mes y año
        list($mes, $anio) = explode('-', $periodo);
    
        // Construye una fecha con el primer día del mes
        $primeroDelMes = date('Y-m-d', strtotime("$anio-$mes-01"));
    
        // Obtiene el último día del mes
        $ultimoDelMes = date('Y-m-t', strtotime($primeroDelMes));
    
        return $ultimoDelMes;
    }
    
}