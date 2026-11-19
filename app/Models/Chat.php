<?php

namespace App\Models;

use App\Enums\ChatType;
use Carbon\Carbon;
use Database\Factories\ChatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string|null $name
 * @property ChatType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'type'])]
class Chat extends Model
{
    /** @use HasFactory<ChatFactory> */
    use HasFactory;
    protected function casts(): array
    {
        return [
            'type' => ChatType::class
        ];
    }

    /**
     * The users that belong to the chat.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_user', 'chat_id', 'user_id');
    }

    /**
     * The messages that belong to the chat.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id');
    }
}
