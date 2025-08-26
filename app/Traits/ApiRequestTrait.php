<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait ApiRequestTrait
{
    protected $api_url;
    protected $api_key;
    protected $client;

    public function initializeApiRequestTrait()
    {
        $this->api_url = env('API_URL_WSO2');
        $this->api_key = env('API_KEY_WSO2');
        $this->client = new Client();
    }

    protected function postJson($endpoint, $params)
    {
        try {
            $response = $this->client->request('POST', $this->api_url . $endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->api_key
                ],
                'json' => $params,
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            $response = $e->getResponse();
            $message = 'Gagal Memanggil API!';
            if ($response) {
                $responseData = json_decode($response->getBody(), true);
                if (isset($responseData['message'])) {
                    $message = $responseData['message'];
                }
            }
            throw new \Exception($message);
        }
    }
}
