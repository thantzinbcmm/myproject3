// app/Models/AdminRolePermission.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminRolePermission extends Model
{
    protected $primaryKey = 'permission_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'permission_id',
        'role_id',
        'resource',
        'action',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->permission_id)) {
                $model->permission_id = Str::uuid()->toString();
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id', 'role_id');
    }
}