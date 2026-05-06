// app/Models/PlanPrice.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PlanPrice extends Model
{
    protected $primaryKey = 'plan_price_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'plan_price_id',
        'plan_id',
        'room_type_id',
        'start_date',
        'end_date',
        'day_of_week',
        'base_price',
        'adult_price',
        'child_price',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'base_price' => 'integer',
        'adult_price' => 'integer',
        'child_price' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->plan_price_id)) {
                $model->plan_price_id = Str::uuid()->toString();
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'room_type_id');
    }

    public function appliesToDate(\Carbon\Carbon $date): bool
    {
        if ($date->lt($this->start_date) || $date->gt($this->end_date)) {
            return false;
        }
        $dayMap = [
            1 => 'MON',
            2 => 'TUE',
            3 => 'WED',
            4 => 'THU',
            5 => 'FRI',
            6 => 'SAT',
            0 => 'SUN',
        ];
        $dayOfWeek = $dayMap[$date->dayOfWeek];
        $daysAllowed = explode(',', $this->day_of_week);
        return in_array($dayOfWeek, $daysAllowed);
    }
}