<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'subject',
])]

class MessageThread extends Model
{
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'thread_participants', 'thread_id', 'user_id')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
