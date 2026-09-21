<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\User;
use Bog\Payment\Models\BogCard;

class SavedCardService
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, BogCard>
     */
    public function list(User $user)
    {
        return BogCard::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    public function delete(User $user, BogCard $card): void
    {
        if ((int) $card->user_id !== (int) $user->id) {
            return;
        }

        $isDefault = (bool) $card->is_default;
        $card->delete();

        if (! $isDefault) {
            return;
        }

        $newDefault = BogCard::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($newDefault) {
            $newDefault->update(['is_default' => true]);
        }
    }

    public function setDefault(User $user, BogCard $card): void
    {
        if ((int) $card->user_id !== (int) $user->id) {
            return;
        }

        BogCard::query()
            ->where('user_id', $user->id)
            ->update(['is_default' => false]);

        $card->update(['is_default' => true]);
    }
}
