<?php
// Class/MercadoPagoAdapter.php
require_once __DIR__ . '/PaymentProvider.php';

class MercadoPagoAdapter implements PaymentProvider
{
    private $accessToken;
    private $isConfigured = false;

    public function __construct(array $envVars = [])
    {
        $this->accessToken = $envVars['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
        if (!empty($this->accessToken)) {
            $this->isConfigured = true;
        }
    }

    public function login(array $credentials = []): bool
    {
        // En MercadoPago la autenticación es por header "Authorization: Bearer <TOKEN>", no requiere login dinámico
        return true;
    }

    public function getPayments(string $desde, string $hasta, string $status = 'todos'): array
    {
        // Al no tener credenciales de MercadoPago configuradas, retornamos vacío para que solo se muestren datos reales de las pasarelas activas
        return [];
    }

    public function createPaymentLink(float $amount, string $description, int $installments = 1, array $extraDatos = []): array
    {
        $txId = 'MP-' . mt_rand(1000000000, 9999999999);
        return [
            'success' => true,
            'transaction_id' => $txId,
            'checkout_url' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=' . md5($txId),
            'message' => 'Link de MercadoPago creado con éxito (Simulado)',
            'provider' => 'mercadopago'
        ];
    }

    public function calculateSettlement(array $payment): array
    {
        // En MercadoPago, el dinero de cuotas (incluso si es 3 o 6 cuotas) se acredita Adelantado
        // cobrando la comisión de cobro (ej. 3.49% a los 14 días) y opcionalmente el costo de financiación (CF).
        // Asumimos comisión de 14 días = 3.49% + IVA = 4.22% neto de comisión.
        $gross = $payment['gross_amount'];
        $installments = $payment['installments'] ?: 1;
        
        $baseCommission = 0.0349; // 3.49%
        $CF = 0.0; // Costo financiero (si el cliente paga el interes, es 0; si lo absorbe el comercio en 3/6 cuotas sin interes, se simula)
        if ($installments == 3) {
            $CF = 0.095; // 9.5% por 3 cuotas
        } elseif ($installments == 6) {
            $CF = 0.18; // 18% por 6 cuotas
        }

        $totalRate = $baseCommission + $CF;
        $feeBase = $gross * $totalRate;
        $feeIva = $feeBase * 0.21; // 21% IVA sobre la comisión
        $totalFees = round($feeBase + $feeIva, 2);
        $net = $gross - $totalFees;

        // Se acredita de una sola vez a los 14 días de la fecha de pago
        $baseDate = strtotime($payment['date']);
        $acredDate = date('Y-m-d', strtotime('+14 days', $baseDate));

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
            'acreditation_type' => 'Acreditación Adelantada (14 días)',
            'installments_detail' => $installmentsDetail
        ];
    }
}
