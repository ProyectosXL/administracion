<?php

/**
 * Clase para manejo de fechas hábiles (días laborables)
 * Valida fines de semana y feriados argentinos
 */
class FechasHabiles {
    
    /**
     * Obtiene feriados argentinos de un año desde API externa
     * @param int $year Año a consultar
     * @return array Array de fechas en formato Y-m-d
     */
    public static function obtenerFeriadosAPI($year) {
        $cacheFile = __DIR__ . "/cache/feriados_ar_{$year}.json";
        
        // Verificar cache (válido por 30 días)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 30 * 24 * 3600)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            return $data;
        }
        
        // Llamar a API Nager.Date
        $url = "https://date.nager.at/api/v3/publicholidays/{$year}/AR";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para servidores sin certificados actualizados
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $feriados = json_decode($response, true);
            if (is_array($feriados)) {
                $fechas = array_column($feriados, 'date');
                
                // Guardar en cache
                if (!is_dir(__DIR__ . '/cache')) {
                    mkdir(__DIR__ . '/cache', 0755, true);
                }
                file_put_contents($cacheFile, json_encode($fechas));
                
                return $fechas;
            }
        }
        
        // Fallback: lista manual mínima
        return self::obtenerFeriadosFallback($year);
    }
    
    /**
     * Fallback manual de feriados principales (si API falla)
     * @param int $year Año
     * @return array
     */

    private static function obtenerFeriadosFallback($year) {
        // Feriados inamovibles principales de Argentina
        $feriados = [
            "$year-01-01", // Año Nuevo
            "$year-05-01", // Día del Trabajador
            "$year-05-25", // Revolución de Mayo
            "$year-06-20", // Día de la Bandera
            "$year-07-09", // Independencia
            "$year-12-25", // Navidad
        ];
        
        // Agregar algunos feriados movibles aproximados para 2024-2026
        if ($year == 2024) {
            $feriados = array_merge($feriados, [
                '2024-02-12', '2024-02-13', // Carnaval
                '2024-03-24', // Memoria, Verdad y Justicia
                '2024-03-29', '2024-04-02', // Semana Santa
                '2024-06-17', // Güemes
                '2024-08-19', // San Martín (3er lunes de agosto)
                '2024-10-14', // Diversidad Cultural (2do lunes octubre)
                '2024-11-18', // Soberanía Nacional (4to lunes noviembre)
                '2024-12-08', // Inmaculada Concepción
            ]);
        } elseif ($year == 2025) {
            $feriados = array_merge($feriados, [
                '2025-03-03', '2025-03-04', // Carnaval
                '2025-03-24', // Memoria, Verdad y Justicia
                '2025-04-02', '2025-04-18', // Semana Santa
                '2025-08-18', // San Martín
                '2025-10-13', // Diversidad Cultural
                '2025-11-24', // Soberanía Nacional
                '2025-12-08', // Inmaculada Concepción
                '2025-12-25', // Adicional Navidad
            ]);
        } elseif ($year == 2026) {
            $feriados = array_merge($feriados, [
                '2026-02-16', '2026-02-17', // Carnaval (estimado)
                '2026-03-24', // Memoria, Verdad y Justicia
                '2026-04-03', '2026-04-06', // Semana Santa (estimado)
                '2026-08-17', // San Martín
                '2026-10-12', // Diversidad Cultural
                '2026-11-23', // Soberanía Nacional
                '2026-12-08', // Inmaculada Concepción
                '2026-12-25', // Adicional Navidad
            ]);
        }
        
        return $feriados;
    }
    
    /**
     * Verifica si una fecha es día hábil (no sábado, domingo ni feriado)
     * @param string $fecha Fecha en formato Y-m-d
     * @return bool
     */
    public static function esDiaHabil($fecha) {
        if (empty($fecha)) {
            return true;
        }
        
        $date = new DateTime($fecha);
        $diaSemana = (int)$date->format('N');  // 1=Lunes, 7=Domingo
        
        // Verificar fin de semana
        if ($diaSemana == 6 || $diaSemana == 7) {  // Sábado o Domingo
            return false;
        }
        
        // Obtener feriados del año
        $year = (int)$date->format('Y');
        $feriados = self::obtenerFeriadosAPI($year);
        
        // Verificar feriado
        return !in_array($fecha, $feriados);
    }
    
    /**
     * Obtiene el siguiente día hábil a partir de una fecha
     * @param string $fecha Fecha en formato Y-m-d
     * @return string Siguiente día hábil en formato Y-m-d
     */
    public static function obtenerSiguienteDiaHabil($fecha) {
        $date = new DateTime($fecha);
        $intentos = 0;
        $maxIntentos = 15;  // Evitar loops infinitos
        
        while ($intentos < $maxIntentos) {
            $date->modify('+1 day');
            $fechaStr = $date->format('Y-m-d');
            
            if (self::esDiaHabil($fechaStr)) {
                return $fechaStr;
            }
            
            $intentos++;
        }
        
        // Fallback: retornar fecha original + 1 día
        return $fecha;
    }
    
    /**
     * Obtiene el motivo por el cual una fecha no es día hábil
     * @param string $fecha Fecha en formato Y-m-d
     * @return string
     */
    public static function obtenerMotivoNoHabil($fecha) {
        $date = new DateTime($fecha);
        $diaSemana = (int)$date->format('N');
        
        // Verificar fin de semana
        if ($diaSemana == 6) return "Sábado";
        if ($diaSemana == 7) return "Domingo";
        
        // Verificar feriado
        $year = (int)$date->format('Y');
        $feriados = self::obtenerFeriadosAPI($year);
        
        if (in_array($fecha, $feriados)) {
            return "Feriado";
        }
        
        return "Día no hábil";
    }
    
    /**
     * Valida una fecha y retorna sugerencia de corrección si no es día hábil
     * @param string $fecha Fecha a validar en formato Y-m-d
     * @return array ['valida' => bool, 'fecha_sugerida' => string|null, 'motivo' => string|null]
     */
    public static function validarFechaHabil($fecha) {
        if (empty($fecha)) {
            return [
                'valida' => true,
                'fecha_sugerida' => null,
                'motivo' => null
            ];
        }
        
        if (!self::esDiaHabil($fecha)) {
            $fechaSugerida = self::obtenerSiguienteDiaHabil($fecha);
            $motivo = self::obtenerMotivoNoHabil($fecha);
            
            return [
                'valida' => false,
                'fecha_sugerida' => $fechaSugerida,
                'motivo' => $motivo
            ];
        }
        
        return [
            'valida' => true,
            'fecha_sugerida' => null,
            'motivo' => null
        ];
    }
    
    /**
     * Obtiene array de feriados para múltiples años (para JavaScript)
     * @param int $yearInicio Año inicial
     * @param int $yearFin Año final
     * @return array
     */
    public static function obtenerFeriadosRango($yearInicio, $yearFin) {
        $todosLosFeriados = [];
        
        for ($year = $yearInicio; $year <= $yearFin; $year++) {
            $feriados = self::obtenerFeriadosAPI($year);
            $todosLosFeriados = array_merge($todosLosFeriados, $feriados);
        }
        
        return array_unique($todosLosFeriados);
    }
}
