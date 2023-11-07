<?php

namespace CarroPublic\LarkBot\Client;

use Illuminate\Support\Str;
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

    public function __construct($botNameOrRecipient = 'default')
    {
        $bots = config('larkbot.bots');
        $botNames = array_keys($bots);
        # If the bot name was specified correctly, use the bot name
        if (!in_array($botNameOrRecipient, $botNames)) {
            # Select the correct bot to use base on the org domain of recipient
            $bot = collect($botNames)->first(fn ($botName) => Str::endsWith($botNameOrRecipient, $bots[$botName]['allowed_domain_names']), 'default');
        } else {
            $bot = 'default';
        }

        $this->appId = config("larkbot.bots.{$bot}.app_id");
        $this->appSecret = config("larkbot.bots.{$bot}.app_secret");
        $this->allowedDomainNames = config("larkbot.bots.{$bot}.allowed_domain_names");
        $this->basePath = config("larkbot.base_path");
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

        $response = $this->execute('auth/v3/tenant_access_token/internal', 'post', [
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
    public function execute($endpoint, $method = 'get', $payload = [], $withoutAuthToken = false)
    {
        return Http::withToken($withoutAuthToken ? null: $this->getAuthToken())
            ->withOptions([
                'timeout' => config("larkbot.connect_timeout", 2),
            ])
            ->withHeaders([
                'Accept' => 'application/json',
            ])->{$method}($this->basePath . $endpoint, $payload);
    }
}
