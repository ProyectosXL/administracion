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
    
    default:
        # code...
        break;
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
    $nroSucursal = $_POST['nroSucursal'];
    $codCuenta = (isset ($_POST['codCuenta'])) ? $_POST['codCuenta'] : "";
    $root = $_SERVER["DOCUMENT_ROOT"];

    $targetDir = $root.'/Imagenes/egresosCaja/';
 
    if(isset($_POST['arrayNcomp'])){

        $arrayArticulos = $_POST['arrayNcomp'];

    }

 
    if(isset($arrayArticulos)){
        
        $contadorFotos = 0;
        $datosDeLosArchivos = [];
        $datosDeLosArchivos['cantidad'] = 0;
        foreach ($arrayArticulos as $key => $codigo) {
            $fileName = $codigo;
            // Abre el directorio
            if ($gestor = opendir($targetDir)) {
                // Recorre los archivos en el directorio
                while (($archivo = readdir($gestor)) !== false) {
                    // Ignora las carpetas "." y ".."
                    if ($archivo != "." && $archivo != "..") {
        
                        if (stripos(pathinfo($archivo, PATHINFO_FILENAME), $fileName) !== false) {
                            // $contadorFotos++;
                            $datosDeLosArchivos['cantidad'] ++;
        
                            $datosDeLosArchivos['nombre'][] = $codigo;
        
                            // array_push($nombreArchivo, pathinfo($archivo, PATHINFO_FILENAME));
                        } 
         
                     
                    }
                }
        
                // Cierra el directorio
                closedir($gestor);
            }

        }

    }else{

    
        $fileName = $nComp.$nroSucursal.$codCuenta;
        $contadorFotos = 0;
        $datosDeLosArchivos = [];
        $datosDeLosArchivos['cantidad'] = 0; 

        // Abre el directorio
        if ($gestor = opendir($targetDir)) {
            // Recorre los archivos en el directorio
            while (($archivo = readdir($gestor)) !== false) {
                // Ignora las carpetas "." y ".."
                if ($archivo != "." && $archivo != "..") {

                    $fileName = str_replace(' ', '', $fileName);

                    if (stripos(pathinfo($archivo, PATHINFO_FILENAME), $fileName) !== false) {
                        
                        // $contadorFotos++;
                        $datosDeLosArchivos['cantidad'] ++;

                        $datosDeLosArchivos['nombre'][] = pathinfo($archivo, PATHINFO_FILENAME);

                        // array_push($nombreArchivo, pathinfo($archivo, PATHINFO_FILENAME));
                    } 
    
                
                }
            }

            // Cierra el directorio
            closedir($gestor);
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