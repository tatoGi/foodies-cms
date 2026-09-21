<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTemplateTranslation extends Model
{
    use HasFactory;

    protected $table = 'page_template_translations';

    public $timestamps = false;

    protected $guarded = [];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'template_id');
    }
}
