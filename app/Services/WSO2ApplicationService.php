<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class WSO2ApplicationService
{
    public function __construct(protected string $baseUrl = '')
    {
        $this->baseUrl = $this->baseUrl ?: env('IS_URL', 'https://localhost:9443');
    }

    protected function client(string $accessToken)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
            'User-Agent' => 'Laravel-WSO2-BFF/1.0'
        ])->withOptions(['verify' => false]);
    }

    public function listApplications(string $accessToken, array $query = [])
    {
        return $this->client($accessToken)->get($this->baseUrl . '/api/server/v1/applications', $query);
    }

    public function getApplication(string $accessToken, string $id)
    {
        return $this->client($accessToken)->get($this->baseUrl . '/api/server/v1/applications/' . rawurlencode($id));
    }

    public function createApplication(string $accessToken, array $payload)
    {
        return $this->client($accessToken)->post($this->baseUrl . '/api/server/v1/applications', $payload);
    }

    public function patchApplication(string $accessToken, string $id, array $payload)
    {
        return $this->client($accessToken)->patch($this->baseUrl . '/api/server/v1/applications/' . rawurlencode($id), $payload);
    }

    public function importApplication(string $accessToken, UploadedFile $file, array $meta = [])
    {
        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'User-Agent' => 'Laravel-WSO2-BFF/1.0'
        ])->withOptions(['verify' => false]);

        // Attach file as multipart
        return $request->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post($this->baseUrl . '/api/server/v1/applications/import', $meta);
    }

    public function regenerateOidcSecret(string $accessToken, string $applicationId)
    {
        return $this->client($accessToken)->post($this->baseUrl . '/api/server/v1/applications/' . rawurlencode($applicationId) . '/inbound-protocols/oidc/regenerate-secret');
    }
}
