<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable([
    'label',
    'is_rejection',
    'sort_order',
    'is_active'
])]

class FeedbackCategory extends Model
{
    protected function casts(): array
    {
        return [
            'is_rejection' => 'boolean',
            'is_active'    => 'boolean',
        ];
    }
}
