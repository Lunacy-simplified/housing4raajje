<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'is_active'
])]

class PaymentMethod extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function payments(): HasMany
    
    {
        return $this->hasMany(Payment::class);
    }
}
