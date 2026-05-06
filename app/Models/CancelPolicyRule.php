// app/Models/CancelPolicyRule.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CancelPolicyRule extends Model
{
    protected $primaryKey = 'rule_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'cancel_policy_id',
        'days_before',
        'charge_rate',
        'is_noshow',
        'sort_order',
    ];

    protected $casts = [
        'days_before' => 'integer',
        'charge_rate' => 'float',
        'is_noshow' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->rule_id)) {
                $model->rule_id = Str::uuid()->toString();
            }
        });
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(CancelPolicy::class, 'cancel_policy_id', 'cancel_policy_id');
    }
}