@php
    $isSession = ($authMode ?? '') === 'session';
    $queueService = app(\App\Services\QueueService::class);
    $initialPractice = $queueService->practiceStatus();
    $initialNowServing = $queueService->nowServing()?->number_label ?? '--';

    if ($isSession) {
        $initialEntries = \App\Models\QueueEntry::today()
            ->orderByRaw("CASE status WHEN 'called' THEN 0 WHEN 'waiting' THEN 1 WHEN 'done' THEN 2 ELSE 3 END")
            ->orderBy('position')
            ->get();
        $initialEntriesData = \App\Http\Resources\QueueEntryResource::collection($initialEntries)->resolve();
    } else {
        $initialEntriesData = [];
    }
@endphp

<div
    id="staff-app"
    data-auth-mode="{{ $authMode }}"
    data-staff-name="{{ $staffName ?? '' }}"
    x-data="queueDashboard({
        authMode: '{{ $authMode }}',
        staffName: '{{ $staffName ?? '' }}',
        initialEntries: @js($initialEntriesData),
        initialPractice: @js($initialPractice),
        initialNowServing: @js($initialNowServing)
    })"
    class="flex flex-col gap-6"
>
    {{-- Feedback Alert --}}
    <div
        id="staff-feedback"
        x-show="feedback.show"
        x-transition
        :class="feedback.isError ? 'border-red-300 bg-red-50 text-red-800' : 'border-blue-200 bg-blue-50 text-blue-900'"
        class="flex items-center justify-between rounded-lg border px-4 py-3 text-base shadow-sm"
        role="status"
        aria-live="polite"
        style="display: none;"
    >
        <div class="flex items-center gap-3">
            <template x-if="feedback.isError">
                <svg class="h-5 w-5 text-red-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </template>
            <template x-if="!feedback.isError">
                <svg class="h-5 w-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </template>
            <span class="font-medium" x-text="feedback.message"></span>
        </div>
        <button type="button" @click="hideFeedback()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
            <span class="sr-only">Tutup</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    {{-- Login Panel (Khusus Token Mode Standalone di /petugas) --}}
    @if ($authMode === 'token')
        <section
            id="login-panel"
            x-show="!isLoggedIn"
            class="mx-auto w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Masuk petugas</h2>
                    <p class="text-sm text-gray-600">Otorisasi petugas & admin klinik.</p>
                </div>
            </div>

            <form id="login-form" @submit.prevent="handleLogin()" class="mt-6 flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <label for="username" class="text-base font-semibold text-gray-800">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        x-model="loginForm.username"
                        autocomplete="username"
                        required
                        placeholder="Masukkan username petugas..."
                        class="min-h-12 rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-base font-semibold text-gray-800">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        x-model="loginForm.password"
                        autocomplete="current-password"
                        required
                        placeholder="••••••••"
                        class="min-h-12 rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <button
                    id="login-button"
                    type="submit"
                    :disabled="loginLoading"
                    class="mt-2 flex min-h-12 items-center justify-center rounded-lg bg-blue-700 px-6 py-3 text-base font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400"
                >
                    <span x-show="!loginLoading">Masuk ke Dashboard</span>
                    <span x-show="loginLoading" class="flex items-center gap-2" style="display: none;">
                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Memeriksa akun...
                    </span>
                </button>
            </form>
        </section>
    @endif

    {{-- Main Dashboard Panel --}}
    <section
        id="dashboard-panel"
        @if ($authMode === 'token')
            x-show="isLoggedIn"
            :class="{ 'hidden': !isLoggedIn, 'flex': isLoggedIn }"
            class="{{ $authMode === 'token' ? 'hidden' : 'flex' }} flex-col gap-6"
        @else
            class="flex flex-col gap-6"
        @endif
    >
        {{-- Clinic Status & Quick Actions Bar --}}
        <div class="flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between lg:p-6">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider bg-gray-100 text-gray-800">
                        <svg class="h-3.5 w-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Klinik Dokter Mandiri
                    </span>
                    <span class="text-sm text-gray-500">|</span>
                    <span class="text-sm font-medium text-gray-600">
                        Petugas: <strong id="staff-name" class="text-gray-900" x-text="staffDisplay || '{{ $staffName ?? '' }}'">{{ $staffName ?? '' }}</strong>
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="h-3.5 w-3.5 rounded-full transition-colors"
                        :class="practice.is_open ? 'bg-green-500 ring-4 ring-green-100 animate-pulse' : 'bg-gray-400'"
                    ></div>
                    <p
                        id="practice-status"
                        class="text-lg font-bold"
                        :class="practice.is_open ? 'text-green-800' : 'text-gray-700'"
                        x-text="practice.message"
                    >{{ $initialPractice['message'] ?? 'Memuat status...' }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                {{-- Bell Chime Audio Toggle --}}
                <button
                    type="button"
                    @click="soundEnabled = !soundEnabled; if(soundEnabled) playBell()"
                    class="inline-flex min-h-12 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :title="soundEnabled ? 'Matikan nada bel panggilan' : 'Nyalakan nada bel panggilan'"
                >
                    <svg x-show="soundEnabled" class="h-5 w-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <svg x-show="!soundEnabled" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                    </svg>
                    <span x-text="soundEnabled ? 'Bel Aktif' : 'Bel Senyap'">Bel Aktif</span>
                </button>

                {{-- Buka / Tutup Praktek --}}
                <button
                    id="practice-button"
                    type="button"
                    @click="togglePractice()"
                    :disabled="busy || practice.state === 'libur'"
                    class="min-h-12 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-base font-bold text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                    x-text="practice.is_open ? 'Tutup praktek' : 'Buka praktek'"
                >
                    {{ ($initialPractice['is_open'] ?? false) ? 'Tutup praktek' : 'Buka praktek' }}
                </button>

                {{-- Panggil Berikutnya --}}
                <button
                    id="call-button"
                    type="button"
                    @click="callNext()"
                    :disabled="!canCallNext"
                    class="flex min-h-12 items-center gap-2 rounded-lg bg-blue-700 px-6 py-2.5 text-base font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    <span>Panggil berikutnya</span>
                </button>

                @if ($authMode === 'token')
                    <button
                        id="logout-button"
                        type="button"
                        @click="handleLogout()"
                        class="min-h-12 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-base font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Keluar
                    </button>
                @endif
            </div>
        </div>

        {{-- Clinical Metrics Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- Sedang Dipanggil --}}
            <article class="relative overflow-hidden rounded-xl border border-blue-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold uppercase tracking-wider text-blue-800">Sedang dipanggil</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                </div>
                <p id="now-serving" class="mt-3 text-4xl font-black text-gray-900" x-text="nowServing">{{ $initialNowServing }}</p>
                <p class="mt-1 text-xs text-gray-500">Pasien di ruang periksa dokter</p>
            </article>

            {{-- Menunggu --}}
            <article class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-600">Menunggu</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-yellow-50 text-yellow-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p id="waiting-count" class="mt-3 text-4xl font-black text-gray-900" x-text="waitingCount">{{ count(array_filter($initialEntriesData, fn ($e) => ($e['status'] ?? '') === 'waiting')) }}</p>
                <p class="mt-1 text-xs text-gray-500">Pasien berada di ruang tunggu</p>
            </article>

            {{-- Terdaftar Hari Ini --}}
            <article class="relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-600">Terdaftar hari ini</p>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                </div>
                <p id="registered-count" class="mt-3 text-4xl font-black text-gray-900" x-text="entries.length">{{ count($initialEntriesData) }}</p>
                <p class="mt-1 text-xs text-gray-500">Total nomor antrian terbit hari ini</p>
            </article>
        </div>

        {{-- Active Called Patient Spotlight (Jika ada yang sedang dipanggil) --}}
        <template x-if="activeEntry">
            <div class="rounded-xl border-2 border-blue-400 bg-blue-50/50 p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-2xl font-black text-white shadow">
                            <span x-text="activeEntry.number_label"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-blue-100 px-3 py-0.5 text-xs font-bold text-blue-900 uppercase">
                                    Sedang Dipanggil
                                </span>
                                <span class="text-xs font-semibold text-gray-600" x-text="'Panggilan ke-' + activeEntry.call_count + ' dari 3'"></span>
                            </div>
                            <h3 class="mt-1 text-2xl font-bold text-gray-900" x-text="activeEntry.patient?.name"></h3>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-700">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span x-text="activeEntry.patient?.age + ' tahun'"></span>
                                </span>
                                <span>•</span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="activeEntry.patient?.address"></span>
                                </span>
                            </div>
                            <div class="mt-3 rounded-lg border border-blue-200 bg-white px-3.5 py-2 text-sm text-gray-800">
                                <strong class="text-blue-950 font-semibold">Keluhan Sakit:</strong>
                                <span class="ml-1" x-text="activeEntry.patient?.complaint"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Spotlight Action Buttons --}}
                    <div class="flex flex-wrap items-center gap-2 sm:flex-col sm:items-end">
                        <button
                            type="button"
                            @click="doneEntry(activeEntry)"
                            :disabled="busy"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Selesai Pemeriksaan</span>
                        </button>

                        <button
                            type="button"
                            @click="callSpecific(activeEntry)"
                            :disabled="busy"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            <span>Panggil Ulang</span>
                        </button>

                        <button
                            type="button"
                            @click="skipEntry(activeEntry)"
                            :disabled="busy"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            <span>Lewati Pasien</span>
                        </button>

                        <button
                            type="button"
                            @click="cancelEntry(activeEntry)"
                            :disabled="busy"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50 disabled:opacity-50"
                        >
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>Batalkan</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Queue List Section --}}
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="queue-title">
            <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="queue-title" class="text-xl font-bold text-gray-900 sm:text-2xl">Antrian hari ini</h2>
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                            Live 5s
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Daftar antrian dan tindakan pelayanan pasien klinik.</p>
                </div>

                {{-- Filter & Search Controls --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Search Input --}}
                    <div class="relative min-w-[220px]">
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="Cari pasien / no antrian..."
                            class="min-h-11 w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                        <svg class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    {{-- Select Status Filter (Dipertahankan ID dan Options untuk Kompatibilitas) --}}
                    <select
                        id="status-filter"
                        x-model="statusFilter"
                        class="min-h-11 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="all">Semua antrian</option>
                        <option value="waiting">Menunggu</option>
                        <option value="called">Dipanggil</option>
                        <option value="done">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
            </div>

            {{-- Queue Cards Container --}}
            <div id="queue-list" class="mt-5 flex flex-col gap-3.5">
                <template x-for="entry in filteredEntries" :key="entry.id">
                    <article class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 transition-shadow hover:shadow-md sm:flex-row sm:items-center sm:justify-between sm:p-5">
                        <div class="flex items-start gap-4">
                            {{-- Number Badge --}}
                            <div
                                class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl font-bold shadow-sm"
                                :class="{
                                    'bg-yellow-100 text-yellow-900 border border-yellow-200': entry.status === 'waiting',
                                    'bg-blue-100 text-blue-900 border border-blue-300 ring-2 ring-blue-300': entry.status === 'called',
                                    'bg-green-100 text-green-900 border border-green-200': entry.status === 'done',
                                    'bg-gray-100 text-gray-500 border border-gray-200': entry.status === 'cancelled'
                                }"
                            >
                                <span class="text-xs font-semibold uppercase tracking-wider">No</span>
                                <span class="text-xl font-black leading-none" x-text="entry.number_label"></span>
                            </div>

                            {{-- Patient Clinical Details --}}
                            <div class="flex flex-col gap-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-bold text-gray-900" x-text="entry.patient?.name"></h3>
                                    {{-- Status Pill --}}
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': entry.status === 'waiting',
                                            'bg-blue-100 text-blue-800': entry.status === 'called',
                                            'bg-green-100 text-green-800': entry.status === 'done',
                                            'bg-gray-200 text-gray-700': entry.status === 'cancelled'
                                        }"
                                        x-text="{
                                            waiting: 'Menunggu',
                                            called: 'Sedang dipanggil',
                                            done: 'Selesai',
                                            cancelled: 'Dibatalkan'
                                        }[entry.status] || entry.status"
                                    ></span>

                                    <template x-if="entry.call_count > 0 && entry.status === 'called'">
                                        <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600" x-text="'Panggilan ke-' + entry.call_count"></span>
                                    </template>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600">
                                    <span>Usia: <strong class="text-gray-800" x-text="entry.patient?.age + ' thn'"></strong></span>
                                    <span>•</span>
                                    <span class="truncate max-w-xs" x-text="entry.patient?.address"></span>
                                </div>

                                <div class="text-sm text-gray-700">
                                    <span class="font-medium text-gray-900">Keluhan:</span>
                                    <span class="italic text-gray-700" x-text="entry.patient?.complaint"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Per-Item Action Buttons --}}
                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            {{-- Action for WAITING --}}
                            <template x-if="entry.status === 'waiting'">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        @click="callSpecific(entry)"
                                        :disabled="busy"
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                        <span>Panggil</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="openMoveModal(entry)"
                                        :disabled="busy"
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"
                                        title="Geser posisi nomor antrian ke bawah"
                                    >
                                        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                        <span>Geser</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="cancelEntry(entry)"
                                        :disabled="busy"
                                        class="inline-flex min-h-10 items-center gap-1 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50 disabled:opacity-50"
                                    >
                                        <span>Batalkan</span>
                                    </button>
                                </div>
                            </template>

                            {{-- Action for CALLED --}}
                            <template x-if="entry.status === 'called'">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        @click="doneEntry(entry)"
                                        :disabled="busy"
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-lg bg-green-700 px-3.5 py-2 text-sm font-bold text-white shadow-sm hover:bg-green-800 disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Selesai</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="skipEntry(entry)"
                                        :disabled="busy"
                                        class="inline-flex min-h-10 items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                    >
                                        <span>Lewati</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="cancelEntry(entry)"
                                        :disabled="busy"
                                        class="inline-flex min-h-10 items-center gap-1 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50"
                                    >
                                        <span>Batal</span>
                                    </button>
                                </div>
                            </template>

                            {{-- Indicator for DONE --}}
                            <template x-if="entry.status === 'done'">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-green-50 px-3 py-1.5 text-xs font-bold text-green-800">
                                    <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Selesai Diperiksa
                                </span>
                            </template>

                            {{-- Indicator for CANCELLED --}}
                            <template x-if="entry.status === 'cancelled'">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-600">
                                    <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                    Batal Periksa
                                </span>
                            </template>
                        </div>
                    </article>
                </template>
            </div>

            {{-- Empty State (Dipertahankan ID dan Class untuk Kompatibilitas) --}}
            <div
                id="empty-queue"
                x-show="filteredEntries.length === 0"
                class="mt-6 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center"
                style="display: none;"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-sm text-gray-400">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <h4 class="mt-4 text-lg font-bold text-gray-900">Belum ada antrian pasien</h4>
                <p class="mt-1 max-w-sm text-sm text-gray-600">Belum ada data antrian untuk status ini.</p>
            </div>
        </section>
    </section>

    {{-- Modal Geser Antrian Pasien --}}
    <div
        x-show="moveModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="closeMoveModal()"
        style="display: none;"
    >
        <div
            @click.away="closeMoveModal()"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Geser Urutan Antrian</h3>
                    <p class="text-sm text-gray-500">Pasien izin keluar sementara / pulang dulu.</p>
                </div>
            </div>

            <template x-if="moveTargetEntry">
                <div class="mt-4 rounded-lg bg-gray-50 p-3.5 text-sm text-gray-700">
                    <p>Nomor Antrian: <strong class="text-gray-900 font-bold" x-text="moveTargetEntry.number_label"></strong></p>
                    <p>Nama Pasien: <span class="font-medium text-gray-900" x-text="moveTargetEntry.patient?.name"></span></p>
                    <p class="mt-2 text-xs text-gray-500">*Nomor antrian pasien tetap sama, yang berubah adalah urutan panggil.</p>
                </div>
            </template>

            <div class="mt-5 flex flex-col gap-2">
                <label for="move-steps-input" class="text-sm font-semibold text-gray-800">Geser berapa langkah ke bawah?</label>
                <select
                    id="move-steps-input"
                    x-model="moveSteps"
                    class="rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="1">1 urutan ke bawah</option>
                    <option value="2">2 urutan ke bawah (standar)</option>
                    <option value="3">3 urutan ke bawah</option>
                    <option value="5">5 urutan ke bawah</option>
                    <option value="10">10 urutan ke bawah</option>
                </select>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="closeMoveModal()"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="confirmMove()"
                    :disabled="busy"
                    class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-800 disabled:opacity-50"
                >
                    Konfirmasi Geser
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Batalkan Antrian (Tanpa Bootstrap / Tailwind + Alpine) --}}
    <div
        x-show="cancelModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 backdrop-blur-sm p-4"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="closeCancelModal()"
        style="display: none;"
    >
        <div
            @click.away="closeCancelModal()"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-gray-100"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
        >
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900">Batalkan Antrian Pasien?</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Apakah Anda yakin ingin membatalkan antrian ini? Pasien yang dibatalkan tidak dapat dipanggil kembali.
                    </p>
                </div>
            </div>

            <template x-if="cancelTargetEntry">
                <div class="mt-4 rounded-xl border border-red-100 bg-red-50/60 p-4 text-sm text-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-red-700">Nomor Antrian</span>
                        <span class="rounded-md bg-red-200 px-2.5 py-0.5 text-base font-black text-red-900" x-text="cancelTargetEntry.number_label"></span>
                    </div>
                    <div class="mt-2.5 space-y-1.5 text-sm text-gray-700">
                        <p><strong class="font-semibold text-gray-900">Nama Pasien:</strong> <span class="font-medium text-gray-900" x-text="cancelTargetEntry.patient?.name"></span></p>
                        <p><strong class="font-semibold text-gray-900">Usia:</strong> <span x-text="cancelTargetEntry.patient?.age + ' tahun'"></span></p>
                        <p><strong class="font-semibold text-gray-900">Keluhan:</strong> <span class="italic text-gray-800" x-text="cancelTargetEntry.patient?.complaint || '-'"></span></p>
                    </div>
                </div>
            </template>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="closeCancelModal()"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400"
                >
                    Tidak, Kembali
                </button>
                <button
                    type="button"
                    @click="confirmCancel()"
                    :disabled="busy"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Ya, Batalkan Antrian</span>
                </button>
            </div>
        </div>
    </div>
</div>
