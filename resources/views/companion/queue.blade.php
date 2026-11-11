<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f4f6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Antrian Pasien') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('icons/antrian.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('icons/antrian-192.png') }}">
        <title>Status Antrian | {{ config('app.name', 'Antrian Pasien') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/companion-queue.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div
            id="companion-queue-app"
            class="min-h-screen bg-gray-100"
            data-vapid-public-key="{{ config('antrian.vapid.public') }}"
        >
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-5xl flex-col gap-3 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <a href="{{ route('landing') }}" class="text-xl font-bold text-gray-900 sm:text-2xl">
                        {{ config('app.name', 'Antrian Pasien') }}
                    </a>
                    <a href="{{ route('landing') }}" class="text-base font-semibold text-blue-700 hover:text-blue-900">
                        Kembali ke pendaftaran
                    </a>
                </div>
            </header>

            <main class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section class="rounded-lg bg-white p-5 shadow-sm sm:p-8" aria-labelledby="queue-title">
                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Informasi pasien</p>
                        <h1 id="queue-title" class="text-2xl font-bold text-gray-900 sm:text-3xl">Status antrian Anda</h1>
                        <p class="text-base leading-relaxed text-gray-700">
                            Halaman ini diperbarui otomatis. Data pribadi pasien tidak ditampilkan.
                        </p>
                    </div>

                    <div id="queue-feedback" class="mt-6 hidden rounded-md border px-4 py-3 text-base" role="status" aria-live="polite"></div>

                    <div id="queue-status" class="mt-6 hidden flex-col gap-6" aria-live="polite">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <article class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-5">
                                <h2 class="text-base font-semibold text-gray-600">Nomor antrian Anda</h2>
                                <p id="my-number" class="text-5xl font-bold text-blue-800">--</p>
                                <p id="my-status" class="text-lg font-semibold text-gray-800"></p>
                            </article>
                            <article class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-5">
                                <h2 class="text-base font-semibold text-gray-600">Sedang diperiksa / dipanggil</h2>
                                <p id="now-serving" class="text-5xl font-bold text-gray-900">--</p>
                                <p id="practice-status" class="text-base text-gray-700"></p>
                            </article>
                        </div>

                        <div class="flex flex-col gap-2 rounded-lg border border-blue-200 bg-blue-50 p-5">
                            <h2 class="text-lg font-bold text-blue-950">Perkiraan giliran</h2>
                            <p id="ahead-count" class="text-base leading-relaxed text-blue-900"></p>
                            <p class="text-sm leading-relaxed text-blue-900">
                                Nomor yang sedang dipanggil dapat berubah setelah pemeriksaan selesai.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button id="refresh-queue" type="button" class="min-h-12 rounded-md border border-gray-300 bg-white px-5 py-3 text-base font-semibold text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Perbarui sekarang
                            </button>
                            <button id="enable-notifications" type="button" class="min-h-12 rounded-md bg-blue-700 px-5 py-3 text-base font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Aktifkan notifikasi giliran
                            </button>
                            <a id="register-another" href="{{ route('landing') }}" class="hidden min-h-12 items-center justify-center rounded-md bg-gray-800 px-5 py-3 text-center text-base font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Daftarkan pasien lain
                            </a>
                        </div>
                    </div>

                    <div id="no-queue" class="mt-6 hidden flex-col gap-3 rounded-lg bg-gray-50 p-5">
                        <p id="no-queue-message" class="text-base leading-relaxed text-gray-700">
                            Data antrian untuk perangkat ini tidak ditemukan hari ini.
                        </p>
                        <a href="{{ route('landing') }}" class="font-semibold text-blue-700 hover:text-blue-900">Kembali ke pendaftaran</a>
                    </div>
                </section>
            </main>

            <dialog id="turn-dialog" class="w-[calc(100%-2rem)] max-w-md rounded-lg p-0 shadow-xl backdrop:bg-gray-900/60">
                <div class="flex flex-col gap-4 p-6">
                    <h2 class="text-2xl font-bold text-gray-900">Giliran Anda</h2>
                    <p id="turn-message" class="text-lg leading-relaxed text-gray-700"></p>
                    <button id="close-turn-dialog" type="button" class="min-h-12 rounded-md bg-blue-700 px-5 py-3 text-base font-bold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Saya mengerti
                    </button>
                </div>
            </dialog>
        </div>
    </body>
</html>
