@php
    $isEdit = isset($role);
@endphp

<div class="row g-4">
    <div class="col-lg-7">
        <div class="dashboard-panel premium-shadow">
            <div class="panel-header border-bottom-0">
                <div class="panel-header-title">
                    <i class="bi bi-shield-lock me-2 text-primary"></i>
                    <span>{{ __('Role Details') }}</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Role Name') }}</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $isEdit ? $role->name : '') }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Slug') }}</label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $isEdit ? $role->slug : '') }}" placeholder="{{ __('Optional. Auto-generated from name') }}">
                    @error('slug')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Description') }}</label>
                    <textarea name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $isEdit ? $role->description : '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="dashboard-panel premium-shadow">
            <div class="panel-header border-bottom-0">
                <div class="panel-header-title">
                    <i class="bi bi-key me-2 text-primary"></i>
                    <span>{{ __('Permissions') }}</span>
                </div>
            </div>
            <div class="panel-body">
                @php
                    $selected = collect(old('permission_ids', $selectedPermissionIds ?? []))
                        ->map(static fn ($id): int => (int) $id)
                        ->all();
                @endphp

                @if(count($permissionsByGroup) === 0)
                    <div class="alert alert-warning mb-3">
                        {{ __('No permissions found yet. Add permission keys below to create them.') }}
                    </div>
                @else
                    @foreach($permissionsByGroup as $group => $permissions)
                        <div class="mb-3">
                            <div class="small fw-bold text-muted uppercase letter-spacing-1 mb-2">{{ \Illuminate\Support\Str::headline($group) }}</div>
                            <div class="d-flex flex-column gap-2">
                                @foreach($permissions as $permission)
                                    <label class="form-check">
                                        <input
                                            type="checkbox"
                                            name="permission_ids[]"
                                            class="form-check-input"
                                            value="{{ $permission->id }}"
                                            @checked(in_array((int) $permission->id, $selected, true))
                                        >
                                        <span class="form-check-label">
                                            {{ $permission->label }}
                                            <span class="text-muted small">({{ $permission->key }})</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('New Permission Keys') }}</label>
                    <textarea
                        name="permission_keys"
                        rows="3"
                        class="form-control @error('permission_keys') is-invalid @enderror"
                        placeholder="pages.view, pages.create, users.manage"
                    >{{ old('permission_keys') }}</textarea>
                    <div class="form-text">{{ __('Optional. Comma or newline separated keys.') }}</div>
                    @error('permission_keys')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
