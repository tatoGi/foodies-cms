<?php

// feat: მკაცრი ტიპების დეკლარაცია დამატებულია ადმინ კონტროლერებში

// declare(strict_types=1) დაემატა ყველა Admin HTTP კონტროლერს.
// ეს უზრუნველყოფს ტიპების მკაცრ შემოწმებას, თავიდან აიცილებს
// ავტომატურ ტიპის გარდაქმნებს და აუმჯობესებს კოდის სტაბილურობას.

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageController extends Controller
{
    public function create(): View
    {
        return view('admin.languages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLanguage($request);

        Language::query()->create([
            'name' => trim($validated['name']),
            'english_name' => trim($validated['english_name']),
            'code' => trim(strtolower($validated['code'])),
            'country_code' => trim(strtoupper($validated['country_code'])),
            'direction' => $validated['direction'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_default' => false,
            'sort_order' => ((int) Language::query()->max('sort_order')) + 1,
        ]);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', __('Language created successfully.'));
    }

    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();
        $search = trim($request->string('q')->toString());

        $query = Language::query();

        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('english_name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $languages = $query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->paginate(10)
            ->withQueryString();

        $activeLanguages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->get(['id', 'name', 'english_name', 'code', 'country_code', 'sort_order']);

        return view('admin.languages.index', [
            'languages' => $languages,
            'activeLanguages' => $activeLanguages,
            'filter' => $filter === '' ? 'all' : $filter,
            'search' => $search,
        ]);
    }

    public function edit(Language $language): View
    {
        return view('admin.languages.edit', [
            'language' => $language,
        ]);
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $this->validateLanguage($request, $language);

        $targetActiveState = (bool) ($validated['is_active'] ?? false);
        if ($language->is_default && $targetActiveState === false) {
            return back()->with('error', __('Default language cannot be deactivated.'));
        }

        $language->update([
            'name' => trim($validated['name']),
            'english_name' => trim($validated['english_name']),
            'code' => trim(strtolower($validated['code'])),
            'country_code' => trim(strtoupper($validated['country_code'])),
            'direction' => $validated['direction'],
            'is_active' => $targetActiveState,
        ]);

        return redirect()
            ->route('admin.languages.index')
            ->with('success', __('Language updated successfully.'));
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            return back()->with('error', __('Default language cannot be deleted.'));
        }

        $usageTables = $this->localeUsageTables((string) $language->code);
        if ($usageTables !== []) {
            return back()->with(
                'error',
                __('Cannot delete language because translations/content exist in related tables: :tables', [
                    'tables' => implode(', ', $usageTables),
                ])
            );
        }

        $language->delete();

        return redirect()
            ->route('admin.languages.index')
            ->with('success', __('Language deleted successfully.'));
    }

    public function toggleActive(Request $request, Language $language): RedirectResponse
    {
        $validated = $request->validate([
            'active' => ['nullable', 'boolean'],
        ]);

        $targetState = array_key_exists('active', $validated)
            ? (bool) $validated['active']
            : ! $language->is_active;

        if ($language->is_default && $targetState === false) {
            return back()->with('error', __('Default language cannot be deactivated.'));
        }

        $language->update([
            'is_active' => $targetState,
        ]);

        return back()->with('success', __('Language status updated.'));
    }

    public function setDefault(Language $language): RedirectResponse
    {
        DB::transaction(function () use ($language): void {
            Language::query()->update(['is_default' => false]);

            $language->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        });

        return back()->with('success', __('Default language updated.'));
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:activate,deactivate'],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer', 'exists:languages,id'],
        ]);

        $ids = collect($validated['selected_ids'])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($validated['action'] === 'activate') {
            Language::query()
                ->whereIn('id', $ids)
                ->update(['is_active' => true]);

            return back()->with('success', __('Selected languages activated.'));
        }

        $defaultId = Language::query()
            ->where('is_default', true)
            ->value('id');

        if ($defaultId !== null && $ids->contains((int) $defaultId)) {
            return back()->with('error', __('Default language cannot be deactivated.'));
        }

        Language::query()
            ->whereIn('id', $ids)
            ->update(['is_active' => false]);

        return back()->with('success', __('Selected languages deactivated.'));
    }

    public function updateSortOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:languages,id'],
        ]);

        $orderedIds = collect($validated['ordered_ids'])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $languageId) {
                Language::query()
                    ->where('id', $languageId)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return back()->with('success', __('Language order updated.'));
    }

    private function validateLanguage(Request $request, ?Language $language = null): array
    {
        $request->merge([
            'code' => strtolower(trim((string) $request->input('code', ''))),
            'country_code' => strtoupper(trim((string) $request->input('country_code', ''))),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'english_name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[a-z]{2}$/',
                Rule::unique('languages', 'code')->ignore($language?->id),
            ],
            'country_code' => [
                'required',
                'string',
                'size:2',
                'regex:/^[A-Z]{2}$/',
            ],
            'direction' => ['required', Rule::in(['ltr', 'rtl'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function localeUsageTables(string $locale): array
    {
        $tables = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('COLUMN_NAME', 'locale')
            ->pluck('TABLE_NAME')
            ->map(static fn ($table): string => (string) $table)
            ->filter(static function (string $table): bool {
                return preg_match('/^[A-Za-z0-9_]+$/', $table) === 1;
            })
            ->values()
            ->all();

        $usedTables = [];
        foreach ($tables as $table) {
            if (DB::table($table)->where('locale', $locale)->exists()) {
                $usedTables[] = $table;
            }
        }

        return $usedTables;
    }
}
