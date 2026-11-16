<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Refreshes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'district_id',
    'house_name',
    'road',
    'unit_no',
    'floor',
    'property_type',
    'rooms',
    'monthly_rent',
])]
#[Refreshes('reference')]

class Property extends Model
{
    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function messageThreads(): HasMany
    {
        return $this->hasMany(MessageThread::class);
    }

    /**
     * Lease matchmaker: active clients whose maximum rent covers this property,
     * who want to live in its branch area, and whose room count (if they gave one) matches.
     */
    public function matchingClients()
    {
        return User::where('is_active', true)->whereHas('clientProfile', fn ($q) => $q
            ->where('max_monthly_rent', '>=', $this->monthly_rent)
            ->where(fn ($q) => $q->whereNull('preferred_rooms')->orWhere('preferred_rooms', $this->rooms))
            ->whereHas('preferredBranches', fn ($q) => $q->where('branches.id', $this->branch_id)));
    }

    /** One-line address, e.g. "Unit 3A, Rose Villa, Majeedhee Magu, Henveiru". */
    public function address(): string
    {
        return collect([$this->unit_no ? "Unit {$this->unit_no}" : null, $this->house_name, $this->road, $this->district->name])
            ->filter()->implode(', ');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PropertyPhoto::class)->orderBy('sort_order');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(Viewing::class);
    }
}
