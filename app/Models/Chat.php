<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'group_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants')
                    ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'chat_id')->latestOfMany();
    }

    public function isParticipant(int $userId): bool
    {
        return $this->participants()->where('users.id', $userId)->exists();
    }
}
