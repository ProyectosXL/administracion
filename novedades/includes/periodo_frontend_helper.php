<?php
/**
 * Utilidades para manejo de períodos desde el frontend
 * /novedades/includes/periodo_frontend_helper.php
 */

class PeriodoFrontendHelper {
    
    /**
     * Obtener opciones de período para el select (desde el mes anterior hasta 11 meses adelante)
     */
    public static function getOpcionesPeriodo() {
        $opciones = [];
        $fechaActual = new DateTime();
        
        // Desde -1 hasta +11 meses (total 13 opciones)
        for ($i = -1; $i <= 11; $i++) {
            $fecha = clone $fechaActual;
            $fecha->modify("{$i} months");
            
            $mes = (int)$fecha->format('m');
            $año = (int)$fecha->format('Y');
            
            $opciones[] = [
                'value' => "{$mes}-{$año}",
                'text' => self::getNombreMes($mes) . ' ' . $año,
                'mes' => $mes,
                'año' => $año,
                'esPeriodoActual' => ($i === 0)
            ];
        }
        
        return $opciones;
    }
    
    /**
     * Obtener período sugerido según tipo de novedad
     */
    public static function getPeriodoSugerido($tipoNovedadId = null, $fechaReferencia = null) {
        $fecha = $fechaReferencia ? new DateTime($fechaReferencia) : new DateTime();
        
        // Por defecto, usar el período actual (mes/año actual)
        $mes = (int)$fecha->format('m');
        $año = (int)$fecha->format('Y');
        
        return [
            'mes' => $mes,
            'año' => $año,
            'value' => "{$mes}-{$año}",
            'text' => self::getNombreMes($mes) . ' ' . $año
        ];
    }
    
    /**
     * Validar período seleccionado
     */
    public static function validarPeriodo($mes, $año) {
        // Validar rango básico
        if ($mes < 1 || $mes > 12) {
            return ['valido' => false, 'error' => 'Mes inválido'];
        }
        
        if ($año < 2020 || $año > 2030) {
            return ['valido' => false, 'error' => 'Año fuera del rango permitido'];
        }
        
        // Validar que esté dentro del rango permitido (mes anterior a 11 meses adelante)
        $fechaActual = new DateTime();
        $fechaPeriodo = new DateTime("{$año}-{$mes}-01");
        
        $fechaMinima = clone $fechaActual;
        $fechaMinima->modify('-1 month');
        
        $fechaMaxima = clone $fechaActual;
        $fechaMaxima->modify('+11 months');
        
        if ($fechaPeriodo < $fechaMinima || $fechaPeriodo > $fechaMaxima) {
            return ['valido' => false, 'error' => 'Período fuera del rango permitido'];
        }
        
        return ['valido' => true];
    }
    
    /**
     * Obtener nombre del mes
     */
    private static function getNombreMes($numeroMes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[$numeroMes] ?? 'Mes inválido';
    }
    
    /**
     * Formatear período como string
     */
    public static function formatearPeriodo($mes, $año) {
        return self::getNombreMes($mes) . ' ' . $año;
    }
}
