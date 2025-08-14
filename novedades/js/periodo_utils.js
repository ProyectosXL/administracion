/**
 * Utilidades para cálculo de períodos
 * /novedades/js/periodo_utils.js
 */

/**
 * Clase para manejo de períodos de novedades
 */
class PeriodoUtils {
    
    /**
     * Calcular el primer día hábil del mes usando API de días feriados
     */
    static async calcularPrimerDiaHabil(year, month) {
        try {
            // API para obtener feriados de Argentina
            const response = await fetch(`https://nolaborables.com.ar/API/v2/feriados/${year}`);
            const feriados = await response.json();
            
            // Crear array de fechas de feriados para el mes específico
            const feriadosDelMes = feriados
                .filter(feriado => {
                    const fechaFeriado = new Date(feriado.fecha);
                    return fechaFeriado.getMonth() === month && fechaFeriado.getFullYear() === year;
                })
                .map(feriado => new Date(feriado.fecha).getDate());
            
            // Buscar el primer día hábil
            for (let dia = 1; dia <= 31; dia++) {
                const fecha = new Date(year, month, dia);
                
                // Verificar que la fecha sea válida para el mes
                if (fecha.getMonth() !== month) break;
                
                const diaSemana = fecha.getDay(); // 0 = domingo, 6 = sábado
                
                // Si no es fin de semana y no es feriado
                if (diaSemana !== 0 && diaSemana !== 6 && !feriadosDelMes.includes(dia)) {
                    return dia;
                }
            }
            
            // Fallback: si no se encuentra, retornar día 1
            return 1;
            
        } catch (error) {
            console.warn('Error obteniendo feriados, usando fallback:', error);
            // Fallback simple: buscar primer día que no sea fin de semana
            for (let dia = 1; dia <= 7; dia++) {
                const fecha = new Date(year, month, dia);
                const diaSemana = fecha.getDay();
                if (diaSemana !== 0 && diaSemana !== 6) {
                    return dia;
                }
            }
            return 1;
        }
    }

    /**
     * Obtener día de cierre efectivo
     */
    static async obtenerDiaCierreEfectivo(valorCierre, year, month) {
        if (valorCierre === '1er día hábil') {
            return await this.calcularPrimerDiaHabil(year, month);
        }
        
        // Si es un número, convertir a entero
        const diaNumerico = parseInt(valorCierre);
        if (!isNaN(diaNumerico) && diaNumerico >= 1 && diaNumerico <= 31) {
            return diaNumerico;
        }
        
        // Fallback
        return 28;
    }

    /**
     * Calcular período basado en día de cierre
     */
    static calcularPeriodoConDiaCierre(diaCierre, fechaReferencia = null, esPrimerDiaHabil = false) {
        const hoy = fechaReferencia ? new Date(fechaReferencia) : new Date();
        const diaActual = hoy.getDate();
        
        let yearPeriodo = hoy.getFullYear();
        let mesPeriodo = hoy.getMonth(); // 0-based
        
        if (esPrimerDiaHabil) {
            // LÓGICA ESPECIAL PARA "1er día hábil":
            // Si día actual <= 1er día hábil → período del mes ANTERIOR
            // Si día actual > 1er día hábil → período del mes ACTUAL
            if (diaActual <= diaCierre) {
                mesPeriodo--;
                
                // Si se va antes de enero, decrementar año
                if (mesPeriodo < 0) {
                    mesPeriodo = 11; // diciembre
                    yearPeriodo--;
                }
            }
            // Si diaActual > diaCierre, se queda en el mes actual (no se modifica)
            
        } else {
            // LÓGICA NORMAL PARA DÍAS NUMÉRICOS:
            // Si día actual > día de cierre → período del mes SIGUIENTE
            if (diaActual > diaCierre) {
                mesPeriodo++;
                
                // Si se pasa de diciembre, incrementar año
                if (mesPeriodo > 11) {
                    mesPeriodo = 0;
                    yearPeriodo++;
                }
            }
            // Si diaActual <= diaCierre, se queda en el mes actual (no se modifica)
        }
        
        return {
            year: yearPeriodo,
            month: mesPeriodo + 1, // Convertir a 1-based para la BD
            monthZeroBased: mesPeriodo,
            diaCierre: diaCierre,
            fechaPeriodo: new Date(yearPeriodo, mesPeriodo, diaCierre),
            esPrimerDiaHabil: esPrimerDiaHabil,
            logicaAplicada: esPrimerDiaHabil 
                ? (diaActual <= diaCierre ? 'mes anterior' : 'mes actual')
                : (diaActual > diaCierre ? 'mes siguiente' : 'mes actual')
        };
    }

    /**
     * Calcular período según configuración de tipo de novedad
     */
    static async calcularPeriodoSegunTipo(tipoNovedad, fechaReferencia = null) {
        if (!tipoNovedad || !tipoNovedad.cierre) {
            return this.calcularPeriodoConDiaCierre(28, fechaReferencia, false);
        }
        
        const hoy = fechaReferencia ? new Date(fechaReferencia) : new Date();
        const year = hoy.getFullYear();
        const month = hoy.getMonth(); // 0-based
        
        // Obtener día de cierre efectivo
        const diaCierre = await this.obtenerDiaCierreEfectivo(tipoNovedad.cierre, year, month);
        
        // Determinar si es "1er día hábil" para aplicar lógica especial
        const esPrimerDiaHabil = tipoNovedad.cierre === '1er día hábil';
        
        return this.calcularPeriodoConDiaCierre(diaCierre, fechaReferencia, esPrimerDiaHabil);
    }

    /**
     * Formatear período como string MM/YYYY
     */
    static formatearPeriodo(periodo) {
        return `${periodo.month.toString().padStart(2, '0')}/${periodo.year}`;
    }

    /**
     * Formatear fecha para input HTML
     */
    static formatearFechaParaInput(fecha) {
        return fecha.toISOString().split('T')[0];
    }
    
    /**
     * Calcular fecha de inicio del período (fecha de vigencia)
     * La fecha de vigencia es el día 28 del mes ANTERIOR al período calculado
     */
    static calcularFechaInicioPeriodo(periodo) {
        let mesInicio = periodo.month - 1; // periodo.month ya está en 1-based
        let yearInicio = periodo.year;
        
        // Si el período es enero, el mes anterior es diciembre del año anterior
        if (mesInicio < 1) {
            mesInicio = 12;
            yearInicio--;
        }
        
        // La fecha de vigencia es siempre el día 28 del mes anterior al período
        return new Date(yearInicio, mesInicio - 1, 28); // mesInicio - 1 porque Date usa 0-based
    }
    
    /**
     * Obtener información completa del período incluyendo fechas de inicio y fin
     */
    static obtenerInfoCompletaPeriodo(periodo) {
        const fechaInicio = this.calcularFechaInicioPeriodo(periodo);
        const fechaFin = new Date(periodo.year, periodo.month - 1, 27); // month - 1 porque Date usa 0-based
        
        return {
            periodo: periodo,
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            fechaInicioString: fechaInicio.toLocaleDateString('es-AR', {day: '2-digit', month: '2-digit', year: 'numeric'}),
            fechaFinString: fechaFin.toLocaleDateString('es-AR', {day: '2-digit', month: '2-digit', year: 'numeric'}),
            periodoString: this.formatearPeriodo(periodo),
            rangoPeriodo: fechaInicio.toLocaleDateString('es-AR', {day: '2-digit', month: '2-digit', year: 'numeric'}) + 
                         ' al ' + 
                         fechaFin.toLocaleDateString('es-AR', {day: '2-digit', month: '2-digit', year: 'numeric'})
        };
    }
}

// Exportar para uso en otros archivos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PeriodoUtils;
}
