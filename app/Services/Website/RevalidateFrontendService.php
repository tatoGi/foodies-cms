<?php

declare(strict_types=1);

namespace App\Services\Website;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Asks the Next.js site to drop cached CMS reads. A failed call must not undo a sync or a settings save.
 */
class RevalidateFrontendService
{
    /**
     * @param  list<string>  $tags
     */
    public function revalidate(array $tags): void
    {
        $url = config('services.frontend.revalidate_url');
        $secret = config('services.frontend.revalidate_secret');
        if (! is_string($url) || $url === '' || ! is_string($secret) || $secret === '' || $tags === []) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeaders(['X-Revalidate-Secret' => $secret])
                ->post($url, ['tags' => array_values(array_unique($tags))]);
        } catch (Throwable $exception) {
            Log::warning('Frontend revalidate failed.', ['message' => $exception->getMessage()]);
        }
    }
}
