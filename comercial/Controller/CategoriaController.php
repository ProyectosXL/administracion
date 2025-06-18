<?php
$accion = $_GET['accion'];

switch ($accion) {
    case 'insertarNuevo':

        insertarNuevo();

        break;

    case 'actualizarDescCategoria':
        actualizarDescCategoria();

        break;

    default:

        break;
}

function insertarNuevo () {

    require_once __DIR__.'/../Class/Categoria.php';

    $categoria = new Categoria();

    $codCategoria = $_POST['codCategoria'];
    $descCategoria = $_POST['descCategoria'];
    $siglaRubro = $_POST['siglaRubro'];


    $result = $categoria->insertarNuevaCategoria($codCategoria, $descCategoria, $siglaRubro);
    
    echo $result;

}

function actualizarDescCategoria () {
    
    require_once __DIR__.'/../Class/Categoria.php';
    $categoria = new Categoria();

    $nuevoValor = $_POST['nuevoValor'];
    $rubro = $_POST['rubro'];
    $Categoria = $_POST['Categoria'];


    $categoria->actualizarDescripcionCategoria($nuevoValor, $rubro, $Categoria);

    return true;
}

?>