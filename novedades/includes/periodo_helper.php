<?php
/**
 * Helper para mostrar información del período actual - ACTUALIZADO
 * /novedades/includes/periodo_helper.php
 */

require_once __DIR__ . '/../class/PeriodoUtils.php';

class PeriodoHelper {
    
    /**
     * Obtener información del período actual basado en día de cierre por defecto (día 28)
     */
    public static function getPeriodoActual($diaCierre = 28) {
        $periodo = PeriodoUtils::calcularPeriodoConDiaCierre($diaCierre);
        $info = PeriodoUtils::obtenerInfoCompletaPeriodo($periodo);
        
        // NUEVO: Formatear como "Mes Año (MM/YY)"
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        $mesNombre = $meses[$periodo['month']] ?? 'Mes';
        $yearCorto = substr($periodo['year'], -2);
        $periodoFormateado = "{$mesNombre} {$periodo['year']} ({$periodo['month']}/{$yearCorto})";
        
        return [
            'periodo' => $info['periodoString'], // Mantener formato original para BD
            'rango' => $info['rangoPeriodo'], // Mantener rango original
            'fechaInicio' => $info['fechaInicio'],
            'fechaFin' => $info['fechaFin'],
            'badge' => "Período {$periodoFormateado}",
            'periodoFormateado' => $periodoFormateado // NUEVO: solo mes y año
        ];
    }
    
    /**
     * Obtener período actual con lógica de primer día hábil
     */
    public static function getPeriodoActualPrimerDiaHabil() {
        $hoy = new DateTime();
        $year = (int)$hoy->format('Y');
        $month = (int)$hoy->format('m');
        
        $diaCierre = PeriodoUtils::calcularPrimerDiaHabil($year, $month);
        $periodo = PeriodoUtils::calcularPeriodoConDiaCierre($diaCierre, null, true);
        $info = PeriodoUtils::obtenerInfoCompletaPeriodo($periodo);
        
        // NUEVO: Formatear como "Mes Año (MM/YY)"
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        $mesNombre = $meses[$periodo['month']] ?? 'Mes';
        $yearCorto = substr($periodo['year'], -2);
        $periodoFormateado = "{$mesNombre} {$periodo['year']} ({$periodo['month']}/{$yearCorto})";
        
        return [
            'periodo' => $info['periodoString'],
            'rango' => $info['rangoPeriodo'],
            'fechaInicio' => $info['fechaInicio'],
            'fechaFin' => $info['fechaFin'],
            'badge' => "Período {$periodoFormateado} (1er día hábil: {$diaCierre})",
            'periodoFormateado' => $periodoFormateado
        ];
    }
    
    /**
     * Renderizar badge del período para usar en las páginas
     */
    public static function renderPeriodoBadge($diaCierre = 28) {
        $info = ($diaCierre === '1er día hábil') 
            ? self::getPeriodoActualPrimerDiaHabil() 
            : self::getPeriodoActual($diaCierre);
            
        return $info['badge'];
    }
    
    /**
     * NUEVO: Obtener solo el formato de mes y año
     */
    public static function getPeriodoFormateado($diaCierre = 28) {
        $info = self::getPeriodoActual($diaCierre);
        return $info['periodoFormateado'];
    }
}