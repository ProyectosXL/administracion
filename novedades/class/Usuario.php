<?php
/**
 * Clase Usuario - SOLO LO ESENCIAL
 * Maneja el tipo de usuario actual: 1=admin, 2=comercial, 3=producción
 */

class Usuario {
    private static $tipoUsuario = 1; // Por defecto admin
    
    public static function setTipoUsuario($tipo) {
        self::$tipoUsuario = (int)$tipo;
    }
    
    public static function getTipoUsuario() {
        return self::$tipoUsuario;
    }
}
?>
