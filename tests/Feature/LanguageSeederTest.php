<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Language;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_georgian_and_english_once(): void
    {
        $this->seed(LanguageSeeder::class);
        $this->seed(LanguageSeeder::class);

        $this->assertSame(2, Language::query()->count());
        $this->assertTrue(Language::query()->where('code', 'ka')->where('is_default', true)->exists());
        $this->assertTrue(Language::query()->where('code', 'en')->where('is_default', false)->where('is_active', true)->exists());
    }
}
