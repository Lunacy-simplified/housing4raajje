<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Table(name: 'staff_profiles', key: 'user_id', incrementing: false)]
#[Fillable(['staff_code', 'branch_id', 'job_title', 'hired_on'])]
class StaffProfile extends Model
{
    protected function casts(): array
    {
        return [
            'hired_on' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function compensations()
    {
        return $this->hasMany(StaffCompensation::class, 'staff_user_id');
    }
}
