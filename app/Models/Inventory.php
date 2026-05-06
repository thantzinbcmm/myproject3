// app/Models/Inventory.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Inventory extends Model
{
    protected $primaryKey = 'inventory_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'inventory_id',
        'room_type_id',
        'facility_id',
        'date',
        'total_count',
        'booked_count',
        'closed_count',
        'stop_sale',
        'version',
    ];

    protected $casts = [
        'date' => 'date',
        'total_count' => 'integer',
        'booked_count' => 'integer',
        'closed_count' => 'integer',
        'stop_sale' => 'boolean',
        'version' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->inventory_id)) {
                $model->inventory_id = Str::uuid()->toString();
            }
        });
    }

    public function getAvailableCountAttribute(): int
    {
        return max(0, $this->total_count - $this->booked_count - $this->closed_count);
    }

    public function isAvailable(): bool
    {
        return !$this->stop_sale && $this->available_count > 0;
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