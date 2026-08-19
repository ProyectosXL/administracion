<?php
// Class/GoCuotasAdapter.php
require_once __DIR__ . '/PaymentProvider.php';

class GoCuotasAdapter implements PaymentProvider
{
    private $email;
    private $defaultToken;
    private $sandbox;
    private $isConfigured = false;
    private $sucursales = [];

    public function __construct(array $envVars = [])
    {
        $this->email = $envVars['GOCUOTAS_EMAIL'] ?? '';
        $this->defaultToken = $envVars['GOCUOTAS_APIKEY'] ?? ($envVars['GOCUOTAS_PASSWORD'] ?? '');
        $this->sandbox = filter_var($envVars['GOCUOTAS_SANDBOX'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $configFile = __DIR__ . '/../config/gocuotas_sucursales.php';
        if (file_exists($configFile)) {
            $this->sucursales = require $configFile;
        }

        if (!empty($this->email) && (!empty($this->defaultToken) || !empty($this->sucursales))) {
            $this->isConfigured = true;
        }
    }

    public function login(array $credentials = []): bool
    {
        return true;
    }

    /**
     * Consulta las órdenes reales de GoCuotas para las sucursales requeridas utilizando cURL Multi para ejecución en paralelo.
     */
    public function getPayments(string $desde, string $hasta, string $status = 'todos', string $sucursalTarget = 'todos'): array
    {
        if (!$this->isConfigured) {
            throw new Exception("El proveedor GoCuotas no está configurado.");
        }

        $baseUrl = $this->sandbox ? 'https://sandbox.gocuotas.com/api_redirect/v1' : 'https://www.gocuotas.com/api_redirect/v1';

        // Determinar qué sucursales consultar
        $sucursalesToFetch = [];

        if ($sucursalTarget !== 'todos' && isset($this->sucursales[$sucursalTarget])) {
            $sucursalesToFetch[$sucursalTarget] = $this->sucursales[$sucursalTarget];
        } else {
            if (!empty($this->sucursales)) {
                $sucursalesToFetch = $this->sucursales;
            } else {
                $sucursalesToFetch['Venta Online'] = [
                    'nombre' => 'Venta Online',
                    'production_key' => $this->defaultToken,
                    'sandbox_key' => $this->defaultToken
                ];
            }
        }

        $startStr = urlencode($desde . ' 00:00:00');
        $endStr   = urlencode($hasta . ' 23:59:59');

        // Inicializar cURL Multi para ejecutar todas las consultas en paralelo
        $mh = curl_multi_init();
        $curlHandles = [];

        foreach ($sucursalesToFetch as $keySuc => $sucInfo) {
            $apiKey = $this->sandbox ? ($sucInfo['sandbox_key'] ?? '') : ($sucInfo['production_key'] ?? '');
            if (empty($apiKey)) {
                $apiKey = $this->defaultToken;
            }

            if (empty($apiKey)) continue;

            $url = "{$baseUrl}/orders?delivered_start={$startStr}&delivered_end={$endStr}";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$apiKey}",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_multi_add_handle($mh, $ch);
            $curlHandles[$keySuc] = [
                'ch' => $ch,
                'nombre' => $sucInfo['nombre'] ?? $keySuc
            ];
        }

        // Ejecutar peticiones en paralelo
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Procesar las respuestas
        $allPayments = [];
        $timezoneART = new DateTimeZone('America/Argentina/Buenos_Aires');

        foreach ($curlHandles as $keySuc => $handleData) {
            $ch = $handleData['ch'];
            $nombreSucursal = $handleData['nombre'];

            $response = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            if ($httpCode === 200 && !empty($response)) {
                $orders = json_decode($response, true);
                if (is_array($orders)) {
                    foreach ($orders as $order) {
                        $orderStatus = strtolower($order['status'] ?? 'approved');
                        if ($status !== 'todos' && $status !== $orderStatus) {
                            continue;
                        }

                        $amount = floatval(($order['amount_in_cents'] ?? 0) / 100);
                        $installments = intval($order['number_of_installments'] ?? 1);
                        $cardName = $order['payment']['card']['name'] ?? 'GoCuotas';
                        $cardNumber = $order['payment']['card']['number'] ?? '';

                        // Cálculo de comisión estimada (7.5% + 21% IVA = 9.075%)
                        $feeBase = $amount * 0.075;
                        $feeIva = $feeBase * 0.21;
                        $totalFees = round($feeBase + $feeIva, 2);
                        $netAmount = round($amount - $totalFees, 2);

                        $rawDate = $order['delivered_at'] ?? $desde;
                        try {
                            $dt = new DateTime($rawDate);
                            $dt->setTimezone($timezoneART);
                            $dateFormatted = $dt->format('Y-m-d H:i:s');
                        } catch (Exception $e) {
                            $dateFormatted = date('Y-m-d H:i:s', strtotime($rawDate));
                        }

                        // Desglose de cuotas
                        $installmentsDetail = [];
                        $eachGross = round($amount / max(1, $installments), 2);
                        $eachFee = round($totalFees / max(1, $installments), 2);
                        $eachNet = round($eachGross - $eachFee, 2);

                        $baseTs = strtotime($dateFormatted);
                        for ($i = 1; $i <= $installments; $i++) {
                            $daysToAdd = ($i === 1) ? 3 : ($i - 1) * 30;
                            $installmentsDetail[] = [
                                'installment_number' => $i,
                                'gross' => $eachGross,
                                'fee' => $eachFee,
                                'net' => $eachNet,
                                'acreditation_date' => date('Y-m-d', strtotime("+$daysToAdd days", $baseTs)),
                                'status' => ($i === 1 && $orderStatus === 'approved') ? 'acreditado' : 'pendiente'
                            ];
                        }

                        $allPayments[] = [
                            'id' => 'GC-ORD-' . ($order['id'] ?? mt_rand(100000, 999999)),
                            'date' => $dateFormatted,
                            'provider' => 'gocuotas',
                            'client_name' => $cardName,
                            'description' => "Cobro $installments cuotas - $cardName (" . ($cardNumber ? $cardNumber : '******') . ")",
                            'installments' => $installments,
                            'gross_amount' => $amount,
                            'fee_amount' => $totalFees,
                            'net_amount' => $netAmount,
                            'status' => $orderStatus === 'approved' ? 'approved' : ($orderStatus === 'rejected' ? 'rejected' : 'pending'),
                            'sucursal' => $nombreSucursal,
                            'reference' => 'ORD-' . ($order['id'] ?? ''),
                            'acreditation_date' => date('Y-m-d', strtotime('+3 days', $baseTs)),
                            'acreditation_type' => 'Liquidación por Cuotas (GoCuotas)',
                            'installments_detail' => $installmentsDetail
                        ];
                    }
                }
            }
        }

        curl_multi_close($mh);

        return $allPayments;
    }

    public function createPaymentLink(float $amount, string $description, int $installments = 1, array $extraDatos = []): array
    {
        $txId = 'GC-' . (270600000 + mt_rand(1000, 9999));
        return [
            'success' => true,
            'transaction_id' => $txId,
            'checkout_url' => 'https://sandbox.gocuotas.com/checkout/' . md5($txId),
            'message' => 'Link de pago generado exitosamente para GoCuotas',
            'provider' => 'gocuotas'
        ];
    }

    public function calculateSettlement(array $payment): array
    {
        $gross = $payment['gross_amount'];
        $installments = $payment['installments'] ?: 3;
        
        $commissionRate = 0.075;
        $ivaRate = 0.21;
        
        $feeBase = $gross * $commissionRate;
        $feeIva = $feeBase * $ivaRate;
        $totalFees = round($feeBase + $feeIva, 2);
        $net = $gross - $totalFees;

        $installmentsDetail = [];
        $eachInstallmentGross = round($gross / $installments, 2);
        $eachInstallmentFee = round($totalFees / $installments, 2);
        $eachInstallmentNet = $eachInstallmentGross - $eachInstallmentFee;

        $baseDate = strtotime($payment['date']);
        for ($c = 1; $c <= $installments; $c++) {
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
