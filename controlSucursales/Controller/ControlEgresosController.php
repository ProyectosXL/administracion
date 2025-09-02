<?php
require_once "../Class/sucursal.php";
$accion = $_GET['accion'];
$sucursal = new Sucursal();

switch ($accion) {
    case 'checkFactura':
        marcarFacturado($sucursal);
        break;

    case 'uncheckFactura':
        uncheckFactura($sucursal);
        break;
    
    case 'checkControl':
        marcarControlado($sucursal);
        break;

    case 'uncheckControl':
        uncheckControl($sucursal);
        break;

    case 'marcarRecibido':
        marcarRecibido($sucursal);
        break;
    
    case 'contarImagenes':
        contarFotosEnCarpeta();
        break;
    
    case 'checkContabilizar':
        contabilizar(1);
        break;
    
    case 'uncheckContabilizar':
        contabilizar(0);
        break;
    
    case 'autorizarEgreso':
        autorizarEgreso();
        break;
    
    case 'controlTesoreria':
        controlTesoreria($sucursal);
        break;
    
    case 'guardarObservaciones':
        guardarObservaciones($sucursal);
        break;
    
    case 'traerRecibosParaVincular':
        traerRecibosParaVincular($sucursal);
        break;

    case 'vincularRecibo':
        vincularRecibo($sucursal);
        break;

    default:
        break;
}

function traerRecibosParaVincular($sucursal) {
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
    $recibos = $sucursal->traerRecibosParaVincular($searchTerm);
    header('Content-Type: application/json');
    echo json_encode($recibos);
}

function vincularRecibo($sucursal) {
    ob_start();
    session_start();
    $usuario = isset($_SESSION['user']) ? $_SESSION['user'] : 'Desconocido';

    $original_cod_comp = $_POST['original_cod_comp'];
    $original_n_comp = $_POST['original_n_comp'];
    $vinculado_cod_comp = $_POST['vinculado_cod_comp'];
    $vinculado_n_comp = $_POST['vinculado_n_comp'];

    $resultado = $sucursal->vincularReciboDb($original_cod_comp, $original_n_comp, $vinculado_cod_comp, $vinculado_n_comp, $usuario);

    ob_end_clean();
    header('Content-Type: application/json');
    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Recibo vinculado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al vincular el recibo.']);
    }
    exit();
}

function marcarFacturado ($sucursal) {
    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];
    $leyenda = $_POST['leyenda'];
    $factura = $_POST['factura'];
    $control = $_POST['control'];    

    $sucursal->marcarFacturado($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control);
}

function uncheckFactura ($sucursal) {
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $monto = $_POST['monto'];

    $sucursal->uncheckFactura($nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto);
}

function marcarControlado ($sucursal){
    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];
    $leyenda = $_POST['leyenda'];
    $factura = $_POST['factura'];
    $control = $_POST['control'];  
    $observaciones = $_POST['observaciones'];  
    
    $sucursal->marcarControlado($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control, $observaciones);
}

function uncheckControl ($sucursal){
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $monto = $_POST['monto'];

    $sucursal->uncheckControl($nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto);
}

function marcarRecibido ($sucursal){
    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nroSucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];
    $observaciones = $_POST['observaciones'];

    $result = $sucursal->marcarRecibido($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $observaciones);
    
    echo $result;
}

function controlTesoreria($sucursal){
    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nroSucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];

    $result = $sucursal->controlTesoreria($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto);
    
    echo $result;
}

function contarFotosEnCarpeta() {
    $nComp = (isset($_POST['nComp'])) ? $_POST['nComp'] : "";
    $nroSucursal = (isset($_POST['nroSucursal'])) ? $_POST['nroSucursal'] : "";
    $codCta = (isset($_POST['codCta'])) ? $_POST['codCta'] : "";
    $codComp = (isset($_POST['codComp'])) ? $_POST['codComp'] : "";
    $fechaComprobante = (isset($_POST['fechaComprobante'])) ? $_POST['fechaComprobante'] : "";

    $root = $_SERVER["DOCUMENT_ROOT"];
    $targetDir = $root.'/Imagenes/egresosCaja/';
    
    if(isset($_POST['arrayNcomp'])){
        $arrayArticulos = $_POST['arrayNcomp'];
        $contadorFotos = 0;
        $datosDeLosArchivos = [];
        $datosDeLosArchivos['cantidad'] = 0;
        
        foreach ($arrayArticulos as $key => $codigo) {
            $fileName = $codigo;
            
            if ($gestor = opendir($targetDir)) {
                while (($archivo = readdir($gestor)) !== false) {
                    if ($archivo != "." && $archivo != "..") {
                        if (stripos(pathinfo($archivo, PATHINFO_FILENAME), $fileName) !== false) {
                            $datosDeLosArchivos['cantidad']++;
                            $datosDeLosArchivos['nombre'][] = $codigo;
                        } 
                    }
                }
                closedir($gestor);
            }
        }
    } else {
        // Buscar fotos individuales con lógica de compatibilidad
        $datosDeLosArchivos = [];
        $datosDeLosArchivos['cantidad'] = 0;
        $datosDeLosArchivos['nombre'] = [];
        
        // Si tenemos todos los datos necesarios, buscar con nueva nomenclatura
        if (!empty($nComp) && !empty($codCta) && !empty($codComp) && !empty($nroSucursal)) {
            $nombreNuevo = $nComp . $nroSucursal . $codCta . $codComp;
            
            if ($gestor = opendir($targetDir)) {
                while (($archivo = readdir($gestor)) !== false) {
                    if ($archivo != "." && $archivo != "..") {
                        if (stripos(pathinfo($archivo, PATHINFO_FILENAME), $nombreNuevo) !== false) {
                            $datosDeLosArchivos['cantidad']++;
                            $datosDeLosArchivos['nombre'][] = pathinfo($archivo, PATHINFO_FILENAME);
                        }
                    }
                }
                closedir($gestor);
            }
        }
        
        // Si no encuentra con nueva nomenclatura, buscar con la vieja
        // PERO verificar la validación del año
        if ($datosDeLosArchivos['cantidad'] === 0 && !empty($nComp)) {
            $puedeUsarModalidadVieja = true;
            
            // Verificar si existe registro guardado con modalidad vieja
            if (!empty($codCta) && !empty($nroSucursal)) {
                require_once "../Class/sucursal.php";
                $sucursal = new Sucursal();
                
                // Obtener el año del comprobante actual para comparar años
                $añoComprobante = date('Y', strtotime($fechaComprobante));
                
                // Buscar en la base de datos si hay registro con modalidad vieja
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                $conexion = $sucursal->cid_central;
                if(isset($_SESSION['entorno']) && $_SESSION['entorno'] == 'suc_uy'){
                    $conexion = $sucursal->cid_uy;
                }
                
                $sql = "SELECT FECHA_GUARDADO FROM SJ_EGRESOS_DE_CAJA_GUARDADO 
                        WHERE N_COMP = ? AND COD_CTA = ? AND NRO_SUCURSAL = ? 
                        AND (COD_COMP IS NULL OR COD_COMP = '')";
                
                $stmt = sqlsrv_prepare($conexion, $sql, array($nComp, $codCta, $nroSucursal));
                
                if ($stmt && sqlsrv_execute($stmt)) {
                    if ($row = sqlsrv_fetch_array($stmt)) {
                        $fechaGuardado = $row['FECHA_GUARDADO'];
                        $añoGuardado = $fechaGuardado->format('Y');
                        
                        // Solo permitir ver fotos de modalidad vieja si son del mismo año
                        if ($añoComprobante != $añoGuardado) {
                            $puedeUsarModalidadVieja = false;
                        }
                    }
                }
            }
            
            if ($puedeUsarModalidadVieja) {
                $nombreViejo = $nComp;
                if (!empty($nroSucursal) && !empty($codCta)) {
                    $nombreViejo = $nComp . $nroSucursal . $codCta;
                }
                
                if ($gestor = opendir($targetDir)) {
                    while (($archivo = readdir($gestor)) !== false) {
                        if ($archivo != "." && $archivo != "..") {
                            if (stripos(pathinfo($archivo, PATHINFO_FILENAME), $nombreViejo) !== false) {
                                $datosDeLosArchivos['cantidad']++;
                                $datosDeLosArchivos['nombre'][] = pathinfo($archivo, PATHINFO_FILENAME);
                            }
                        }
                    }
                    closedir($gestor);
                }
            }
        }
    }

    echo json_encode($datosDeLosArchivos);
}

function contabilizar ($contabilizado) {
    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nro_sucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $monto = $_POST['monto'];

    $sucursal = new Sucursal();
    $result = $sucursal->contabilizar($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $monto,$contabilizado);
    
    echo $result;
}

function autorizarEgreso (){
    $fecha= $_POST['fecha'];
    $nroSucursal= $_POST['nroSucursal'];
    $tipoComp= $_POST['tipoComp'];
    $comprobante= $_POST['comprobante'];
    $codCuenta= $_POST['codCuenta'];
    $descCuenta= $_POST['descCuenta'];
    $monto= $_POST['monto'];
    $leyenda= $_POST['leyenda'];
    $fechaDeHoy = date('Y-m-d');

    $sucursal = new Sucursal();
    $result = $sucursal->autorizarEgreso ($fecha, $nroSucursal, $tipoComp, $comprobante, $codCuenta, $descCuenta, $monto, $leyenda, $fechaDeHoy);
  
    echo $result;
}

function guardarObservaciones ($sucursal) {
    $observaciones = $_POST['observaciones'];
    $nroSucursal = $_POST['nroSucursal'];
    $nroComprobante = $_POST['nroComprobante'];

    $sucursal->guardarObservaciones($observaciones, $nroSucursal, $nroComprobante);

    return true; 
}
?>