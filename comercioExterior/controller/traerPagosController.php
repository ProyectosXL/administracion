<?php
/**
 * Los pagos de un contenedor y su saldo, todo en U$S.
 *
 * QUÉ CAMBIÓ Y POR QUÉ
 *
 * 1. EL SALDO ES EN DÓLARES. Antes salía de VALOR_FOB_PESO menos la suma de
 *    MONTO, con MONTO en una moneda que la columna no declaraba. Ahora MONTO
 *    está en U$S (ver comercioExterior/sql/08_pagos_en_dolares.sql) y el saldo
 *    se calcula contra VALOR_FOB_DOLAR, que es la moneda en la que se le paga
 *    al proveedor del exterior.
 *
 * 2. LA CUENTA LA HACE LA CLASE. Estaba escrita acá y repetida en el
 *    navegador; ahora las dos pantallas piden lo mismo a Pagos::obtenerResumen()
 *    y no pueden divergir.
 *
 * 3. CONSULTAS PARAMETRIZADAS. El ID entraba concatenado en dos SELECT.
 *
 * 4. OC HIJAS. El ID que llega puede ser el de una orden hija; los pagos viven
 *    en la OC principal. Lo resuelve Pagos, igual que el resto del módulo.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../class/Pagos.php';

header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

try {
    // Se acepta por POST (como lo manda la pantalla) o por GET, para poder
    // mirar el saldo de un contenedor desde el navegador sin armar un form.
    $idCrudo = $_POST['id_despacho'] ?? $_GET['id_despacho'] ?? null;

    if ($idCrudo === null || $idCrudo === '') {
        throw new Exception('ID de despacho no proporcionado');
    }

    $idDespacho = intval($idCrudo);
    if ($idDespacho <= 0) {
        throw new Exception('ID de despacho inválido');
    }

    $pagosClass = new Pagos();

    $pagos = $pagosClass->obtenerPagosPorEncabezado($idDespacho);
    if (!is_array($pagos)) {
        throw new Exception($pagos);
    }

    $resumen = $pagosClass->obtenerResumen($idDespacho);

    // Fechas al formato de la pantalla, y el monto como número para que el
    // navegador no tenga que desarmar un texto formateado para sumarlo.
    foreach ($pagos as &$pago) {
        if (isset($pago['FECHA_PAGO']) && is_object($pago['FECHA_PAGO'])) {
            $pago['FECHA_PAGO'] = $pago['FECHA_PAGO']->format('d/m/Y');
        }
        if (isset($pago['FECHA_CREACION']) && is_object($pago['FECHA_CREACION'])) {
            $pago['FECHA_CREACION'] = $pago['FECHA_CREACION']->format('d/m/Y H:i');
        }

        $pago['MONTO'] = floatval($pago['MONTO']);

        /* Si el pago se cargó originalmente en pesos y el script 08 lo
           convirtió, la pantalla lo dice en el tooltip de la fila. El importe
           que se ve es el de U$S -que es el que cuenta para el saldo- pero de
           dónde salió no debería tener que averiguarse en la base. */
        $pago['MONTO_ORIGEN_ARS'] = isset($pago['MONTO_ORIGEN_ARS']) && $pago['MONTO_ORIGEN_ARS'] !== null
            ? floatval($pago['MONTO_ORIGEN_ARS'])
            : null;
    }
    unset($pago);

    echo json_encode([
        'success'        => true,
        'moneda'         => 'USD',
        'pagos'          => $pagos,
        'fobUsd'         => $resumen['fobUsd'],
        'totalPagado'    => $resumen['totalPagado'],
        'saldoPendiente' => $resumen['saldoPendiente'],
        'estado'         => $resumen['estado'],
        'idEncabezado'   => $resumen['idEncabezado']
    ]);

} catch (Exception $e) {
    error_log("Error en traerPagosController: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
