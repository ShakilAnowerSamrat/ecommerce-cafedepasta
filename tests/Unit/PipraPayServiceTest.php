<?php

namespace Tests\Unit;

use App\Models\PiprapayPayment;
use App\Services\Payment\PipraPayService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PipraPayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.piprapay.base_url', 'https://pay.softsasi.com/api');
        Config::set('services.piprapay.api_key', 'test_api_key_12345');
    }

    /**
     * Test successful checkout creation.
     */
    public function test_create_checkout_success()
    {
        Http::fake([
            'https://pay.softsasi.com/api/checkout/redirect' => Http::response([
                'pp_id'  => '349452200706799329851862826',
                'pp_url' => 'https://pay.softsasi.com/checkout/349452200706799329851862826',
            ], 200),
        ]);

        $service = new PipraPayService();
        $payload = [
            'full_name'     => 'Test Customer',
            'email_address' => 'customer@example.com',
            'mobile_number' => '01300000000',
            'amount'        => '100.00',
            'currency'      => 'BDT',
            'metadata'      => ['order_id' => '123', 'invoice_id' => 'INV-123'],
            'return_url'    => 'https://example.com/payment/piprapay/return',
            'webhook_url'   => 'https://example.com/api/payment/piprapay/webhook',
        ];

        $result = $service->createCheckout($payload);

        $this->assertTrue($result['success']);
        $this->assertEquals('349452200706799329851862826', $result['pp_id']);
        $this->assertEquals('https://pay.softsasi.com/checkout/349452200706799329851862826', $result['pp_url']);
    }

    /**
     * Test checkout fails when required fields are missing.
     */
    public function test_create_checkout_missing_required_field()
    {
        $service = new PipraPayService();
        $payload = [
            'full_name'     => '',
            'email_address' => 'customer@example.com',
            'mobile_number' => '01300000000',
            'amount'        => '100.00',
            'currency'      => 'BDT',
            'metadata'      => '{"order_id":"123"}',
            'return_url'    => 'https://example.com/payment/piprapay/return',
            'webhook_url'   => 'https://example.com/api/payment/piprapay/webhook',
        ];

        $result = $service->createCheckout($payload);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required', $result['message']);
    }

    /**
     * Test successful payment verification.
     */
    public function test_verify_payment_success()
    {
        Http::fake([
            'https://pay.softsasi.com/api/verify-payment' => Http::response([
                'pp_id'          => '349452200706799329851862826',
                'full_name'      => 'Test Customer',
                'email_address'  => 'customer@example.com',
                'mobile_number'  => '01300000000',
                'amount'         => '100.00',
                'currency'       => 'BDT',
                'status'         => 'completed',
                'transaction_id' => 'TRX12345678',
                'fee'            => '1.50',
            ], 200),
        ]);

        $service = new PipraPayService();
        $result = $service->verifyPayment('349452200706799329851862826');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verified']);
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals('TRX12345678', $result['data']['transaction_id']);
    }

    /**
     * Test payment verification when status is failed or pending.
     */
    public function test_verify_payment_unsuccessful()
    {
        Http::fake([
            'https://pay.softsasi.com/api/verify-payment' => Http::response([
                'pp_id'  => '349452200706799329851862826',
                'status' => 'failed',
            ], 200),
        ]);

        $service = new PipraPayService();
        $result = $service->verifyPayment('349452200706799329851862826');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertEquals('failed', $result['status']);
    }

    /**
     * Test refund payment success.
     */
    public function test_refund_payment_success()
    {
        Http::fake([
            'https://pay.softsasi.com/api/refund-payment' => Http::response([
                'pp_id'   => '349452200706799329851862826',
                'status'  => 'refunded',
                'message' => 'Refund processed successfully.',
            ], 200),
        ]);

        $service = new PipraPayService();
        $result = $service->refundPayment('349452200706799329851862826');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Refund', $result['message']);
    }

    /**
     * Test refund payment failure.
     */
    public function test_refund_payment_failure()
    {
        Http::fake([
            'https://pay.softsasi.com/api/refund-payment' => Http::response([
                'error' => [
                    'code'    => 'REFUND_NOT_ALLOWED',
                    'message' => 'Transaction cannot be refunded.',
                ],
            ], 422),
        ]);

        $service = new PipraPayService();
        $result = $service->refundPayment('349452200706799329851862826');

        $this->assertFalse($result['success']);
        $this->assertEquals('Transaction cannot be refunded.', $result['message']);
    }

    /**
     * Test webhook rejects missing pp_id.
     */
    public function test_webhook_missing_pp_id()
    {
        $service = new PipraPayService();
        $result = $service->processWebhook([]);

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['status_code']);
    }

    /**
     * Test webhook with unknown pp_id returns 404.
     */
    public function test_webhook_unknown_payment()
    {
        $service = new PipraPayService();
        $result = $service->processWebhook([
            'pp_id' => 'non_existent_pp_id_999999999',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['status_code']);
    }
}
