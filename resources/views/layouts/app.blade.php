<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $siteSettings['site_name'])</title>

    @if($siteSettings['site_favicon'])
        <link rel="icon" href="{{ $siteSettings['site_favicon'] }}">
    @endif

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Dynamic color theme from settings --}}
    @if(!empty($siteSettings['primary_color_shades']) || !empty($siteSettings['secondary_color_shades']))
        <style>
            :root {
                @if(!empty($siteSettings['primary_color_shades']))
                    --color-primary-50: {{ $siteSettings['primary_color_shades']['50'] }} !important;
                    --color-primary-100: {{ $siteSettings['primary_color_shades']['100'] }} !important;
                    --color-primary-200: {{ $siteSettings['primary_color_shades']['200'] }} !important;
                    --color-primary-300: {{ $siteSettings['primary_color_shades']['300'] }} !important;
                    --color-primary-400: {{ $siteSettings['primary_color_shades']['400'] }} !important;
                    --color-primary-500: {{ $siteSettings['primary_color_shades']['500'] }} !important;
                    --color-primary-600: {{ $siteSettings['primary_color_shades']['600'] }} !important;
                    --color-primary-700: {{ $siteSettings['primary_color_shades']['700'] }} !important;
                    --color-primary-800: {{ $siteSettings['primary_color_shades']['800'] }} !important;
                    --color-primary-900: {{ $siteSettings['primary_color_shades']['900'] }} !important;
                @endif

                @if(!empty($siteSettings['secondary_color_shades']))
                    --color-secondary-50: {{ $siteSettings['secondary_color_shades']['50'] }} !important;
                    --color-secondary-100: {{ $siteSettings['secondary_color_shades']['100'] }} !important;
                    --color-secondary-200: {{ $siteSettings['secondary_color_shades']['200'] }} !important;
                    --color-secondary-300: {{ $siteSettings['secondary_color_shades']['300'] }} !important;
                    --color-secondary-400: {{ $siteSettings['secondary_color_shades']['400'] }} !important;
                    --color-secondary-500: {{ $siteSettings['secondary_color_shades']['500'] }} !important;
                    --color-secondary-600: {{ $siteSettings['secondary_color_shades']['600'] }} !important;
                    --color-secondary-700: {{ $siteSettings['secondary_color_shades']['700'] }} !important;
                    --color-secondary-800: {{ $siteSettings['secondary_color_shades']['800'] }} !important;
                    --color-secondary-900: {{ $siteSettings['secondary_color_shades']['900'] }} !important;
                @endif
            }
        </style>
    @endif

    {{-- Custom CSS from settings --}}
    @if(!empty($siteSettings['custom_css']))
        <style>
            {!! $siteSettings['custom_css'] !!}
        </style>
    @endif

    @stack('styles')

    {{-- KingsChat SDK --}}
    @if(config('services.kingschat.app_id'))
        <script src="https://cdn.kingsch.at/sdk/web/v1/kingschat-sdk.js" defer></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.KingsChatSDK) {
                    KingsChatSDK.init({
                        appId: '{{ config('services.kingschat.app_id') }}',
                        onReady: () => console.log('KingsChat SDK initialized'),
                        onError: (error) => console.error('KingsChat SDK error:', error)
                    });
                }
            });
        </script>
    @endif
</head>
<body x-data="appLayout" class="font-sans bg-gray-50 antialiased">

    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeSidebar()"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        style="display: none;"
    ></div>

    {{-- Sidebar --}}
    @auth
        @include('partials.sidebar')
    @endauth

    {{-- Main content area --}}
    <div class="@auth lg:pl-64 @endauth min-h-screen flex flex-col">
        {{-- Top navbar --}}
        @auth
            @include('partials.topnav')
        @endauth

        {{-- Page content --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @yield('content')
        </main>
    </div>

    {{-- Toast notifications --}}
    <x-toast />

    {{-- Session flash messages --}}
    @if(session('success'))
        <div x-init="$dispatch('toast', { type: 'success', title: '{{ addslashes(session('success')) }}' })"></div>
    @endif
    @if(session('error'))
        <div x-init="$dispatch('toast', { type: 'error', title: '{{ addslashes(session('error')) }}' })"></div>
    @endif
    @if(session('warning'))
        <div x-init="$dispatch('toast', { type: 'warning', title: '{{ addslashes(session('warning')) }}' })"></div>
    @endif
    @if(session('info'))
        <div x-init="$dispatch('toast', { type: 'info', title: '{{ addslashes(session('info')) }}' })"></div>
    @endif

    @stack('scripts')
</body>
</html>
