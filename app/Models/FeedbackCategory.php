<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function viewings(): HasMany
    {
        return $this->hasMany(Viewing::class, 'feedback_category_id');
    }
}
