@extends('admin.layouts.app')

@section('title', __('Create Menu'))
@section('page_title', __('Create Menu'))

@section('content')
    @include('admin.menus.form', [
        'menu' => null,
        'action' => route('admin.menus.store'),
        'method' => 'POST',
        'submitLabel' => __('Create Menu'),
    ])
@endsection
