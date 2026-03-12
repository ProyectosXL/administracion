<?php
// api/vencimientos_controller.php

if (!function_exists('verificarYActualizarVencimientosCliente')) {

    /**
     * Revisa y actualiza las propuestas PENDIENTES a VENCIDAS si el cliente no responde a tiempo.
     * Ahora soporta chequeo GLOBAL (si $cod_cliente es null) para uso por administración.
     *
     * @param resource $conn La conexión a la base de datos ya establecida.
     * @param string|array|null $cod_cliente El código del cliente o array de códigos. Si es NULL, verifica TODO.
     * @return int El número de propuestas actualizadas.
     */
    function verificarYActualizarVencimientosCliente($conn, $cod_cliente = null)
    {
        if (!$conn)
            return 0;

        // --- LISTADO DE FERIADOS (Para conteo preciso de hs hábiles) ---
        // Se puede ampliar según necesidad. Formato: 'YYYY-MM-DD'
        $feriados = [
            '2026-01-01', // Año Nuevo
            '2026-03-02',
            '2026-03-03', // Carnaval
            '2026-03-24', // Memoria
            '2026-04-02',
            '2026-04-03', // Malvinas / Viernes Santo
            '2026-05-01',
            '2026-05-25', // Día del trabajador / Revolución Mayo
            '2026-06-15',
            '2026-06-20', // Güemes / Belgrano
            '2026-07-09', // Independencia
            '2026-08-17', // San Martín
            '2026-10-12', // Diversidad Cultural
            '2026-11-23', // Soberanía
            '2026-12-08',
            '2026-12-25'  // Inmaculada / Navidad
        ];

        // 1. Construir la consulta base
        $where_cliente = "";
        $params = [];

        if (!empty($cod_cliente)) {
            if (is_array($cod_cliente)) {
                $placeholders = implode(',', array_fill(0, count($cod_cliente), '?'));
                $where_cliente = " AND cod_cliente IN ($placeholders)";
                $params = $cod_cliente;
            } else {
                $where_cliente = " AND cod_cliente = ?";
                $params = [$cod_cliente];
            }
        }

        // OPTIMIZACIÓN: Solo traemos propuestas creadas hace más de 4 días corridos 
        // para no procesar propuestas nuevas que sabemos que no vencieron.
        $sql_candidatas = "SELECT id, fecha_creacion, cod_cliente, estado 
                           FROM FP_propuestas_pago 
                           WHERE estado = 'PENDIENTE_APROBACION_CLIENTE' 
                           AND fecha_creacion <= DATEADD(day, -4, GETDATE())
                           $where_cliente";

        $stmt_candidatas = sqlsrv_query($conn, $sql_candidatas, $params);
        if ($stmt_candidatas === false)
            return 0;

        $hoy = new DateTime();
        $propuestas_vencidas_count = 0;

        while ($propuesta = sqlsrv_fetch_array($stmt_candidatas, SQLSRV_FETCH_ASSOC)) {
            $fecha_inicio = $propuesta['fecha_creacion'];
            if (!$fecha_inicio)
                continue;

            if (!($fecha_inicio instanceof DateTime)) {
                try {
                    $fecha_inicio = new DateTime($fecha_inicio);
                } catch (Exception $e) {
                    continue;
                }
            }

            $horas_habiles_pasadas = 0;
            $iterador = clone $fecha_inicio;

            // --- CÁLCULO DE HORAS HÁBILES (Saltando fines de semana y FERIADOS) ---
            // Solo avanzamos si el iterador es menor a hoy
            while ($iterador < $hoy) {
                $dia_semana = (int) $iterador->format('N'); // 1 (Mon) to 7 (Sun)
                $fecha_solo_dia = $iterador->format('Y-m-d');

                // Es hábil si es de Lunes a Viernes Y no está en la lista de feriados
                if ($dia_semana >= 1 && $dia_semana <= 5 && !in_array($fecha_solo_dia, $feriados)) {
                    $horas_habiles_pasadas++;
                }

                $iterador->modify('+1 hour');
            }

            // Si llegamos a las 96hs hábiles (son exactamente 4 días hábiles de 24hs)
            if ($horas_habiles_pasadas >= 96) {
                if (sqlsrv_begin_transaction($conn) === false)
                    continue;

                try {
                    // Actualizamos el estado a VENCIDA
                    $sql_update = "UPDATE FP_propuestas_pago SET estado = 'VENCIDA', fecha_ultima_modificacion = GETDATE() WHERE id = ?";
                    $stmt_update = sqlsrv_query($conn, $sql_update, [$propuesta['id']]);

                    if ($stmt_update === false)
                        throw new Exception("Error al actualizar estado.");

                    // Historial
                    $descripcion_evento = "Propuesta marcada como VENCIDA por el sistema.";
                    $comentario_accion = "El plazo para responder a la propuesta (96hs hábiles) ha expirado.";
                    $sql_historial = "INSERT INTO FP_propuestas_pago_historial (id_propuesta, tipo_usuario, descripcion, comentario, id_usuario_evento) VALUES (?, ?, ?, ?, ?)";
                    sqlsrv_query($conn, $sql_historial, [$propuesta['id'], 'SISTEMA', $descripcion_evento, $comentario_accion, 0]);

                    sqlsrv_commit($conn);
                    $propuestas_vencidas_count++;

                    // Notificación (opcional, encapsulada en try para no frenar el proceso)
                    try {
                        require_once __DIR__ . '/notificaciones_controller.php';
                        $datos_cliente = obtenerEmailFranquiciado($propuesta['cod_cliente']);
                        if ($datos_cliente && $datos_cliente['email']) {
                            $titulo = "Propuesta de Pago Vencida (#{$propuesta['id']})";
                            $msj = "La propuesta #{$propuesta['id']} ha vencido por falta de respuesta en el plazo de 96hs hábiles.";
                            enviarNotificacion($datos_cliente['email'], $titulo, generarCuerpoEmail($titulo, $msj));
                        }
                    } catch (Exception $e_mail) {
                    }

                } catch (Exception $e) {
                    sqlsrv_rollback($conn);
                }
            }
        }

        return $propuestas_vencidas_count;
    }
}
?>