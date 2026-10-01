<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('ID del despacho no proporcionado');
    }
    
    $idMg = intval($_GET['id']);
    
    require_once '../class/estimacionCostos.php';
    
    $estimacion = new EstimacionCostos();
    
    // Obtener datos del despacho
    $despacho = $estimacion->obtenerDespacho($idMg);
    
    if (!$despacho) {
        throw new Exception('Despacho no encontrado');
    }
    
    /* Los conceptos salen con la alícuota que regía a la FECHA DE
       NACIONALIZACIÓN del contenedor, no con la vigente hoy: una operación
       nacionalizada en marzo se calcula con la alícuota de marzo. Sin fecha
       cargada -o sin vigencia que la cubra- se usa el padrón, que es lo que
       la aplicación hacía antes. Ver AlicuotasVigencia. */
    $fechaNacionalizacion = isset($despacho['FECHA_DESP_ADU']) ? $despacho['FECHA_DESP_ADU'] : null;

    $conceptos = $estimacion->obtenerConceptos($fechaNacionalizacion);
    
    /* Ajuste del concepto DESPACHANTE: Farre usa VALOR_DEFAULT_2 como param1,
       cualquier otro VALOR_DEFAULT_1, y param2 queda en null. Vive en
       EstimacionCostos::ajustarDespachante() porque la generación y el
       recálculo automáticos de la estimación tienen que partir de los MISMOS
       conceptos que esta pantalla. */
    $despachante = $despacho['DESPACHANTE'];
    $conceptos = EstimacionCostos::ajustarDespachante($conceptos, $despachante);
    
    // Obtener estimación existente (si existe)
    $estimacionExistente = $estimacion->obtenerEstimacion($idMg);
    
    // Aplicar lógica dinámica de parámetros para concepto DESPACHANTE en estimación existente
    if ($estimacionExistente) {
        // Buscar concepto DESPACHANTE en el array de conceptos (ya tiene valores ajustados)
        $conceptoDespachanteAjustado = null;
        foreach ($conceptos as $c) {
            if (strcasecmp($c['CONCEPTO'], 'DESPACHANTE') === 0 || strcasecmp($c['CONCEPTO'], 'Despachante') === 0) {
                $conceptoDespachanteAjustado = $c;
                break;
            }
        }
        
        // Aplicar los valores ajustados a la estimación existente
        if ($conceptoDespachanteAjustado) {
            foreach ($estimacionExistente as &$itemEstimacion) {
                if (strcasecmp($itemEstimacion['CONCEPTO'], 'DESPACHANTE') === 0 || strcasecmp($itemEstimacion['CONCEPTO'], 'Despachante') === 0) {
                    // Usar los valores ya ajustados del array de conceptos
                    $itemEstimacion['VALOR_DEFAULT_1'] = $conceptoDespachanteAjustado['VALOR_DEFAULT_1'];
                    $itemEstimacion['VALOR_DEFAULT_2'] = $conceptoDespachanteAjustado['VALOR_DEFAULT_2'];
                    break;
                }
            }
            unset($itemEstimacion); // Romper referencia
        }
    }
    
    // Verificar si está confirmada
    $confirmada = $estimacion->estaConfirmada($idMg);
    
    // LOG: Depurar valores de DESPACHANTE
    error_log("=== DEBUG DESPACHANTE ===");
    error_log("Despacho DESPACHANTE: " . ($despacho['DESPACHANTE'] ?? 'NULL'));
    foreach ($conceptos as $c) {
        if ($c['CONCEPTO'] === 'DESPACHANTE') {
            error_log("Concepto DESPACHANTE - V1: " . $c['VALOR_DEFAULT_1'] . ", V2: " . ($c['VALOR_DEFAULT_2'] ?? 'NULL'));
        }
    }
    if ($estimacionExistente) {
        foreach ($estimacionExistente as $e) {
            if ($e['CONCEPTO'] === 'DESPACHANTE') {
                error_log("Estimacion DESPACHANTE - V1: " . $e['VALOR_DEFAULT_1'] . ", V2: " . ($e['VALOR_DEFAULT_2'] ?? 'NULL'));
            }
        }
    }
    error_log("=========================");
    
    echo json_encode([
        'success' => true,
        'data' => [
            'despacho' => $despacho,
            'conceptos' => $conceptos,
            'estimacion' => $estimacionExistente,
            'confirmada' => $confirmada,
            // De dónde salieron las alícuotas: de una vigencia o del padrón.
            // La pantalla lo dice; sin esto, los dos casos se ven igual.
            'vigencia' => $estimacion->resumenVigencia($fechaNacionalizacion),

            /* Qué conceptos tienen guardada una alícuota distinta de la que
               rige para este contenedor. El valor guardado NO se pisa: la
               pantalla lo avisa y ofrece recalcular.

               Se calcula ACÁ ABAJO, después del ajuste de despachante de más
               arriba, y no en el navegador: ese ajuste pisa VALOR_DEFAULT_1 en
               los dos lados para las operaciones de Farre, y comparar antes
               marcaría como desviadas todas esas operaciones. Ver
               EstimacionCostos::desviosDeAlicuota(). */
            'desvios' => EstimacionCostos::desviosDeAlicuota($conceptos, $estimacionExistente)
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error en cargarEstimacion.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
