<?php

require_once __DIR__ ."/../Class/Orden.php";

function listar (){

    $ordenes = new Orden();
    $listaDeOrdenes = $ordenes->listarOrden();
    return ( $listaDeOrdenes);
    // echo "datos"

}

function listarPorOrdenCompra($id){
    $ordenes = new Orden();
    $orden = $ordenes->traerPorOrdenCompra($id);
    return ( $orden);
}

?>