<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable('chat_id', 'user_id', 'body')]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    public function chat() : BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
