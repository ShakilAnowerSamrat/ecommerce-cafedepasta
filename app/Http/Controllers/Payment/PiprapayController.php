<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use App\Models\CustomerPackage;
use App\Models\Order;
use App\Models\PiprapayPayment;
use App\Models\SellerPackage;
use App\Services\Payment\PipraPayService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PiprapayController extends Controller
{
    protected PipraPayService $pipraPayService;

    public function __construct(PipraPayService $pipraPayService)
    {
        $this->pipraPayService = $pipraPayService;
    }

    /**
     * Initiate PipraPay payment flow.
     * Called by CheckoutController or directly from route piprapay.pay.
     */
    public function pay(Request $request = null)
    {
        try {
            $paymentType = Session::get('payment_type', 'cart_payment');
            $amount = 0;
            $combinedOrderId = null;
            $orderId = null;

            if ($paymentType == 'cart_payment') {
                $combinedOrderId = Session::get('combined_order_id');
                if (!$combinedOrderId) {
                    flash(translate('Order session expired. Please try again.'))->error();
                    return redirect()->route('cart');
                }
                $combinedOrder = CombinedOrder::findOrFail($combinedOrderId);
                $amount = $combinedOrder->grand_total;
                $firstOrder = $combinedOrder->orders()->first();
                $orderId = $firstOrder ? $firstOrder->id : null;
            } elseif ($paymentType == 'wallet_payment') {
                $paymentData = Session::get('payment_data');
                $amount = $paymentData['amount'] ?? 0;
            } elseif ($paymentType == 'customer_package_payment') {
                $paymentData = Session::get('payment_data');
                $package = CustomerPackage::findOrFail($paymentData['customer_package_id']);
                $amount = $package->amount;
            } elseif ($paymentType == 'seller_package_payment') {
                $paymentData = Session::get('payment_data');
                if (class_exists(SellerPackage::class)) {
                    $package = SellerPackage::findOrFail($paymentData['seller_package_id']);
                    $amount = $package->amount;
                }
            }

            if ($amount <= 0) {
                flash(translate('Invalid payment amount.'))->error();
                return redirect()->route('cart');
            }

            $user = Auth::user();
            $userId = $user ? $user->id : null;

            // Resolve customer phone, email, and name safely
            $fullName = $user ? $user->name : ($request->name ?? 'Customer');
            $email = $user && !empty($user->email) ? $user->email : ($request->email ?? 'customer@example.com');
            $phone = $user && !empty($user->phone) ? $user->phone : ($request->phone ?? null);

            // If phone is missing from user, check order shipping address
            if (empty($phone) && $combinedOrderId) {
                $combinedOrder = CombinedOrder::find($combinedOrderId);
                if ($combinedOrder && !empty($combinedOrder->shipping_address)) {
                    $addr = json_decode($combinedOrder->shipping_address, true);
                    $phone = $addr['phone'] ?? null;
                    if (empty($fullName) && !empty($addr['name'])) {
                        $fullName = $addr['name'];
                    }
                }
            }

            if (empty($phone)) {
                $phone = '01300000000'; // Default valid BD format placeholder if guest checkout lacks phone
            }

            // Clean phone number (strip whitespace or symbols)
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) > 11 && str_starts_with($phone, '88')) {
                $phone = substr($phone, 2);
            }

            PiprapayPayment::ensureTableExists();

            // Create pending local payment record
            $payment = PiprapayPayment::create([
                'order_id'          => $orderId,
                'combined_order_id' => $combinedOrderId,
                'user_id'           => $userId,
                'payment_type'      => $paymentType,
                'provider'          => 'piprapay',
                'amount'            => $amount,
                'currency'          => 'BDT',
                'status'            => 'pending',
                'metadata'          => json_encode([
                    'order_id'          => $orderId,
                    'combined_order_id' => $combinedOrderId,
                    'user_id'           => $userId,
                    'payment_type'      => $paymentType,
                ]),
            ]);

            // Construct trusted metadata to send to PipraPay
            $metaPayload = json_encode([
                'payment_id'        => (string) $payment->id,
                'order_id'          => (string) ($orderId ?? ''),
                'combined_order_id' => (string) ($combinedOrderId ?? ''),
                'payment_type'      => (string) $paymentType,
            ]);

            $checkoutPayload = [
                'full_name'     => $fullName,
                'email_address' => $email,
                'mobile_number' => $phone,
                'amount'        => $amount,
                'currency'      => 'BDT',
                'metadata'      => $metaPayload,
                'return_url'    => route('piprapay.return'),
                'webhook_url'   => url('/api/payment/piprapay/webhook'),
            ];

            $response = $this->pipraPayService->createCheckout($checkoutPayload);

            if (!$response['success']) {
                $payment->status = 'failed';
                $payment->provider_response = json_encode($response);
                $payment->save();

                flash(translate($response['message'] ?? 'Payment initiation failed'))->error();
                return redirect()->route('cart');
            }

            // Save reference and URL
            $payment->pp_id        = $response['pp_id'];
            $payment->checkout_url = $response['pp_url'];
            $payment->provider_response = json_encode($response['raw'] ?? []);
            $payment->save();

            Session::put('piprapay_pp_id', $response['pp_id']);
            Session::put('piprapay_payment_id', $payment->id);

            // Redirect user to PipraPay hosted checkout page
            return redirect()->away($response['pp_url']);
        } catch (Exception $e) {
            Log::error('[PipraPay] Error in pay(): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            flash(translate('An unexpected error occurred. Please try again.'))->error();
            return redirect()->route('cart');
        }
    }

    /**
     * Customer return callback after checkout.
     * Route: GET/POST /payment/piprapay/return
     */
    public function return(Request $request)
    {
        try {
            $ppId = $request->input('pp_id')
                ?? $request->query('pp_id')
                ?? Session::get('piprapay_pp_id');

            if (empty($ppId)) {
                $paymentId = Session::get('piprapay_payment_id');
                if ($paymentId) {
                    $payment = PiprapayPayment::find($paymentId);
                    $ppId = $payment?->pp_id;
                }
            }

            if (empty($ppId)) {
                flash(translate('Payment reference not found.'))->error();
                return redirect()->route('home');
            }

            PiprapayPayment::ensureTableExists();

            /** @var PiprapayPayment|null $payment */
            $payment = PiprapayPayment::where('pp_id', $ppId)->first();

            if (!$payment) {
                flash(translate('Payment record not found.'))->error();
                return redirect()->route('home');
            }

            // If already marked as paid/completed (e.g. verified by webhook), safely redirect to confirmation
            if ($payment->isPaid()) {
                if ($payment->payment_type == 'cart_payment' && $payment->combined_order_id) {
                    Session::put('combined_order_id', $payment->combined_order_id);
                    flash(translate('Your payment was successful and your order has been placed!'))->success();
                    return redirect()->route('order_confirmed');
                }
                flash(translate('Payment completed successfully.'))->success();
                return redirect()->route('home');
            }

            // Call PipraPay verification endpoint
            $verification = $this->pipraPayService->verifyPayment($ppId);

            if ($verification['success'] && $verification['verified']) {
                $verifiedData = $verification['data'];

                // Validate amount
                $verifiedAmount = (float) ($verifiedData['amount'] ?? 0);
                if (abs($verifiedAmount - (float) $payment->amount) > 0.05) {
                    Log::error("[PipraPay Return] Amount mismatch on return verification for {$ppId}.");
                    $payment->status = 'amount_mismatch';
                    $payment->save();

                    flash(translate('Payment verification failed due to amount mismatch.'))->error();
                    return redirect()->route('cart');
                }

                // Finalize payment atomically
                DB::transaction(function () use ($payment, $verifiedData) {
                    $this->pipraPayService->finalizePaymentSuccess($payment, $verifiedData);
                });

                if ($payment->payment_type == 'cart_payment' && $payment->combined_order_id) {
                    Session::put('combined_order_id', $payment->combined_order_id);
                    flash(translate('Your payment was successful and your order has been placed!'))->success();
                    return redirect()->route('order_confirmed');
                }

                flash(translate('Payment completed successfully.'))->success();
                return redirect()->route('home');
            } else {
                $status = $verification['status'] ?? 'failed';
                $payment->status = $status;
                $payment->save();

                Log::warning("[PipraPay Return] Payment verification returned {$status} for {$ppId}");
                flash(translate('Payment was not completed or was cancelled.'))->error();
                return redirect()->route('cart');
            }
        } catch (Exception $e) {
            Log::error('[PipraPay Return] Exception: ' . $e->getMessage());
            flash(translate('An error occurred while verifying your payment.'))->error();
            return redirect()->route('home');
        }
    }

    /**
     * Webhook notification from PipraPay.
     * Route: POST /api/payment/piprapay/webhook
     */
    public function webhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('[PipraPay Webhook] Incoming payload received', [
                'pp_id' => $payload['pp_id'] ?? null,
                'status' => $payload['status'] ?? null,
            ]);

            $result = $this->pipraPayService->processWebhook($payload);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['status_code']);
        } catch (Exception $e) {
            Log::error('[PipraPay Webhook] Fatal error processing webhook: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error processing webhook.',
            ], 500);
        }
    }

    /**
     * Cancel route when customer cancels out of payment.
     */
    public function cancel(Request $request)
    {
        flash(translate('Payment was cancelled.'))->warning();
        return redirect()->route('cart');
    }
}
