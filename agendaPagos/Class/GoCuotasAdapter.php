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
        // Leer credenciales de W:\.env a través de las variables pasadas
        $this->email = $envVars['GOCUOTAS_EMAIL'] ?? '';
        $this->password = $envVars['GOCUOTAS_PASSWORD'] ?? '';
        $this->sandbox = filter_var($envVars['GOCUOTAS_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (!empty($this->email) && !empty($this->password)) {
            $this->isConfigured = true;
        }
    }

    public function login(array $credentials = []): bool
    {
        if (!$this->isConfigured) {
            // Modo Simulación autorizado si no hay credenciales
            return true;
        }

        // Si hay credenciales reales, prepararíamos la petición HTTP POST a GoCuotas:
        // Endpoint: POST https://sandbox.gocuotas.com/api_redirect/v1/auth/login o www.gocuotas.com
        // Retornaría un JWT en $this->token
        $this->token = 'mock_jwt_token_gocuotas_' . time();
        return true;
    }

    public function getPayments(string $desde, string $hasta, string $status = 'todos'): array
    {
        // Si no está configurada, devolvemos cobros mock realistas
        // En producción se consultaría a la API Client V1 de GoCuotas
        $payments = [];

        // Generar un set de datos de prueba coherentes para GoCuotas
        // GoCuotas es una procesadora Buy Now Pay Later (débito en cuotas)
        $clientes = ['Agustina Fernandez', 'Gaston Rodriguez', 'Sofia Lopez', 'Nicolas Diaz', 'Clara Benitez'];
        $conceptos = ['Zapatillas Urbanas', 'Remera XL Lakers', 'Jean Slim Fit', 'Campera Térmica', 'Accesorios de Cuero'];
        
        // Semilla basada en las fechas para mantener consistencia
        $seed = strtotime($desde);
        mt_srand($seed);

        $numTx = mt_rand(4, 8);
        for ($i = 0; $i < $numTx; $i++) {
            $amount = mt_rand(150, 600) * 100; // montos entre 15.000 y 60.000
            $installments = [3, 4, 6][mt_rand(0, 2)]; // 3, 4 o 6 cuotas
            $txStatus = ['approved', 'approved', 'approved', 'pending', 'rejected'][mt_rand(0, 4)];
            
            if ($status !== 'todos' && $txStatus !== $status) {
                continue;
            }

            // Generar una fecha aleatoria dentro del rango
            $daysDiff = (strtotime($hasta) - strtotime($desde)) / (60 * 60 * 24);
            $randomDays = ($daysDiff > 0) ? mt_rand(0, intval($daysDiff)) : 0;
            $txDate = date('Y-m-d H:i:s', strtotime($desde . " +$randomDays days") + mt_rand(0, 86400));

            $txId = 'GC-' . (270600000 + mt_rand(1000, 9999));
            $cliente = $clientes[$i % count($clientes)];
            $concepto = $conceptos[$i % count($conceptos)];

            $sucursales = ['Local 1 - Abasto', 'Local 2 - Palermo', 'Local 3 - Belgrano', 'Local 4 - Centro'];
            $payment = [
                'id' => $txId,
                'date' => $txDate,
                'provider' => 'gocuotas',
                'client_name' => $cliente,
                'description' => $concepto,
                'installments' => $installments,
                'gross_amount' => floatval($amount),
                'status' => $txStatus,
                'sucursal' => $sucursales[$i % count($sucursales)],
                'reference' => 'REF-' . mt_rand(100000, 999999)
            ];

            // Calcular comisiones y fechas de acreditación
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
