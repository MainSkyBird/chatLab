<?php

namespace App\Policies;

use App\Enums\ChatType;
use App\Models\Chat;
use App\Models\User;

class ChatPolicy
{
    public function view(User $user, Chat $chat): bool
    {
        return $chat->users()->wherePivot('user_id', $user->id)->exists();
    }

    public function sendMessage(User $user, Chat $chat): bool
    {
        return $chat->users()->wherePivot('user_id', $user->id)->exists();
    }

    public function addParticipant(User $user, Chat $chat): bool
    {
        return $chat->type === ChatType::Group && $chat->users()->wherePivot('user_id', $user->id)->exists();
    }
}
