<?php 

$accion = isset($_GET['accion']) ? $_GET['accion'] : "";

switch ($accion) {

    case 'insertarDetalle':
        insertarDetalle(); 
        break;

    case 'actualizarDetalle':
        actualizarDetalle(); 
        break;

    case 'procesar':
        execSpAlquileres(); 
        break;

    case 'verificarProcesado':
        verificarProcesado(); 
        break;

    case 'checkCierrePeriodoAnt':
        checkCierrePeriodoAnt(); 
        break;

    case 'cerrarPeriodo':
        cerrarPeriodo(); 
        break;

    case 'abrirPeriodo':
        abrirPeriodo(); 
        break;

    case 'ocultarSucursal':
        ocultarSucursal(); 
        break;

    case 'guardarContratoAlquiler':
        guardarContratoAlquiler(); 
        break;

    case 'aplicarAjuste':
        aplicarAjuste(); 
        break;

    case 'comprobarAjuste':
        comprobarAjuste(); 
        break;
    
    case 'verificarSolapamientoContrato':
        verificarSolapamientoContrato(); 
        break;

    default:
      
        break;

}

function insertarDetalle () {

    require_once '../Class/Alquiler.php';

    $alquiler = new Alquiler();

    $data = $_POST['values'];

    $result = $alquiler->insertarDetalle($data);

    return true;


}

function actualizarDetalle () {

    require_once '../Class/Alquiler.php';
    
    $alquiler = new Alquiler();
    
    $periodo = $_POST['periodo'];
    $sucursal = $_POST['sucursal'];
    $concepto = $_POST['concepto'];
    $importe = $_POST['importe'];
    $userName = $_POST['userName'];
    $porcentaje = $_POST['porcentaje'];


    $result = $alquiler->actualizarDetalle($periodo, $sucursal, $concepto, $importe, $userName, $porcentaje);
    
    
    return true;
      
    
}

function cargarAlquieres ($fecha, $periodo) {

    require_once "Class/Alquiler.php";
    require_once "Class/sucursal.php";


    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $conceptos = $alquiler->traerConceptos();

    $contratoAlquiler = $alquiler->traerContratoAlquiler($fecha);

    $todosLosLocales= $sucursal->traerLocales();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();
    
    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo); 

    $newArray = [];

    foreach ($todosLosLocales as $k => $v) {
        
        $newArray[$v['NRO_SUCURSAL']] = [];
        
        foreach ($conceptos as $key => $value) {    

                $total = 0;

                if($value['carga_manual'] != 1){
                    
                        if( in_array($value['ID_CA'], ["9", "13"]) ) {

                            foreach ($traerPorcentajes as  $porcentaje) {
                   
                                if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                               
                                    $total = $porcentaje['PORCENTAJE'];
                                }
                            }   
                        }

                        if( in_array($value['ID_CA'], ["6", "15", "16"]) ) {

                            foreach ($rentabilidadBruta as $rentabilidad) {

                                if($rentabilidad['NRO_SUCURSAL'] == $v['NRO_SUCURSAL']) {
                                    foreach ($traerPorcentajes as  $porcentaje) {
                                        if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURSAL']) {
                                            $total = ( ( $rentabilidad['IMPORTE'] * $porcentaje['PORCENTAJE'] ) / 100 ) ;
                                        }
                                    }   
                                }
                                
                            }

                        }

                        if( in_array($value['ID_CA'], ["7", "14", "17"]) ) {

                            foreach ($rentabilidadNeta  as $rentabilidad) {

                                if($rentabilidad['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                    foreach ($traerPorcentajes as  $porcentaje) {
                                        if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURS']) {

                                            $total = $rentabilidad['VENTA'] * $porcentaje['PORCENTAJE'] / 100;

                                            if(in_array($v['NRO_SUCURSAL'],["02","16","60","79","81"]) && $value['ID_CA'] == "14"){

                                                $total = ($newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"] - $newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"]) * $porcentaje['PORCENTAJE'] / 100;
        
                                            }
                                        }
                                    }   

                                }

                            }
                        }
    

                }
                
                if( in_array($value['ID_CA'], ["4", "5", "18"]) ) {

                     foreach ($contratoAlquiler as  $contrato) {

        

                            $vigDesde = new DateTime($contrato['VIG_DESDE']->format("Y-m-d")); // Primera fecha
                            $vigHasta = new DateTime($contrato['VIG_HASTA']->format("Y-m-d")); // Segunda fecha
                             

                            $diferenciaDeDias = $vigHasta->diff($vigDesde)->days;

                            // Calcula la diferencia en meses
                            $mesesDiferencia = round(($diferenciaDeDias / 365) * 12);

                            if($mesesDiferencia == 0){
                                $mesesDiferencia = 1;
                            }

                            if($contrato['ID_CA'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                $total = ($contrato['IMPORTE'] / $mesesDiferencia);

                            }

                            if($contrato['ID_CA_2'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                $$total = ($contrato['IMPORTE_2'] / $mesesDiferencia);

                            }

                            if($contrato['ID_CA_3'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                $$total  = ($contrato['IMPORTE_3'] / $mesesDiferencia);

                            }

    
                        }
                 
                }

                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $total;      
            
            }
        }

    return $newArray;

}

function traerDetalleAlquiler ($fecha,$periodo) {

    require_once "Class/Alquiler.php";
    require_once "Class/sucursal.php";


    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales= $sucursal->traerLocales();

    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo); 

    $inputStringWithDay = $fecha . "-01";

    $detalle = $alquiler->traerDetalle($periodo);
    $estado = $alquiler->traerEstado($periodo);
    $contratoAlquiler = $alquiler->traerContratoAlquiler($fecha);

    
    $sucursalesOcultas = $alquiler->traerSucursalesOcultas($periodo);
    $arraySucursalesOcultas = [];
    $sucursalesOcultasArray = [];
    if(count($sucursalesOcultas) > 0){
        $arraySucursalesOcultas = json_decode($sucursalesOcultas[0]['JSON_LOCALES'],true);
        $sucursalesOcultasArray = explode(',', $arraySucursalesOcultas['sucursales']);
    }
 
    $newArray = [];

    if($estado == 1){
        foreach ($todosLosLocales as $k => $v) {

            if (in_array($v['NRO_SUCURSAL'], $sucursalesOcultasArray)) {
                                               
                continue;
            }

            foreach ($conceptos as $key => $value) {  

                foreach ($detalle as $det) {

                    if($det['NRO_SUCURS'] == $v['NRO_SUCURSAL'] && $det['ID_CA'] == $value['ID_CA']) {

                        
                        $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $det['IMPORTE_PARSE'];

                        break;
                
    
                    }

                }  

            }
        }



    }else{

        
        
        foreach ($todosLosLocales as $k => $v) {

            $newArray[$v['NRO_SUCURSAL']] = [];

            foreach ($conceptos as $key => $value) {  
                $total = 0;

                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = 0;

                if($value['carga_manual'] != 1){

                    if( in_array($value['ID_CA'], ["9", "13"]) ) {

                        foreach ($traerPorcentajes as $porcentaje ) {

                            if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                                $total = $porcentaje['PORCENTAJE'];
                            }

                        }
                    }

                    if( in_array($value['ID_CA'], ["6", "15", "16"]) ) {

                        foreach ($rentabilidadBruta as $rentabilidad) {

                            if($rentabilidad['NRO_SUCURSAL'] == $v['NRO_SUCURSAL']) {
                                foreach ($traerPorcentajes as  $porcentaje) {
                                    if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURSAL']) {
                                        $total = ( ( $rentabilidad['IMPORTE'] * $porcentaje['PORCENTAJE'] ) / 100 ) ;
                                    }
                                }   
                            }

                        }

                    }

                    if( in_array($value['ID_CA'], ["7", "14", "17"]) ) {

                        foreach ($rentabilidadNeta  as $rentabilidad) {

                            if($rentabilidad['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                foreach ($traerPorcentajes as  $porcentaje) {
                                    
                                    if($porcentaje['ID_CA'] == $value['ID_CA'] && $porcentaje['NRO_SUCURS'] == $rentabilidad['NRO_SUCURS']) {
                                        $total = $rentabilidad['VENTA'] * $porcentaje['PORCENTAJE'] / 100;

                                        if(in_array($v['NRO_SUCURSAL'],["02","16","60","79","81"]) && $value['ID_CA'] == "14"){
                                            // $total = ($newArray[$v['NRO_SUCURSAL']]["Porc. S/ventas netas"] - $newArray[$v['NRO_SUCURSAL']]["Valor minimo mensual"]) * $porcentaje['PORCENTAJE'] / 100;
                                            $total = $porcentaje['PORCENTAJE'] ;
                                        }
                                    }
                                }   

                            }

                        }
                    }

                    $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $total;  

                }else{
                    

                    if( in_array($value['ID_CA'], ["4", "5", "18"]) ) {

                        foreach ($contratoAlquiler as  $contrato) {

                            $vigDesde = new DateTime($contrato['VIG_DESDE']->format("Y-m-d")); // Primera fecha
                            $vigHasta = new DateTime($contrato['VIG_HASTA']->format("Y-m-d")); // Segunda fecha
                            $hoy = new DateTime(date("Y-m-d")); // Segunda fecha
                             
                    

                            $diferenciaDeDias = $vigHasta->diff($vigDesde)->days;

                            // Calcula la diferencia en meses
                            $mesesDiferencia = round(($diferenciaDeDias / 365) * 12);

                            if($mesesDiferencia == 0){
                                $mesesDiferencia = 1;
                            }
          
                            if($contrato['ID_CA'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {
                                // var_dump($contratoAlquiler);
                                // var_dump($v['NRO_SUCURSAL']);

                                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = ($contrato['IMPORTE'] / $mesesDiferencia);

                            }

                            if($contrato['ID_CA_2'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = ($contrato['IMPORTE_2'] / $mesesDiferencia);

                            }

                            if($contrato['ID_CA_3'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = ($contrato['IMPORTE_3'] / $mesesDiferencia);

                            }

    
                        }
                   
                    }

                    foreach ($detalle as $det) {

                        if($det['NRO_SUCURS'] == $v['NRO_SUCURSAL'] && $det['ID_CA'] == $value['ID_CA']) {

                            if( in_array($det['ID_CA'], ["4", "5", "18"]) ) {
                                if($det['AJUSTADO'] != "1"){
                                    continue;
                                }
                            }

                            $newArray[$v['NRO_SUCURSAL']][$value['CONCEPTO']] = $det['IMPORTE_PARSE'];

                            break;
                    
        
                        }

                    }  
                }

            }
    
        }
    }


    return $newArray;
}

function traerLocales () {
    
    require_once "Class/sucursal.php";

    $sucursal = new Sucursal();
    $todosLosLocales= $sucursal->traerLocales();

    return $todosLosLocales;
}

function traerConceptos () {
    
    require_once "Class/Alquiler.php";

    $alquiler = new Alquiler();

    $conceptos = $alquiler->traerConceptos();

    return $conceptos;
}

function consultarMesesDetalle ($periodoPasado, $periodo) {
    
    require_once "Class/Alquiler.php";
    require_once "Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $conceptos = $alquiler->traerConceptos();
    $detalles = $alquiler->consultarMesesDetalle($periodoPasado, $periodo);
    
    $locales = $sucursal->traerLocales();

    $newArray = [];
    $periodoActual = "";
    $sucursalActual = "";
    foreach ($conceptos as $concepto) {

        $newArray[$concepto['ID_CA']] = [] ;
        $total = 0;
        foreach ($detalles as $key => $detalle) {

            if($detalle['ID_CA'] == $concepto['ID_CA']){

                if($detalle['PERIODO'] != $periodoActual){
                    $total = 0;
                }

                $newArray[$concepto['ID_CA']][$detalle['PERIODO']] = [];
    
                $total += (int)$detalle['IMPORTE'];
                $newArray[$concepto['ID_CA']][$detalle['PERIODO']]['TOTAL'] = "";
                $newArray[$concepto['ID_CA']][$detalle['PERIODO']]['TOTAL'] = $total;
 
                $periodoActual = $detalle['PERIODO'];

            }

        }

 

    }


    return $newArray;

}

function traerArrayPeriodo () {
    $fechaActual = date('Y-m-d'); // Obtiene la fecha actual en el formato "Año-Mes-Día"
    $fechaHaceUnAnio = date('Y-m-d', strtotime('-1 year', strtotime($fechaActual)));

    $hoy = new DateTime($fechaActual);
    $haceUnAnio = new DateTime($fechaHaceUnAnio);

    $fecha = $hoy;

    $interval = $hoy->diff($haceUnAnio);
    $interval = $interval->format('%a');

    $dates = [];

    for($i = $interval; $i > 0; $i--){

    $fecha = $fecha->sub(new DateInterval('P1D'));
    $periodo = substr($fecha->format('Y-m-d'), 0, 7);

    if(!in_array($periodo, $dates)){
        $dates[] = $periodo;
    }
    }
    foreach ($dates as $key => &$value) {
        $valor = explode("-", $value);
        $value = (int)$valor[1]."-".$valor[0];
        
    }

    return ($dates);
}

function traerDetalleHaceUnAño ($periodoPasado, $now) {
    
    require_once "Class/Alquiler.php";
    require_once "Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();

    $detalles = $alquiler->consultarMesesDetalle ($periodoPasado, $now);

    return $detalles;
}


function execSpAlquileres () {

    require_once "../Class/Alquiler.php";
    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    $detalles = $alquiler->execSpAlquileres($periodo);

    return $detalles;

}



function verificarProcesado () {

    require_once "../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $fecha = $_POST['fecha'];

    $result = $alquiler->verificarProcesado($fecha);

    echo json_encode($result);


}

function cerrarPeriodo () {

    require_once "../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    $result = $alquiler->cerrarPeriodo($periodo);

    return $result;


}

function abrirPeriodo () {

    require_once "../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $periodo = $_POST['periodo'];

    $result = $alquiler->abrirPeriodo($periodo);

    return $result;


}

function checkCierrePeriodoAnt () {

    require_once "../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $mesAnterior = $_POST['mesAnterior'];

    $result = $alquiler->checkCierrePeriodoAnt($mesAnterior);

    echo json_encode($result);


}

function ocultarSucursal () {

    require_once "../Class/Alquiler.php";

    $alquiler = new Alquiler();

    $sucursal = $_POST['sucursal'];
    $periodo = $_POST['periodo'];

    $result = $alquiler->traerSucursalesOcultas($periodo);

    if(count($result) > 0){

        $arraySucursales = json_decode($result[0]['JSON_LOCALES'], true);

        if ($arraySucursales !== null) {
          
            $nuevosValores = [$sucursal]; 
        
            if (isset($arraySucursales['sucursales'])) {
    
                $valoresExistente = explode(',', $arraySucursales['sucursales']);
          
                $nuevosValores = array_filter($nuevosValores, function ($valor) use ($valoresExistente) {
                    return !in_array($valor, $valoresExistente);
                });

                $nuevosValores = array_merge($valoresExistente, $nuevosValores);
            }

            $arraySucursales['sucursales'] = implode(',', $nuevosValores);
        
        }
            
  
        $result = $alquiler->ocultarSucursal($periodo, json_encode($arraySucursales));
    }else{

        $jsonSucursal = [
            "sucursales" => $sucursal
        ];

        $jsonSucursal = json_encode($jsonSucursal);
        $result = $alquiler->ocultarSucursal($periodo, $jsonSucursal);
    }

    echo json_encode($result);


}

/**
 * Actualización del método guardarContratoAlquiler para mejor validación
 */
function guardarContratoAlquiler($sucursal, $descSucursal, $valorLlave, $comisiones, $lanzamiento, $desde, $hasta) {
    
    // Primero verificar si hay solapamiento
    $verificacion = $this->verificarSolapamientoContrato($sucursal, $desde, $hasta);
    
    if ($verificacion['solapamiento']) {
        return false; // No permitir guardar si hay solapamiento
    }

    $sql = "
        INSERT INTO RO_T_CONTRATOS_ALQUILERES 
        (FECHA_CARGA, NRO_SUCURS, DESC_SUCURS, ID_CA, IMPORTE, ID_CA_2, IMPORTE_2, ID_CA_3, IMPORTE_3, VIG_DESDE, VIG_HASTA)
        VALUES 
        (GETDATE(), ?, ?, '4', ?, '5', ?, '18', ?, ?, ?)
    ";

    try {
        $stmt = sqlsrv_prepare($this->cid_central, $sql, [
            $sucursal,
            $descSucursal,
            $valorLlave,
            $comisiones,
            $lanzamiento,
            $desde,
            $hasta
        ]);

        if (!$stmt) {
            throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $result = sqlsrv_execute($stmt);

        if (!$result) {
            throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $rowCount = sqlsrv_rows_affected($stmt);

        return $rowCount > 0;

    } catch (\Throwable $th) {
        error_log("Error al guardar contrato: " . $th->getMessage());
        throw $th;
    }
}

function aplicarAjuste () {

    require_once "../Class/Alquiler.php";
    
    $alquiler = new Alquiler();
    
    $data = $_POST['arrayData'];  

    $periodo = $_POST['periodo'];

    $coeficiente = $alquiler->traerCoeficiente($periodo);

    if($coeficiente == 0){
      
        echo 0;
        die();

    }


    foreach ($data as $key => $value) {

        foreach ($value as $detalle) {

            $importe = $detalle['value'] * $coeficiente;
            $alquiler->aplicarAjuste($key, $detalle['concepto'],$importe, $periodo);
    
        }
          
    }

    echo 1;
}

function comprobarAjuste () {

    require_once "../Class/Alquiler.php";
    
    $alquiler = new Alquiler();
    
    $data = $_POST['arrayData'];  

    $periodo = $_POST['periodo'];
    $error = 0;

    foreach ($data as $key => $value) {

        foreach ($value as $detalle) {
            $result = $alquiler->comprobarAjuste($key, $detalle['concepto'], $periodo);
            if($result == 0){
                $error = 1;
            }
        }
    }

    echo ($error);

}

/**
 * Verifica si existe solapamiento de contratos para una sucursal en un rango de fechas
 * @param string $sucursal - Número de sucursal
 * @param string $fechaDesde - Fecha de inicio del nuevo contrato (YYYY-MM-DD)
 * @param string $fechaHasta - Fecha de fin del nuevo contrato (YYYY-MM-DD)
 * @return array - Array con información sobre el solapamiento
 */
public function verificarSolapamientoContrato($sucursal, $fechaDesde, $fechaHasta) {
    
    $sql = "
        SELECT TOP 1
            ID,
            NRO_SUCURS,
            DESC_SUCURS,
            VIG_DESDE,
            VIG_HASTA,
            ID_CA,
            IMPORTE,
            ID_CA_2,
            IMPORTE_2,
            ID_CA_3,
            IMPORTE_3
        FROM RO_T_CONTRATOS_ALQUILERES
        WHERE NRO_SUCURS = ?
        AND (
            -- Caso 1: El nuevo contrato empieza durante un contrato existente
            (? BETWEEN VIG_DESDE AND VIG_HASTA)
            OR
            -- Caso 2: El nuevo contrato termina durante un contrato existente  
            (? BETWEEN VIG_DESDE AND VIG_HASTA)
            OR
            -- Caso 3: El nuevo contrato engloba completamente a uno existente
            (? <= VIG_DESDE AND ? >= VIG_HASTA)
            OR
            -- Caso 4: Un contrato existente engloba completamente al nuevo
            (VIG_DESDE <= ? AND VIG_HASTA >= ?)
        )
        ORDER BY VIG_DESDE DESC
    ";

    try {
        $stmt = sqlsrv_prepare($this->cid_central, $sql, [
            $sucursal,
            $fechaDesde,
            $fechaHasta, 
            $fechaDesde,
            $fechaHasta,
            $fechaDesde,
            $fechaHasta
        ]);

        if (!$stmt) {
            throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $result = sqlsrv_execute($stmt);
        
        if (!$result) {
            throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $contratoExistente = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if ($contratoExistente) {
            // Formatear las fechas para la respuesta
            $vigDesde = $contratoExistente['VIG_DESDE'];
            $vigHasta = $contratoExistente['VIG_HASTA'];
            
            // Convertir objetos DateTime a string si es necesario
            if ($vigDesde instanceof DateTime) {
                $vigDesde = $vigDesde->format('Y-m-d');
            }
            if ($vigHasta instanceof DateTime) {
                $vigHasta = $vigHasta->format('Y-m-d');
            }

            return [
                'solapamiento' => true,
                'contrato_existente' => [
                    'ID' => $contratoExistente['ID'],
                    'NRO_SUCURS' => $contratoExistente['NRO_SUCURS'],
                    'DESC_SUCURS' => $contratoExistente['DESC_SUCURS'],
                    'VIG_DESDE' => $vigDesde,
                    'VIG_HASTA' => $vigHasta,
                    'IMPORTE_1' => $contratoExistente['IMPORTE'],
                    'IMPORTE_2' => $contratoExistente['IMPORTE_2'],
                    'IMPORTE_3' => $contratoExistente['IMPORTE_3']
                ],
                'mensaje' => 'Ya existe un contrato para esta sucursal que se solapa con el período seleccionado'
            ];
        } else {
            return [
                'solapamiento' => false,
                'mensaje' => 'No hay solapamiento de contratos'
            ];
        }

    } catch (\Throwable $th) {
        error_log("Error en verificarSolapamientoContrato: " . $th->getMessage());
        throw $th;
    }
}

/**
 * Trae los contratos futuros (que aún no han comenzado)
 * @return array - Array de contratos futuros
 */
function traerContratosFuturos() {
    $sql = "
        DECLARE @fecha_actual DATE = GETDATE();
        
        WITH CTE AS (
            SELECT
                *,
                ROW_NUMBER() OVER(PARTITION BY NRO_SUCURS ORDER BY VIG_DESDE ASC) AS rn
            FROM RO_T_CONTRATOS_ALQUILERES
            WHERE VIG_DESDE > @fecha_actual
        )
        
        SELECT * FROM CTE WHERE rn = 1
        ORDER BY VIG_DESDE ASC
    ";

    try {
        $stmt = sqlsrv_query($this->cid_central, $sql);

        if (!$stmt) {
            throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $rows = array();
        while ($v = sqlsrv_fetch_array($stmt)) {
            $rows[] = $v;
        }

        return $rows;
        
    } catch (\Throwable $th) {
        error_log("Error en traerContratosFuturos: " . $th->getMessage());
        throw $th;
    }
}

/**
 * Método adicional para obtener contratos activos de una sucursal
 * @param string $sucursal - Número de sucursal
 * @return array - Contratos activos
 */
public function obtenerContratosActivosSucursal($sucursal) {
    
    $sql = "
        SELECT 
            ID,
            NRO_SUCURS,
            DESC_SUCURS,
            VIG_DESDE,
            VIG_HASTA,
            ID_CA,
            IMPORTE,
            ID_CA_2,
            IMPORTE_2,
            ID_CA_3,
            IMPORTE_3,
            FECHA_CARGA
        FROM RO_T_CONTRATOS_ALQUILERES
        WHERE NRO_SUCURS = ?
        AND VIG_HASTA >= GETDATE()
        ORDER BY VIG_DESDE DESC
    ";

    try {
        $stmt = sqlsrv_prepare($this->cid_central, $sql, [$sucursal]);
        
        if (!$stmt) {
            throw new \Exception("Error al preparar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $result = sqlsrv_execute($stmt);
        
        if (!$result) {
            throw new \Exception("Error al ejecutar la consulta: " . print_r(sqlsrv_errors(), true));
        }

        $contratos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            
            // Formatear fechas si son objetos DateTime
            if ($row['VIG_DESDE'] instanceof DateTime) {
                $row['VIG_DESDE'] = $row['VIG_DESDE']->format('Y-m-d');
            }
            if ($row['VIG_HASTA'] instanceof DateTime) {
                $row['VIG_HASTA'] = $row['VIG_HASTA']->format('Y-m-d');
            }
            if ($row['FECHA_CARGA'] instanceof DateTime) {
                $row['FECHA_CARGA'] = $row['FECHA_CARGA']->format('Y-m-d H:i:s');
            }
            
            $contratos[] = $row;
        }

        return $contratos;

    } catch (\Throwable $th) {
        error_log("Error en obtenerContratosActivosSucursal: " . $th->getMessage());
        throw $th;
    }
}

?>