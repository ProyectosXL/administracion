<?php
/**
 * Utilidades para cálculo de períodos en PHP
 * /novedades/class/PeriodoUtils.php
 */

class PeriodoUtils {
    
    /**
     * Calcular el primer     /**
     * Calcular período según tipo de novedad con soporte para "Período siguiente"
     */
    public static function calcularPeriodoSegunTipoCompleto($tipoNovedad, $fechaReferencia = null) {
        if (!$tipoNovedad || !isset($tipoNovedad['cierre'])) {
            return self::calcularPeriodoConDiaCierre(28, $fechaReferencia, false);
        }
        
        $hoy = $fechaReferencia ? new DateTime($fechaReferencia) : new DateTime();
        $year = (int)$hoy->format('Y');
        $month = (int)$hoy->format('m');
        
        // Obtener día de cierre efectivo
        $diaCierre = self::obtenerDiaCierreEfectivo($tipoNovedad['cierre'], $year, $month);
        
        // Determinar si es "1er día hábil" para aplicar lógica especial
        $esPrimerDiaHabil = $tipoNovedad['cierre'] === '1er día hábil';
        
        // Calcular período base
        $periodo = self::calcularPeriodoConDiaCierre($diaCierre, $fechaReferencia, $esPrimerDiaHabil);
        
        // Si el tipo de novedad tiene configurado "Período siguiente", adelantar un mes
        if (isset($tipoNovedad['corte']) && $tipoNovedad['corte'] === 'Período siguiente') {
            $periodo['month']++;
            if ($periodo['month'] > 12) {
                $periodo['month'] = 1;
                $periodo['year']++;
            }
        }
        
        return $periodo;
    }
    
    /**
     * Obtener información completa del período incluyendo fechas
     * ACTUALIZADO: Ahora calcula fechas para período mensual (1er día - último día del mes)
     */
    public static function obtenerInfoCompletaPeriodo($periodo) {
        // Fecha de inicio: primer día del mes del período
        $fechaInicio = new DateTime($periodo['year'] . "-" . str_pad($periodo['month'], 2, '0', STR_PAD_LEFT) . "-01");
        
        // Fecha de fin: último día del mes del período
        $fechaFin = new DateTime($periodo['year'] . "-" . str_pad($periodo['month'], 2, '0', STR_PAD_LEFT) . "-01");
        $fechaFin->modify('last day of this month');
        
        return [
            'periodo' => $periodo,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'fechaInicioString' => $fechaInicio->format('d/m/Y'),
            'fechaFinString' => $fechaFin->format('d/m/Y'),
            'periodoString' => self::formatearPeriodo($periodo),
            'rangoPeriodo' => $fechaInicio->format('d/m/Y') . ' al ' . $fechaFin->format('d/m/Y')
        ];
    }
    
    /**
     * Calcular el primer día hábil del mes
     * Versión simplificada sin API (para el backend)
     */
    public static function calcularPrimerDiaHabil($year, $month) {
        // Lista básica de feriados fijos de Argentina (se puede expandir)
        $feriadosFijos = [
            '01-01', // Año Nuevo
            '05-01', // Día del Trabajador  
            '05-25', // Revolución de Mayo
            '07-09', // Día de la Independencia
            '12-08', // Inmaculada Concepción
            '12-25'  // Navidad
        ];
        
        // Formatear mes para comparación
        $mesFormateado = str_pad($month, 2, '0', STR_PAD_LEFT);
        
        // Buscar el primer día hábil
        for ($dia = 1; $dia <= 31; $dia++) {
            $fecha = new DateTime("$year-$mesFormateado-" . str_pad($dia, 2, '0', STR_PAD_LEFT));
            
            // Verificar que la fecha sea válida para el mes
            if ($fecha->format('m') != $mesFormateado) break;
            
            $diaSemana = (int)$fecha->format('w'); // 0 = domingo, 6 = sábado
            $fechaFormateada = $fecha->format('m-d');
            
            // Si no es fin de semana y no es feriado fijo
            if ($diaSemana !== 0 && $diaSemana !== 6 && !in_array($fechaFormateada, $feriadosFijos)) {
                return $dia;
            }
        }
        
        // Fallback
        return 1;
    }
    
    /**
     * Obtener día de cierre efectivo
     */
    public static function obtenerDiaCierreEfectivo($valorCierre, $year, $month) {
        if ($valorCierre === '1er día hábil') {
            return self::calcularPrimerDiaHabil($year, $month);
        }
        
        // Si es un número, convertir a entero
        $diaNumerico = (int)$valorCierre;
        if ($diaNumerico >= 1 && $diaNumerico <= 31) {
            return $diaNumerico;
        }
        
        // Fallback
        return 28;
    }
    
    /**
     * Calcular período basado en día de cierre
     * ACTUALIZADO: Ahora devuelve el MES actual independientemente del día de cierre
     */
    public static function calcularPeriodoConDiaCierre($diaCierre, $fechaReferencia = null, $esPrimerDiaHabil = false) {
        $hoy = $fechaReferencia ? new DateTime($fechaReferencia) : new DateTime();
        $diaActual = (int)$hoy->format('d');
        
        $yearPeriodo = (int)$hoy->format('Y');
        $mesPeriodo = (int)$hoy->format('m');
        
        // NUEVA LÓGICA: Siempre devolver el mes actual
        // Independientemente del día de cierre, el período es el mes calendario actual
        
        $fechaPeriodo = new DateTime("$yearPeriodo-" . str_pad($mesPeriodo, 2, '0', STR_PAD_LEFT) . "-01");
        
        return [
            'year' => $yearPeriodo,
            'month' => $mesPeriodo,
            'diaCierre' => $diaCierre,
            'fechaPeriodo' => $fechaPeriodo,
            'esPrimerDiaHabil' => $esPrimerDiaHabil,
            'logicaAplicada' => 'mes calendario actual',
            'periodoString' => str_pad($mesPeriodo, 2, '0', STR_PAD_LEFT) . '/' . $yearPeriodo
        ];
    }
    
    /**
     * Calcular período según configuración de tipo de novedad
     */
    public static function calcularPeriodoSegunTipo($tipoNovedad, $fechaReferencia = null) {
        if (!$tipoNovedad || !isset($tipoNovedad['cierre'])) {
            return self::calcularPeriodoConDiaCierre(28, $fechaReferencia, false);
        }
        
        $hoy = $fechaReferencia ? new DateTime($fechaReferencia) : new DateTime();
        $year = (int)$hoy->format('Y');
        $month = (int)$hoy->format('m');
        
        // Obtener día de cierre efectivo
        $diaCierre = self::obtenerDiaCierreEfectivo($tipoNovedad['cierre'], $year, $month);
        
        // Determinar si es "1er día hábil" para aplicar lógica especial
        $esPrimerDiaHabil = $tipoNovedad['cierre'] === '1er día hábil';
        
        return self::calcularPeriodoConDiaCierre($diaCierre, $fechaReferencia, $esPrimerDiaHabil);
    }
    
    /**
     * Formatear período como string MM/YYYY
     */
    public static function formatearPeriodo($periodo) {
        return str_pad($periodo['month'], 2, '0', STR_PAD_LEFT) . '/' . $periodo['year'];
    }
    
    /**
     * Calcular fecha de inicio del período (fecha de vigencia)
     * ACTUALIZADO: Ahora es el primer día del mes del período
     */
    public static function calcularFechaInicioPeriodo($periodo) {
        // La fecha de inicio es el primer día del mes del período
        $fechaInicio = new DateTime($periodo['year'] . "-" . str_pad($periodo['month'], 2, '0', STR_PAD_LEFT) . "-01");
        
        return $fechaInicio;
    }
}
