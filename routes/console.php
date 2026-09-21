<?php

use App\Services\BlockTypeSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cms:sync-block-types {--create-missing}', function (BlockTypeSyncService $blockTypeSyncService) {
    $result = $blockTypeSyncService->syncSystemBlocks(
        seedFieldsOnCreate: true,
        createMissing: (bool) $this->option('create-missing'),
    );

    $this->info(sprintf(
        'Block types synced. Created: %d, updated: %d',
        $result['created'],
        $result['updated'],
    ));
})->purpose('Sync existing CMS block types from code metadata. Use --create-missing to recreate missing canonical types.');
