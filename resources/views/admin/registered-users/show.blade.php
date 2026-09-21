@extends('admin.layouts.app')

@section('title', __('User'))
@section('page_title', __('User'))

@section('content')
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ $user->name }}</h2>
            <p class="text-muted mb-0">{{ __('Registered user details.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light-soft px-4">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to List') }}
            </a>
        </div>
    </div>

    <div class="dashboard-panel premium-shadow overflow-hidden">
        <div class="panel-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-3 text-muted">{{ __('Name') }}</dt>
                <dd class="col-sm-9 fw-semibold">{{ $user->name }}</dd>

                <dt class="col-sm-3 text-muted">{{ __('Email') }}</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>

                <dt class="col-sm-3 text-muted">{{ __('Phone') }}</dt>
                <dd class="col-sm-9">{{ $user->phone ?: '—' }}</dd>

                <dt class="col-sm-3 text-muted">{{ __('Address') }}</dt>
                <dd class="col-sm-9">{{ $user->address ?: '—' }}</dd>

                <dt class="col-sm-3 text-muted">{{ __('Wishlist') }}</dt>
                <dd class="col-sm-9">{{ $user->wishlist_count }}</dd>

                <dt class="col-sm-3 text-muted">{{ __('Email verified') }}</dt>
                <dd class="col-sm-9">{{ $user->email_verified_at?->format('Y-m-d H:i') ?? __('No') }}</dd>

                <dt class="col-sm-3 text-muted">{{ __('Registered') }}</dt>
                <dd class="col-sm-9">{{ $user->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
