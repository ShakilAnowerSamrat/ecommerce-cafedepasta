<?php

namespace App\Services\Payment;

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerPackageController;
use App\Http\Controllers\SellerPackageController;
use App\Http\Controllers\WalletController;
use App\Models\CombinedOrder;
use App\Models\Order;
use App\Models\PiprapayPayment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PipraPayService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.piprapay.base_url', config('piprapay.base_url', 'https://pay.softsasi.com/api')), '/');
        $this->apiKey  = (string) config('services.piprapay.api_key', config('piprapay.api_key', ''));
    }

    /**
     * Get HTTP client with PipraPay required authentication headers.
     */
    protected function client()
    {
        return Http::withHeaders([
            'MHS-PIPRAPAY-API-KEY' => $this->apiKey,
            'Content-Type'         => 'application/json',
            'Accept'               => 'application/json',
        ])->timeout(30);
    }

    /**
     * 1. Create Checkout Session with PipraPay
     *
     * @param array $payload
     * @return array ['success' => bool, 'pp_id' => string|null, 'pp_url' => string|null, 'message' => string]
     */
    public function createCheckout(array $payload): array
    {
        try {
            if (empty($this->apiKey)) {
                Log::error('[PipraPay] API Key is missing in configuration.');
                return [
                    'success' => false,
                    'message' => 'Payment gateway configuration error. Please contact administrator.',
                ];
            }

            // Ensure metadata is serialized as a JSON string
            if (isset($payload['metadata']) && is_array($payload['metadata'])) {
                $payload['metadata'] = json_encode($payload['metadata']);
            }

            // Ensure amount is formatted cleanly without exponent
            $payload['amount'] = (string) number_format((float) ($payload['amount'] ?? 0), 2, '.', '');
            $payload['currency'] = $payload['currency'] ?? 'BDT';

            // Validate required fields
            $required = ['full_name', 'email_address', 'mobile_number', 'amount', 'currency', 'metadata', 'return_url', 'webhook_url'];
            foreach ($required as $field) {
                if (empty($payload[$field])) {
                    Log::warning("[PipraPay] Missing required checkout field: {$field}");
                    return [
                        'success' => false,
                        'message' => "Missing required customer or transaction field: {$field}",
                    ];
                }
            }

            $endpoint = $this->baseUrl . '/checkout/redirect';

            // Log request safely (excluding API keys)
            Log::info('[PipraPay] Initiating checkout request', [
                'endpoint' => $endpoint,
                'amount'   => $payload['amount'],
                'currency' => $payload['currency'],
                'mobile'   => substr($payload['mobile_number'], 0, 5) . '****',
            ]);

            $response = $this->client()->post($endpoint, $payload);

            if ($response->failed()) {
                Log::error('[PipraPay] Checkout initiation failed', [
                    'status' => $response->status(),
                    'body'   => $response->json() ?? $response->body(),
                ]);
                $errorMsg = $response->json('error.message') ?? $response->json('message') ?? 'Unable to connect to PipraPay gateway.';
                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }

            $data = $response->json();

            $ppId  = $data['pp_id'] ?? null;
            $ppUrl = $data['pp_url'] ?? null;

            if (empty($ppId) || empty($ppUrl)) {
                Log::error('[PipraPay] Incomplete response from gateway checkout endpoint', ['response' => $data]);
                return [
                    'success' => false,
                    'message' => 'Invalid response received from payment gateway.',
                ];
            }

            return [
                'success'  => true,
                'pp_id'    => (string) $ppId,
                'pp_url'   => (string) $ppUrl,
                'raw'      => $data,
            ];
        } catch (Exception $e) {
            Log::error('[PipraPay] Exception during createCheckout: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment gateway communication error. Please try again.',
            ];
        }
    }

    /**
     * 2. Verify Payment with PipraPay Server
     *
     * @param string $ppId
     * @return array ['success' => bool, 'verified' => bool, 'data' => array|null, 'message' => string]
     */
    public function verifyPayment(string $ppId): array
    {
        try {
            if (empty($ppId)) {
                return [
                    'success'  => false,
                    'verified' => false,
                    'message'  => 'Missing transaction reference ID.',
                ];
            }

            $endpoint = $this->baseUrl . '/verify-payment';

            $response = $this->client()->post($endpoint, [
                'pp_id' => $ppId,
            ]);

            if ($response->failed()) {
                Log::error('[PipraPay] Payment verification request failed', [
                    'pp_id'  => $ppId,
                    'status' => $response->status(),
                    'body'   => $response->json() ?? $response->body(),
                ]);
                $errorMsg = $response->json('error.message') ?? $response->json('message') ?? 'Verification failed.';
                return [
                    'success'  => false,
                    'verified' => false,
                    'message'  => $errorMsg,
                ];
            }

            $data = $response->json();

            // Expected status in verification payload is typically 'completed'
            $status = strtolower($data['status'] ?? '');
            $isCompleted = in_array($status, ['completed', 'success', 'successful', 'paid']);

            return [
                'success'     => true,
                'verified'    => $isCompleted,
                'status'      => $status,
                'data'        => $data,
                'message'     => $isCompleted ? 'Payment verified successfully.' : "Payment status is {$status}.",
            ];
        } catch (Exception $e) {
            Log::error('[PipraPay] Exception during verifyPayment: ' . $e->getMessage(), ['pp_id' => $ppId]);
            return [
                'success'  => false,
                'verified' => false,
                'message'  => 'An error occurred during payment verification.',
            ];
        }
    }

    /**
     * 3. Refund Payment with PipraPay
     *
     * @param string $ppId
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function refundPayment(string $ppId): array
    {
        try {
            if (empty($ppId)) {
                return [
                    'success' => false,
                    'message' => 'Missing transaction reference ID for refund.',
                ];
            }

            $endpoint = $this->baseUrl . '/refund-payment';

            Log::info('[PipraPay] Requesting refund for pp_id: ' . $ppId);

            $response = $this->client()->post($endpoint, [
                'pp_id' => $ppId,
            ]);

            if ($response->failed()) {
                Log::error('[PipraPay] Refund request failed', [
                    'pp_id'  => $ppId,
                    'status' => $response->status(),
                    'body'   => $response->json() ?? $response->body(),
                ]);
                $errorMsg = $response->json('error.message') ?? $response->json('message') ?? 'Refund failed on gateway.';
                return [
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }

            $data = $response->json();
            $status = strtolower($data['status'] ?? $data['refund_status'] ?? '');

            // Ensure not a logical failure returned with HTTP 200
            if (isset($data['error']) || (isset($data['success']) && $data['success'] === false)) {
                return [
                    'success' => false,
                    'message' => $data['error']['message'] ?? $data['message'] ?? 'Refund could not be processed.',
                    'data'    => $data,
                ];
            }

            return [
                'success' => true,
                'message' => 'Refund processed successfully.',
                'data'    => $data,
            ];
        } catch (Exception $e) {
            Log::error('[PipraPay] Exception during refundPayment: ' . $e->getMessage(), ['pp_id' => $ppId]);
            return [
                'success' => false,
                'message' => 'An error occurred while processing refund: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Webhook or Reconcile Return safely and idempotently.
     *
     * @param array $payload
     * @return array ['success' => bool, 'status_code' => int, 'message' => string]
     */
    public function processWebhook(array $payload): array
    {
        $ppId = $payload['pp_id'] ?? null;
        if (empty($ppId)) {
            Log::warning('[PipraPay Webhook] Received webhook payload missing pp_id', ['payload' => $payload]);
            return [
                'success'     => false,
                'status_code' => 400,
                'message'     => 'Missing pp_id in webhook payload.',
            ];
        }

        PiprapayPayment::ensureTableExists();

        // Use DB Transaction with row lock to ensure concurrency safety
        return DB::transaction(function () use ($ppId, $payload) {
            /** @var PiprapayPayment|null $payment */
            $payment = PiprapayPayment::where('pp_id', $ppId)->lockForUpdate()->first();

            if (!$payment) {
                // If not found by pp_id, attempt to match using metadata invoice/order ID
                $meta = $payload['metadata'] ?? [];
                if (is_string($meta)) {
                    $meta = json_decode($meta, true) ?? [];
                }

                if (!empty($meta['payment_id'])) {
                    $payment = PiprapayPayment::where('id', $meta['payment_id'])->lockForUpdate()->first();
                } elseif (!empty($meta['combined_order_id'])) {
                    $payment = PiprapayPayment::where('combined_order_id', $meta['combined_order_id'])->latest()->lockForUpdate()->first();
                }
            }

            if (!$payment) {
                Log::warning("[PipraPay Webhook] No matching local payment record found for pp_id: {$ppId}");
                return [
                    'success'     => false,
                    'status_code' => 404,
                    'message'     => 'Unknown local payment record.',
                ];
            }

            // Check if payment is already finalized
            if ($payment->isPaid()) {
                Log::info("[PipraPay Webhook] Payment {$ppId} is already completed. Skipping duplicate processing.");
                return [
                    'success'     => true,
                    'status_code' => 200,
                    'message'     => 'Payment already processed.',
                ];
            }

            // Verify with PipraPay server (authoritative source)
            $verification = $this->verifyPayment($ppId);
            if (!$verification['success'] || !$verification['verified']) {
                $status = $verification['status'] ?? 'failed';
                $payment->status = $status;
                $payment->webhook_payload = json_encode($payload);
                $payment->save();

                Log::warning("[PipraPay Webhook] Verification did not confirm completion for {$ppId}. Status: {$status}");
                return [
                    'success'     => false,
                    'status_code' => 422,
                    'message'     => "Payment verification status: {$status}",
                ];
            }

            $verifiedData = $verification['data'];

            // Financial validation: Validate amount & currency
            $verifiedAmount = (float) ($verifiedData['amount'] ?? 0);
            $expectedAmount = (float) $payment->amount;

            if (abs($verifiedAmount - $expectedAmount) > 0.05) {
                Log::error("[PipraPay Webhook] Amount mismatch for {$ppId}! Expected: {$expectedAmount}, Verified: {$verifiedAmount}");
                $payment->status = 'amount_mismatch';
                $payment->provider_response = json_encode($verifiedData);
                $payment->save();

                return [
                    'success'     => false,
                    'status_code' => 422,
                    'message'     => 'Payment amount mismatch.',
                ];
            }

            $verifiedCurrency = strtoupper($verifiedData['currency'] ?? $verifiedData['local_currency'] ?? 'BDT');
            $expectedCurrency = strtoupper($payment->currency ?: 'BDT');

            if ($verifiedCurrency !== $expectedCurrency && !in_array($verifiedCurrency, ['BDT', 'USD'])) {
                Log::error("[PipraPay Webhook] Currency mismatch for {$ppId}! Expected: {$expectedCurrency}, Verified: {$verifiedCurrency}");
                $payment->status = 'currency_mismatch';
                $payment->save();

                return [
                    'success'     => false,
                    'status_code' => 422,
                    'message'     => 'Payment currency mismatch.',
                ];
            }

            // Finalize payment locally
            $this->finalizePaymentSuccess($payment, $verifiedData, $payload);

            return [
                'success'     => true,
                'status_code' => 200,
                'message'     => 'Payment verified and finalized successfully.',
            ];
        });
    }

    /**
     * Atomically finalize payment record and trigger business logic exactly once.
     */
    public function finalizePaymentSuccess(PiprapayPayment $payment, array $verifiedData, ?array $rawWebhook = null): void
    {
        $payment->status                 = 'completed';
        $payment->provider_transaction_id = $verifiedData['transaction_id'] ?? $payment->provider_transaction_id;
        $payment->fee                    = (float) ($verifiedData['fee'] ?? 0.00);
        $payment->discount_amount        = (float) ($verifiedData['discount_amount'] ?? 0.00);
        $payment->net_amount             = (float) ($verifiedData['local_net_amount'] ?? $verifiedData['total'] ?? $payment->amount);
        $payment->provider_response      = json_encode($verifiedData);
        if ($rawWebhook) {
            $payment->webhook_payload    = json_encode($rawWebhook);
        }
        $payment->paid_at                = now();
        $payment->save();

        $paymentDetails = json_encode([
            'provider'                => 'piprapay',
            'pp_id'                   => $payment->pp_id,
            'provider_transaction_id' => $payment->provider_transaction_id,
            'amount'                  => $payment->amount,
            'currency'                => $payment->currency,
            'fee'                     => $payment->fee,
            'gateway'                 => $verifiedData['gateway'] ?? 'PipraPay',
            'sender'                  => $verifiedData['sender'] ?? null,
            'date'                    => $verifiedData['date'] ?? now()->toDateTimeString(),
        ]);

        // Trigger corresponding eCommerce fulfillment logic based on payment_type
        switch ($payment->payment_type) {
            case 'cart_payment':
                if ($payment->combined_order_id) {
                    $combinedOrder = CombinedOrder::find($payment->combined_order_id);
                    if ($combinedOrder) {
                        (new CheckoutController)->checkout_done($payment->combined_order_id, $paymentDetails);
                    }
                } elseif ($payment->order_id) {
                    $order = Order::find($payment->order_id);
                    if ($order && $order->combined_order_id) {
                        (new CheckoutController)->checkout_done($order->combined_order_id, $paymentDetails);
                    } elseif ($order) {
                        $order->payment_status = 'paid';
                        $order->payment_details = $paymentDetails;
                        $order->save();
                    }
                }
                break;

            case 'wallet_payment':
                $paymentData = json_decode($payment->metadata, true) ?? [];
                $paymentData['amount'] = $payment->amount;
                $paymentData['payment_method'] = 'piprapay';
                (new WalletController)->wallet_payment_done($paymentData, $paymentDetails);
                break;

            case 'customer_package_payment':
                $paymentData = json_decode($payment->metadata, true) ?? [];
                $paymentData['payment_method'] = 'piprapay';
                (new CustomerPackageController)->purchase_payment_done($paymentData, $paymentDetails);
                break;

            case 'seller_package_payment':
                $paymentData = json_decode($payment->metadata, true) ?? [];
                $paymentData['payment_method'] = 'piprapay';
                if (class_exists(SellerPackageController::class)) {
                    (new SellerPackageController)->purchase_payment_done($paymentData, $paymentDetails);
                }
                break;
        }

        Log::info("[PipraPay] Payment {$payment->pp_id} marked as completed for type: {$payment->payment_type}");
    }
}
