<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Table(name: 'owner_profiles', key: 'user_id', incrementing: false)]
#[Fillable(['national_id_or_passport', 'bank_name', 'bank_account_name', 'bank_account_no'])]
class OwnerProfile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
