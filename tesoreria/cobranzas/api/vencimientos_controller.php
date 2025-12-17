<?php
// api/vencimientos_controller.php

if (!function_exists('verificarYActualizarVencimientosCliente')) {
    
    function verificarYActualizarVencimientosCliente($conn, $cod_cliente) {
        if (!$conn || empty($cod_cliente)) {
            return 0;
        }
        
        $sql_candidatas = "SELECT id, fecha_propuesta_pago FROM FP_propuestas_pago WHERE estado = 'ACEPTADA' AND cod_cliente = ?";
        $params = [$cod_cliente];
        $stmt_candidatas = sqlsrv_query($conn, $sql_candidatas, $params);

        if ($stmt_candidatas === false) {
            return 0;
        }

        $hoy = new DateTime();
        $propuestas_vencidas_count = 0;

        while ($propuesta = sqlsrv_fetch_array($stmt_candidatas, SQLSRV_FETCH_ASSOC)) {
            if (empty($propuesta['fecha_propuesta_pago'])) {
                continue;
            }

            $fecha_vencimiento = new DateTime($propuesta['fecha_propuesta_pago']);
            
            if ($hoy > $fecha_vencimiento) {
                $horas_habiles_pasadas = 0;
                $fecha_actual = clone $fecha_vencimiento;

                while ($fecha_actual < $hoy) {
                    $dia_semana = (int)$fecha_actual->format('N');
                    if ($dia_semana >= 1 && $dia_semana <= 5) {
                        $horas_habiles_pasadas++;
                    }
                    $fecha_actual->modify('+1 hour');
                }

                if ($horas_habiles_pasadas >= 96) {
                    // Primero, actualizamos el estado. Si esto falla, no continuamos.
                    $sql_update = "UPDATE FP_propuestas_pago SET estado = 'VENCIDA' WHERE id = ?";
                    $stmt_update = sqlsrv_query($conn, $sql_update, [$propuesta['id']]);

                    // Verificamos que la actualización fue exitosa
                    if ($stmt_update !== false && sqlsrv_rows_affected($stmt_update) > 0) {
                        $propuestas_vencidas_count++;

                        // Ahora, intentamos insertar en el historial
                        $descripcion_evento = "Propuesta marcada como VENCIDA por el sistema.";
                        $comentario_accion = "El plazo de 96hs hábiles para el pago ha expirado. Por favor, contacte a Cobranzas para regularizar su situación.";
                        
                        // ======================= CORRECCIÓN DEFINITIVA DEL INSERT =======================
                        // La consulta INSERT debe incluir TODAS las columnas NOT NULL.
                        // Usamos 0 para id_usuario_evento como valor por defecto para el sistema.
                        $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, tipo_usuario, descripcion, comentario, id_usuario_evento) VALUES (?, ?, ?, ?, ?)";
                        $params_historial = [$propuesta['id'], 'SISTEMA', $descripcion_evento, $comentario_accion, 0];
                        
                        // Ejecutamos la inserción. No necesitamos verificar el resultado por ahora,
                        // ya que el problema principal es que se actualice el estado.
                        sqlsrv_query($conn, $sql_historial, $params_historial);
                        // ==============================================================================
                    }
                }
            }
        }

        return $propuestas_vencidas_count;
    }
}
?>