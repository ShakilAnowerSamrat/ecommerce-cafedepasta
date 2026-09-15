<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PiprapayPayment;
use App\Services\Payment\PipraPayService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PiprapayAdminController extends Controller
{
    protected PipraPayService $pipraPayService;

    public function __construct(PipraPayService $pipraPayService)
    {
        $this->middleware(['auth', 'admin']);
        $this->pipraPayService = $pipraPayService;
    }

    /**
     * Process a refund for a PipraPay transaction from the admin panel.
     */
    public function refund(Request $request)
    {
        $request->validate([
            'payment_id' => 'nullable|integer',
            'order_id'   => 'nullable|integer',
            'pp_id'      => 'nullable|string',
        ]);

        try {
            PiprapayPayment::ensureTableExists();

            /** @var PiprapayPayment|null $payment */
            $payment = null;

            if ($request->filled('payment_id')) {
                $payment = PiprapayPayment::find($request->payment_id);
            } elseif ($request->filled('pp_id')) {
                $payment = PiprapayPayment::where('pp_id', $request->pp_id)->first();
            } elseif ($request->filled('order_id')) {
                $payment = PiprapayPayment::where('order_id', $request->order_id)
                    ->orWhere('combined_order_id', function ($query) use ($request) {
                        $query->select('combined_order_id')
                            ->from('orders')
                            ->where('id', $request->order_id)
                            ->limit(1);
                    })
                    ->latest()
                    ->first();
            }

            if (!$payment) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'PipraPay transaction record not found.'], 404);
                }
                flash(translate('PipraPay transaction record not found.'))->error();
                return back();
            }

            if (!$payment->isPaid()) {
                $msg = 'Cannot refund an unpaid or incomplete transaction.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                flash(translate($msg))->warning();
                return back();
            }

            if ($payment->isRefunded()) {
                $msg = 'This transaction has already been refunded.';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                flash(translate($msg))->warning();
                return back();
            }

            if (empty($payment->pp_id)) {
                $msg = 'Transaction is missing gateway reference ID (pp_id).';
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                flash(translate($msg))->error();
                return back();
            }

            Log::info("[PipraPay Admin] Admin " . Auth::id() . " initiating refund for pp_id: " . $payment->pp_id);

            // Execute refund API request
            $refundResponse = $this->pipraPayService->refundPayment($payment->pp_id);

            if (!$refundResponse['success']) {
                $errorMsg = $refundResponse['message'] ?? 'Gateway rejected the refund request.';
                Log::error("[PipraPay Admin] Refund failed for pp_id {$payment->pp_id}: {$errorMsg}");
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                flash(translate($errorMsg))->error();
                return back();
            }

            // Update database states in transaction
            DB::transaction(function () use ($payment, $refundResponse) {
                $payment->status        = 'refunded';
                $payment->refund_status = 'refunded';
                $payment->refunded_at   = now();
                $payment->provider_response = json_encode(array_merge(
                    json_decode($payment->provider_response ?? '{}', true) ?: [],
                    ['refund' => $refundResponse['data'] ?? []]
                ));
                $payment->save();

                // Update related order(s)
                if ($payment->combined_order_id) {
                    Order::where('combined_order_id', $payment->combined_order_id)->update([
                        'payment_status' => 'refunded',
                    ]);
                } elseif ($payment->order_id) {
                    Order::where('id', $payment->order_id)->update([
                        'payment_status' => 'refunded',
                    ]);
                }
            });

            Log::info("[PipraPay Admin] Refund completed successfully for pp_id: {$payment->pp_id}");

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment refunded successfully through PipraPay.',
                ]);
            }

            flash(translate('Payment refunded successfully through PipraPay.'))->success();
            return back();
        } catch (Exception $e) {
            Log::error('[PipraPay Admin] Exception during refund: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'An error occurred while processing the refund.'], 500);
            }
            flash(translate('An error occurred while processing the refund.'))->error();
            return back();
        }
    }
}
