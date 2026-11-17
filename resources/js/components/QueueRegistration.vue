<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    practiceOpen: {
        type: Boolean,
        required: true,
    },
    queueFull: {
        type: Boolean,
        required: true,
    },
    practiceMessage: {
        type: String,
        required: true,
    },
});

const tokenKey = 'antrian_device_token';
const submitting = ref(false);
const form = reactive({
    patient_name: '',
    patient_age: '',
    patient_address: '',
    complaint: '',
});
const registrationDisabled = computed(() => !props.practiceOpen || props.queueFull);

function showFeedback(message, isError = false) {
    if (isError) {
        toast.error(message);
        return;
    }

    toast.success(message);
}

async function submitRegistration() {
    if (registrationDisabled.value || submitting.value) {
        return;
    }

    submitting.value = true;

    try {
        const token = window.localStorage.getItem(tokenKey);
        const payload = {
            ...form,
            patient_age: Number(form.patient_age),
        };
        const response = await fetch('/api/companion/register', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(token ? { 'X-Device-Token': token } : {}),
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message ?? Object.values(data.errors ?? {}).flat()[0] ?? 'Pendaftaran belum berhasil.');
        }

        window.localStorage.setItem(tokenKey, data.device_token);
        Object.assign(form, {
            patient_name: '',
            patient_age: '',
            patient_address: '',
            complaint: '',
        });
        window.location.assign('/antrian');
    } catch (error) {
        const message = error instanceof Error
            ? error.message
            : 'Koneksi gagal. Periksa jaringan lalu coba lagi.';
        await showFeedback(message, true);
    } finally {
        submitting.value = false;
    }
}

function handleExternalFeedback(event) {
    if (typeof event.detail?.message === 'string') {
        showFeedback(event.detail.message, Boolean(event.detail.isError));
    }
}

onMounted(() => {
    window.addEventListener('queue-feedback', handleExternalFeedback);
});

onBeforeUnmount(() => {
    window.removeEventListener('queue-feedback', handleExternalFeedback);
});
</script>

<template>
    <section
        class="order-first w-full overflow-hidden rounded-xl border border-slate-200 bg-white text-slate-950 shadow-sm"
        aria-labelledby="registration-title">
        <header class="space-y-2 border-b border-slate-200 px-6 py-6 sm:px-8">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-500">
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 5h8m-8 4h8m-8 4h5M7 3.75h7.586a2 2 0 011.414.586l3.664 3.664a2 2 0 01.586 1.414V19.5A1.5 1.5 0 0118.75 21h-12A1.5 1.5 0 015.25 19.5v-14A1.75 1.75 0 017 3.75z" />
                    </svg>
                </span>
                Pendaftaran pemeriksaan
            </div>
            <h1 id="registration-title" class="text-2xl font-semibold leading-none tracking-tight">
                Daftarkan pasien
            </h1>
            <p class="text-sm leading-6 text-slate-500">
                Isi data pasien untuk mendapatkan nomor antrean. Data pasien hanya dapat dilihat petugas dan admin.
            </p>
        </header>

        <div class="space-y-6 px-6 py-6 sm:px-8 sm:py-8">
            <form id="registration-form" class="space-y-5" @submit.prevent="submitRegistration()">
                <fieldset
                    id="registration-fields"
                    class="grid gap-5 sm:grid-cols-2"
                    :disabled="registrationDisabled">
                    <label class="space-y-2 text-sm font-medium leading-none" for="patient_name">
                        <span class="block">Nama pasien <span class="text-red-600" aria-hidden="true">*</span></span>
                        <input
                            id="patient_name"
                            v-model="form.patient_name"
                            name="patient_name"
                            type="text"
                            autocomplete="name"
                            maxlength="100"
                            required
                            placeholder="Nama lengkap pasien"
                            class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-normal text-slate-950 ring-offset-white placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    </label>

                    <label class="space-y-2 text-sm font-medium leading-none" for="patient_age">
                        <span class="block">Usia pasien <span class="text-red-600" aria-hidden="true">*</span></span>
                        <input
                            id="patient_age"
                            v-model="form.patient_age"
                            name="patient_age"
                            type="number"
                            inputmode="numeric"
                            min="0"
                            max="130"
                            required
                            placeholder="Contoh: 45"
                            class="flex h-10 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-normal text-slate-950 ring-offset-white placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                    </label>

                    <label class="space-y-2 text-sm font-medium leading-none sm:col-span-2" for="patient_address">
                        <span class="block">Alamat pasien <span class="text-red-600" aria-hidden="true">*</span></span>
                        <textarea
                            id="patient_address"
                            v-model="form.patient_address"
                            name="patient_address"
                            rows="3"
                            maxlength="255"
                            required
                            placeholder="Dusun / Desa / Kecamatan tempat tinggal pasien"
                            class="flex min-h-20 w-full resize-y rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-normal text-slate-950 ring-offset-white placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        ></textarea>
                    </label>

                    <label class="space-y-2 text-sm font-medium leading-none sm:col-span-2" for="complaint">
                        <span class="block">Keterangan sakit <span class="text-red-600" aria-hidden="true">*</span></span>
                        <textarea
                            id="complaint"
                            v-model="form.complaint"
                            name="complaint"
                            rows="3"
                            maxlength="500"
                            required
                            placeholder="Keluhan atau gejala yang dirasakan pasien"
                            class="flex min-h-20 w-full resize-y rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-normal text-slate-950 ring-offset-white placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        ></textarea>
                    </label>
                </fieldset>

                <p class="text-xs text-slate-500">Tanda <span class="text-red-600">*</span> wajib diisi.</p>

                <button
                    id="submit-registration"
                    type="submit"
                    :disabled="registrationDisabled || submitting"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white ring-offset-white transition-colors hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                    <svg v-if="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                    </svg>
                    {{ submitting ? 'Memproses pendaftaran...' : 'Daftarkan pasien' }}
                </button>
            </form>

            <div
                v-if="!practiceOpen"
                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
                role="status">
                {{ practiceMessage }} Pendaftaran akan dibuka oleh petugas.
            </div>
            <div
                v-else-if="queueFull"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                role="status">
                Antrian hari ini penuh.
            </div>
        </div>
    </section>
</template>
