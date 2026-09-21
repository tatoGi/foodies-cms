<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin')</title>
    @php
        $adminFavicon = \App\Models\GeneralSetting::query()
            ->where('key', 'header_logo')
            ->first()?->value;
        $adminFavicon = is_string($adminFavicon) && $adminFavicon !== '' ? $adminFavicon : null;
    @endphp
    @if($adminFavicon)
        <link rel="icon" type="image/x-icon" href="{{ $adminFavicon }}">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.2.3/css/flag-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
    <div class="admin-shell">
        @include('admin.partials.sidebar')
        <button
            class="admin-sidebar-backdrop"
            id="adminSidebarBackdrop"
            type="button"
            aria-label="{{ __('Close sidebar') }}"
        ></button>
        <div class="admin-main">
            @include('admin.partials.header')
            <main class="admin-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </main>
            @include('admin.partials.media-picker')
          
        </div>
       
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>
