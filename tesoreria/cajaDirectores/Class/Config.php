<?php

/**
 * Clase Config
 * Gestiona la configuración de la aplicación Caja Directores
 */
class Config {
    /**
     * Fecha de inicio de uso de la aplicación
     * Todos los cálculos de saldo considerarán movimientos desde esta fecha en adelante
     * Formato: YYYY-MM-DD
     */
    private const FECHA_INICIO_APP = '2025-11-05';
    
    /**
     * Obtiene la fecha de inicio de la aplicación
     * @return string Fecha en formato YYYY-MM-DD
     */
    public static function getFechaInicioApp(): string {
        return self::FECHA_INICIO_APP;
    }
    
    /**
     * Verifica si una fecha es válida para los cálculos
     * (debe ser igual o posterior a la fecha de inicio)
     * @param string $fecha Fecha a validar en formato YYYY-MM-DD
     * @return bool
     */
    public static function esFechaValida(string $fecha): bool {
        return strtotime($fecha) >= strtotime(self::FECHA_INICIO_APP);
    }
    
    /**
     * Obtiene la fecha de inicio como DateTime
     * @return DateTime
     */
    public static function getFechaInicioDateTime(): DateTime {
        return new DateTime(self::FECHA_INICIO_APP);
    }
}
