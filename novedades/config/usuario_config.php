<?php
/**
 * Configuración del tipo de usuario actual - SIMPLIFICADA
 * /novedades/config/usuario_config.php
 */

// CAMBIAR ESTA VARIABLE SEGÚN EL TIPO DE USUARIO:
// 1 = Administrador (ve todos los tipos permitidos para admin)
// 2 = Comercial (ve solo tipos comerciales)  
// 3 = Producción (ve solo tipos de producción)

$TIPO_USUARIO_ACTUAL = 3; // ← CAMBIAR AQUÍ

// Configurar en la clase Usuario
require_once __DIR__ . '/../class/Usuario.php';
Usuario::setTipoUsuario($TIPO_USUARIO_ACTUAL);
?>
