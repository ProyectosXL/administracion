<?php 
    $accion = $_GET['accion'];
        
    include_once '../Class/Vendedor.php';
    $vendedor = new Vendedor();

    switch ($accion) {
        case 'traerSucursales':
            traerSucursales($vendedor);
            break;
        
        case 'crearGrupo':
            crearGrupo($vendedor);
            break;
        
        case 'traerGrupos':
            traerGrupos($vendedor);
            break;
        
        case 'borrarGrupo':
            borrarGrupo($vendedor);
            break;
        
        case 'traerLocalesPorGrupo':
            traerLocalesPorGrupo($vendedor);
            break;
        
        case 'editarGrupo':
            editarGrupo($vendedor);
            break;
        
        default:
            # code...
            break;
    }
    


    function traerSucursales($vendedor){
 
        $result = $vendedor->traertSucursales();
        $result = json_encode($result);

        echo $result;
    }


    function crearGrupo ($vendedor) {
        $nombreGrupo = $_POST['nombreGrupo'];
        $localesPorGrupo = $_POST['sucursales'];
      
        $result = $vendedor->crearGrupoEnc($nombreGrupo);

        if ($result == true) {
            $result = $vendedor->crearGrupoDet($localesPorGrupo);
        }

        
        echo $result;
    }

    function traerGrupos($vendedor){
        
        $result = $vendedor->traerGrupos();
        $result = json_encode($result);

        echo $result;
    } 

    function borrarGrupo($vendedor){

        $nombreGrupo = $_POST['nombreGrupo'];

        $result = $vendedor->borrarGrupo($nombreGrupo);

        echo $result;

    }

    function traerLocalesPorGrupo($vendedor){

        $nombreGrupo = $_POST['grupos'];
    

        $result = $vendedor->traerLocalesPorGrupo($nombreGrupo);
        $result = json_encode($result);

        echo $result;

    }

    function editarGrupo ($vendedor) {

        $nombreGrupo = $_POST['nombreGrupo'];
        $localesPorGrupo = $_POST['sucursales'];

  
        $result = $vendedor->editarGrupo($nombreGrupo, $localesPorGrupo);

        
        echo $result;
    }
?>