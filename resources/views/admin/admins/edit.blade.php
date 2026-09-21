@extends('admin.layouts.app')

@section('title', __('Edit User'))
@section('page_title', __('Edit User'))

@section('content')
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('Edit Admin User') }}</h2>
            <p class="text-muted mb-0">{{ __('Update account details, role, and status.') }}</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('admin.admins.index') }}" class="btn btn-light-soft px-4">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to List') }}
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ __('Please fix the validation errors and try again.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.admins.update', $user) }}">
        @csrf
        @method('PUT')
        @include('admin.admins._form')

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-5 py-2 rounded-3">
                <i class="bi bi-check-lg me-1"></i>{{ __('Update User') }}
            </button>
        </div>
    </form>
@endsection
