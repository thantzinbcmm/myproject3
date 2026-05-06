// app/Models/Room.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Room extends Model
{
    protected $primaryKey = 'room_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'room_id',
        'room_type_id',
        'facility_id',
        'room_number',
        'floor',
        'status',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'floor' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->room_id)) {
                $model->room_id = Str::uuid()->toString();
            }
        });
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'room_type_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }
}