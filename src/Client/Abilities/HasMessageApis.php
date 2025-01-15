<?php

namespace CarroPublic\LarkBot\Client\Abilities;

use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;
use Psr\Http\Client\ClientExceptionInterface;

trait HasMessageApis
{
    /**
     * Send message to single or multiple chat_id
     * @param $payload
     * @param $receiver_id
     * @param $msg_type
     * @return Response
     */
    public function sendMessage($payload, $receiver_id, $msg_type, $buzzedList = [])
    {
        if (Str::contains($receiver_id, ",")) {
            foreach (explode(",", $receiver_id) as $chat_id) {
                $this->sendToChatId($payload, $chat_id, $msg_type, $buzzedList);
            }
        } else {
            return $this->sendToChatId($payload, $receiver_id, $msg_type, $buzzedList);
        }

        return null;
    }

    /**
     * Reply to a message (use to reply in topic as thread view)
     * @param $payload
     * @param $msg_type
     * @param $parent_id
     * @return Response
     */
    public function replyMessage($payload, $msg_type, $parent_id)
    {
        return $this->selectDefaultBot()->execute("/im/v1/messages/{$parent_id}/reply", 'POST', [
            'msg_type' => $msg_type,
            'content' => is_string($payload) ? $payload : json_encode($payload),
        ]);
    }

    /**
     * Send an emoji to the message
     * https://open.larksuite.com/document/server-docs/im-v1/message-reaction/emojis-introduce
     * @param $messageId
     * @param $emojiType
     * @return void
     */
    public function reactMessage($messageId, $emojiType)
    {
        $this->execute("/im/v1/messages/{$messageId}/reactions", 'POST', [
            'reaction_type' => [
                'emoji_type' => $emojiType,
            ]
        ]);
    }

    /**
     * @param $messageId
     * @param $userList
     * @return Response
     */
    public function buzzMessage($messageId, $userList = [])
    {
        return $this->execute("im/v1/messages/{$messageId}/urgent_app?user_id_type=user_id", 'POST', [
            'user_id_list' => $this->getUserIdFromEmails($userList)->values()->toArray(),
        ]);
    }

    /**
     * Send message to a single chat_id, support email and chat_id
     * @param $payload
     * @param $receiver_id
     * @param $msg_type
     * @return Response
     */
    protected function sendToChatId($payload, $receiver_id, $msg_type, $buzzedList = [])
    {
        if (Str::contains($receiver_id, "@")) {
            $this->selectBotByEmail($receiver_id);
            # If the receiver is not in allowed emails, do not send
            if (!in_array(data_get(explode("@", $receiver_id), 1), $this->currentBot->getAllowedDomainNames())) {
                return null;
            }
        }
        
        switch (true) {
            case Str::contains($receiver_id, "@"):
                $receiver_id_type = 'email';
                break;
            case Str::startsWith($receiver_id, "ou_"):
                $receiver_id_type = 'open_id';
                break;
            default:
                $receiver_id_type = 'chat_id';
                break;
        }

        return tap($this->execute("/im/v1/messages?receive_id_type={$receiver_id_type}", 'POST', [
            'receive_id' => $receiver_id,
            'msg_type' => $msg_type,
            'content' => is_string($payload) ? $payload : json_encode($payload),
        ]), function (Response $response) use ($buzzedList, $receiver_id) {
            if (!empty($buzzedList)) {
                return;
            }
            
            $messageId = $response->json('data.message_id');
            $this->buzzMessage($messageId, $buzzedList);
        });
    }
}
