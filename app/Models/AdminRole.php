// app/Models/AdminRole.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdminRole extends Model
{
    protected $primaryKey = 'role_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'role_name',
        'description',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->role_id)) {
                $model->role_id = Str::uuid()->toString();
            }
        });
    }

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'role_id', 'role_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id', 'role_id');
    }

    public function hasPermission(string $resource, string $action): bool
    {
        return $this->permissions()
            ->where('resource', $resource)
            ->where('action', $action)
            ->exists();
    }
}