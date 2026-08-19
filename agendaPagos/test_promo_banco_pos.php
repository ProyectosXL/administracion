<?php
// test_promo_banco_pos.php
require_once __DIR__ . '/../controlSucursales/Class/sucursal.php';

$suc = new Sucursal();
$sales = $suc->traerImportesTotalesPorPeriodo('7', '2026-08-01', '2026-08-14', 'PROMO BANCO');

echo "=== PROMO BANCO SALES FOR ABASTO (NRO 7) FROM RO_T_VENTA_DIARIA_SUCURSALES ($ SISTEMA) ===\n";
if (is_array($sales) && count($sales) > 0) {
    foreach ($sales as $s) {
        $fecha = is_a($s['FECHA'], 'DateTime') ? $s['FECHA']->format('Y-m-d') : $s['FECHA'];
        $montoSistema = floatval($s['IMPORTE_$_SISTEMA'] ?? 0);
        $montoFisico = floatval($s['IMPORTE_$_FISICO'] ?? 0);
        echo "FECHA: {$fecha} | $ SISTEMA: $" . number_format($montoSistema, 2, ',', '.') . " | FISICO: $" . number_format($montoFisico, 2, ',', '.') . "\n";
    }
} else {
    echo "Sin resultados o array vacío.\n";
}
