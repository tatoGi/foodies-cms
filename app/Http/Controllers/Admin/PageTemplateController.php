<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageTemplateRequest;
use App\Http\Requests\Admin\UpdatePageTemplateRequest;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PageTemplateController extends Controller
{
    public function index(): View
    {
        $templates = PageTemplate::with('translations')
            ->withCount('pages')
            ->get();

        return view('admin.page-templates.index', [
            'templates' => $templates,
            'currentLocale' => app()->getLocale(),
        ]);
    }

    public function create(): View
    {
        $locales = $this->locales();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput(
            $locales,
            collect($locales)->pluck('code')->map(static fn ($code): string => (string) $code)->all()
        );

        return view('admin.page-templates.create', [
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
        ]);
    }

    public function store(StorePageTemplateRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $locales = $this->locales();
            $localeCodes = collect($locales)->pluck('code')->values();
            $names = collect((array) $request->input('names', []));
            $slug = trim((string) $request->input('slug', ''));

            $template = PageTemplate::create([
                'slug' => $slug,
            ]);

            foreach ($localeCodes as $locale) {
                $locale = (string) $locale;
                $name = trim((string) $names->get($locale, ''));

                if ($name !== '') {
                    $template->translations()->create([
                        'locale' => $locale,
                        'name' => $name,
                    ]);
                }
            }
        });

        return redirect()->route('admin.page-templates.index')
            ->with('success', __('Page template created successfully.'));
    }

    public function edit(PageTemplate $pageTemplate): View
    {
        $locales = $this->locales();
        $defaultLocale = $this->defaultLocaleCode($locales);
        $existingLocaleCodes = $pageTemplate->translations
            ->pluck('locale')
            ->map(static fn ($locale): string => (string) $locale)
            ->values()
            ->all();
        $fallbackSelected = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $selectedLocaleCodes = $this->selectedLocaleCodesFromOldInput($locales, $fallbackSelected);

        return view('admin.page-templates.edit', [
            'pageTemplate' => $pageTemplate,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'translations' => $pageTemplate->translations->keyBy('locale'),
            'existingLocaleCodes' => $existingLocaleCodes,
        ]);
    }

    public function update(UpdatePageTemplateRequest $request, PageTemplate $pageTemplate): RedirectResponse
    {
        DB::transaction(function () use ($request, $pageTemplate): void {
            $locales = $this->locales();
            $localeCodes = collect($locales)->pluck('code')->values();
            $names = collect((array) $request->input('names', []));
            $slug = trim((string) $request->input('slug', ''));

            $oldSlug = (string) $pageTemplate->slug;

            $pageTemplate->update([
                'slug' => $slug,
            ]);

            if ($oldSlug !== '' && $slug !== '' && $oldSlug !== $slug) {
                Page::query()->where('template', $oldSlug)->update(['template' => $slug]);
            }

            $existingLocales = $pageTemplate->translations->pluck('locale')->all();
            $handledLocales = [];

            foreach ($localeCodes as $locale) {
                $locale = (string) $locale;
                $name = trim((string) $names->get($locale, ''));

                if ($name !== '') {
                    $pageTemplate->translations()->updateOrCreate(
                        ['locale' => $locale],
                        [
                            'name' => $name,
                        ]
                    );

                    $handledLocales[] = $locale;
                }
            }

            $toDelete = array_diff($existingLocales, $handledLocales);
            if ($toDelete !== []) {
                $pageTemplate->translations()->whereIn('locale', $toDelete)->delete();
            }
        });

        return redirect()->route('admin.page-templates.index')
            ->with('success', __('Page template updated successfully.'));
    }

    public function destroy(PageTemplate $pageTemplate): RedirectResponse
    {
        if ($pageTemplate->pages()->exists()) {
            return back()->with('error', __('Cannot delete template that is assigned to pages.'));
        }

        $pageTemplate->delete();

        return redirect()->route('admin.page-templates.index')
            ->with('success', __('Page template deleted successfully.'));
    }

    /**
     * @return array<int, array{code:string,name:string,flag:string,is_default:bool}>
     */
    private function locales(): array
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->get(['code', 'name', 'country_code', 'is_default']);

        if ($languages->isEmpty()) {
            $fallbackCode = app()->getLocale();

            return [[
                'code' => $fallbackCode,
                'name' => strtoupper($fallbackCode),
                'flag' => '',
                'is_default' => true,
            ]];
        }

        return $languages
            ->map(static function (Language $language): array {
                $code = (string) $language->code;

                return [
                    'code' => $code,
                    'name' => (string) ($language->name ?: strtoupper($code)),
                    'flag' => (string) $language->country_code,
                    'is_default' => (bool) $language->is_default,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{code:string,name:string,flag:string,is_default:bool}>  $locales
     */
    private function defaultLocaleCode(array $locales): string
    {
        $defaultLocale = collect($locales)->firstWhere('is_default', true);
        if (is_array($defaultLocale) && isset($defaultLocale['code'])) {
            return (string) $defaultLocale['code'];
        }

        return (string) (collect($locales)->first()['code'] ?? app()->getLocale());
    }

    /**
     * @param  array<int, array{code:string,name:string,flag:string,is_default:bool}>  $locales
     * @param  array<int, string>  $fallback
     * @return array<int, string>
     */
    private function selectedLocaleCodesFromOldInput(array $locales, array $fallback): array
    {
        $allowed = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values();

        $oldNames = collect(array_keys((array) old('names', [])))->map(static fn ($key): string => (string) $key);
        $fromOld = $oldNames
            ->filter(static fn (string $code): bool => $allowed->contains($code))
            ->unique()
            ->values();

        if ($fromOld->isNotEmpty()) {
            return $fromOld->all();
        }

        return collect($fallback)
            ->map(static fn ($code): string => (string) $code)
            ->filter(static fn (string $code): bool => $allowed->contains($code))
            ->unique()
            ->values()
            ->all();
    }
}
