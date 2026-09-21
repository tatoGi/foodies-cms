<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageContentBlock extends Model
{
    use HasFactory;

    protected $table = 'page_content_blocks';

    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PageTranslation::class, 'translation_id');
    }
}
