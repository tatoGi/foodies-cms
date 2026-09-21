@extends('admin.layouts.app')

@section('title', __('Edit Language'))
@section('page_title', __('Edit Language'))

@section('content')
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h2 class="welcome-title mb-1">{{ __('Edit Language') }}</h2>
            <p class="text-muted mb-0">{{ __('Update language details and availability settings.') }}</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($language->is_default)
        <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4">
            {{ __('This is the default language. It cannot be deleted and must remain active.') }}
        </div>
    @endif

    @include('admin.languages.form', [
        'language' => $language,
        'formAction' => route('admin.languages.update', $language),
        'submitLabel' => __('Save Changes'),
    ])
@endsection
