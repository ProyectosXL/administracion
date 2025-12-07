<?php

require_once __DIR__ . "/../class/Orden.php";

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
function listarPorFecha($desde, $hasta){
    $ordenes = new Orden();
    $orden = $ordenes->traerOrdenPorFecha($desde, $hasta);
    return ( $orden);
}

?>