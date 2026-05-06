// app/Models/Reservation.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reservation extends Model
{
    protected $primaryKey = 'reservation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'reservation_id',
        'reservation_no',
        'facility_id',
        'group_reservation_id',
        'guest_id',
        'member_id',
        'channel',
        'channel_reservation_no',
        'status',
        'check_in_date',
        'check_out_date',
        'nights',
        'adult_count',
        'child_count',
        'total_amount',
        'cancelled_at',
        'cancel_fee',
        'cancel_reason',
        'cancel_policy_applied',
        'special_requests',
        'internal_notes',
        'confirmed_at',
        'checkin_at',
        'checkout_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'nights' => 'integer',
        'adult_count' => 'integer',
        'child_count' => 'integer',
        'total_amount' => 'integer',
        'cancel_fee' => 'integer',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checkin_at' => 'datetime',
        'checkout_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->reservation_id)) {
                $model->reservation_id = Str::uuid()->toString();
            }
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id', 'guest_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'member_id');
    }

    public function groupReservation(): BelongsTo
    {
        return $this->belongsTo(GroupReservation::class, 'group_reservation_id', 'group_reservation_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ReservationDetail::class, 'reservation_id', 'reservation_id');
    }

    public function canChange(): bool
    {
        if (!in_array($this->status, ['CONFIRMED', 'PENDING'])) {
            return false;
        }
        $limitDate = $this->check_in_date->copy()->subDays(config('hotel.change_limit_days'));
        return now()->startOfDay()->lte($limitDate);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['CONFIRMED', 'PENDING']);
    }

    public function isPast(): bool
    {
        return in_array($this->status, ['CHECKOUT', 'CANCELLED', 'NOSHOW']);
    }
}