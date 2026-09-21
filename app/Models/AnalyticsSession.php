<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsSession extends Model
{
    use HasFactory;

    protected $table = 'analytics_sessions';

    protected $keyType = 'string';

    public $incrementing = false;

    const CREATED_AT = 'first_visit_at';

    const UPDATED_AT = 'last_visit_at';

    protected $guarded = [];

    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
        'page_views' => 'integer',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Analytics::class, 'session_id', 'id');
    }
}
