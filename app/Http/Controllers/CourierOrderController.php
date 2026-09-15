<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SteadfastCourierService;
use App\Models\Order;
use PhpParser\Node\Stmt\TryCatch;

class CourierOrderController extends Controller
{
    private $courierService;

    public function __construct(SteadfastCourierService $courierService)
    {
        $this->courierService = $courierService;
    }

    public function createOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'invoice' => 'required',
            ]);

            $order = Order::where('code', $request->invoice)->first();

            if (!$order) {
                return response()->json([
                    'status' => false,
                    'message' => "Order not found",
                ], 200);
            }

            $shippingAddress = json_decode($order->shipping_address);

            if (!$shippingAddress && empty($shippingAddress->name) && empty($shippingAddress->phone) && empty($shippingAddress->address)) {
                return response()->json([
                    'status' => false,
                    'message' => "Invalid shipping address format.",
                ], 422);
            }

            $data = [
                'invoice' => $order->code,
                'recipient_name' => $shippingAddress->name,
                'recipient_phone' => $shippingAddress->phone,
                'recipient_address' => $shippingAddress->address,
                'cod_amount' => $order->grand_total,
                'note' => $request->note ?? null,
            ];

            $response = $this->courierService->createOrder($data);

            // Error Handling for API Response
            if (isset($response['status']) && $response['status'] !== 200) {
                return response()->json([
                    'status' => false,
                    'message' => 'Courier Service Error',
                    'errors' => $response['errors'] ?? [],
                ], $response['status']);
            }

            if (!empty($response['consignment'])) {
                $order->consignment = json_encode($response['consignment']);
                $order->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => $response,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 200);
        }
    }


    public function bulkCreateOrders(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|array',
        ]);

        $order_codes = $request->id;
        $responseData = [];

        foreach ($order_codes as $code) {
            try {
                $order = Order::where('id', $code)->first();
                if (!$order) {
                    $responseData[] = [
                        'invoice' => $code,
                        'success' => false,
                        'message' => "Order not found",
                        'status' => 500,
                    ];
                    continue; // Skip further processing for this order
                }

                $shippingAddress = json_decode($order->shipping_address);

                if (!$shippingAddress || empty($shippingAddress->name) || empty($shippingAddress->phone) || empty($shippingAddress->address)) {
                    $responseData[] = [
                        'invoice' => $order->code,
                        'success' => false,
                        'message' => "Invalid shipping address format",
                        'status' => 500,
                    ];
                    continue; // Skip further processing for this order
                }

                $data = [
                    'invoice' => $order->code,
                    'recipient_name' => $shippingAddress->name,
                    'recipient_phone' => $shippingAddress->phone,
                    'recipient_address' => $shippingAddress->address,
                    'cod_amount' => $order->grand_total,
                    'note' => $request->note ?? null,
                ];

                // Call courier service to create the order
                $response = $this->courierService->createOrder($data);

                // Error Handling for Courier API Response
                if (isset($response['status']) && $response['status'] !== 200) {
                    $responseData[] = [
                        'invoice' => $order->code,
                        'success' => false,
                        'message' => 'Courier Service Error',
                        'errors' => $response['errors'] ?? [],
                        'status' => 500,
                    ];
                    continue; // Skip further processing for this order
                }

                // If the response contains a consignment, save it to the order
                if (!empty($response['consignment'])) {
                    $order->consignment = json_encode($response['consignment']);
                    $order->save();
                }

                // Add the response data to the response array
                $responseData[] = [
                    'invoice' => $order->code,
                    'success' => true,
                    'message' => 'Order placed successfully with the courier service',
                    'consignment' => $response['consignment'] ?? null,
                    'status' => 200,
                ];
            } catch (\Exception $e) {
                $responseData[] = [
                    'invoice' => $order->code,
                    'success' => false,
                    'message' => 'Something went wrong',
                    'error' => $e->getMessage(),
                    'status' => 500,
                ];
            }
        }

        return response()->json([
            'data' => $responseData,
        ], 200);
    }



    public function checkStatusByConsignmentId($id)
    {
        $response = $this->courierService->checkStatusByConsignmentId($id);
        return response()->json($response);
    }

    public function checkStatusByInvoice($invoice)
    {
        $response = $this->courierService->checkStatusByInvoice($invoice);
        return response()->json($response);
    }

    public function checkStatusByTrackingCode($trackingCode)
    {
        $response = $this->courierService->checkStatusByTrackingCode($trackingCode);
        return response()->json($response);
    }

    public function getBalance()
    {
        $response = $this->courierService->getBalance();
        return response()->json($response);
    }
}