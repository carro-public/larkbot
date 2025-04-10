<?php

namespace CarroPublic\LarkBot\Client;

use Illuminate\Support\Facades\Http;

class Bot
{
    protected $httpClient;

    protected $appId;

    protected $appSecret;

    protected $allowedDomainNames;

    protected $basePath;
    
    public function __construct($appId, $appSecret, $allowedDomainNames, $basePath)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->allowedDomainNames = $allowedDomainNames;
        $this->basePath = $basePath;
    }

    /**
     * @return []
     */
    public function getAllowedDomainNames()
    {
        return $this->allowedDomainNames;
    }

    /**
     * Get Auth Token
     * @return string
     */
    protected function getAuthToken()
    {
        $tokenCacheKey = "lark-bot-token:{$this->appId}";

        if (cache()->has($tokenCacheKey)) {
            return cache()->get($tokenCacheKey);
        }

        $response = $this->sendRequest('auth/v3/tenant_access_token/internal', 'post', [
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret
        ], true);

        if (!$response->successful()) {
            throw new \RuntimeException("Lark Bot Credential is invalid");
        }

        # Expiration time 
        return cache()->remember($tokenCacheKey, max((int) $response->json('expire') - 60, 1), function () use ($response) {
            return $response->json('tenant_access_token');
        });
    }
    
    /**
     * @param $endpoint
     * @param $method
     * @param $payload
     * @return \Illuminate\Http\Client\Response
     */
    public function sendRequest($endpoint, $method = 'get', $payload = [], $withoutAuthToken = false)
    {
        return Http::retry(config("larkbot.connection_options.retries", 2), config("larkbot.connection_options.backoff", 5000), )
            ->withToken($withoutAuthToken ? null: $this->getAuthToken())
            ->withOptions([
                'timeout' => config("larkbot.connection_options.connect_timeout", 2),
            ])
            ->withHeaders([
                'Accept' => 'application/json',
            ])->{$method}($this->basePath . '/' . ltrim($endpoint), $payload);
    }
}
