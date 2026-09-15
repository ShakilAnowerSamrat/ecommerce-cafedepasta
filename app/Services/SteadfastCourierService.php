<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SteadfastCourierService
{
    protected $base_url;
    protected $api_key;
    protected $secret_key;

    public function __construct()
    {
        $this->base_url = 'https://portal.packzy.com/api/v1';
        $this->api_key = env('STEADFAST_API_KEY', 'your_api_key');
        $this->secret_key = env('STEADFAST_SECRET_KEY', 'your_secret_key');
    }

    public function createOrder($orderData)
    {

        $response = Http::withHeaders([
            'Api-Key' => $this->api_key,
            'Secret-Key' => $this->secret_key,
            'Content-Type' => 'application/json',
        ])->post($this->base_url . '/create_order', $orderData);

        return $response->json();
    }

    public function bulkCreateOrder($orders)
    {
        $response = Http::withHeaders([
            'Api-Key' => $this->api_key,
            'Secret-Key' => $this->secret_key,
            'Content-Type' => 'application/json',
        ])->post($this->base_url . '/create_order/bulk-order', [
            'data' => $orders,
        ]);


        return $response->json();
    }

    public function checkStatusByConsignmentId($id)
    {
        return $this->makeRequest('GET', "/status/consignment/{$id}");
    }

    public function checkStatusByInvoice($invoice)
    {
        return $this->makeRequest('GET', "/status/invoice/{$invoice}");
    }

    public function checkStatusByTrackingCode($trackingCode)
    {
        return $this->makeRequest('GET', "/status/tracking/{$trackingCode}");
    }

    public function getBalance()
    {
        return $this->makeRequest('GET', "/balance");
    }

    private function makeRequest($method, $endpoint, $data = [])
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.courier.api_key'),
            ])->{$method}($this->base_url . $endpoint, $data);

            return $response->json();
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'API request failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
