<?php
require_once "../Class/sucursal.php";
$accion = $_GET['accion'];

switch ($accion) {
    case 'checkFactura':
        marcarFacturado();
        break;
    
    case 'checkControl':
        marcarControlado();
        break;

    case 'marcarRecibido':
        marcarRecibido();
        break;
    
    case 'contarImagenes':
        contarFotosEnCarpeta();
        break;
    
    default:
        # code...
        break;
}


function marcarFacturado (){

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

    $sucursal = new Sucursal();

    $sucursal->marcarFacturado($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control);


}

function marcarControlado (){

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

    $sucursal = new Sucursal();

    $sucursal->marcarControlado($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto, $leyenda, $factura, $control);


}

function marcarRecibido (){

    $fecha = $_POST['fecha'];
    $nroSucursal = $_POST['nroSucursal'];
    $tipoComprobante = $_POST['tipoComprobante'];
    $nroComprobante = $_POST['nroComprobante'];
    $codCuenta = $_POST['codCuenta'];
    $descripcionCuenta = $_POST['descripcionCuenta'];
    $monto = $_POST['monto'];

    $sucursal = new Sucursal();

    $result = $sucursal->marcarRecibido($fecha, $nroSucursal, $tipoComprobante, $nroComprobante, $codCuenta, $descripcionCuenta, $monto);
    
    echo $result;

}

function contarFotosEnCarpeta() {
    
    $nComp = (isset($_POST['nComp'])) ? $_POST['nComp'] : "";
    
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

    
        $fileName = $nComp;
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
?>