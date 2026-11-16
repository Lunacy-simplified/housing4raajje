<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
    'key',
    'value',
    'description'
])]

class SystemParameter extends Model
{
    /** Read a setting, e.g. SystemParameter::setting('management_fee_percent', 10). */
    public static function setting(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
