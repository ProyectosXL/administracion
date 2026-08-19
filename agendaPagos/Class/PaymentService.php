<?php
// Class/PaymentService.php
require_once __DIR__ . '/GoCuotasAdapter.php';
require_once __DIR__ . '/MercadoPagoAdapter.php';
require_once __DIR__ . '/GetnetAdapter.php';

class PaymentService
{
    private $adapters = [];
    private $envVars = [];

    public function __construct()
    {
        // Cargar variables de entorno
        $envPath = __DIR__ . '/../../../.env';
        if (file_exists($envPath)) {
            require_once __DIR__ . '/../../class/classEnv.php';
            try {
                $dotenv = new DotEnv($envPath);
                $this->envVars = $dotenv->listVars();
            } catch (Exception $e) {
                $this->envVars = $_ENV;
            }
        } else {
            $this->envVars = $_ENV;
        }

        // Registrar los adaptadores de pago correspondientes
        $this->adapters['gocuotas'] = new GoCuotasAdapter($this->envVars);
        $this->adapters['mercadopago'] = new MercadoPagoAdapter($this->envVars);
        $this->adapters['getnet'] = new GetnetAdapter($this->envVars);
    }

    /**
     * Devuelve el listado consolidado de cobros según los filtros.
     */
    public function getConsolidatedPayments(string $desde, string $hasta, string $procesador = 'todos', string $estado = 'todos', string $sucursal = 'todos'): array
    {
        $allPayments = [];

        $targets = [];
        if ($procesador === 'todos') {
            $targets = array_keys($this->adapters);
        } else {
            if (isset($this->adapters[$procesador])) {
                $targets[] = $procesador;
            }
        }

        foreach ($targets as $pName) {
            $adapter = $this->adapters[$pName];
            $adapter->login();
            
            if ($pName === 'gocuotas') {
                $pPayments = $adapter->getPayments($desde, $hasta, $estado, $sucursal);
            } else {
                $pPayments = $adapter->getPayments($desde, $hasta, $estado);
                if ($sucursal !== 'todos') {
                    $pPayments = array_filter($pPayments, function($p) use ($sucursal) {
                        return strtolower($p['sucursal'] ?? '') === strtolower($sucursal);
                    });
                }
            }

            $allPayments = array_merge($allPayments, $pPayments);
        }

        usort($allPayments, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_values($allPayments);
    }

    /**
     * Consulta las ventas registradas en el sistema POS ($ SISTEMA) para la conciliación.
     */
    public function getPosSales(string $desde, string $hasta, string $sucursalTarget = 'todos', string $procesador = 'gocuotas'): array
    {
        $posMap = [];
        $configFile = __DIR__ . '/../config/gocuotas_sucursales.php';
        if (!file_exists($configFile)) {
            return $posMap;
        }

        $sucursalesConfig = require $configFile;

        $medioPagoDB = 'GO CUOTAS';
        if ($procesador === 'mercadopago') $medioPagoDB = 'MERCADO PAGO';
        if ($procesador === 'getnet') $medioPagoDB = 'GETNET';

        $sucClassPath = __DIR__ . '/../../controlSucursales/Class/sucursal.php';
        if (!file_exists($sucClassPath)) {
            return $posMap;
        }

        try {
            require_once $sucClassPath;
            $sucModel = new Sucursal();

            $sucursalesToFetch = [];
            if ($sucursalTarget !== 'todos' && isset($sucursalesConfig[$sucursalTarget])) {
                $sucursalesToFetch[$sucursalTarget] = $sucursalesConfig[$sucursalTarget];
            } else {
                $sucursalesToFetch = $sucursalesConfig;
            }

            foreach ($sucursalesToFetch as $conf) {
                $nro = $conf['nro_sucursal'] ?? null;
                if (!$nro) continue;

                $sales = $sucModel->traerImportesTotalesPorPeriodo($nro, $desde, $hasta, $medioPagoDB);
                if (is_array($sales)) {
                    foreach ($sales as $s) {
                        $fechaRaw = $s['FECHA'];
                        $fecha = is_a($fechaRaw, 'DateTime') ? $fechaRaw->format('Y-m-d') : substr(strval($fechaRaw), 0, 10);
                        $monto = floatval($s['IMPORTE_$_SISTEMA'] ?? 0);
                        $sucName = $conf['nombre'];
                        $key = "{$fecha}_{$sucName}_gocuotas";
                        $posMap[$key] = $monto;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error al consultar ventas POS del sistema: " . $e->getMessage());
        }

        return $posMap;
    }

    public function createPaymentLink(string $procesador, float $amount, string $description, int $installments = 1, array $extraDatos = []): array
    {
        if (!isset($this->adapters[$procesador])) {
            return [
                'success' => false,
                'message' => "Procesador de pago '$procesador' no soportado."
            ];
        }

        $adapter = $this->adapters[$procesador];
        $adapter->login();
        return $adapter->createPaymentLink($amount, $description, $installments, $extraDatos);
    }

    public function calculateMetrics(array $payments): array
    {
        $grossTotal = 0;
        $feesTotal = 0;
        $netTotal = 0;
        $count = count($payments);

        $distribution = [
            'gocuotas' => ['gross' => 0, 'count' => 0, 'percentage' => 0],
            'mercadopago' => ['gross' => 0, 'count' => 0, 'percentage' => 0],
            'getnet' => ['gross' => 0, 'count' => 0, 'percentage' => 0]
        ];

        $branchesSummary = [];

        $acredForecast = [
            'immediate' => 0,
            'short' => 0,
            'scheduled' => 0
        ];

        foreach ($payments as $p) {
            $sucName = $p['sucursal'] ?? 'General';

            if (!isset($branchesSummary[$sucName])) {
                $branchesSummary[$sucName] = [
                    'name' => $sucName,
                    'count' => 0,
                    'gross' => 0,
                    'fee' => 0,
                    'net' => 0
                ];
            }

            $branchesSummary[$sucName]['count']++;
            $branchesSummary[$sucName]['gross'] += $p['gross_amount'];
            $branchesSummary[$sucName]['fee'] += $p['fee_amount'];
            $branchesSummary[$sucName]['net'] += $p['net_amount'];

            if ($p['status'] === 'approved') {
                $grossTotal += $p['gross_amount'];
                $feesTotal += $p['fee_amount'];
                $netTotal += $p['net_amount'];

                $prov = $p['provider'];
                if (isset($distribution[$prov])) {
                    $distribution[$prov]['gross'] += $p['gross_amount'];
                    $distribution[$prov]['count']++;
                }

                if ($prov === 'gocuotas' && isset($p['installments_detail'])) {
                    foreach ($p['installments_detail'] as $inst) {
                        $cDate = strtotime($inst['acreditation_date']);
                        $daysDiff = ($cDate - time()) / (60 * 60 * 24);
                        
                        if ($daysDiff <= 0) {
                            $acredForecast['immediate'] += $inst['net'];
                        } elseif ($daysDiff <= 2) {
                            $acredForecast['short'] += $inst['net'];
                        } else {
                            $acredForecast['scheduled'] += $inst['net'];
                        }
                    }
                } else {
                    $acredDate = strtotime($p['acreditation_date']);
                    $daysDiff = ($acredDate - time()) / (60 * 60 * 24);

                    if ($daysDiff <= 0) {
                        $acredForecast['immediate'] += $p['net_amount'];
                    } elseif ($daysDiff <= 2) {
                        $acredForecast['short'] += $p['net_amount'];
                    } else {
                        $acredForecast['scheduled'] += $p['net_amount'];
                    }
                }
            }
        }

        if ($grossTotal > 0) {
            foreach ($distribution as $key => $d) {
                $distribution[$key]['percentage'] = round(($d['gross'] / $grossTotal) * 100, 1);
            }
        }

        $ticketAverage = ($count > 0 && $grossTotal > 0) ? round($grossTotal / $count, 2) : 0;
        $feePercentage = ($grossTotal > 0) ? round(($feesTotal / $grossTotal) * 100, 1) : 0;

        uasort($branchesSummary, function ($a, $b) {
            return $b['gross'] <=> $a['gross'];
        });

        return [
            'gross_total' => $grossTotal,
            'fees_total' => $feesTotal,
            'net_total' => $netTotal,
            'count' => $count,
            'ticket_average' => $ticketAverage,
            'fee_percentage' => $feePercentage,
            'distribution' => $distribution,
            'branches_summary' => array_values($branchesSummary),
            'forecast' => $acredForecast
        ];
    }
}
