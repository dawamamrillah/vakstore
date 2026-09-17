<?php

namespace App\Services\Digiflazz;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigiflazzClient
{
    protected string $username;

    protected string $apiKey;

    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->username = (string) config('digiflazz.username', '');
        $this->baseUrl = rtrim((string) config('digiflazz.base_url', 'https://api.digiflazz.com/v1'), '/');
        $this->timeout = (int) config('digiflazz.timeout', 30);

        // Select API Key: Development Key for local/development, Production Key for production
        $mode = (string) config('digiflazz.mode', app()->environment('production') ? 'production' : 'development');
        $isProduction = ($mode === 'production') || app()->environment('production');
        $productionKey = (string) config('digiflazz.production_key', '');
        $developmentKey = (string) config('digiflazz.development_key', '');

        if ($isProduction && ! empty($productionKey)) {
            $this->apiKey = $productionKey;
        } else {
            $this->apiKey = ! empty($developmentKey) ? $developmentKey : $productionKey;
        }
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->username) && ! empty($this->apiKey);
    }

    /**
     * Generate MD5 signature according to Digiflazz specifications:
     * md5(username + apiKey + postfix)
     */
    public function generateSignature(string $postfix): string
    {
        return md5($this->username.$this->apiKey.$postfix);
    }

    /**
     * Check Digiflazz Account Balance
     */
    public function checkBalance(): array
    {
        $sign = $this->generateSignature('depo');

        return $this->post('/cek-saldo', [
            'cmd' => 'deposit',
            'username' => $this->username,
            'sign' => $sign,
        ]);
    }

    /**
     * Fetch Price List (prepaid or pasca)
     */
    public function getPriceList(string $cmd = 'prepaid'): array
    {
        $sign = $this->generateSignature('pricelist');

        return $this->post('/price-list', [
            'cmd' => $cmd,
            'username' => $this->username,
            'sign' => $sign,
        ]);
    }

    /**
     * Send Prepaid Transaction
     */
    public function createPrepaidTransaction(string $buyerSkuCode, string $customerNo, string $refId, bool $testing = false): array
    {
        $sign = $this->generateSignature($refId);

        $payload = [
            'username' => $this->username,
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
        ];

        $mode = (string) config('digiflazz.mode', app()->environment('production') ? 'production' : 'development');
        $isProduction = ($mode === 'production') || app()->environment('production');

        if ($testing || ! $isProduction) {
            $payload['testing'] = true;
        }

        return $this->post('/transaction', $payload);
    }

    /**
     * Send Postpaid Inquiry (inq-pasca)
     */
    public function inquiryPasca(string $buyerSkuCode, string $customerNo, string $refId): array
    {
        $sign = $this->generateSignature($refId);

        return $this->post('/transaction', [
            'commands' => 'inq-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
        ]);
    }

    /**
     * Send Postpaid Bill Payment (pay-pasca)
     */
    public function payPasca(string $buyerSkuCode, string $customerNo, string $refId): array
    {
        $sign = $this->generateSignature($refId);

        return $this->post('/transaction', [
            'commands' => 'pay-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
        ]);
    }

    /**
     * Check Postpaid Status (status-pasca)
     */
    public function statusPasca(string $buyerSkuCode, string $customerNo, string $refId): array
    {
        $sign = $this->generateSignature($refId);

        return $this->post('/transaction', [
            'commands' => 'status-pasca',
            'username' => $this->username,
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no' => $customerNo,
            'ref_id' => $refId,
            'sign' => $sign,
        ]);
    }

    /**
     * Execute secure HTTP POST request
     */
    protected function post(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl.$endpoint;

        try {
            /** @var Response $response */
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $status = $response->status();
            $body = $response->json();

            if (! is_array($body)) {
                $body = ['raw_body' => $response->body()];
            }

            // Safe logging without leaking keys
            $safePayload = $payload;
            if (isset($safePayload['sign'])) {
                $safePayload['sign'] = '***MASKED***';
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $status,
                    'data' => $body['data'] ?? $body,
                    'raw_response' => $body,
                    'message' => $body['data']['message'] ?? ($body['message'] ?? 'OK'),
                ];
            }

            Log::warning('Digiflazz HTTP error: '.$status, [
                'endpoint' => $endpoint,
                'payload' => $safePayload,
                'response' => $body,
            ]);

            return [
                'success' => false,
                'status_code' => $status,
                'data' => $body['data'] ?? [],
                'raw_response' => $body,
                'message' => $body['data']['message'] ?? ($body['message'] ?? 'Digiflazz HTTP Error '.$status),
            ];
        } catch (Exception $e) {
            Log::error('Digiflazz API Exception: '.$e->getMessage(), [
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => [],
                'raw_response' => null,
                'message' => 'Koneksi ke gateway Digiflazz gagal: '.$e->getMessage(),
            ];
        }
    }
}
