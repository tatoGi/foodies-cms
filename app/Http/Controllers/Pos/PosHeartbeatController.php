<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosHeartbeatRequest;
use App\Services\Pos\PosDeviceService;
use Illuminate\Http\JsonResponse;

class PosHeartbeatController extends Controller
{
    public function __construct(private readonly PosDeviceService $devices) {}

    public function __invoke(PosHeartbeatRequest $request): JsonResponse
    {
        $this->devices->recordHeartbeat(
            $request->attributes->get('pos_device'),
            $request->validated('app_version'),
            $request->safe()->except('app_version'),
        );

        return response()->json(['ok' => true, 'server_time' => now()->toIso8601String()]);
    }
}
