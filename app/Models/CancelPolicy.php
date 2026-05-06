// app/Models/CancelPolicy.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CancelPolicy extends Model
{
    protected $primaryKey = 'cancel_policy_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cancel_policy_id',
        'facility_id',
        'name',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->cancel_policy_id)) {
                $model->cancel_policy_id = Str::uuid()->toString();
            }
        });
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'facility_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CancelPolicyRule::class, 'cancel_policy_id', 'cancel_policy_id')
            ->orderBy('sort_order');
    }
}