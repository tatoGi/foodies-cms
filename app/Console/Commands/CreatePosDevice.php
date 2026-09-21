<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Pos\PosDeviceService;
use Illuminate\Console\Command;

class CreatePosDevice extends Command
{
    protected $signature = 'pos:device-create {name : Human-readable device name, e.g. "Bakery POS"}';

    protected $description = 'Register a FoodEase POS device and print its credentials (shown only once)';

    public function handle(PosDeviceService $devices): int
    {
        $credentials = $devices->register($this->argument('name'));

        $this->info('Device created. Store these in the POS .env - they cannot be shown again.');
        $this->line('Token:  '.$credentials['token']);
        $this->line('Secret: '.$credentials['secret']);

        return self::SUCCESS;
    }
}
