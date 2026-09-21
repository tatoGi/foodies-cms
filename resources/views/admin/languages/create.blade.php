@extends('admin.layouts.app')

@section('title', __('Create Language'))
@section('page_title', __('Create Language'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h2 class="welcome-title mb-1">{{ __('Add New Language') }}</h2>
            <p class="text-muted mb-0">{{ __('Create a language entry for localization and content management.') }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('admin.languages.form', [
        'formAction' => route('admin.languages.store'),
        'submitLabel' => __('Create Language'),
    ])
@endsection
