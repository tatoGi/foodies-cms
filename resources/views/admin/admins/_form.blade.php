@php
    $isEdit = isset($user);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="dashboard-panel premium-shadow">
            <div class="panel-header border-bottom-0">
                <div class="panel-header-title">
                    <i class="bi bi-person-vcard me-2 text-primary"></i>
                    <span>{{ __('Account Details') }}</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Full Name') }}</label>
                    <input
                        type="text"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $isEdit ? $user->name : '') }}"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Email') }}</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $isEdit ? $user->email : '') }}"
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Password') }}</label>
                    <input
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        {{ $isEdit ? '' : 'required' }}
                    >
                    @if($isEdit)
                        <div class="form-text">{{ __('Leave blank to keep current password.') }}</div>
                    @endif
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Confirm Password') }}</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        {{ $isEdit ? '' : 'required' }}
                    >
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="dashboard-panel premium-shadow">
            <div class="panel-header border-bottom-0">
                <div class="panel-header-title">
                    <i class="bi bi-gear me-2 text-primary"></i>
                    <span>{{ __('Settings') }}</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Role') }}</label>
                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                        <option value="">{{ __('Select Role') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id', $isEdit ? $user->role_id : 0) === (int) $role->id)>
                                {{ $role->name }} ({{ $role->slug }})
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check form-switch p-0 ps-5 mt-4">
                    <input
                        class="form-check-input ms-n5"
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $isEdit ? (bool) $user->is_active : true))
                    >
                    <label class="form-check-label fw-bold" for="is_active">{{ __('Active Account') }}</label>
                </div>
            </div>
        </div>
    </div>
</div>
