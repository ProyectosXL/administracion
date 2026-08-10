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
        // Al no tener credenciales de Getnet configuradas, retornamos vacío para que solo se muestren datos reales de las pasarelas activas
        return [];
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
