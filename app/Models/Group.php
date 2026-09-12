<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'profile_image',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
                    ->withPivot('joined_at')
                    ->withTimestamps();
    }

    public function chat(): HasOne
    {
        return $this->hasOne(Chat::class, 'group_id');
    }
}
