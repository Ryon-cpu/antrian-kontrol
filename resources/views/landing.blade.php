<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f4f6">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ $app_name }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" href="{{ asset('icons/antrian.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('icons/antrian-192.png') }}">
        <title>{{ $app_name }} | Pendaftaran Antrian</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/landing.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div
            id="queue-app"
            class="min-h-screen bg-gray-100"
            data-practice-open="{{ $practice['is_open'] ? 'true' : 'false' }}"
            data-queue-full="{{ $queue['is_full'] ? 'true' : 'false' }}"
        >
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                    <a href="{{ route('landing') }}" class="text-xl font-bold text-gray-900 sm:text-2xl">
                        {{ $app_name }}
                    </a>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button id="install-app" type="button" class="min-h-11 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Pasang aplikasi
                        </button>
                        <span class="rounded-full px-4 py-2 text-sm font-semibold {{ $practice['is_open'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $practice['message'] }}
                        </span>
                    </div>
                </div>
            </header>

            <main class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
                <section aria-labelledby="registration-title" class="order-first rounded-lg bg-white p-5 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-3">
                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Pendaftaran pemeriksaan</p>
                        <h1 id="registration-title" class="text-2xl font-bold leading-tight text-gray-900 sm:text-3xl">
                            Daftarkan pasien
                        </h1>
                        <p class="text-lg leading-relaxed text-gray-700">
                            Isi data pasien untuk mendapatkan nomor antrian. Data pasien hanya dapat dilihat petugas dan admin.
                        </p>
                    </div>

                    <div id="queue-feedback" class="mt-6 hidden rounded-md border px-4 py-3 text-base" role="status" aria-live="polite"></div>

                    <form id="registration-form" class="mt-6">
                        <fieldset id="registration-fields" class="flex flex-col gap-5" {{ (! $practice['is_open'] || $queue['is_full']) ? 'disabled' : '' }}>
                            <div class="flex flex-col gap-2">
                                <label for="patient_name" class="text-base font-semibold text-gray-800">Nama pasien</label>
                                <input id="patient_name" name="patient_name" type="text" autocomplete="name" maxlength="100" required class="min-h-12 rounded-md border-gray-300 text-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="patient_age" class="text-base font-semibold text-gray-800">Usia pasien</label>
                                <input id="patient_age" name="patient_age" type="number" inputmode="numeric" min="0" max="130" required class="min-h-12 rounded-md border-gray-300 text-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="patient_address" class="text-base font-semibold text-gray-800">Alamat pasien</label>
                                <textarea id="patient_address" name="patient_address" rows="2" maxlength="255" required class="rounded-md border-gray-300 text-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"></textarea>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="complaint" class="text-base font-semibold text-gray-800">Keterangan sakit</label>
                                <textarea id="complaint" name="complaint" rows="3" maxlength="500" required class="rounded-md border-gray-300 text-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"></textarea>
                            </div>
                        </fieldset>

                        <button id="submit-registration" type="submit" class="mt-6 min-h-14 w-full rounded-md border border-transparent bg-gray-800 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400">
                            Daftarkan pasien
                        </button>
                    </form>

                    @if (! $practice['is_open'])
                        <p class="mt-4 text-base font-semibold text-gray-700">{{ $practice['message'] }} Pendaftaran akan dibuka oleh petugas.</p>
                    @elseif ($queue['is_full'])
                        <p class="mt-4 text-base font-semibold text-red-700">Antrian hari ini penuh.</p>
                    @endif
                </section>

                <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-label="Informasi tempat dan dokter">
                    <article class="rounded-lg bg-white p-5 shadow-sm sm:p-8">
                        <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ $place['name'] ?: 'Informasi tempat' }}</h2>
                        <div class="mt-5 flex flex-col gap-3 text-lg leading-relaxed text-gray-700">
                            @if ($place['type'])
                                <p class="capitalize">{{ str_replace('_', ' ', $place['type']) }}</p>
                            @endif
                            @if ($place['address'])
                                <p>{{ $place['address'] }}</p>
                            @endif
                            @if ($place['phone'])
                                <p>{{ $place['phone'] }}</p>
                            @endif
                            @if ($place['description'])
                                <p>{{ $place['description'] }}</p>
                            @endif
                            @if ($place['practice_hours_note'])
                                <p><span class="font-semibold">Jam praktek:</span> {{ $place['practice_hours_note'] }}</p>
                            @endif
                        </div>
                    </article>

                    <article class="rounded-lg bg-white p-5 shadow-sm sm:p-8">
                        <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">Informasi dokter</h2>
                        <div class="mt-5 flex flex-col gap-4 text-lg leading-relaxed text-gray-700">
                            @if ($doctor['photo_url'])
                                <img src="{{ $doctor['photo_url'] }}" alt="Foto {{ $doctor['name'] ?: 'dokter' }}" class="h-48 w-48 rounded-lg object-cover">
                            @endif
                            <p class="font-semibold text-gray-900">
                                {{ trim(($doctor['name'] ?? '').' '.($doctor['title'] ?? '')) ?: 'Informasi dokter belum tersedia' }}
                            </p>
                            @if ($doctor['bio'])
                                <p>{{ $doctor['bio'] }}</p>
                            @endif
                        </div>
                    </article>
                </section>

                @if ($achievements->isNotEmpty())
                    <section class="rounded-lg bg-white p-5 shadow-sm sm:p-8" aria-labelledby="achievements-title">
                        <h2 id="achievements-title" class="text-xl font-bold text-gray-900 sm:text-2xl">Prestasi dokter</h2>
                        <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($achievements as $achievement)
                                <article class="flex flex-col gap-3 rounded-lg border border-gray-200 p-4">
                                    @if ($achievement['image_url'])
                                        <img src="{{ $achievement['image_url'] }}" alt="" class="h-40 w-full rounded-md object-cover">
                                    @endif
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $achievement['title'] }}</h3>
                                    @if ($achievement['description'])
                                        <p class="text-base leading-relaxed text-gray-700">{{ $achievement['description'] }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <footer class="pb-6 text-center text-sm text-gray-600">
                    {{ $app_name }}
                </footer>
            </main>
        </div>
    </body>
</html>
