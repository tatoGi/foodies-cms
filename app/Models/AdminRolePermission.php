<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRolePermission extends Model
{
    use HasFactory;

    protected $table = 'admin_role_permissions';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(AdminPermission::class, 'permission_id');
    }
}
