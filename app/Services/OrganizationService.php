<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use App\Helpers\ScimHelper;

class OrganizationService
{
    protected $cacheKey = 'wso2_organizations';
    protected $scimHelper;

    public function __construct()
    {
        $this->scimHelper = app(ScimHelper::class);
    }

    public function refreshCache()
    {
        $token = $this->getToken();
        $baseUrl = env('IS_URL', 'https://172.18.1.111:9443');
        $tenantDomain = 'carbon.super';
        $endpoint = $baseUrl . '/t/' . $tenantDomain . '/o/api/server/v1/organizations';

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])
        ->withOptions([
            'verify' => false,
            'timeout' => 30
        ])
        ->get($endpoint);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch organizations');
        }

        $organizations = $response->json('organizations');
        Cache::put($this->cacheKey, $organizations, 3600);
        return $organizations;
    }

    public function clearCache()
    {
        Cache::forget($this->cacheKey);
    }

    public function getOrganizations()
    {
        return Cache::get($this->cacheKey, []);
    }

    protected function getToken()
    {
        return $this->scimHelper->getTokenISApi();
    }
}
