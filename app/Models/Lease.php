<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Refreshes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'start_date',
    'term_months',
    'monthly_rent',
    'advance_amount',
    'payment_method_id',
])]
#[Refreshes(['reference', 'end_date'])]

class Lease extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_rent' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'executed_at' => 'datetime',
            'activated_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by_user_id');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function renewalOf(): BelongsTo
    {
        return $this->belongsTo(Lease::class, 'renewal_of_lease_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Lease::class, 'renewal_of_lease_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(LeaseSignature::class, 'lease_id');
    }
}
