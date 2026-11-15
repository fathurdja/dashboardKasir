<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IakService
{
    protected string $baseUrl;
    protected string $userHp;
    protected string $apiKey;

    public function __construct()
    {
        $mode = config('services.iak.mode');
        $this->baseUrl = $mode === 'production' 
            ? 'https://prepaid.iak.id/api' 
            : 'https://prepaid.iak.dev/api';
            
        $this->userHp = config('services.iak.user_hp');
        $this->apiKey = config('services.iak.api_key');
    }

    /**
     * Generate MD5 Sign for IAK
     */
    protected function generateSign(string $identifier): string
    {
        return md5($this->userHp . $this->apiKey . $identifier);
    }

    /**
     * Check IAK Balance
     */
    public function checkBalance(): array
    {
        $response = Http::post("{$this->baseUrl}/check-balance", [
            'username' => $this->userHp,
            'sign' => $this->generateSign('bl'),
        ]);

        return $response->json();
    }

    /**
     * Get Prepaid Pricelist (Pulsa, Data, Token, etc)
     * $type can be: pulsa, data, pln, e-money, etc.
     */
    public function getPricelist(string $type = '', string $operator = ''): array
    {
        $response = Http::post("{$this->baseUrl}/pricelist", [
            'username' => $this->userHp,
            'sign' => $this->generateSign('pl'),
            'status' => 'active',
        ]);

        return $response->json();
    }

    /**
     * Top Up Prepaid Product
     */
    public function topUp(string $customerNumber, string $productCode, string $refId): array
    {
        $response = Http::post("{$this->baseUrl}/top-up", [
            'username' => $this->userHp,
            'customer_id' => $customerNumber,
            'product_code' => $productCode,
            'ref_id' => $refId,
            'sign' => $this->generateSign($refId),
        ]);

        return $response->json();
    }

    /**
     * Check Status of Transaction
     */
    public function checkStatus(string $refId): array
    {
        $response = Http::post("{$this->baseUrl}/check-status", [
            'username' => $this->userHp,
            'ref_id' => $refId,
            'sign' => $this->generateSign($refId),
        ]);

        return $response->json();
    }
}
