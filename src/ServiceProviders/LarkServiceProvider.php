<?php

namespace CarroPublic\LarkBot\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use CarroPublic\Notifications\Managers\SenderManager;
use CarroPublic\LarkBot\Senders\LarkAsNotificationSender;

class LarkServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/larkbot.php', 'larkbot');

        if (class_exists(SenderManager::class)) {
            SenderManager::extendSender('webhook', 'larkbot', fn () => new LarkAsNotificationSender(
                $this->app['config'],
                $this->app['events'],
                $this->app['log'],
            ));
        }
    }
}
