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

    case 'verificarDiferenciasPreCierre':
        verificarDiferenciasPreCierre(); 
        break;

    case 'abrirPeriodo':
        abrirPeriodo(); 
        break;

    // case 'ocultarSucursal':
    //     ocultarSucursal(); 
    //     break;

    case 'aplicarAjuste':
        aplicarAjuste(); 
        break;

    case 'comprobarAjuste':
        comprobarAjuste(); 
        break;

    case 'revertirProcesamiento':
        revertirProcesamiento(); 
        break;

    default:
        // No hacer nada si no hay acción válida
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
    $porcentaje = $_POST['porcentaje'];

    $result = $alquiler->actualizarDetalle($periodo, $sucursal, $concepto, $importe, null, $porcentaje);
    
    return true;
}

function cargarAlquieres ($fecha, $periodo) {

    require_once "Class/Alquiler.php";
    require_once "Class/sucursal.php";
    require_once "contratos/Class/Contrato.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $contrato = new Contrato();
    $conceptos = $alquiler->traerConceptos();

    $contratoAlquiler = $contrato->traerContratoAlquiler($fecha);

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

                                $total = ($contrato['IMPORTE_2'] / $mesesDiferencia);

                            }

                            if($contrato['ID_CA_3'] == $value['ID_CA'] && $contrato['NRO_SUCURS'] == $v['NRO_SUCURSAL']) {

                                $total  = ($contrato['IMPORTE_3'] / $mesesDiferencia);

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
    require_once "contratos/Class/Contrato.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    $contrato = new Contrato();

    $traerPorcentajes = $alquiler->traerTodosLosPorcentajes();

    $conceptos = $alquiler->traerConceptos();
    $todosLosLocales= $sucursal->traerLocales();

    $rentabilidadNeta = $alquiler->traerRentabilidadNeta($fecha);
    $rentabilidadBruta = $alquiler->traerRentabilidadBruta($periodo); 

    $inputStringWithDay = $fecha . "-01";

    $detalle = $alquiler->traerDetalle($periodo);
    $estado = $alquiler->traerEstado($periodo);
    $contratoAlquiler = $contrato->traerContratoAlquiler($fecha);

    $newArray = [];

    if($estado == 1){
        foreach ($todosLosLocales as $k => $v) {

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

    $alquiler->execSpAlquileres($periodo);

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
    $forzarCierre = isset($_POST['forzarCierre']) ? $_POST['forzarCierre'] : false;

    // Si no es un cierre forzado, verificar que no haya diferencias pendientes
    if (!$forzarCierre) {
        $diferencias = verificarDiferenciasInterno($periodo);
        if (!empty($diferencias)) {
            echo json_encode([
                'status' => 'diferencias',
                'message' => 'Se detectaron diferencias en los valores',
                'diferencias' => $diferencias
            ]);
            return;
        }
    }

    $result = $alquiler->cerrarPeriodo($periodo);

    echo json_encode([
        'status' => 'success',
        'message' => 'Período cerrado correctamente'
    ]);
}

function verificarDiferenciasPreCierre() {
    $periodo = $_POST['periodo'];
    $diferencias = verificarDiferenciasInterno($periodo);
    
    echo json_encode([
        'status' => 'success',
        'diferencias' => $diferencias,
        'tieneDiferencias' => !empty($diferencias)
    ]);
}

function verificarDiferenciasInterno($periodo) {
    require_once "../Class/Alquiler.php";
    require_once "../Class/sucursal.php";

    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    
    // Obtener los datos guardados en la base de datos
    $detalleGuardado = $alquiler->traerDetalle($periodo);
    
    // Obtener los datos actuales enviados desde el frontend
    $datosActuales = isset($_POST['datosActuales']) ? $_POST['datosActuales'] : [];
    
    $diferencias = [];
    
    // Comparar cada registro
    foreach ($detalleGuardado as $registroGuardado) {
        $nroSucursal = $registroGuardado['NRO_SUCURS'];
        $idConcepto = $registroGuardado['ID_CA'];
        $importeGuardado = floatval($registroGuardado['IMPORTE_PARSE']);
        
        // Buscar el valor actual correspondiente
        if (isset($datosActuales[$nroSucursal]) && isset($datosActuales[$nroSucursal][$idConcepto])) {
            $importeActual = floatval($datosActuales[$nroSucursal][$idConcepto]);
            
            // Verificar si hay diferencia (tolerancia de 0.01 por redondeos)
            // Usar abs() para comparar valores absolutos y evitar problemas con signos
            $diferencia = $importeActual - $importeGuardado;
            
            if (abs($diferencia) > 0.01) {
                $diferencias[] = [
                    'sucursal' => $nroSucursal,
                    'concepto' => $idConcepto,
                    'desc_sucursal' => $registroGuardado['DESC_SUCURS'],
                    'importeGuardado' => $importeGuardado,
                    'importeActual' => $importeActual,
                    'diferencia' => $diferencia
                ];
            }
        }
    }
    
    return $diferencias;
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

// FUNCIÓN DESHABILITADA - No se usa más
/*
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
*/

function aplicarAjuste () {

    require_once "../Class/Alquiler.php";
    require_once "../Class/sucursal.php";
    
    $alquiler = new Alquiler();
    $sucursal = new Sucursal();
    
    $data = $_POST['arrayData'];  
    $periodo = $_POST['periodo'];

    // Verificar si hay datos para ajustar
    $hayDatosParaAjustar = false;
    foreach ($data as $key => $value) {
        if (count($value) > 0) {
            $hayDatosParaAjustar = true;
            break;
        }
    }

    // Si no hay datos, verificar si ya están ajustados
    if (!$hayDatosParaAjustar) {
        // Obtener todas las sucursales y verificar si tienen conceptos 4, 5, 18 con valores
        $detalle = $alquiler->traerDetalle($periodo);
        $conceptosConValor = [];
        $conceptosAjustados = [];
        
        foreach ($detalle as $det) {
            if (in_array($det['ID_CA'], ['4', '5', '18'])) {
                $importe = floatval($det['IMPORTE_PARSE']);
                $ajustado = intval($det['AJUSTADO']);
                
                if ($importe > 0) {
                    $key = $det['NRO_SUCURS'] . '-' . $det['ID_CA'];
                    $conceptosConValor[] = $key;
                    
                    if ($ajustado == 1) {
                        $conceptosAjustados[] = $key;
                    }
                }
            }
        }
        
        if (count($conceptosConValor) == 0) {
            echo json_encode([
                'status' => 'warning',
                'code' => 3,
                'message' => 'No hay valores en los conceptos 4, 5 o 18 para ajustar'
            ]);
        } else if (count($conceptosAjustados) > 0 && count($conceptosConValor) == count($conceptosAjustados)) {
            echo json_encode([
                'status' => 'info',
                'code' => 2,
                'message' => 'El ajuste ya fue aplicado anteriormente para todos los conceptos'
            ]);
        } else if (count($conceptosAjustados) > 0) {
            echo json_encode([
                'status' => 'warning',
                'code' => 4,
                'message' => 'Algunos conceptos ya tienen ajuste aplicado. Verifique los datos.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'code' => 5,
                'message' => 'Hay valores para ajustar pero no se enviaron en la solicitud. Recargue la página.'
            ]);
        }
        die();
    }

    // Verificar coeficiente
    $coeficiente = $alquiler->traerCoeficiente($periodo);

    if($coeficiente == 0 || $coeficiente == null){
        echo json_encode([
            'status' => 'error',
            'code' => 0,
            'message' => 'El coeficiente correspondiente al período no se encuentra cargado'
        ]);
        die();
    }

    // Aplicar ajuste
    $registrosActualizados = 0;
    foreach ($data as $key => $value) {
        foreach ($value as $detalle) {
            $importe = $detalle['value'] * $coeficiente;
            $alquiler->aplicarAjuste($key, $detalle['concepto'], $importe, $periodo);
            $registrosActualizados++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'code' => 1,
        'message' => 'Ajuste aplicado correctamente',
        'registros_actualizados' => $registrosActualizados,
        'coeficiente' => $coeficiente
    ]);
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


function revertirProcesamiento() {
    
    require_once "../Class/Alquiler.php";
    
    $alquiler = new Alquiler();
    
    $periodo = $_POST['periodo'];

    try {
        $result = $alquiler->revertirProcesamiento($periodo);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Procesamiento revertido correctamente',
            'periodo' => $periodo
        ]);
        
    } catch (\Throwable $th) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al revertir procesamiento: ' . $th->getMessage(),
            'periodo' => $periodo
        ]);
    }
}

?>
