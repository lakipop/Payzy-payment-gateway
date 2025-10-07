<?php

namespace PayzyLaravel\PaymentGateway\Tests\Feature;

use PayzyLaravel\PaymentGateway\Tests\TestCase;
use PayzyLaravel\PaymentGateway\Services\PayzyPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PayzyPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PayzyPaymentService $payzyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payzyService = new PayzyPaymentService();
    }

    /** @test */
    public function it_can_prepare_payment_data(): void
    {
        $orderData = [
            'order_id' => 'TEST123',
            'amount' => 100.00,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+94771234567',
            'address' => '123 Test Street',
            'city' => 'Colombo',
            'state' => 'Western',
            'country' => 'Sri Lanka',
            'zip' => '10100',
        ];

        $paymentData = $this->payzyService->preparePaymentData($orderData);

        $this->assertIsArray($paymentData);
        $this->assertEquals('on', $paymentData['x_test_mode']); // Test mode should be on
        $this->assertEquals('test_shop_id', $paymentData['x_shopid']);
        $this->assertEquals('100', $paymentData['x_amount']);
        $this->assertEquals('TEST123', $paymentData['x_order_id']);
        $this->assertArrayHasKey('signature', $paymentData);
        $this->assertArrayHasKey('signed_field_names', $paymentData);
    }

    /** @test */
    public function it_generates_proper_signature(): void
    {
        $orderData = [
            'order_id' => 'TEST123',
            'amount' => 100.00,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+94771234567',
            'address' => '123 Test Street',
            'city' => 'Colombo',
            'state' => 'Western',
            'country' => 'Sri Lanka',
            'zip' => '10100',
        ];

        $paymentData = $this->payzyService->preparePaymentData($orderData);

        $this->assertNotEmpty($paymentData['signature']);
        $this->assertTrue(base64_decode($paymentData['signature'], true) !== false);
    }
}