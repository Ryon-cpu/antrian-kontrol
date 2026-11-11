<div
    id="staff-app"
    data-auth-mode="{{ $authMode }}"
    data-staff-name="{{ $staffName ?? '' }}"
    class="flex flex-col gap-6"
>
    <div id="staff-feedback" class="hidden rounded-md border px-4 py-3 text-base" role="status" aria-live="polite"></div>

    @if ($authMode === 'token')
        <section id="login-panel" class="mx-auto w-full max-w-lg rounded-lg bg-white p-5 shadow-sm sm:p-8">
            <h2 class="text-2xl font-bold text-gray-900">Masuk petugas</h2>
            <p class="mt-2 text-base leading-relaxed text-gray-700">Gunakan username dan password akun petugas atau admin.</p>
            <form id="login-form" class="mt-6 flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <label for="username" class="text-base font-semibold text-gray-800">Username</label>
                    <input id="username" name="username" type="text" autocomplete="username" required class="min-h-12 rounded-md border-gray-300 text-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-base font-semibold text-gray-800">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="min-h-12 rounded-md border-gray-300 text-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button id="login-button" type="submit" class="min-h-14 rounded-md bg-gray-800 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400">
                    Masuk
                </button>
            </form>
        </section>
    @endif

    <section id="dashboard-panel" class="{{ $authMode === 'token' ? 'hidden' : '' }} flex-col gap-6">
        <div class="flex flex-col gap-4 rounded-lg bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="flex flex-col gap-1">
                <p class="text-base text-gray-600">Petugas masuk sebagai</p>
                <p id="staff-name" class="text-xl font-bold text-gray-900">{{ $staffName ?? '' }}</p>
                <p id="practice-status" class="text-base font-semibold"></p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <button id="practice-button" type="button" class="min-h-14 rounded-md bg-gray-800 px-6 py-3 text-lg font-bold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                    Buka praktek
                </button>
                <button id="call-button" type="button" class="min-h-14 rounded-md bg-blue-700 px-6 py-3 text-lg font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400">
                    Panggil berikutnya
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-base font-semibold text-gray-600">Sedang dipanggil</p>
                <p id="now-serving" class="mt-2 text-4xl font-bold text-gray-900">--</p>
            </article>
            <article class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-base font-semibold text-gray-600">Menunggu</p>
                <p id="waiting-count" class="mt-2 text-4xl font-bold text-gray-900">0</p>
            </article>
            <article class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-base font-semibold text-gray-600">Terdaftar hari ini</p>
                <p id="registered-count" class="mt-2 text-4xl font-bold text-gray-900">0</p>
            </article>
        </div>

        <section class="rounded-lg bg-white p-5 shadow-sm sm:p-6" aria-labelledby="queue-title">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex flex-col gap-1">
                    <h2 id="queue-title" class="text-xl font-bold text-gray-900 sm:text-2xl">Antrian hari ini</h2>
                    <p class="text-base text-gray-600">Daftar diperbarui otomatis setiap lima detik.</p>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="status-filter" class="text-base font-semibold text-gray-800">Tampilkan status</label>
                    <select id="status-filter" class="min-h-12 rounded-md border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">Semua antrian</option>
                        <option value="waiting">Menunggu</option>
                        <option value="called">Dipanggil</option>
                        <option value="done">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
            </div>
            <div id="queue-list" class="mt-5 flex flex-col gap-4"></div>
            <p id="empty-queue" class="mt-5 hidden rounded-md bg-gray-50 px-4 py-6 text-center text-lg text-gray-600">Belum ada data antrian untuk status ini.</p>
        </section>
    </section>
</div>
