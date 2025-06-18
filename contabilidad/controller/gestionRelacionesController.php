
<?php 

$accion = $_GET['accion'];

switch ($accion) {
    case 'insert':
        insertarNuevo();
        break;
    case 'update':
        actualizarRelacion();
        break;
    case 'delete':
        eliminarRelacion();
        break;
    default:
        break;
}

function insertarNuevo() {
    require_once "../Class/gasto.php";

    $gasto = new Gasto();

    $codCuenta = $_POST['codCuenta'];
    $sector = $_POST['sector'];
    $codRubro = $_POST['codRubro'];
    $codProrrateo = $_POST['codProrrateo'];

    $result = $gasto->insertarRelacionCuentaRubroContable($codCuenta, $sector, $codRubro, $codProrrateo);

    echo $result;
}

function actualizarRelacion() {
    require_once "../Class/gasto.php";

    $gasto = new Gasto();

    $id = $_POST['id'];
    $codCuenta = $_POST['codCuenta'];
    $sector = $_POST['sector'];
    $codRubro = $_POST['codRubro'];
    $codProrrateo = $_POST['codProrrateo'];

    $result = $gasto->actualizarRelacionCuentaRubroContable($id, $codCuenta, $sector, $codRubro, $codProrrateo);

    echo $result;
}

function eliminarRelacion() {
    require_once "../Class/gasto.php";

    $gasto = new Gasto();

    $id = $_POST['id'];

    $result = $gasto->eliminarRelacionCuentaRubroContable($id);

    echo $result;
}

?>