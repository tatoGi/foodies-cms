<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdminPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_permissions';

    public $timestamps = false;

    protected $guarded = [];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_permissions', 'permission_id', 'role_id');
    }
}
