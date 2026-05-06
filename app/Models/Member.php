// app/Models/Member.php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Member extends Authenticatable implements JWTSubject
{
    protected $primaryKey = 'member_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'member_id',
        'guest_id',
        'member_number',
        'email',
        'password_hash',
        'member_rank',
        'email_verified',
        'email_verified_at',
        'last_login_at',
        'login_failed_count',
        'locked_until',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'is_active' => 'boolean',
        'login_failed_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->member_id)) {
                $model->member_id = Str::uuid()->toString();
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
            'email' => $this->email,
            'memberNumber' => $this->member_number,
            'memberRank' => $this->member_rank,
            'iss' => 'hotel-booking-system',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'guest_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'member_id', 'member_id');
    }
}