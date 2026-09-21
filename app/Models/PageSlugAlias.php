<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSlugAlias extends Model
{
    use HasFactory;

    protected $table = 'page_slug_aliases';

    public $timestamps = false;

    protected $guarded = [];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
