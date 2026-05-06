// app/Models/GroupReservation.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GroupReservation extends Model
{
    protected $primaryKey = 'group_reservation_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'group_reservation_id',
        'group_reservation_no',
        'facility_id',
        'group_name',
        'contact_guest_id',
        'status',
        'check_in_date',
        'check_out_date',
        'total_rooms',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'total_rooms' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->group_reservation_id)) {
                $model->group_reservation_id = Str::uuid()->toString();
            }
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function contactGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'contact_guest_id', 'guest_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'group_reservation_id', 'group_reservation_id');
    }
}