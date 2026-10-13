<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
    'months',
    'label',
    'is_active'
])]

class LeaseTerm extends Model
{
    protected function casts()
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
