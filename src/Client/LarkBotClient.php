<?php

namespace CarroPublic\LarkBot\Client;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\Response;
use CarroPublic\LarkBot\Client\Abilities\HasUserApis;
use CarroPublic\LarkBot\Client\Abilities\HasGroupApis;
use CarroPublic\LarkBot\Client\Abilities\HasMessageApis;

class LarkBotClient
{
    use HasUserApis, HasGroupApis, HasMessageApis;
    
    /**
     * @var Collection | Array<Bot>
     */
    protected Collection $bots;

    /**
     * The current bot to be used
     * @var Bot $currentBot
     */
    protected $currentBot;

    public function __construct()
    {
        $this->bots = collect(config('larkbot.bots', []))->mapWithKeys(function ($botCredentials, $botName) {
            return [
                $botName => new Bot(
                    config("larkbot.bots.{$botName}.app_id"),
                    config("larkbot.bots.{$botName}.app_secret"),
                    config("larkbot.bots.{$botName}.allowed_domain_names"),
                    Str::beforeLast(config("larkbot.base_path"), '/'),
                )
            ];
        });

        $this->selectDefaultBot();
    }

    /**
     * @param $name
     * @return LarkBotClient
     */
    public function selectDefaultBot()
    {
        $this->currentBot = $this->bots->get(config('larkbot.default_bot'));

        return $this;
    }
    
    /**
     * @param $name
     * @return LarkBotClient
     */
    public function selectBotByName($name)
    {
        $this->currentBot = $this->bots->get($name);
        
        return $this;
    }

    /**
     * @param $domain
     * @return $this
     */
    public function selectBotByEmail($email)
    {
        if (!Str::contains($email, '@')) {
            return $this;
        }

        $this->currentBot = $this->bots->first(fn (Bot $bot) => Str::endsWith($email, $bot->getAllowedDomainNames()));

        # Use default bot as default if can't find any available bot
        if (empty($this->currentBot)) {
            $this->selectDefaultBot();
        }

        return $this;
    }

    /**
     * @param $endpoint
     * @param $method
     * @param $payload
     * @param $withoutAuthToken
     * @return Response
     */
    public function execute($endpoint, $method = 'get', $payload = [], $withoutAuthToken = false)
    {
        return $this->currentBot->sendRequest($endpoint, $method, $payload, $withoutAuthToken);
    }
}
