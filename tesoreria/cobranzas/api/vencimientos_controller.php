<?php
// api/vencimientos_controller.php

if (!function_exists('verificarYActualizarVencimientosCliente')) {
    
    /**
     * Revisa y actualiza las propuestas PENDIENTES a VENCIDAS si el cliente no responde a tiempo.
     *
     * @param resource $conn La conexión a la base de datos ya establecida.
     * @param string $cod_cliente El código del cliente a verificar.
     * @return int El número de propuestas actualizadas.
     */
    function verificarYActualizarVencimientosCliente($conn, $cod_cliente) {
        if (!$conn || empty($cod_cliente)) {
            return 0;
        }
        
        // ======================= INICIO DE LA LÓGICA CORREGIDA =======================
        // 1. Seleccionamos las propuestas que están esperando la respuesta del cliente
        $sql_candidatas = "SELECT id, fecha_creacion FROM FP_propuestas_pago WHERE estado = 'PENDIENTE_APROBACION_CLIENTE' AND cod_cliente = ?";
        $params = [$cod_cliente];
        $stmt_candidatas = sqlsrv_query($conn, $sql_candidatas, $params);

        if ($stmt_candidatas === false) {
            return 0;
        }

        $hoy = new DateTime();
        $propuestas_vencidas_count = 0;

        while ($propuesta = sqlsrv_fetch_array($stmt_candidatas, SQLSRV_FETCH_ASSOC)) {
            // El punto de partida es la fecha de creación de la propuesta
            $fecha_inicio_conteo = $propuesta['fecha_creacion'];

            $horas_habiles_pasadas = 0;
            $fecha_actual = clone $fecha_inicio_conteo;

            // Calculamos las horas hábiles desde la CREACIÓN hasta hoy
            while ($fecha_actual < $hoy) {
                $dia_semana = (int)$fecha_actual->format('N');
                if ($dia_semana >= 1 && $dia_semana <= 5) {
                    $horas_habiles_pasadas++;
                }
                $fecha_actual->modify('+1 hour');
            }

            // Si han pasado 96 horas hábiles y el cliente no ha respondido
            if ($horas_habiles_pasadas >= 96) {
            // ======================== FIN DE LA LÓGICA CORREGIDA =========================
                if (sqlsrv_begin_transaction($conn) === false) {
                    continue;
                }

                try {
                    // Actualizamos el estado a VENCIDA
                    $sql_update = "UPDATE FP_propuestas_pago SET estado = 'VENCIDA' WHERE id = ?";
                    $stmt_update = sqlsrv_query($conn, $sql_update, [$propuesta['id']]);
                    
                    if ($stmt_update === false || sqlsrv_rows_affected($stmt_update) <= 0) {
                        throw new Exception("Error al actualizar estado a VENCIDA.");
                    }

                    // Dejamos un registro claro en el historial
                    $descripcion_evento = "Propuesta marcada como VENCIDA por el sistema.";
                    $comentario_accion = "El plazo para responder a la propuesta (96hs hábiles) ha expirado.";
                    $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, tipo_usuario, descripcion, comentario, id_usuario_evento) VALUES (?, ?, ?, ?, ?)";
                    $params_historial = [$propuesta['id'], 'SISTEMA', $descripcion_evento, $comentario_accion, 0];
                    $stmt_historial = sqlsrv_query($conn, $sql_historial, $params_historial);

                    if ($stmt_historial === false) {
                         throw new Exception("Error al insertar en historial de vencimiento.");
                    }

                    sqlsrv_commit($conn);
                    $propuestas_vencidas_count++;

                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                }
            }
        }

        return $propuestas_vencidas_count;
    }
}
?>