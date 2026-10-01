<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Exceptions\WebApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Website\AddressRequest;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    private const LIMIT = 10;

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get()->map->toApi()->values(),
        ]);
    }

    public function store(AddressRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->addresses()->count() >= self::LIMIT) {
            throw new WebApiException('address_limit', 'You can save up to 10 addresses.');
        }

        $address = DB::transaction(function () use ($user, $request): UserAddress {
            $makeDefault = $request->boolean('is_default') || ! $user->addresses()->exists();
            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            return $user->addresses()->create(['is_default' => $makeDefault] + $request->safe()->except('is_default'));
        });

        return response()->json(['address' => $address->toApi()], 201);
    }

    public function update(AddressRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);

        DB::transaction(function () use ($user, $address, $request): void {
            if ($request->boolean('is_default')) {
                $user->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
                $address->is_default = true;
            }
            $address->fill($request->safe()->except('is_default'))->save();
        });

        return response()->json(['address' => $address->fresh()->toApi()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $address = $user->addresses()->findOrFail($id);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $user->addresses()->orderBy('id')->first()?->update(['is_default' => true]);
        }

        return response()->json(['success' => true]);
    }
}
