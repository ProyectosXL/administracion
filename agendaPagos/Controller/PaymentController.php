<?php
// Controller/PaymentController.php
header('Content-Type: application/json');

require_once __DIR__ . '/../Class/PaymentService.php';

$service = new PaymentService();

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_payments':
        $desde = $_GET['desde'] ?? date('Y-m-d');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $procesador = $_GET['procesador'] ?? 'todos';
        $estado = $_GET['estado'] ?? 'todos';
        $sucursal = $_GET['sucursal'] ?? 'todos';

        try {
            $payments = $service->getConsolidatedPayments($desde, $hasta, $procesador, $estado, $sucursal);
            $metrics = $service->calculateMetrics($payments);
            $posSales = $service->getPosSales($desde, $hasta, $sucursal, $procesador);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'metrics' => $metrics,
                    'pos_sales' => $posSales
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al consultar transacciones: ' . $e->getMessage()
            ]);
        }
        break;

    case 'create_link':
        $procesador = $_POST['provider'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $description = $_POST['description'] ?? '';
        $installments = intval($_POST['installments'] ?? 1);
        $client = $_POST['client'] ?? '';

        if (empty($procesador) || $amount <= 0 || empty($description)) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos incompletos para generar el cobro.'
            ]);
            exit;
        }

        try {
            $res = $service->createPaymentLink($procesador, $amount, $description, $installments, ['client' => $client]);
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al generar link: ' . $e->getMessage()
            ]);
        }
        break;

    case 'get_bank_promos':
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $sucursal = $_GET['sucursal'] ?? 'todos';
        $banco = $_GET['banco'] ?? 'todos';

        require_once __DIR__ . '/../Class/BankPromoService.php';
        $promoService = new BankPromoService();

        try {
            $res = $promoService->getBankPromos($desde, $hasta, $sucursal, $banco);
            echo json_encode([
                'success' => true,
                'data' => $res
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al consultar promociones bancarias: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no permitida o desconocida.'
        ]);
        break;
}
