<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
    'code',
    'name',
    'is_active'
])]

class PaymentMethod extends Model
{
    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
