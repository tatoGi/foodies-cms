<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\BlockTypeSyncService;
use Illuminate\Database\Seeder;

class BlockTypeDefinitionSeeder extends Seeder
{
    public function __construct(
        private readonly BlockTypeSyncService $blockTypeSyncService,
    ) {}

    public function run(): void
    {
        // On a fresh seed we only want canonical block types.
        // Field schema is configured later by content managers in admin.
        $this->blockTypeSyncService->syncSystemBlocks(seedFieldsOnCreate: false, createMissing: true);
    }
}
