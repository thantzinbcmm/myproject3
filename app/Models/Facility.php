// app/Models/Facility.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Facility extends Model
{
    protected $primaryKey = 'facility_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'facility_id',
        'facility_code',
        'name_ja',
        'name_en',
        'name_zh_cn',
        'name_zh_tw',
        'name_ko',
        'name_my',
        'postal_code',
        'address',
        'phone_number',
        'email',
        'check_in_time',
        'check_out_time',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->facility_id)) {
                $model->facility_id = Str::uuid()->toString();
            }
        });
    }

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        $field = match ($locale) {
            'en' => 'name_en',
            'zh-CN' => 'name_zh_cn',
            'zh-TW' => 'name_zh_tw',
            'ko' => 'name_ko',
            'my' => 'name_my',
            default => 'name_ja',
        };
        return $this->$field ?? $this->name_ja;
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class, 'facility_id', 'facility_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'facility_id', 'facility_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class, 'facility_id', 'facility_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'facility_id', 'facility_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'facility_id', 'facility_id');
    }

    public function cancelPolicies(): HasMany
    {
        return $this->hasMany(CancelPolicy::class, 'facility_id', 'facility_id');
    }
}