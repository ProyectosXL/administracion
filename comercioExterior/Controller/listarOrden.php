<?php

require_once __DIR__ ."/../Class/Orden.php";

function listar ($idEncabezado = null){

    $ordenes = new Orden();
    $listaDeOrdenes = $ordenes->listarOrden($idEncabezado);
    return ( $listaDeOrdenes);


}

function listarPorOrdenCompra($id){
    $ordenes = new Orden();
    $orden = $ordenes->traerPorOrdenCompra($id);
    return ( $orden);
}

?>