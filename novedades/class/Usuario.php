<?php
/**
 * Clase Usuario - SOLO LO ESENCIAL
 * Maneja el tipo de usuario actual: 1=admin, 2=comercial, 3=producción, 4=RRHH
 */

class Usuario {
    private static $tipoUsuario = 1; // Por defecto admin
    
    // Constantes para tipos de usuario
    const TIPO_ADMIN = 1;
    const TIPO_COMERCIAL = 2;
    const TIPO_PRODUCCION = 3;
    const TIPO_RRHH = 4;
    
    public static function setTipoUsuario($tipo) {
        self::$tipoUsuario = (int)$tipo;
    }
    
    public static function getTipoUsuario() {
        return self::$tipoUsuario;
    }
    
    /**
     * Obtener descripción del tipo de usuario
     */
    public static function getTipoUsuarioDescripcion($tipo = null) {
        $tipo = $tipo ?? self::$tipoUsuario;
        
        switch($tipo) {
            case self::TIPO_ADMIN: return 'Administrador';
            case self::TIPO_COMERCIAL: return 'Comercial';
            case self::TIPO_PRODUCCION: return 'Producción';
            case self::TIPO_RRHH: return 'Recursos Humanos';
            default: return 'Desconocido';
        }
    }
    
    /**
     * Verificar si el usuario es de RRHH
     */
    public static function esUsuarioRRHH() {
        return self::$tipoUsuario === self::TIPO_RRHH;
    }
}
?>
