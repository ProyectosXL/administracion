<?php
// Class/GoCuotasAdapter.php
require_once __DIR__ . '/PaymentProvider.php';

class GoCuotasAdapter implements PaymentProvider
{
    private $email;
    private $password;
    private $sandbox;
    private $token;
    private $isConfigured = false;

    public function __construct(array $envVars = [])
    {
        // Leer credenciales de W:\.env
        $this->email = $envVars['GOCUOTAS_EMAIL'] ?? '';
        // Soporta GOCUOTAS_APIKEY y GOCUOTAS_PASSWORD como fallback
        $this->password = $envVars['GOCUOTAS_APIKEY'] ?? ($envVars['GOCUOTAS_PASSWORD'] ?? '');
        $this->sandbox = filter_var($envVars['GOCUOTAS_SANDBOX'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!empty($this->email) && !empty($this->password)) {
            $this->isConfigured = true;
            $this->token = $this->password;
        }
    }

    public function login(array $credentials = []): bool
    {
        if (!$this->isConfigured) {
            return true;
        }
        // En GoCuotas, el API Key actúa directamente como Bearer Token, no requiere login dinámico
        return true;
    }

    public function getPayments(string $desde, string $hasta, string $status = 'todos'): array
    {
        if (!$this->isConfigured) {
            throw new Exception("El proveedor GoCuotas no está configurado (falta API Key o email).");
        }

        $baseUrl = $this->sandbox ? 'https://sandbox.gocuotas.com/api_client/v1' : 'https://www.gocuotas.com/api_client/v1';
        
        // El endpoint requiere 'from' y 'to' en formato YYYY-MM-DD
        $url = "{$baseUrl}/expense_settlements?from={$desde}&to={$hasta}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->token}",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Error de red al consultar GoCuotas: " . $curlError);
        }

        if ($httpCode !== 200) {
            $errDetail = json_decode($response, true);
            $errMsg = $errDetail['message'] ?? ($errDetail['errors'] ?? 'Error desconocido');
            throw new Exception("Error en API GoCuotas (HTTP {$httpCode}): " . $errMsg);
        }

        $settlements = json_decode($response, true);
        if (!is_array($settlements)) {
            throw new Exception("La API de GoCuotas no devolvió un formato JSON válido.");
        }

        $payments = [];
        foreach ($settlements as $settlement) {
            if ($status !== 'todos' && $status !== 'approved') {
                // Las liquidaciones procesadas se consideran aprobadas
                continue;
            }

            $retained = floatval(($settlement['payment_expense_retained_amount_in_cents'] ?? 0) / 100);
            $net = floatval(($settlement['payment_expense_amount_in_cents'] ?? 0) / 100);
            $gross = $net + $retained;

            $method = $settlement['payment_expense_method'] ?? 'liquidacion';
            $methodClean = ucwords(str_replace('_', ' ', $method));

            $payments[] = [
                'id' => 'GC-SET-' . ($settlement['id'] ?? mt_rand(100000, 999999)),
                'date' => ($settlement['payment_expense_at'] ?? $desde) . ' 12:00:00',
                'provider' => 'gocuotas',
                'client_name' => 'Liquidación GoCuotas',
                'description' => 'Pago recibido via ' . $methodClean,
                'installments' => 1,
                'gross_amount' => $gross,
                'fee_amount' => $retained,
                'net_amount' => $net,
                'status' => 'approved',
                'sucursal' => 'Venta Online',
                'reference' => 'SET-' . ($settlement['id'] ?? ''),
                'acreditation_date' => $settlement['due_expense_at'] ?? ($settlement['payment_expense_at'] ?? $desde),
                'acreditation_type' => 'Liquidación Programada (Expensas)',
                'installments_detail' => [
                    [
                        'installment_number' => 1,
                        'gross' => $gross,
                        'fee' => $retained,
                        'net' => $net,
                        'acreditation_date' => $settlement['due_expense_at'] ?? ($settlement['payment_expense_at'] ?? $desde),
                        'status' => 'acreditado'
                    ]
                ]
            ];
        }

        return $payments;
    }

    public function createPaymentLink(float $amount, string $description, int $installments = 1, array $extraDatos = []): array
    {
        // Simular llamada a POST /api_redirect/v1/checkouts
        $txId = 'GC-' . (270600000 + mt_rand(1000, 9999));
        return [
            'success' => true,
            'transaction_id' => $txId,
            'checkout_url' => 'https://sandbox.gocuotas.com/checkout/' . md5($txId),
            'message' => 'Link de pago generado exitosamente para GoCuotas (Simulado)',
            'provider' => 'gocuotas'
        ];
    }

    public function calculateSettlement(array $payment): array
    {
        // GoCuotas cobra una comisión promedio del 7.5% + IVA sobre el importe total de la venta
        // GoCuotas nos acredita semanalmente o mensualmente según el cronograma de cuotas debitadas
        $gross = $payment['gross_amount'];
        $installments = $payment['installments'] ?: 3;
        
        $commissionRate = 0.075; // 7.5%
        $ivaRate = 0.21; // 21% de IVA sobre la comisión
        
        $feeBase = $gross * $commissionRate;
        $feeIva = $feeBase * $ivaRate;
        $totalFees = round($feeBase + $feeIva, 2);
        $net = $gross - $totalFees;

        // Distribución de acreditaciones: en GoCuotas, el comercio suele cobrar la primera cuota a las 72hs de la transacción
        // y el resto de las cuotas a los 30, 60 días respectivamente.
        $installmentsDetail = [];
        $eachInstallmentGross = round($gross / $installments, 2);
        $eachInstallmentFee = round($totalFees / $installments, 2);
        $eachInstallmentNet = $eachInstallmentGross - $eachInstallmentFee;

        $baseDate = strtotime($payment['date']);
        for ($c = 1; $c <= $installments; $c++) {
            // Cuota 1 acredita a las 72hs hábiles (simulado en 3 días)
            // Cuota 2 a los 30 días, Cuota 3 a los 60 días, etc.
            $daysToAdd = ($c === 1) ? 3 : ($c - 1) * 30;
            $acredDate = date('Y-m-d', strtotime("+$daysToAdd days", $baseDate));
            $installmentsDetail[] = [
                'installment_number' => $c,
                'gross' => $eachInstallmentGross,
                'fee' => $eachInstallmentFee,
                'net' => $eachInstallmentNet,
                'acreditation_date' => $acredDate,
                'status' => ($c === 1 && $payment['status'] === 'approved') ? 'acreditado' : 'pendiente'
            ];
        }

        // Para el resumen general de la agenda, mostramos la fecha de la última acreditación (fin del plan)
        $lastAcredDate = $installmentsDetail[count($installmentsDetail) - 1]['acreditation_date'];

        return [
            'fee_amount' => $totalFees,
            'net_amount' => $net,
            'acreditation_date' => $lastAcredDate,
            'acreditation_type' => 'Liquidación Programada (Por Cuotas)',
            'installments_detail' => $installmentsDetail
        ];
    }
}
