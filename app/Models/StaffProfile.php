<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'staff_profiles', key: 'user_id', incrementing: false)]
#[Fillable([
    'staff_code',
    'branch_id',
    'job_title',
    'hired_on',
])]

class StaffProfile extends Model
{
    protected function casts(): array
    {
        return [
            'hired_on' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function compensations(): HasMany
    {
        return $this->hasMany(StaffCompensation::class, 'staff_user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
