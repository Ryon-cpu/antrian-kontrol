@php
    $landingConfig = [
        'appName' => $app_name,
        'logoUrl' => asset('icons/antrian-512.png?v=' . filemtime(public_path('icons/antrian-512.png'))),
        'practice' => $practice,
        'queue' => $queue,
        'place' => $place,
        'doctor' => $doctor,
        'achievements' => $achievements,
        'currentYear' => date('Y'),
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f4f6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ $app_name }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('icons/antrian-512.png?v=' . filemtime(public_path('icons/antrian-512.png'))) }}" type="image/png">
        <link rel="apple-touch-icon" href="{{ asset('icons/antrian-512.png?v=' . filemtime(public_path('icons/antrian-512.png'))) }}">
        <title>{{ $app_name }} | Pendaftaran Antrian</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/landing.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-100 min-h-screen">
        <div
            id="queue-app"
            data-landing-page
            data-queue-registration
            data-practice-open="{{ $practice['is_open'] ? 'true' : 'false' }}"
            data-queue-full="{{ $queue['is_full'] ? 'true' : 'false' }}"
            data-landing-config="{{ json_encode($landingConfig, JSON_UNESCAPED_SLASHES) }}"
        ></div>
    </body>
</html>
