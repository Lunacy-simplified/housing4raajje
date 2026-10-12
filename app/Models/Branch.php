<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('branch_no', 'code', 'name', 'phone', 'is_active')]
class Branch extends Model
{
    protected function casts(): array
    {
        return [
            'branch_no' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function staffProfiles()
    {
        return $this->hasMany(StaffProfile::class);
    }
}
