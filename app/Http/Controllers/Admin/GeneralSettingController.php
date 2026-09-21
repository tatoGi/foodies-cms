<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGeneralSettingRequest;
use App\Services\GeneralSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GeneralSettingController extends Controller
{
    public function __construct(
        private readonly GeneralSettingService $settingService,
    ) {}

    public function edit(): View
    {
        return view('admin.settings.edit', $this->settingService->buildEditViewData());
    }

    public function update(UpdateGeneralSettingRequest $request): RedirectResponse
    {
        $this->settingService->save($request->validated());

        return redirect()->route('admin.settings.edit')
            ->with('success', __('Settings saved successfully.'));
    }
}
