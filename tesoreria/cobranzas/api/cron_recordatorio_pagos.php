<?php
/**
 * api/cron_recordatorio_pagos.php
 * Script para ser ejecutado diariamente (CRON).
 * Envía recordatorios de pago para propuestas ACEPTADAS cuyos vencimientos son en 48hs.
 * Incluye lógica para evitar duplicados.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notificaciones_controller.php';

if (php_sapi_name() === 'cli' || isset($_GET['manual'])) {
    try {
        $conn_apps = Database::getConnection('apps');
        $procesados = ejecutarRecordatoriosPago($conn_apps);
        echo "Proceso finalizado. Recordatorios enviados: " . $procesados;
    } catch (Exception $e) {
        echo "Error en Recordatorio: " . $e->getMessage();
    }
}

/**
 * Función principal que busca y envía los recordatorios.
 * Se asegura de NO repetir envíos usando la columna aviso_recordatorio_enviado.
 */
function ejecutarRecordatoriosPago($conn_apps)
{
    if (!$conn_apps)
        return 0;

    $procesados = 0;

    // Caso A: Propuestas de Pago Único (Sin cuotas) que vencen en 48hs
    $sql_unicas = "SELECT p.id as id_propuesta, p.cod_cliente, p.total_propuesto as monto, p.fecha_propuesta_pago as fecha_vencimiento
                  FROM FP_propuestas_pago p
                  WHERE p.estado = 'ACEPTADA'
                  AND CAST(p.fecha_propuesta_pago AS DATE) = CAST(DATEADD(day, 2, GETDATE()) AS DATE)
                  AND NOT EXISTS (SELECT 1 FROM FP_propuestas_pago_cuotas WHERE id_propuesta = p.id)
                  AND ISNULL(p.aviso_recordatorio_enviado, 0) = 0";

    $stmt_unicas = sqlsrv_query($conn_apps, $sql_unicas);
    if ($stmt_unicas) {
        while ($row = sqlsrv_fetch_array($stmt_unicas, SQLSRV_FETCH_ASSOC)) {
            if (enviarRecordatorio($row, false)) {
                sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago SET aviso_recordatorio_enviado = 1 WHERE id = ?", [$row['id_propuesta']]);
                $procesados++;
            }
        }
    }

    // Caso B: Cuotas individuales que vencen en 48hs
    $sql_cuotas = "SELECT p.id as id_propuesta, p.cod_cliente, c.id as id_cuota, c.monto, c.fecha_vencimiento, c.num_cuota,
                  (SELECT COUNT(*) FROM FP_propuestas_pago_cuotas WHERE id_propuesta = p.id) as total_cuotas
                  FROM FP_propuestas_pago p
                  JOIN FP_propuestas_pago_cuotas c ON p.id = c.id_propuesta
                  WHERE p.estado = 'ACEPTADA' 
                  AND CAST(c.fecha_vencimiento AS DATE) = CAST(DATEADD(day, 2, GETDATE()) AS DATE)
                  AND c.estado = 'PENDIENTE'
                  AND ISNULL(c.aviso_recordatorio_enviado, 0) = 0";

    $stmt_cuotas = sqlsrv_query($conn_apps, $sql_cuotas);
    if ($stmt_cuotas) {
        while ($row = sqlsrv_fetch_array($stmt_cuotas, SQLSRV_FETCH_ASSOC)) {
            if (enviarRecordatorio($row, true)) {
                sqlsrv_query($conn_apps, "UPDATE FP_propuestas_pago_cuotas SET aviso_recordatorio_enviado = 1 WHERE id = ?", [$row['id_cuota']]);
                $procesados++;
            }
        }
    }

    return $procesados;
}

/**
 * Función auxiliar para enviar el mail de recordatorio
 */
function enviarRecordatorio($data, $es_cuota)
{
    try {
        $datos_cliente = obtenerEmailFranquiciado($data['cod_cliente']);
        if (!$datos_cliente || !$datos_cliente['email'])
            return false;

        $monto_fmt = "$" . number_format($data['monto'], 2, ',', '.');
        $fecha_fmt = ($data['fecha_vencimiento'] instanceof DateTime) ? $data['fecha_vencimiento']->format('d/m/Y') : $data['fecha_vencimiento'];

        $detalle_pago = $es_cuota ? "Cuota {$data['num_cuota']} de {$data['total_cuotas']}" : "Pago Único";

        $id_p = $data['id_propuesta'];
        $titulo = "Recordatorio de Pago Proximo: Propuesta #{$id_p}";
        $mensaje = "Hola <strong>{$datos_cliente['razon_social']}</strong>,<br><br>" .
            "Le enviamos este recordatorio porque tiene un pago programado para el próximo <strong>{$fecha_fmt}</strong> (en 48hs).<br><br>" .
            "<strong>Detalle:</strong><br>" .
            "• Propuesta: #{$id_p}<br>" .
            "• Concepto: {$detalle_pago}<br>" .
            "• Monto: <strong>{$monto_fmt}</strong><br><br>" .
            "Si ya ha realizado el pago por favor ignore este mensaje o adjunte el comprobante en nuestro portal.";

        $cuerpo = generarCuerpoEmail($titulo, $mensaje, "Ver Portal de Clientes", "https://app.xl.com.ar/administracion/tesoreria/cobranzas/portal_cliente.php");

        return enviarNotificacion($datos_cliente['email'], $titulo, $cuerpo);
    } catch (Exception $e) {
        return false;
    }
}
