@extends('admin.layouts.app')

@section('title', __('Edit Menu'))
@section('page_title', __('Edit Menu'))

@section('content')
    @include('admin.menus.form', [
        'menu' => $menu,
        'action' => route('admin.menus.update', $menu),
        'method' => 'PUT',
        'submitLabel' => __('Update Menu'),
    ])
@endsection
