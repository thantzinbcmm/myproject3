// app/Models/ReservationDetail.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReservationDetail extends Model
{
    protected $primaryKey = 'detail_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'detail_id',
        'reservation_id',
        'room_id',
        'room_type_id',
        'plan_id',
        'night_date',
        'daily_amount',
        'adult_count',
        'child_count',
    ];

    protected $casts = [
        'night_date' => 'date',
        'daily_amount' => 'integer',
        'adult_count' => 'integer',
        'child_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->detail_id)) {
                $model->detail_id = Str::uuid()->toString();
            }
            $model->created_at = now();
        });
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'reservation_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'room_type_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }
}