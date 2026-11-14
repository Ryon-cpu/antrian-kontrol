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
    <div id="queue-app" class="min-h-screen flex flex-col"
        data-practice-open="{{ $practice['is_open'] ? 'true' : 'false' }}"
        data-queue-full="{{ $queue['is_full'] ? 'true' : 'false' }}">
        {{-- Top Navbar --}}
        <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                            <img src="{{ asset('icons/antrian-512.png?v=' . filemtime(public_path('icons/antrian-512.png'))) }}" alt="Logo {{ $app_name }}"
                                class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm">
                            <span class="text-xl font-bold text-gray-900 tracking-tight sm:text-2xl">
                                {{ $app_name }}
                            </span>
                        </a>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="install-app" type="button"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Pasang aplikasi</span>
                        </button>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold {{ $practice['is_open'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                            <span
                                class="h-2 w-2 rounded-full {{ $practice['is_open'] ? 'bg-green-500 animate-pulse' : 'bg-gray-400' }}"></span>
                            {{ $practice['message'] }}
                        </span>
                    </div>
                </div>
            </div>
        </nav>
        {{-- Main Content --}}
        <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 flex-1 flex flex-col gap-8">
            {{-- Form Pendaftaran Pemeriksaan (Wajib Paling Atas) --}}
            <section aria-labelledby="registration-title"
                class="order-first rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-2 border-b border-gray-100 pb-5">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-blue-700">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Pendaftaran pemeriksaan
                        </span>
                    </div>
                    <h1 id="registration-title" class="text-base font-bold text-gray-900 sm:text-2xl">
                        Daftarkan pasien
                    </h1>
                    <p class="text-base text-gray-600 sm:text-lg">
                        Isi data pasien untuk mendapatkan nomor antrian. Data pasien hanya dapat dilihat petugas dan
                        admin.
                    </p>
                </div>

                <div id="queue-feedback" class="mt-6 hidden rounded-lg border px-4 py-3 text-base shadow-sm"
                    role="status" aria-live="polite"></div>

                <form id="registration-form" class="mt-6">
                    <fieldset id="registration-fields" class="flex flex-col gap-5"
                        {{ !$practice['is_open'] || $queue['is_full'] ? 'disabled' : '' }}>
                        <div class="flex flex-col gap-2">
                            <label for="patient_name" class="text-base font-semibold text-gray-800">Nama pasien</label>
                            <input id="patient_name" name="patient_name" type="text" autocomplete="name"
                                maxlength="100" required placeholder="Nama lengkap pasien yang akan diperiksa..."
                                class="min-h-12 rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="patient_age" class="text-base font-semibold text-gray-800">Usia pasien</label>
                            <input id="patient_age" name="patient_age" type="number" inputmode="numeric" min="0"
                                max="130" required placeholder="Contoh: 45"
                                class="min-h-12 rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="patient_address" class="text-base font-semibold text-gray-800">Alamat
                                pasien</label>
                            <textarea id="patient_address" name="patient_address" rows="2" maxlength="255" required
                                placeholder="Dusun / Desa / Kecamatan tempat tinggal pasien..."
                                class="rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"></textarea>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="complaint" class="text-base font-semibold text-gray-800">Keterangan
                                sakit</label>
                            <textarea id="complaint" name="complaint" rows="3" maxlength="500" required
                                placeholder="Keluhan atau gejala yang dirasakan pasien..."
                                class="rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"></textarea>
                        </div>
                    </fieldset>

                    <button id="submit-registration" type="submit"
                        class="mt-6 min-h-12 w-full rounded-lg bg-blue-700 px-6 py-3 text-base font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400">
                        Daftarkan pasien
                    </button>
                </form>

                @if (!$practice['is_open'])
                    <div
                        class="mt-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm font-semibold text-yellow-800">
                        {{ $practice['message'] }} Pendaftaran akan dibuka oleh petugas.
                    </div>
                @elseif ($queue['is_full'])
                    <div
                        class="mt-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm font-semibold text-red-800">
                        Antrian hari ini penuh.
                    </div>
                @endif
            </section>

            {{-- Informasi Tempat & Informasi Dokter --}}
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-label="Informasi tempat dan dokter">
                {{-- Informasi Tempat Praktik --}}
                <article
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-blue-700">
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Praktik Mandiri (DPM)</span>
                        </div>

                        <h2 class="mt-2 text-xl font-bold text-gray-900 sm:text-2xl">
                            {{ $place['name'] ?: 'Informasi tempat' }}
                        </h2>

                        <div class="mt-4 flex flex-col gap-3.5 text-base text-gray-700">
                            @if ($place['type'])
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-blue-700">
                                        Dokter Praktik Mandiri (Pelayanan Medis Umum)
                                    </span>
                                </div>
                            @endif

                            @if ($place['address'])
                                <div class="flex items-start gap-2.5">
                                    <svg class="h-5 w-5 text-gray-400 shrink-0 mt-0.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="leading-relaxed">{{ $place['address'] }}</p>
                                </div>
                            @endif

                            @if ($place['description'])
                                <div class="flex items-start gap-2.5">
                                    <svg class="h-5 w-5 text-gray-400 shrink-0 mt-0.5" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="leading-relaxed">{{ $place['description'] }}</p>
                                </div>
                            @endif

                            @if ($place['phone'])
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-5 w-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <p>{{ $place['phone'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($place['practice_hours_note'])
                        <div class="mt-6 rounded-xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-700">
                            <span class="font-bold text-gray-900 block">Jadwal Praktik:</span>
                            <p class="mt-0.5 text-gray-600">{{ $place['practice_hours_note'] }}</p>
                        </div>
                    @endif
                </article>

                {{-- Informasi Dokter --}}
                <article
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-blue-700">
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Profil Dokter</span>
                        </div>

                        <h2 class="mt-2 text-xl font-bold text-gray-900 sm:text-2xl">Informasi dokter</h2>

                        <div class="mt-4 flex flex-col gap-4">
                            @if ($doctor['photo_url'])
                                <img src="{{ $doctor['photo_url'] }}" alt="Foto {{ $doctor['name'] ?: 'dokter' }}"
                                    class="h-44 w-44 rounded-xl object-cover shadow-sm">
                            @endif

                            <div>
                                <h3 class="text-2xl font-black text-gray-900">
                                    {{ trim(($doctor['name'] ?? '') . ' ' . ($doctor['title'] ?? '')) ?: 'Informasi dokter belum tersedia' }}
                                </h3>
                            </div>

                            @if ($doctor['bio'])
                                <div
                                    class="rounded-xl border border-blue-50 bg-blue-50/40 p-4 text-sm text-gray-700 leading-relaxed">
                                    <p class="whitespace-pre-line">{{ $doctor['bio'] }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex items-center gap-2 text-xs text-gray-500">
                        <svg class="h-4 w-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>Informasi profil dikelola oleh pengelola klinik.</span>
                    </div>
                </article>
            </section>

            {{-- Prestasi & Rekam Jejak Dokter --}}
            @if ($achievements->isNotEmpty())
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8"
                    aria-labelledby="achievements-title">
                    <div class="border-b border-gray-100 pb-4">
                        <div class="flex items-center gap-2 text-blue-700">
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Rekam Jejak & Pengabdian</span>
                        </div>
                        <h2 id="achievements-title" class="mt-2 text-xl font-bold text-gray-900 sm:text-2xl">Prestasi dokter</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Informasi prestasi dan kegiatan yang dimasukkan oleh pengelola.
                        </p>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($achievements as $achievement)
                            <article
                                class="flex flex-col justify-between rounded-xl border border-gray-200 bg-gray-50/50 p-5 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex flex-col gap-2.5">
                                    @if ($achievement['image_url'])
                                        <img src="{{ $achievement['image_url'] }}" alt=""
                                            class="h-36 w-full rounded-lg object-cover">
                                    @else
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <h3 class="text-base font-bold text-gray-900 leading-snug">
                                        {{ $achievement['title'] }}</h3>
                                    @if ($achievement['description'])
                                        <p class="text-sm leading-relaxed text-gray-600">
                                            {{ $achievement['description'] }}</p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Footer --}}
            <footer class="py-6 text-center text-xs text-gray-500 border-t border-gray-200">
                &copy; {{ date('Y') }} {{ $app_name }} &bull; Sistem Antrian Pasien & Pelayanan Medis Mandiri
            </footer>
        </main>
    </div>
</body>

</html>
