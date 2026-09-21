<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageTranslation extends Model
{
    use HasFactory;

    protected $table = 'page_translations';

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageContentBlock::class, 'translation_id');
    }
}
