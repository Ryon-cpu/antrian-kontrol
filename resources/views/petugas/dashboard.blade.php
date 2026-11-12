<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Dashboard Petugas | {{ $appName }}</title>
        <link rel="icon" href="{{ asset('icons/antrian-512.png?v=' . filemtime(public_path('icons/antrian-512.png'))) }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('icons/antrian-512.png?v=' . filemtime(public_path('icons/antrian-512.png'))) }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div class="min-h-screen bg-gray-100">
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-1">
                        <a href="{{ route('landing') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">← Halaman utama</a>
                        <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Dashboard petugas</h1>
                    </div>
                    <button id="logout-button" type="button" class="hidden min-h-12 rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-semibold text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Keluar
                    </button>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @include('petugas.partials.queue-dashboard', ['authMode' => 'token', 'staffName' => ''])
            </main>
        </div>
    </body>
</html>
