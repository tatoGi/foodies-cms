<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** One command for the site's demo content: php artisan db:seed --class=SiteDemoSeeder */
class SiteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SitePageTemplateSeeder::class,
            SitePagesDemoSeeder::class,
        ]);
    }
}
