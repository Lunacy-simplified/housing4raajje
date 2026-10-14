<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Refreshes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Refreshes('reference')]
#[Fillable(
    'lease_term_id',
    'requested_start_date',
    'occupants',
    'employer',
    'income_bracket',
)]

class Application extends Model
{
    protected function casts(): array
    {
        return [
            'requested_start_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function leaseTerm(): BelongsTo
    {
        return $this->belongsTo(LeaseTerm::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
