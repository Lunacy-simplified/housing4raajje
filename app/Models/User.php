<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    const MFA_ROLES = ['system_admin', 'branch_manager'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'is_active' => true,
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'client_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function clientLeases(): HasMany
    {
        return $this->hasMany(Lease::class, 'client_user_id');
    }

    public function clientProfile(): HasOne
    {
        return $this->hasOne(ClientProfile::class, 'user_id');
    }

    public function dashboardRoute(): string
    {
        return match ($this->role?->code) {
            'system_admin' => 'admin.dashboard',
            'branch_manager' => 'manager.dashboard',
            'property_specialist', 'receptionist' => 'staff.dashboard',
            'owner' => 'owner.dashboard',
            'client' => 'client.dashboard',
            default => abort(403, 'Your account does not have a valid role.'),
        };
    }

    public function loggedViewings(): HasMany
    {
        return $this->hasMany(Viewing::class, 'logged_by_user_id');
    }

    public function messageThreads(): BelongsToMany
    {
        return $this->belongsToMany(MessageThread::class, 'thread_participants', 'user_id', 'thread_id')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function oneTimeCodes(): HasMany
    {
        return $this->hasMany(OneTimeCode::class);
    }

    public function ownedProperties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_user_id');
    }

    public function ownerLeases(): HasMany
    {
        return $this->hasMany(Lease::class, 'owner_user_id');
    }

    public function ownerProfile(): HasOne
    {
        return $this->hasOne(OwnerProfile::class, 'user_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'owner_user_id');
    }

    public function reviewedApplications(): HasMany
    {
        return $this->hasMany(Application::class, 'reviewed_by_user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function requiresMfa()
    {
        return in_array($this->role?->code, self::MFA_ROLES, true);
    }

    public function scheduledViewings(): HasMany
    {
        return $this->hasMany(Viewing::class, 'scheduled_by_user_id');
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class, 'user_id');
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(Viewing::class, 'client_user_id');
    }
}
