<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ACME Corp') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>

    <!-- Pass Laravel data to Vue -->
    <script>
        window.Laravel = {
            user: @json(auth()->check() ? auth()->user() : null),
            csrfToken: '{{ csrf_token() }}',
            appName: '{{ config('app.name', 'ACME Corp') }}'
        };
    </script>
</body>
</html>
