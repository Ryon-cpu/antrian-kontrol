<script setup>
import QueueRegistration from './QueueRegistration.vue';
import { Toaster } from 'vue-sonner';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50 text-slate-950">
        <Toaster position="top-right" rich-colors close-button />

        <nav class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="/" class="flex min-w-0 items-center gap-3">
                    <img
                        :src="props.config.logoUrl"
                        :alt="`Logo ${props.config.appName}`"
                        class="h-10 w-10 shrink-0 rounded-full border border-slate-200 object-cover"
                    >
                    <span class="truncate text-lg font-semibold tracking-tight sm:text-xl">
                        {{ props.config.appName }}
                    </span>
                </a>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <button
                        id="install-app"
                        type="button"
                        class="inline-flex min-h-10 items-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0l-4-4m4 4l4-4M5 17v2a2 2 0 002 2h10a2 2 0 002-2v-2" />
                        </svg>
                        <span class="hidden sm:inline">Pasang aplikasi</span>
                    </button>
                    <span
                        class="inline-flex min-h-8 items-center gap-2 rounded-full px-3 text-xs font-medium"
                        :class="props.config.practice.is_open
                            ? 'bg-emerald-50 text-emerald-800'
                            : 'bg-slate-100 text-slate-700'"
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="props.config.practice.is_open ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'"
                        ></span>
                        <span class="hidden sm:inline">{{ props.config.practice.message }}</span>
                        <span class="sm:hidden">{{ props.config.practice.is_open ? 'Buka' : 'Tutup' }}</span>
                    </span>
                </div>
            </div>
        </nav>

        <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
            <QueueRegistration
                :practice-open="props.config.practice.is_open"
                :queue-full="props.config.queue.is_full"
                :practice-message="props.config.practice.message"
            />

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-label="Informasi tempat dan dokter">
                <article class="flex flex-col justify-between gap-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="space-y-5">
                        <div class="flex items-center gap-3 text-slate-600">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-800">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a2 2 0 014 0v5" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium">Praktik Mandiri (DPM)</span>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">
                                {{ props.config.place.name || 'Informasi tempat' }}
                            </h2>
                            <span
                                v-if="props.config.place.type"
                                class="mt-3 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700"
                            >
                                Dokter Praktik Mandiri (Pelayanan Medis Umum)
                            </span>
                        </div>

                        <div class="space-y-4 text-sm leading-6 text-slate-600">
                            <div v-if="props.config.place.address" class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1116 0z" />
                                    <circle cx="12" cy="10" r="2.5" stroke-width="1.8" />
                                </svg>
                                <p>{{ props.config.place.address }}</p>
                            </div>
                            <div v-if="props.config.place.description" class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 11v5m0-8h.01" />
                                </svg>
                                <p>{{ props.config.place.description }}</p>
                            </div>
                            <div v-if="props.config.place.phone" class="flex items-center gap-3">
                                <svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 3h4l2 5-3 2a16 16 0 006 6l2-3 5 2v4a2 2 0 01-2 2C10 21 3 14 3 5a2 2 0 012-2z" />
                                </svg>
                                <p>{{ props.config.place.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="props.config.place.practice_hours_note"
                        class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm"
                    >
                        <p class="font-medium text-slate-900">Jadwal praktik</p>
                        <p class="mt-1 whitespace-pre-line leading-6 text-slate-600">
                            {{ props.config.place.practice_hours_note }}
                        </p>
                    </div>
                </article>

                <article class="flex flex-col justify-between gap-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="space-y-5">
                        <div class="flex items-center gap-3 text-slate-600">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-800">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke="currentColor" stroke-width="1.8" d="M5 21a7 7 0 0114 0" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium">Profil dokter</span>
                        </div>

                        <h2 class="text-xl font-semibold tracking-tight sm:text-2xl">Informasi dokter</h2>

                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <img
                                v-if="props.config.doctor.photo_url"
                                :src="props.config.doctor.photo_url"
                                :alt="`Foto ${props.config.doctor.name || 'dokter'}`"
                                class="h-36 w-36 shrink-0 rounded-lg border border-slate-200 object-cover"
                                loading="lazy"
                            >
                            <div
                                v-else
                                class="flex h-36 w-36 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"
                                aria-hidden="true"
                            >
                                <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle cx="12" cy="8" r="4" stroke-width="1.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 21a7 7 0 0114 0" />
                                </svg>
                            </div>

                            <div class="min-w-0 space-y-3">
                                <h3 class="text-2xl font-bold tracking-tight">
                                    {{ `${props.config.doctor.name || ''} ${props.config.doctor.title || ''}`.trim() || 'Informasi dokter belum tersedia' }}
                                </h3>
                                <p
                                    v-if="props.config.doctor.bio"
                                    class="whitespace-pre-line rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600"
                                >
                                    {{ props.config.doctor.bio }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Informasi profil dikelola oleh pengelola klinik.</span>
                    </div>
                </article>
            </section>

            <section
                v-if="props.config.achievements.length"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"
                aria-labelledby="achievements-title"
            >
                <header class="space-y-2 border-b border-slate-200 pb-5">
                    <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                        <svg class="h-5 w-5 text-slate-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l2.2 4.45 4.91.71-3.55 3.46.84 4.89L12 14.2l-4.4 2.31.84-4.89L4.9 8.16l4.9-.71L12 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16.5V21l4-2 4 2v-4.5" />
                        </svg>
                        Rekam jejak &amp; pengabdian
                    </div>
                    <h2 id="achievements-title" class="text-xl font-semibold tracking-tight sm:text-2xl">
                        Prestasi dokter
                    </h2>
                    <p class="text-sm leading-6 text-slate-500">
                        Informasi prestasi dan kegiatan yang dimasukkan oleh pengelola.
                    </p>
                </header>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="achievement in props.config.achievements"
                        :key="achievement.id"
                        class="flex flex-col gap-4 rounded-lg border border-slate-200 bg-white p-4 transition-shadow hover:shadow-md"
                    >
                        <img
                            v-if="achievement.image_url"
                            :src="achievement.image_url"
                            alt=""
                            class="h-36 w-full rounded-md object-cover"
                            loading="lazy"
                        >
                        <div
                            v-else
                            class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-100 text-slate-700"
                            aria-hidden="true"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l2.2 4.45 4.91.71-3.55 3.46.84 4.89L12 14.2l-4.4 2.31.84-4.89L4.9 8.16l4.9-.71L12 3z" />
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-base font-semibold leading-snug">{{ achievement.title }}</h3>
                            <p v-if="achievement.description" class="text-sm leading-6 text-slate-600">
                                {{ achievement.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500">
                &copy; {{ props.config.currentYear }} {{ props.config.appName }} &bull; Sistem Antrean Pasien &amp; Pelayanan Medis Mandiri
            </footer>
        </main>
    </div>
</template>
