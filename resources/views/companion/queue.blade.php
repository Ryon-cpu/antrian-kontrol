<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f4f6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ $appName ?? 'Antrian Pasien' }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('icons/antrian.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('icons/antrian-192.png') }}">
        <title>Status Antrian | {{ $appName ?? 'Antrian Pasien' }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/companion-queue.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-100 min-h-screen">
        <div
            id="companion-queue-app"
            class="min-h-screen flex flex-col"
            data-vapid-public-key="{{ config('antrian.vapid.public') }}"
        >
            {{-- Top Navbar (Sama Persis dengan Style Navbar Dashboard) --}}
            <nav class="bg-white border-b border-gray-100 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-700 text-white shadow-sm">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <span class="font-bold text-lg text-gray-900 tracking-tight">
                                    {{ $appName ?? 'Antrian Pasien' }}
                                </span>
                            </a>
                        </div>

                        <div class="flex items-center gap-3">
                            <a
                                href="{{ route('landing') }}"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span>Kembali ke pendaftaran</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            {{-- Page Heading (Sama Persis dengan Page Heading Dashboard) --}}
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                    <div>
                        <h1 id="queue-title" class="text-xl font-semibold leading-tight text-gray-800">
                            Status antrian Anda
                        </h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                            <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                            Live 10 dtk
                        </span>
                    </div>
                </div>
            </header>

            {{-- Main Content Container (Full Layar max-w-7xl px-4 py-8 sm:px-6 lg:px-8 Sama Persis dengan Dashboard) --}}
            <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 flex-1">
                <div class="flex flex-col gap-6">

                    {{-- Feedback Alert --}}
                    <div id="queue-feedback" class="hidden rounded-lg border px-4 py-3 text-base shadow-sm" role="status" aria-live="polite"></div>

                    {{-- Kontainer Status Antrian --}}
                    <div id="queue-status" class="flex flex-col gap-6" aria-live="polite">

                        {{-- Clinic Status & Quick Actions Bar (Sama Persis dengan Header Bar di Dashboard) --}}
                        <div class="flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between lg:p-6">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider bg-gray-100 text-gray-800">
                                        <svg class="h-3.5 w-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        Klinik Dokter Mandiri
                                    </span>
                                    <span class="text-sm text-gray-400">|</span>
                                    <span class="text-sm font-medium text-gray-600">
                                        Informasi antrian pasien hari ini
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="h-3.5 w-3.5 rounded-full bg-green-500 ring-4 ring-green-100 animate-pulse"></div>
                                    <p id="practice-status" class="text-lg font-bold text-green-800">
                                        Praktek sedang buka.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                {{-- Tombol Perbarui Sekarang --}}
                                <button
                                    id="refresh-queue"
                                    type="button"
                                    class="inline-flex min-h-12 items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                                >
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Perbarui sekarang</span>
                                </button>

                                {{-- Tombol Aktifkan Notifikasi --}}
                                <button
                                    id="enable-notifications"
                                    type="button"
                                    class="flex min-h-12 items-center gap-2 rounded-lg bg-blue-700 px-6 py-2.5 text-base font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <span>Aktifkan notifikasi giliran</span>
                                </button>

                                {{-- Daftarkan Pasien Lain (Muncul jika status selesai / batal) --}}
                                <a
                                    id="register-another"
                                    href="{{ route('landing') }}"
                                    class="hidden min-h-12 items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-base font-bold text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Daftarkan pasien lain</span>
                                </a>
                            </div>
                        </div>

                        {{-- 3 Kartu Metrik (Sama Persis Font, Ukuran, Grid sm:grid-cols-3 dengan Dashboard) --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            {{-- Card 1: Nomor Antrian Anda --}}
                            <article class="relative overflow-hidden rounded-xl border border-blue-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold uppercase tracking-wider text-blue-800">Nomor antrian Anda</p>
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                    </div>
                                </div>
                                <p id="my-number" class="mt-3 text-4xl font-black text-blue-700">--</p>
                                <div class="mt-1">
                                    <span id="my-status" class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-900">
                                        Memuat status...
                                    </span>
                                </div>
                            </article>

                            {{-- Card 2: Sedang Diperiksa / Dipanggil --}}
                            <article class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600">Sedang diperiksa / dipanggil</h2>
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                    </div>
                                </div>
                                <p id="now-serving" class="mt-3 text-4xl font-black text-gray-900">--</p>
                                <p class="mt-1 text-xs text-gray-500">Pasien di ruang periksa dokter</p>
                            </article>

                            {{-- Card 3: Perkiraan Giliran --}}
                            <article class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600">Perkiraan giliran</h2>
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-yellow-50 text-yellow-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <p id="ahead-count" class="mt-3 text-2xl font-black text-gray-900 leading-snug">Menghitung...</p>
                                <p class="mt-1 text-xs text-gray-500">Nomor dipanggil berubah saat periksa selesai.</p>
                            </article>
                        </div>

                        {{-- Section Petunjuk & Informasi Pasien (Sama Persis Style Kartu Putih Dashboard) --}}
                        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="border-b border-gray-200 pb-4">
                                <h3 class="text-xl font-bold text-gray-900 sm:text-2xl">Petunjuk Ruang Tunggu Pasien</h3>
                                <p class="mt-1 text-base text-gray-600">
                                    Halaman ini diperbarui otomatis setiap sepuluh detik. Data pribadi pasien tidak ditampilkan di layar.
                                </p>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="flex items-center gap-2 text-blue-700 font-bold text-base">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        <span>1. Bunyi Bel Panggilan</span>
                                    </div>
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        Saat giliran nomor antrian Anda tiba, halaman akan membunyikan nada bel dan menampilkan jendela peringatan.
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="flex items-center gap-2 text-yellow-700 font-bold text-base">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span>2. Toleransi 3 Kali Panggil</span>
                                    </div>
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        Petugas akan memanggil nomor maksimal 3 kali dengan jeda 2 menit sebelum urutan otomatis digeser ke bawah.
                                    </p>
                                </div>

                                <div class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="flex items-center gap-2 text-green-700 font-bold text-base">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>3. Menuju Ruang Periksa</span>
                                    </div>
                                    <p class="text-sm text-gray-600 leading-relaxed">
                                        Segera dampingi pasien menuju ruang periksa dokter saat nomor antrian Anda menyala aktif.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- Empty State jika Antrian Tidak Ditemukan --}}
                    <div id="no-queue" class="hidden flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h4 class="mt-4 text-lg font-bold text-gray-900">Antrian Tidak Ditemukan</h4>
                        <p id="no-queue-message" class="mt-1 max-w-sm text-sm text-gray-600">
                            Data antrian untuk perangkat ini tidak ditemukan hari ini.
                        </p>
                        <a
                            href="{{ route('landing') }}"
                            class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800"
                        >
                            <span>Daftar antrian baru</span>
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </main>

            {{-- Dialog Peringatan Giliran Tiba --}}
            <dialog
                id="turn-dialog"
                class="w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-black/60 backdrop:backdrop-blur-sm border border-blue-200"
            >
                <div class="flex flex-col p-6 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-blue-700 animate-bounce">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-2xl font-black text-gray-900">Giliran Anda Tiba!</h2>
                    <p id="turn-message" class="mt-2 text-base leading-relaxed text-gray-700 font-medium"></p>
                    <button
                        id="close-turn-dialog"
                        type="button"
                        class="mt-6 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-700 px-5 py-3 text-base font-bold text-white shadow-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Saya Mengerti / Menuju Ruang Periksa
                    </button>
                </div>
            </dialog>

            {{-- Footer --}}
            <footer class="py-6 text-center text-xs text-gray-500 border-t border-gray-200 bg-white">
                &copy; {{ date('Y') }} {{ $appName ?? 'Antrian Pasien' }} &bull; Sistem Antrian Klinik Dokter Mandiri
            </footer>
        </div>
    </body>
</html>
