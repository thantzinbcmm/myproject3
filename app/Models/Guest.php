// app/Models/Guest.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $primaryKey = 'guest_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'guest_id',
        'last_name',
        'first_name',
        'last_name_kana',
        'first_name_kana',
        'last_name_en',
        'first_name_en',
        'email',
        'phone',
        'nationality',
        'preferred_language',
        'postal_code',
        'address',
        'is_anonymized',
    ];

    protected $casts = [
        'is_anonymized' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->guest_id)) {
                $model->guest_id = Str::uuid()->toString();
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return $this->last_name . ' ' . $this->first_name;
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class, 'guest_id', 'guest_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'guest_id', 'guest_id');
    }

    public function anonymize(): void
    {
        $timestamp = now()->timestamp;
        $this->update([
            'last_name' => '削除済',
            'first_name' => '削除済',
            'last_name_kana' => null,
            'first_name_kana' => null,
            'last_name_en' => null,
            'first_name_en' => null,
            'email' => "deleted_{$timestamp}@deleted.invalid",
            'phone' => '00000000000',
            'postal_code' => null,
            'address' => null,
            'is_anonymized' => true,
        ]);
    }
}