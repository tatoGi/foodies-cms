@extends('admin.layouts.app')

@section('title', __('My Profile'))
@section('page_title', __('My Profile'))

@section('content')
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('My Profile') }}</h2>
            <p class="text-muted mb-0">{{ __('Update account details, role, and status.') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ __('Please fix the validation errors and try again.') }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')

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
                                value="{{ old('name', $adminUser->name) }}"
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
                                value="{{ old('email', $adminUser->email) }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Role') }}</label>
                            <input
                                type="text"
                                class="form-control"
                                value="{{ optional($adminUser->role)->name ?? __('No Role') }}"
                                disabled
                            >
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-3">
                        <i class="bi bi-check-lg me-1"></i>{{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-5" id="security">
            <form method="POST" action="{{ route('admin.profile.password.update') }}">
                @csrf
                @method('PUT')

                <div class="dashboard-panel premium-shadow">
                    <div class="panel-header border-bottom-0">
                        <div class="panel-header-title">
                            <i class="bi bi-shield-lock me-2 text-primary"></i>
                            <span>{{ __('Security') }}</span>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Current Password') }}</label>
                            <input
                                type="password"
                                name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                required
                            >
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('New Password') }}</label>
                            <input
                                type="password"
                                name="new_password"
                                class="form-control @error('new_password') is-invalid @enderror"
                                required
                            >
                            @error('new_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold small text-muted uppercase letter-spacing-1">{{ __('Confirm Password') }}</label>
                            <input
                                type="password"
                                name="new_password_confirmation"
                                class="form-control"
                                required
                            >
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-outline-primary px-4 py-2 rounded-3">
                        <i class="bi bi-shield-check me-1"></i>{{ __('Update Password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
