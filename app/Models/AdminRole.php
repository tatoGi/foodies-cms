<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    use HasFactory;

    protected $table = 'admin_roles';

    protected $guarded = [];

    public function users(): HasMany
    {
        return $this->hasMany(AdminUser::class, 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permissions', 'role_id', 'permission_id');
    }
}
