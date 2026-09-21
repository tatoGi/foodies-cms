<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosMenuSnapshotRequest;
use App\Services\Pos\PosMenuSyncService;
use Illuminate\Http\JsonResponse;

class PosMenuSnapshotController extends Controller
{
    public function __construct(private readonly PosMenuSyncService $sync) {}

    public function __invoke(PosMenuSnapshotRequest $request): JsonResponse
    {
        $counts = $this->sync->syncSnapshot($request->attributes->get('pos_device'), $request->validated());

        return response()->json(['ok' => true] + $counts);
    }
}
