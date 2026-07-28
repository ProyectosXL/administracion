<?php
// Class/PaymentProvider.php
// Interfaz base común para todos los proveedores de pagos

interface PaymentProvider
{
    /**
     * Autentica el adaptador en la pasarela correspondiente.
     * Lee credenciales del .env o parámetros pasados.
     * 
     * @param array $credentials Opcional, credenciales custom.
     * @return bool True si la autenticación es exitosa o simulación autorizada.
     */
    public function login(array $credentials = []): bool;

    /**
     * Obtiene el listado de pagos/transacciones para un rango de fechas.
     * 
     * @param string $desde Fecha inicial (YYYY-MM-DD).
     * @param string $hasta Fecha final (YYYY-MM-DD).
     * @param string $status Estado del pago (todos, approved, pending, rejected).
     * @return array Listado de cobros estructurado de forma estándar.
     */
    public function getPayments(string $desde, string $hasta, string $status = 'todos'): array;

    /**
     * Crea un link de cobro (checkout) para la procesadora.
     * 
     * @param float $amount Importe bruto.
     * @param string $description Descripción/Concepto del cobro.
     * @param int $installments Cantidad de cuotas.
     * @param array $extraDatos Datos adicionales del cliente.
     * @return array Detalle del cobro y link generado.
     */
    public function createPaymentLink(float $amount, string $description, int $installments = 1, array $extraDatos = []): array;

    /**
     * Devuelve información y estimación de acreditación/liquidación del dinero para el control.
     * 
     * @param array $payment Operación de cobro.
     * @return array Fecha estimada de acreditación, comisiones aplicadas, monto neto real.
     */
    public function calculateSettlement(array $payment): array;
}
