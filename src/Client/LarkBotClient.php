<?php

namespace CarroPublic\LarkBot\Client;

use Illuminate\Support\Facades\Http;
use CarroPublic\LarkBot\Client\Abilities\HasUserApis;
use CarroPublic\LarkBot\Client\Abilities\HasGroupApis;
use CarroPublic\LarkBot\Client\Abilities\HasMessageApis;

class LarkBotClient
{
    use HasUserApis, HasGroupApis, HasMessageApis;

    protected $httpClient;

    protected $appId;

    protected $appSecret;

    protected $basePath;

    protected $allowedDomainNames;

    public function __construct($bot = 'default')
    {
        $this->appId = config("services.lark.bots.{$bot}.app_id");
        $this->appSecret = config("services.lark.bots.{$bot}.app_secret");
        $this->allowedDomainNames = config("services.lark.bots.{$bot}.allowed_domain_names");
        $this->basePath = config("services.lark.base_path");
    }

    /**
     * Get Auth Token
     * @return string
     */
    protected function getAuthToken()
    {
        # Auth Token has 7200s expiration as default
        return cache()->remember("lark-bot-token:{$this->appId}", 7200, function () {
            $response = $this->execute('auth/v3/tenant_access_token/internal', 'post', [
                'app_id' => $this->appId,
                'app_secret' => $this->appSecret
            ], true);

            if (!$response->successful()) {
                throw new \RuntimeException("Lark Bot Credential is invalid");
            }

            return $response->json('tenant_access_token');
        });
    }

    /**
     * @param $endpoint
     * @param $method
     * @param $payload
     * @return \Illuminate\Http\Client\Response
     */
    public function execute($endpoint, $method = 'get', $payload = [], $withoutAuthToken = false)
    {
        return Http::withToken($withoutAuthToken ? null: $this->getAuthToken())
            ->withHeaders([
                'Accept' => 'application/json',
            ])->{$method}($this->basePath . $endpoint, $payload);
    }
}
