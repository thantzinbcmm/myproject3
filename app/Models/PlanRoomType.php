// app/Models/PlanRoomType.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PlanRoomType extends Model
{
    protected $primaryKey = 'plan_room_type_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'plan_room_type_id',
        'plan_id',
        'room_type_id',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->plan_room_type_id)) {
                $model->plan_room_type_id = Str::uuid()->toString();
            }
            $model->created_at = now();
        });
    }
}