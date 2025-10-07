<?php

namespace PayzyLaravel\PaymentGateway\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array preparePaymentData(array $orderData)
 * @method static array initiatePayment(array $orderData, \PayzyLaravel\PaymentGateway\Models\PayzyPayment $payment = null)
 * @method static array verifyPayment(string $orderId, string $responseCode, string $signature)
 *
 * @see \PayzyLaravel\PaymentGateway\Services\PayzyPaymentService
 */
class Payzy extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'payzy';
    }
}