<?php

namespace CarroPublic\LarkBot\Client\Abilities;

use Illuminate\Http\Client\Response;

trait HasGroupApis
{
    /**
     * Check whether a user is inside a Channel/Group
     * @param $groupId
     * @param $userId
     * @return bool
     */
    public function ifUserInGroup($groupId, $userId)
    {
        $nextPage = true;
        $pageToken = null;

        # Fetch all members, if found the user, then return true
        while ($nextPage) {
            $response = $this->getUsersInGroup($groupId, $pageToken);
            $result = collect($response->json('data.items'))->firstWhere('member_id', $userId);
            if ($result) {
                return true;
            }
            $nextPage = $response->json('data.has_more');
            $pageToken = $response->json('data.page_token');
        }

        return false;
    }

    /**
     * Fetch all users inside a Channel/Group
     * @param $groupId
     * @param $pageToken
     * @return Response
     */
    public function getUsersInGroup($groupId, $pageToken = null)
    {
        return $this->execute("im/v1/chats/{$groupId}/members", 'GET', [
            'page_size' => 100,
            'page_token' => $pageToken,
        ]);
    }
}
