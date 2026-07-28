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
        $payments = [];

        $clientes = ['Mariano Gomez', 'Laura Gimenez', 'Andres Perez', 'Facundo Castro', 'Estefania Ortiz'];
        $conceptos = ['Gift Card $10000', 'Bota de Cuero Especial', 'Cinturon Clasico', 'Camisa Lino Blanca', 'Sweater de Lana'];
        
        $seed = strtotime($desde) + 12345;
        mt_srand($seed);

        $numTx = mt_rand(6, 12);
        for ($i = 0; $i < $numTx; $i++) {
            $amount = mt_rand(50, 400) * 100; // montos entre 5.000 y 40.000
            $installments = [1, 1, 1, 3, 6][mt_rand(0, 4)];
            $txStatus = ['approved', 'approved', 'approved', 'approved', 'pending', 'rejected'][mt_rand(0, 5)];
            
            if ($status !== 'todos' && $txStatus !== $status) {
                continue;
            }

            $daysDiff = (strtotime($hasta) - strtotime($desde)) / (60 * 60 * 24);
            $randomDays = ($daysDiff > 0) ? mt_rand(0, intval($daysDiff)) : 0;
            $txDate = date('Y-m-d H:i:s', strtotime($desde . " +$randomDays days") + mt_rand(0, 86400));

            $txId = 'MP-' . mt_rand(1000000000, 9999999999);
            $cliente = $clientes[$i % count($clientes)];
            $concepto = $conceptos[$i % count($conceptos)];

            $sucursales = ['Local 1 - Abasto', 'Local 2 - Palermo', 'Local 3 - Belgrano', 'Local 4 - Centro'];
            $payment = [
                'id' => $txId,
                'date' => $txDate,
                'provider' => 'mercadopago',
                'client_name' => $cliente,
                'description' => $concepto,
                'installments' => $installments,
                'gross_amount' => floatval($amount),
                'status' => $txStatus,
                'sucursal' => $sucursales[$i % count($sucursales)],
                'reference' => 'MP-REF-' . mt_rand(100000, 999999)
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
