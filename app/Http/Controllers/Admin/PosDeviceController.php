<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Pos\PosDeviceService;
use Illuminate\View\View;

class PosDeviceController extends Controller
{
    public function __construct(private readonly PosDeviceService $devices) {}

    public function index(): View
    {
        return view('admin.pos-devices.index', [
            'devices' => $this->devices->listForAdmin(),
        ]);
    }
}
