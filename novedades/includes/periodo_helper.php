<?php
/**
 * Helper para mostrar información del período actual
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
        
        return [
            'periodo' => $info['periodoString'],
            'rango' => $info['rangoPeriodo'],
            'fechaInicio' => $info['fechaInicio'],
            'fechaFin' => $info['fechaFin'],
            'badge' => "Período " . $info['periodoString'] . " | " . $info['rangoPeriodo']
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
        
        return [
            'periodo' => $info['periodoString'],
            'rango' => $info['rangoPeriodo'],
            'fechaInicio' => $info['fechaInicio'],
            'fechaFin' => $info['fechaFin'],
            'badge' => "Período " . $info['periodoString'] . " | " . $info['rangoPeriodo'] . " (1er día hábil: " . $diaCierre . ")"
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
}
