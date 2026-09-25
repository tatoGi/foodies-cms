<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\BlockTypeDefinition;
use App\Models\PageTemplate;
use Database\Seeders\SitePageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_seeder_creates_about_block_types_and_template_idempotently(): void
    {
        $this->seed(SitePageTemplateSeeder::class);
        $this->seed(SitePageTemplateSeeder::class);

        foreach (SitePageTemplateSeeder::ABOUT_BLOCKS as $key) {
            $definition = BlockTypeDefinition::query()->where('key', $key)->sole();
            $this->assertSame('page', $definition->scope);
            $this->assertTrue($definition->is_enabled);
            $this->assertNotEmpty($definition->schema['fields']);
        }

        $why = BlockTypeDefinition::query()->where('key', 'about_why_choose_us')->sole();
        $listOne = collect($why->schema['fields'])->firstWhere('key', 'list_one');
        $this->assertSame('repeater', $listOne['type']);
        $this->assertSame('text', $listOne['fields'][0]['key']);

        $template = PageTemplate::query()->where('slug', 'about')->sole();
        $this->assertSame('ჩვენ შესახებ', $template->translations()->where('locale', 'ka')->value('name'));
    }
}
