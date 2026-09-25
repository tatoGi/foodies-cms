<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosSalesSnapshotRequest;
use App\Services\Pos\PosSalesSyncService;
use Illuminate\Http\JsonResponse;

class PosSalesController extends Controller
{
    public function __construct(private readonly PosSalesSyncService $sales) {}

    public function __invoke(PosSalesSnapshotRequest $request): JsonResponse
    {
        $days = $this->sales->apply($request->attributes->get('pos_device'), $request->validated('days'));

        return response()->json(['ok' => true, 'days' => $days]);
    }
}
