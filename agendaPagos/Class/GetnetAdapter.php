<?php
// Class/GetnetAdapter.php
require_once __DIR__ . '/PaymentProvider.php';

class GetnetAdapter implements PaymentProvider
{
    private $clientId;
    private $clientSecret;
    private $sandbox;
    private $isConfigured = false;

    public function __construct(array $envVars = [])
    {
        $this->clientId = $envVars['GETNET_CLIENT_ID'] ?? '';
        $this->clientSecret = $envVars['GETNET_CLIENT_SECRET'] ?? '';
        $this->sandbox = filter_var($envVars['GETNET_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (!empty($this->clientId) && !empty($this->clientSecret)) {
            $this->isConfigured = true;
        }
    }

    public function login(array $credentials = []): bool
    {
        if (!$this->isConfigured) {
            return true;
        }
        // Llamada a POST /auth/oauth/v2/token para obtener OAuth token
        return true;
    }

    public function getPayments(string $desde, string $hasta, string $status = 'todos'): array
    {
        $payments = [];

        $clientes = ['Carlos Varela', 'Patricia Quiroga', 'Daniel Mendez', 'Sofia Alvarez', 'Mateo Sosa'];
        $conceptos = ['Cartera de Cuero', 'Zapatos Lakers', 'Par de Medias Algodon', 'Campera Cuero Ecológico', 'Billetera Slim'];
        
        $seed = strtotime($desde) + 98765;
        mt_srand($seed);

        $numTx = mt_rand(3, 7);
        for ($i = 0; $i < $numTx; $i++) {
            $amount = mt_rand(40, 300) * 100; // montos entre 4.000 y 30.000
            $installments = [1, 1, 3][mt_rand(0, 2)];
            $txStatus = ['approved', 'approved', 'approved', 'pending', 'rejected'][mt_rand(0, 4)];
            
            if ($status !== 'todos' && $txStatus !== $status) {
                continue;
            }

            $daysDiff = (strtotime($hasta) - strtotime($desde)) / (60 * 60 * 24);
            $randomDays = ($daysDiff > 0) ? mt_rand(0, intval($daysDiff)) : 0;
            $txDate = date('Y-m-d H:i:s', strtotime($desde . " +$randomDays days") + mt_rand(0, 86400));

            $txId = 'GN-' . mt_rand(10000000, 99999999);
            $cliente = $clientes[$i % count($clientes)];
            $concepto = $conceptos[$i % count($conceptos)];

            $sucursales = ['Local 1 - Abasto', 'Local 2 - Palermo', 'Local 3 - Belgrano', 'Local 4 - Centro'];
            $payment = [
                'id' => $txId,
                'date' => $txDate,
                'provider' => 'getnet',
                'client_name' => $cliente,
                'description' => $concepto,
                'installments' => $installments,
                'gross_amount' => floatval($amount),
                'status' => $txStatus,
                'sucursal' => $sucursales[$i % count($sucursales)],
                'reference' => 'GN-REF-' . mt_rand(100000, 999999)
            ];

            $settlement = $this->calculateSettlement($payment);
            $payment['fee_amount'] = $settlement['fee_amount'];
            $payment['net_amount'] = $settlement['net_amount'];
            $payment['acreditation_date'] = $settlement['acreditation_date'];
            $payment['acreditation_type'] = $settlement['acreditation_type'];
            $payment['installments_detail'] = $settlement['installments_detail'];

            $payments[] = $payment;
        }

        return $payments;
    }

    public function createPaymentLink(float $amount, string $description, int $installments = 1, array $extraDatos = []): array
    {
        $txId = 'GN-' . mt_rand(10000000, 99999999);
        return [
            'success' => true,
            'transaction_id' => $txId,
            'checkout_url' => 'https://getnet.com.ar/checkout/' . md5($txId),
            'message' => 'Link de Getnet generado con éxito (Simulado)',
            'provider' => 'getnet'
        ];
    }

    public function calculateSettlement(array $payment): array
    {
        // En Getnet:
        // Cobro en 1 pago (Débito): Comisión de 1.8% + IVA, se acredita en 24-48 horas.
        // Cobro en 1 pago (Crédito): Comisión de 3.5% + IVA, se acredita en 21 días.
        // Cobro en Cuotas (Getnet): Comisión de 3.5% + IVA + costo de plan, acredita a las 48hs.
        $gross = $payment['gross_amount'];
        $installments = $payment['installments'] ?: 1;
        
        $baseCommission = ($installments > 1) ? 0.035 : 0.018; // 3.5% para cuotas/credito, 1.8% para debito
        $ivaRate = 0.21;
        
        // Sumar costo financiero por cuotas
        $CF = 0.0;
        if ($installments == 3) {
            $CF = 0.082; // 8.2%
        }
        
        $feeBase = $gross * ($baseCommission + $CF);
        $feeIva = $feeBase * $ivaRate;
        $totalFees = round($feeBase + $feeIva, 2);
        $net = $gross - $totalFees;

        $baseDate = strtotime($payment['date']);
        // Plazos de acreditación
        if ($installments > 1) {
            // Acredita a las 48hs hábiles (con costo financiero descontado)
            $acredDate = date('Y-m-d', strtotime('+2 days', $baseDate));
            $acredType = 'Acreditación 48 Horas (Plan Getnet)';
        } else {
            // Asumimos Débito: acredita en 24hs hábiles
            $acredDate = date('Y-m-d', strtotime('+1 days', $baseDate));
            $acredType = 'Acreditación 24 Horas (Venta Débito)';
        }

        $installmentsDetail = [
            [
                'installment_number' => 1,
                'gross' => $gross,
                'fee' => $totalFees,
                'net' => $net,
                'acreditation_date' => $acredDate,
                'status' => (time() >= strtotime($acredDate) && $payment['status'] === 'approved') ? 'acreditado' : 'pendiente'
            ]
        ];

        return [
            'fee_amount' => $totalFees,
            'net_amount' => $net,
            'acreditation_date' => $acredDate,
            'acreditation_type' => $acredType,
            'installments_detail' => $installmentsDetail
        ];
    }
}
