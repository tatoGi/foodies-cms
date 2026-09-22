<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Services\GeneralSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreadcrumbSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_include_the_breadcrumb_banner(): void
    {
        app(GeneralSettingService::class)->save([
            'breadcrumb_image' => '/storage/banners/hero.jpg',
            'breadcrumb_color' => '#102030',
        ]);

        $settings = app(GeneralSettingService::class)->getFrontendSettings('ka', 'ka');

        $this->assertSame('/storage/banners/hero.jpg', $settings['breadcrumbImage']);
        $this->assertSame('#102030', $settings['breadcrumbColor']);
    }

    public function test_missing_banner_uses_a_dark_fallback_color(): void
    {
        $settings = app(GeneralSettingService::class)->getFrontendSettings('ka', 'ka');

        $this->assertNull($settings['breadcrumbImage']);
        $this->assertSame('#1c1714', $settings['breadcrumbColor']);
    }
}
