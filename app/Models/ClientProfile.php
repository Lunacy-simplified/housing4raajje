<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Table(name: 'client_profiles', key: 'user_id', incrementing: false)]
#[Fillable([
    'national_id_or_passport',
    'sex',
    'date_of_birth',
    'max_monthly_rent',
    'preferred_rooms',
    'preferred_term_months'
])]

class ClientProfile extends Model
{
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'max_monthly_rent' => 'decimal:2',
            'preferred_rooms' => 'integer',
            'preferred_term_months' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function preferredBranches()
    {
        return $this->belongsToMany(
            Branch::class,
            'client_preferred_branches',
            'client_user_id',
            'branch_id'
        );
    }
}
