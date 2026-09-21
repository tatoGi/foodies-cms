<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');
        $fallbackLocale = config('app.locale', 'en');

        if (Schema::hasTable('languages')) {
            $activeCodes = Language::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('code')
                ->values()
                ->all();

            $fallbackLocale = Language::query()
                ->where('is_default', true)
                ->value('code')
                ?? ($activeCodes[0] ?? $fallbackLocale);

            if (! in_array($locale, $activeCodes, true)) {
                $locale = $fallbackLocale;
            }
        } else {
            if (! in_array($locale, ['en', 'ka'], true)) {
                $locale = $fallbackLocale;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
