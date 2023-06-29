<?php 

$accion = $_GET['accion'];

switch ($accion) {
    case 'insert':
        insertarNuevo();
        break;
    
    default:
        break;
}

function insertarNuevo () {
    
    require_once "../Class/gasto.php";

    $gasto = new Gasto();

    $codCuenta = $_POST['codCuenta'];
    $sector = $_POST['sector'];
    $codRubro = $_POST['codRubro'];
    $codProrrateo = $_POST['codProrrateo'];

    $result = $gasto->insertarRelacionCuentaRubroContable($codCuenta, $sector, $codRubro, $codProrrateo);

    echo $result;

}


?>