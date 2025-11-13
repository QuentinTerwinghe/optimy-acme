<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'ACME Corp') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 @yield('body-class')">
    <div id="app" @yield('app-container-class')>
        <!-- Navigation -->
        @auth
            <nav class="bg-white shadow-sm border-b border-gray-200">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between items-center">
                        <!-- Logo -->
                        <div class="flex items-center gap-4">
                            <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-900">
                                {{ config('app.name', 'ACME Corp') }}
                            </a>
                            <a href="{{ route('campaigns.create') }}"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors duration-200 shadow-sm hover:shadow-md"
                               title="Create new campaign">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:flex sm:space-x-8">
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-gray-900 border-b-2 border-indigo-500' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('campaigns.index') }}"
                               class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('campaigns.index') ? 'text-gray-900 border-b-2 border-indigo-500' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                Manage Campaigns
                            </a>
                            @can('*')
                                <a href="{{ route('admin.roles') }}"
                                   class="inline-flex items-center px-1 pt-1 text-sm font-medium {{ request()->routeIs('admin.*') ? 'text-gray-900 border-b-2 border-indigo-500' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Admin
                                </a>
                            @endcan
                        </div>

                        <!-- User Dropdown -->
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline" @submit.prevent="handleLogout">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @endauth

        <!-- Page Content -->
        <main @auth class="py-8" @endauth>
            @yield('content')
        </main>
    </div>

    <!-- Pass Laravel data to Vue if needed -->
    @auth
        <script>
            window.Laravel = {
                user: @json(auth()->user()),
                csrfToken: '{{ csrf_token() }}'
            };
        </script>
    @endauth
</body>
</html>
