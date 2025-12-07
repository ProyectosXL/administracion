<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../Class/proveedor.php';
require_once '../../Class/encabezado.php';

// Obtener ID del despacho
$idDespacho = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idDespacho === 0) {
    header('Location: ../gestionDespachos.php');
    exit;
}

// Obtener datos del despacho
$encabezadoClass = new Encabezado();
$proveedorClass = new Proveedor();

// Aquí deberías crear un método en la clase Encabezado para obtener un despacho por ID
// Por ahora redirigimos a cargaInicial con el ID como parámetro para edición
header('Location: ../cargaInicial.php?id=' . $idDespacho . '&modo=edicion');
exit;
?>
