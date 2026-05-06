// app/Models/AdminUser.php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class AdminUser extends Authenticatable implements JWTSubject
{
    protected $primaryKey = 'admin_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'admin_id',
        'username',
        'email',
        'password_hash',
        'last_name',
        'first_name',
        'role_id',
        'facility_id',
        'is_active',
        'last_login_at',
        'login_failed_count',
        'locked_until',
        'password_changed_at',
        'created_by',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->admin_id)) {
                $model->admin_id = Str::uuid()->toString();
            }
        });
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'username' => $this->username,
            'role' => $this->role->role_name ?? null,
            'facilityId' => $this->facility_id,
            'iss' => 'hotel-booking-system',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id', 'role_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function hasPermission(string $resource, string $action): bool
    {
        return $this->role?->hasPermission($resource, $action) ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->role_name === 'SUPER_ADMIN';
    }

    public function canAccessFacility(string $facilityId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->facility_id === $facilityId;
    }
}