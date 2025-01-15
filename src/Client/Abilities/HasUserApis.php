<?php

namespace CarroPublic\LarkBot\Client\Abilities;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasUserApis
{
    /**
     * Get User Detail
     * @param $openId
     * @return mixed
     */
    public function getUserFromId($openId)
    {
        $response = $this->selectDefaultBot()->execute("/contact/v3/users/{$openId}?department_id_type=open_department_id&user_id_type=open_id");

        return $response->json('data');
    }
    
    /**
     * Get UserID from Email
     * @param $emails
     * @param $mobiles
     * @return Collection
     */
    public function getUserIdFromEmails($emails)
    {
        $response = $this->selectDefaultBot()->execute('/contact/v3/users/batch_get_id', 'POST', [
            'emails' => Arr::wrap($emails),
        ]);

        return collect($response->json('data.user_list'))->mapWithKeys(function ($item, $key) {
            return [data_get($item, 'email') => data_get($item, 'user_id')];
        });
    }
}
