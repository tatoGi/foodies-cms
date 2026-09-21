<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageTemplate extends Model
{
    use HasFactory;

    protected $table = 'page_templates';

    protected $guarded = [];

    public function translations(): HasMany
    {
        return $this->hasMany(PageTemplateTranslation::class, 'template_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'template', 'slug');
    }
}
