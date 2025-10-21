<?php
/**
 * Archivo de configuración del módulo de Locatarios IRSA
 * Define constantes y configuraciones específicas del módulo
 */

// Definir constantes de la aplicación
define('LOCATARIOS_VERSION', '2.0.0');
define('LOCATARIOS_BASE_PATH', __DIR__);
define('LOCATARIOS_CONTROLLER_PATH', LOCATARIOS_BASE_PATH . '/Controller');
define('LOCATARIOS_ASSETS_PATH', LOCATARIOS_BASE_PATH . '/assets');

// Configuración de fechas
define('LOCATARIOS_MAX_DATE_RANGE_DAYS', 365); // Máximo 1 año
define('LOCATARIOS_DATE_FORMAT', 'Y-m-d');

// Configuración de exportación
define('LOCATARIOS_EXPORT_PREFIX', 'locatarios_irsa');
define('LOCATARIOS_EXPORT_EXTENSION', 'txt');

// Sucursales disponibles
define('LOCATARIOS_SUCURSALES', [
    3 => 'Alto Palermo',
    6 => 'Avellaneda',
    7 => 'Abasto',
    48 => 'Alto Rosario',
    66 => 'Dot',
    76 => 'Soleil',
    78 => 'Arcos'
]);

// Stored Procedure
define('LOCATARIOS_SP_NAME', 'SJ_LOCATARIOS_IRSA');

// Configuración de base de datos
define('LOCATARIOS_DB_SERVER', 'locales'); // Nombre del servidor en la clase Conexion

/**
 * Función helper para obtener el nombre de una sucursal
 * @param int $id ID de la sucursal
 * @return string Nombre de la sucursal o "Desconocida"
 */
function getSucursalNombre($id) {
    $sucursales = LOCATARIOS_SUCURSALES;
    return isset($sucursales[$id]) ? $sucursales[$id] : 'Desconocida';
}

/**
 * Función helper para validar ID de sucursal
 * @param int $id ID de la sucursal
 * @return bool True si es válida
 */
function isValidSucursal($id) {
    return array_key_exists($id, LOCATARIOS_SUCURSALES);
}

/**
 * Función helper para obtener todas las sucursales
 * @return array Array de sucursales [id => nombre]
 */
function getAllSucursales() {
    return LOCATARIOS_SUCURSALES;
}
