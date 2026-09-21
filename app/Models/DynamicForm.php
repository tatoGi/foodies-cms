<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DynamicForm extends Model
{
    use HasFactory;

    protected $table = 'dynamic_forms';

    protected $guarded = [];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }
}
