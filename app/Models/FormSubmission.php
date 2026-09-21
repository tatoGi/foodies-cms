<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use HasFactory;

    protected $table = 'form_submissions';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DynamicForm::class, 'form_id');
    }
}
