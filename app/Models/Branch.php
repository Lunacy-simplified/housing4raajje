<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(
    'branch_no',
    'code',
    'name',
    'phone',
    'is_active'
)]

class Branch extends Model
{
    protected function casts(): array
    {
        return [
            'branch_no' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }
}
