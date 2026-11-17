<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Dashboard Admin
        </h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Area manajemen</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-900">Panel administrasi klinik</h1>
                </div>
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                    Akses admin aktif
                </span>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 inline-flex rounded-lg bg-blue-100 p-2 text-blue-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.292 4 4 0 010-8.292zm-7 14.5A7 7 0 0112 14a7 7 0 017 4.854" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Kelola petugas</h2>
                <p class="mt-2 text-sm text-slate-600">Tambah, aktifkan, atau nonaktifkan akun petugas yang bertugas di klinik.</p>
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 inline-flex rounded-lg bg-violet-100 p-2 text-violet-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.013 1.724 1.724 0 011.672 2.973c.715.836 1.013 2.024.618 3.075a1.724 1.724 0 011.013 2.573 1.724 1.724 0 01-1.013 2.573c-.836.715-2.024 1.013-3.075.618a1.724 1.724 0 01-2.573 1.013 1.724 1.724 0 01-2.573-1.013c-.715-.836-1.013-2.024-.618-3.075a1.724 1.724 0 01-1.013-2.573 1.724 1.724 0 011.013-2.573c.836-.715 2.024-1.013 3.075-.618A1.724 1.724 0 0110.325 4.317z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Pengaturan klinik</h2>
                <p class="mt-2 text-sm text-slate-600">Ubah nama klinik, informasi dokter, jam operasional, dan data publik yang tampil di halaman utama.</p>
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 inline-flex rounded-lg bg-emerald-100 p-2 text-emerald-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-6m3 6V7m-8 10h10M5 21h14a2 2 0 002-2V7l-4-4H7L3 7v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-slate-900">Laporan harian</h2>
                <p class="mt-2 text-sm text-slate-600">Pantau jumlah antrian, pasien selesai, dibatalkan, dan yang pernah dilewati untuk audit operasional.</p>
            </article>
        </div>

        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700 shadow-sm">
            <p class="font-semibold text-slate-900">Catatan:</p>
            <p class="mt-2">Area operasional antrian tetap berada di panel petugas, sedangkan admin fokus pada pengelolaan pengguna, pengaturan klinik, dan laporan.</p>
            <a href="{{ route('petugas.dashboard') }}" class="mt-4 inline-flex min-h-10 items-center rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800">
                Buka dashboard petugas &amp; riwayat pasien
            </a>
        </div>
    </div>
</x-app-layout>
