<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-google-analytics />
    <link href="https://fonts.googleapis.com/css?family=Signika:300,400,600,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body>
    @inertia
    <x-ai-chat-widget />
</body>

</html>
