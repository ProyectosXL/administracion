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
    public function getConsolidatedPayments(string $desde, string $hasta, string $procesador = 'todos', string $estado = 'todos'): array
    {
        $allPayments = [];

        // Determinar qué adaptadores consultar
        $targets = [];
        if ($procesador === 'todos') {
            $targets = array_keys($this->adapters);
        } else {
            if (isset($this->adapters[$procesador])) {
                $targets[] = $procesador;
            }
        }

        // Consultar cada pasarela e integrar resultados
        foreach ($targets as $pName) {
            $adapter = $this->adapters[$pName];
            $adapter->login();
            $pPayments = $adapter->getPayments($desde, $hasta, $estado);
            $allPayments = array_merge($allPayments, $pPayments);
        }

        // Ordenar transacciones por fecha de pago de forma descendente (más recientes primero)
        usort($allPayments, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $allPayments;
    }

    /**
     * Genera un nuevo link de cobro simulado para el procesador seleccionado.
     */
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

    /**
     * Calcula métricas y distribución de caja consolidada.
     */
    public function calculateMetrics(array $payments): array
    {
        $grossTotal = 0;
        $feesTotal = 0;
        $netTotal = 0;
        $count = count($payments);

        // Distribución por procesadora
        $distribution = [
            'gocuotas' => ['gross' => 0, 'count' => 0, 'percentage' => 0],
            'mercadopago' => ['gross' => 0, 'count' => 0, 'percentage' => 0],
            'getnet' => ['gross' => 0, 'count' => 0, 'percentage' => 0]
        ];

        // Calendario de acreditaciones
        $acredForecast = [
            'immediate' => 0,
            'short' => 0,      // 24-48 hs
            'scheduled' => 0   // cuotas a futuro
        ];

        foreach ($payments as $p) {
            if ($p['status'] === 'approved') {
                $grossTotal += $p['gross_amount'];
                $feesTotal += $p['fee_amount'];
                $netTotal += $p['net_amount'];

                // Clasificar en la distribución
                $prov = $p['provider'];
                if (isset($distribution[$prov])) {
                    $distribution[$prov]['gross'] += $p['gross_amount'];
                    $distribution[$prov]['count']++;
                }

                // Clasificar en calendario de flujo de caja según el tipo
                // Para las liquidaciones en cuotas de GoCuotas, separamos las cuotas ya cobradas de las futuras!
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
                    // Para MP y Getnet
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

        // Calcular porcentajes de distribución
        if ($grossTotal > 0) {
            foreach ($distribution as $key => $d) {
                $distribution[$key]['percentage'] = round(($d['gross'] / $grossTotal) * 100, 1);
            }
        }

        $ticketAverage = ($count > 0 && $grossTotal > 0) ? round($grossTotal / $count, 2) : 0;
        $feePercentage = ($grossTotal > 0) ? round(($feesTotal / $grossTotal) * 100, 1) : 0;

        return [
            'gross_total' => $grossTotal,
            'fees_total' => $feesTotal,
            'net_total' => $netTotal,
            'count' => $count,
            'ticket_average' => $ticketAverage,
            'fee_percentage' => $feePercentage,
            'distribution' => $distribution,
            'forecast' => $acredForecast
        ];
    }
}
