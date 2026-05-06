// app/Models/Plan.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $primaryKey = 'plan_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'plan_id',
        'facility_id',
        'plan_code',
        'name_ja',
        'name_en',
        'name_zh_cn',
        'name_zh_tw',
        'name_ko',
        'name_my',
        'description_ja',
        'description_en',
        'meal_type',
        'min_nights',
        'max_nights',
        'available_from',
        'available_to',
        'cancel_policy_id',
        'is_public',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'available_from' => 'date',
        'available_to' => 'date',
        'min_nights' => 'integer',
        'max_nights' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->plan_id)) {
                $model->plan_id = Str::uuid()->toString();
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

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function cancelPolicy(): BelongsTo
    {
        return $this->belongsTo(CancelPolicy::class, 'cancel_policy_id', 'cancel_policy_id');
    }

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            RoomType::class,
            'plan_room_types',
            'plan_id',
            'room_type_id',
            'plan_id',
            'room_type_id'
        );
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class, 'plan_id', 'plan_id');
    }
}