<?php

namespace CarroPublic\LarkBot\Senders;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Redis;
use CarroPublic\LarkBot\Client\LarkBotClient;
use CarroPublic\Notifications\Senders\Sender;
use CarroPublic\Notifications\Messages\Message;
use CarroPublic\Notifications\Messages\WebhookMessage;

class LarkAsNotificationSender extends Sender
{
    /**
     * Return false when message is not valid to send
     * @param $to
     * @param WebhookMessage $message
     * @return bool|mixed
     * @throws \Exception
     */
    public function send($to, Message $message)
    {
        if ($this->sandbox && !$this->isValidForSandbox($to, $message)) {
            return false;
        }

        $lark = new LarkBotClient();
        /** @var Response $response */
        if (isset($message->extraPayload['parent_message_id'])) {
            $response = $lark->replyMessage($message->payload, 'interactive', $message->extraPayload['parent_message_id']);
        } else {
            $response = $lark->sendMessage($message->payload, $to, 'interactive');
        }

        if (isset($message->extraPayload['preserve_message_id_with_key'])) {
            Redis::set(
                $message->extraPayload['preserve_message_id_with_key'],
                $response->json('data.message_id'),
                'EX',
                data_get($message->extraPayload, 'preserve_message_id_duration', 86400),
            );
        }

        if ($response && $response->serverError()) {
            $this->logger->info("Lark API Payload", compact('message', 'to'));
            
            throw new \Exception($response->body());
        }
        
        if ($response && $response->clientError()) {
            $this->logger->info("Lark API Payload", compact('message', 'to'));
            $skippableErrorCodes = explode(',', config('larkbot.skippable_error_codes'));

            # If we allow to skip those client error codes
            if (in_array($response->json('code'), $skippableErrorCodes)) {
                return $response;
            }

            throw new \Exception($response->body());
        }

        return $response;
    }
}
