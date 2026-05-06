// app/Models/RoomType.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class RoomType extends Model
{
    protected $primaryKey = 'room_type_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'room_type_id',
        'facility_id',
        'type_code',
        'name_ja',
        'name_en',
        'name_zh_cn',
        'name_zh_tw',
        'name_ko',
        'name_my',
        'description_ja',
        'description_en',
        'standard_capacity',
        'max_capacity',
        'floor_area',
        'amenities',
        'image_urls',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'amenities' => 'array',
        'image_urls' => 'array',
        'is_active' => 'boolean',
        'floor_area' => 'float',
        'standard_capacity' => 'integer',
        'max_capacity' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->room_type_id)) {
                $model->room_type_id = Str::uuid()->toString();
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

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        $field = match ($locale) {
            'en' => 'description_en',
            default => 'description_ja',
        };
        return $this->$field ?? $this->description_ja;
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'room_type_id', 'room_type_id');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            Plan::class,
            'plan_room_types',
            'room_type_id',
            'plan_id',
            'room_type_id',
            'plan_id'
        )->withPivot('plan_room_type_id');
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class, 'room_type_id', 'room_type_id');
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'room_type_id', 'room_type_id');
    }
}