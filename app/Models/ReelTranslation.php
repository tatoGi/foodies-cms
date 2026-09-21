<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReelTranslation extends Model
{
    use HasFactory;

    protected $table = 'reel_translations';

    protected $guarded = [];

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class, 'reel_id');
    }
}
