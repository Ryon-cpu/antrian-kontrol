<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import {
    FlexRender,
    columnFilteringFeature,
    createFilteredRowModel,
    createPaginatedRowModel,
    createSortedRowModel,
    globalFilteringFeature,
    rowPaginationFeature,
    rowSortingFeature,
    sortFn_alphanumeric,
    tableFeatures,
    useTable,
} from '@tanstack/vue-table';
import { Toaster, toast } from 'vue-sonner';
import { createQueueDashboardState } from '../petugas';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
});

const dashboard = reactive(createQueueDashboardState(props.config));
const activeEntries = computed(() => dashboard.entries.filter((entry) => (
    entry.status === 'waiting' || entry.status === 'called'
)));
const doneEntries = computed(() => dashboard.entries
    .filter((entry) => entry.status === 'done')
    .sort((first, second) => second.id - first.id));
const cancelledEntries = computed(() => dashboard.entries
    .filter((entry) => entry.status === 'cancelled')
    .sort((first, second) => second.id - first.id));

const queryClient = useQueryClient();
const queueQueryKey = ['staff', 'queue'];
const queueQuery = useQuery({
    queryKey: queueQueryKey,
    queryFn: () => dashboard.api('petugas/queue'),
    initialData: {
        data: dashboard.entries,
        now_serving: dashboard.nowServing,
        practice: dashboard.practice,
    },
    enabled: computed(() => (
        dashboard.isLoggedIn
        && (dashboard.authMode !== 'token' || Boolean(dashboard.token))
    )),
    refetchInterval: 5000,
    refetchIntervalInBackground: false,
});
const searchQuery = ref('');
const statusFilter = ref('all');
const activeView = ref('dashboard');
const historySearchDraft = ref('');
const historyExporting = ref(null);
const historyFilters = reactive({
    from: '',
    to: '',
    search: '',
    status: 'all',
    page: 1,
    perPage: 20,
});
const columns = [
    {
        id: 'number_label',
        accessorFn: (entry) => entry.number_label,
        header: 'Nomor',
        enableGlobalFilter: false,
        sortFn: 'alphanumeric',
    },
    {
        id: 'patient',
        accessorFn: (entry) => [
            entry.number_label,
            entry.patient?.name,
            entry.patient?.age,
            entry.patient?.address,
            entry.patient?.complaint,
        ].filter(Boolean).join(' '),
        header: 'Pasien',
        enableGlobalFilter: true,
        sortFn: 'alphanumeric',
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        enableGlobalFilter: false,
        filterFn: (row, columnId, filterValue) => row.getValue(columnId) === filterValue,
    },
    {
        id: 'actions',
        header: 'Tindakan',
        enableSorting: false,
        enableColumnFilter: false,
        enableGlobalFilter: false,
    },
];
const features = tableFeatures({
    columnFilteringFeature,
    globalFilteringFeature,
    rowPaginationFeature,
    rowSortingFeature,
    filteredRowModel: createFilteredRowModel(),
    paginatedRowModel: createPaginatedRowModel(),
    sortedRowModel: createSortedRowModel(),
    sortFns: {
        alphanumeric: sortFn_alphanumeric,
    },
});
const table = useTable({
    features,
    columns,
    data: activeEntries,
    globalFilterFn: (row, columnId, filterValue) => (
        columnId === 'patient'
        && String(row.getValue(columnId) ?? '')
            .toLocaleLowerCase()
            .includes(String(filterValue).trim().toLocaleLowerCase())
    ),
    getColumnCanGlobalFilter: (column) => column.id === 'patient',
    initialState: {
        pagination: {
            pageIndex: 0,
            pageSize: 10,
        },
    },
});
const pagination = computed(() => table.atoms.pagination.get());
const historyQueryKey = computed(() => [
    'staff',
    'queue-history',
    historyFilters.from,
    historyFilters.to,
    historyFilters.search,
    historyFilters.status,
    historyFilters.page,
    historyFilters.perPage,
]);
const historyQuery = useQuery({
    queryKey: historyQueryKey,
    queryFn: () => dashboard.api(`petugas/queue/history?${buildHistoryParams()}`),
    enabled: computed(() => (
        (dashboard.authMode === 'session' || dashboard.isLoggedIn)
        && activeView.value === 'history'
    )),
});
const historyRows = computed(() => historyQuery.data.value?.data ?? []);
const historyMeta = computed(() => historyQuery.data.value?.meta ?? {
    current_page: 1,
    from: null,
    last_page: 1,
    per_page: historyFilters.perPage,
    to: null,
    total: 0,
});
const historyFilterData = computed(() => historyQuery.data.value?.filters ?? {});
const historyAvailableFrom = computed(() => historyFilterData.value.available_from ?? '');
const historyAvailableTo = computed(() => historyFilterData.value.available_to ?? '');
const historyPeriodLabel = computed(() => {
    const from = historyFilterData.value.from;
    const to = historyFilterData.value.to;

    return from && to ? `${formatDate(from)} - ${formatDate(to)}` : '30 hari terakhir';
});
const historyIsFetching = computed(() => historyQuery.isFetching.value);
const historyIsExportDisabled = computed(() => (
    historyIsFetching.value
    || historyExporting.value !== null
    || historyMeta.value.total === 0
));
const historyColumns = [
    {
        id: 'queue_date',
        accessorKey: 'queue_date',
        header: 'Tanggal',
        sortFn: 'alphanumeric',
    },
    {
        id: 'number_label',
        accessorKey: 'number_label',
        header: 'Nomor',
        sortFn: 'alphanumeric',
    },
    {
        id: 'patient',
        accessorFn: (entry) => [
            entry.patient?.name,
            entry.patient?.age,
            entry.patient?.address,
            entry.patient?.complaint,
        ].filter(Boolean).join(' '),
        header: 'Data pasien',
        sortFn: 'alphanumeric',
    },
    {
        id: 'status',
        accessorKey: 'status',
        header: 'Status',
        sortFn: 'alphanumeric',
    },
];
const historyTableFeatures = tableFeatures({
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
    sortFns: {
        alphanumeric: sortFn_alphanumeric,
    },
});
const historyTable = useTable({
    features: historyTableFeatures,
    columns: historyColumns,
    data: historyRows,
});

dashboard.refreshQueue = async () => {
    await queryClient.invalidateQueries({ queryKey: queueQueryKey });
};

const showFeedback = dashboard.showFeedback.bind(dashboard);
dashboard.showFeedback = (message, isError = false) => {
    showFeedback(message, isError);
    if (isError) {
        toast.error(message);
        return;
    }

    toast.success(message);
};

watch(queueQuery.data, (data) => {
    if (!data) {
        return;
    }

    dashboard.entries = data.data ?? [];
    dashboard.nowServing = data.now_serving ?? '--';
    if (data.practice) {
        dashboard.practice = data.practice;
    }
}, { immediate: true });

watch(queueQuery.isError, (isError) => {
    if (isError && queueQuery.error.value instanceof Error) {
        dashboard.showFeedback(queueQuery.error.value.message, true);
    }
});

watch(historyQuery.isError, (isError) => {
    if (isError && historyQuery.error.value instanceof Error) {
        dashboard.showFeedback(historyQuery.error.value.message, true);
    }
});

watch(searchQuery, (query) => {
    table.setGlobalFilter(query);
    table.setPageIndex(0);
});

watch(statusFilter, (status) => {
    table.getColumn('status').setFilterValue(status === 'all' ? undefined : status);
    table.setPageIndex(0);
});

watch(
    () => [
        historyFilters.from,
        historyFilters.to,
        historyFilters.search,
        historyFilters.status,
        historyFilters.perPage,
    ],
    () => {
        historyFilters.page = 1;
    },
);

function buildHistoryParams(page = historyFilters.page, perPage = historyFilters.perPage, useResolvedPeriod = false) {
    const params = new URLSearchParams({
        page: String(page),
        per_page: String(perPage),
    });
    const from = useResolvedPeriod
        ? historyFilterData.value.from ?? historyFilters.from
        : historyFilters.from;
    const to = useResolvedPeriod
        ? historyFilterData.value.to ?? historyFilters.to
        : historyFilters.to;

    if (from) {
        params.set('from', from);
    }

    if (to) {
        params.set('to', to);
    }

    if (historyFilters.search) {
        params.set('search', historyFilters.search);
    }

    if (historyFilters.status !== 'all') {
        params.set('status', historyFilters.status);
    }

    return params.toString();
}

function formatDate(value) {
    return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function statusLabel(status) {
    return {
        waiting: 'Menunggu',
        called: 'Dipanggil',
        done: 'Selesai',
        cancelled: 'Dibatalkan',
    }[status] ?? status;
}

function applyHistorySearch() {
    historyFilters.search = historySearchDraft.value.trim();
}

function resetHistoryFilters() {
    historyFilters.from = '';
    historyFilters.to = '';
    historyFilters.search = '';
    historyFilters.status = 'all';
    historySearchDraft.value = '';
    historyFilters.page = 1;
}

function adjustHistoryDateRange(changedDate) {
    if (historyFilters.from && historyFilters.to && historyFilters.from > historyFilters.to) {
        if (changedDate === 'from') {
            historyFilters.to = historyFilters.from;
        } else {
            historyFilters.from = historyFilters.to;
        }
    }
}

function changeHistoryPage(page) {
    if (page >= 1 && page <= historyMeta.value.last_page) {
        historyFilters.page = page;
    }
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

async function fetchAllHistoryEntries() {
    const entries = [];
    let page = 1;
    let lastPage = 1;

    do {
        const params = buildHistoryParams(page, 100, true);
        const response = await dashboard.api(`petugas/queue/history?${params}`);
        entries.push(...response.data);
        lastPage = response.meta.last_page;
        page += 1;
    } while (page <= lastPage);

    return entries;
}

async function exportHistory(format) {
    if (historyExporting.value !== null) {
        return;
    }

    historyExporting.value = format;

    try {
        const entries = await fetchAllHistoryEntries();
        const dateStamp = new Date().toISOString().slice(0, 10);

        if (format === 'excel') {
            const { default: writeExcelFile } = await import('write-excel-file/browser');
            const workbook = await writeExcelFile(entries, {
                sheet: 'Riwayat Pasien',
                columns: [
                    { header: 'Tanggal', cell: (entry) => ({ value: entry.queue_date }), width: 14 },
                    { header: 'Nomor antrean', cell: (entry) => ({ value: entry.number_label }), width: 16 },
                    { header: 'Nama pasien', cell: (entry) => ({ value: entry.patient?.name ?? '' }), width: 24 },
                    { header: 'Usia', cell: (entry) => ({ value: entry.patient?.age ?? '' }), width: 10 },
                    { header: 'Alamat', cell: (entry) => ({ value: entry.patient?.address ?? '' }), width: 32 },
                    { header: 'Keluhan', cell: (entry) => ({ value: entry.patient?.complaint ?? '' }), width: 40 },
                    { header: 'Status', cell: (entry) => ({ value: statusLabel(entry.status) }), width: 16 },
                ],
            }).toBlob();

            downloadBlob(workbook, `riwayat-pasien-${dateStamp}.xlsx`);
        } else {
            const [{ jsPDF }, { default: autoTable }] = await Promise.all([
                import('jspdf'),
                import('jspdf-autotable'),
            ]);
            const document = new jsPDF({ orientation: 'landscape' });
            document.setFontSize(16);
            document.text('Riwayat Pasien', 14, 16);
            document.setFontSize(10);
            document.text(`Periode: ${historyPeriodLabel.value}`, 14, 23);
            autoTable(document, {
                startY: 28,
                head: [['Tanggal', 'Nomor', 'Nama pasien', 'Usia', 'Alamat', 'Keluhan', 'Status']],
                body: entries.map((entry) => [
                    formatDate(entry.queue_date),
                    entry.number_label,
                    entry.patient?.name ?? '',
                    entry.patient?.age ?? '',
                    entry.patient?.address ?? '',
                    entry.patient?.complaint ?? '',
                    statusLabel(entry.status),
                ]),
                styles: { fontSize: 8, cellPadding: 2 },
                headStyles: { fillColor: [30, 64, 175] },
            });
            document.save(`riwayat-pasien-${dateStamp}.pdf`);
        }

        dashboard.showFeedback(`Ekspor ${format === 'excel' ? 'Excel' : 'PDF'} berhasil diunduh.`);
    } catch (error) {
        dashboard.showFeedback(
            error instanceof Error ? error.message : 'Ekspor riwayat pasien gagal.',
            true,
        );
    } finally {
        historyExporting.value = null;
    }
}

async function logoutFromSidebar() {
    if (dashboard.authMode === 'token') {
        await dashboard.handleLogout();
        return;
    }

    const logoutForm = document.getElementById('staff-logout-form');
    if (!(logoutForm instanceof HTMLFormElement)) {
        dashboard.showFeedback('Form keluar tidak tersedia.', true);
        return;
    }

    logoutForm.submit();
}

function goBack() {
    if (activeView.value === 'history') {
        activeView.value = 'dashboard';
        return;
    }

    window.history.back();
}

onMounted(() => {
    dashboard.init();
});
</script>

<template>
    <div id="staff-app" class="flex flex-col gap-6">
        <Toaster position="top-right" rich-colors close-button />

        <section
            v-if="dashboard.authMode === 'token' && !dashboard.isLoggedIn"
            id="login-panel"
            class="mx-auto w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    aria-label="Kembali Kehalaman Sebelumnya"
                    title="Kembali Kehalaman Sebelumnya"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @click="goBack()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Kembali Kehalaman Sebelumnya</span>
                </button>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Masuk petugas</h2>
                    <p class="text-sm text-gray-600">Otorisasi petugas &amp; admin klinik.</p>
                </div>
            </div>

            <form id="login-form" class="mt-6 flex flex-col gap-5" @submit.prevent="dashboard.handleLogin()">
                <label class="flex flex-col gap-2" for="username">
                    <span class="text-base font-semibold text-gray-800">Username</span>
                    <input
                        id="username"
                        v-model="dashboard.loginForm.username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        required
                        placeholder="Masukkan username petugas..."
                        class="min-h-12 rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </label>
                <label class="flex flex-col gap-2" for="password">
                    <span class="text-base font-semibold text-gray-800">Password</span>
                    <input
                        id="password"
                        v-model="dashboard.loginForm.password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        placeholder="Masukkan password..."
                        class="min-h-12 rounded-lg border-gray-300 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </label>
                <button
                    id="login-button"
                    type="submit"
                    :disabled="dashboard.loginLoading"
                    class="mt-2 flex min-h-12 items-center justify-center rounded-lg bg-blue-700 px-6 py-3 text-base font-bold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-gray-400"
                >
                    {{ dashboard.loginLoading ? 'Memeriksa akun...' : 'Masuk ke Dashboard' }}
                </button>
            </form>
        </section>

        <section
            v-if="dashboard.authMode === 'session' || dashboard.isLoggedIn"
            id="dashboard-panel"
            class="grid items-start gap-6 pb-24 lg:grid-cols-[15rem_minmax(0,1fr)] lg:pb-0"
        >
            <aside class="hidden rounded-xl border border-gray-200 bg-white p-3 shadow-sm lg:sticky lg:top-6 lg:block">
                <p class="hidden px-3 py-2 text-xs font-bold uppercase tracking-wider text-gray-500 lg:block">Menu utama</p>
                <nav class="flex gap-2 overflow-x-auto lg:flex-col" aria-label="Navigasi dashboard">
                    <button
                        id="staff-nav-dashboard"
                        type="button"
                        class="flex min-h-11 shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors"
                        :class="activeView === 'dashboard' ? 'bg-blue-50 text-blue-800' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                        :aria-current="activeView === 'dashboard' ? 'page' : undefined"
                        @click="activeView = 'dashboard'"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                        </svg>
                        Dashboard antrean
                    </button>
                    <button
                        id="staff-nav-history"
                        type="button"
                        class="flex min-h-11 shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors"
                        :class="activeView === 'history' ? 'bg-blue-50 text-blue-800' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                        :aria-current="activeView === 'history' ? 'page' : undefined"
                        @click="activeView = 'history'"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat pasien
                    </button>
                </nav>
                <div class="mt-3 flex gap-2 border-t border-gray-200 pt-3 lg:flex-col">
                    <a
                        id="staff-nav-profile"
                        :href="props.config.profileUrl"
                        class="flex min-h-11 shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.88 17.8M15 9a3 3 0 11-6 0 3 3 0 016 0zm-3 12a8.96 8.96 0 005.879-2.196A6 6 0 006.12 18.8 8.96 8.96 0 0012 21z" />
                        </svg>
                        Profile
                    </a>
                    <button
                        id="staff-nav-logout"
                        type="button"
                        class="flex min-h-11 shrink-0 items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-gray-600 hover:bg-red-50 hover:text-red-700"
                        @click="logoutFromSidebar()"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Log Out
                    </button>
                </div>
            </aside>

            <div class="flex min-w-0 flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <button
                        v-if="activeView === 'history'"
                        id="history-back-button"
                        type="button"
                        aria-label="Kembali Kehalaman Sebelumnya"
                        title="Kembali Kehalaman Sebelumnya"
                        class="inline-flex items-center gap-2 self-start rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        @click="goBack()"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Kembali Kehalaman Sebelumnya</span>
                    </button>
                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between lg:p-6">
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-bold uppercase tracking-wider text-gray-800">
                                    Klinik Dokter Mandiri
                                </span>
                                <span class="text-sm font-medium text-gray-600">
                                    Petugas: <strong id="staff-name" class="text-gray-900">{{ dashboard.staffDisplay }}</strong>
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="h-3.5 w-3.5 rounded-full transition-colors"
                                    :class="dashboard.practice.is_open ? 'bg-green-500 ring-4 ring-green-100 animate-pulse' : 'bg-gray-400'"
                                ></span>
                                <p
                                    id="practice-status"
                                    class="text-lg font-bold"
                                    :class="dashboard.practice.is_open ? 'text-green-800' : 'text-gray-700'"
                                >
                                    {{ dashboard.practice.message }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-12 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :title="dashboard.soundEnabled ? 'Matikan nada bel panggilan' : 'Nyalakan nada bel panggilan'"
                                @click="dashboard.soundEnabled = !dashboard.soundEnabled; if (dashboard.soundEnabled) dashboard.playBell()"
                            >
                                <span>{{ dashboard.soundEnabled ? 'Bel Aktif' : 'Bel Senyap' }}</span>
                            </button>
                            <button
                                id="practice-button"
                                type="button"
                                :disabled="dashboard.busy || dashboard.practice.state === 'libur'"
                                class="min-h-12 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-base font-bold text-gray-800 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                @click="dashboard.togglePractice()"
                            >
                                {{ dashboard.practice.is_open ? 'Tutup praktek' : 'Buka praktek' }}
                            </button>
                            <button
                                id="call-button"
                                type="button"
                                :disabled="!dashboard.canCallNext"
                                class="flex min-h-12 items-center gap-2 rounded-lg bg-blue-700 px-6 py-2.5 text-base font-bold text-white shadow-sm hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-gray-400"
                                @click="dashboard.callNext()"
                            >
                                Panggil berikutnya
                            </button>
                        </div>
                    </div>
                </div>

            <template v-if="activeView === 'dashboard'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <article class="rounded-xl border border-blue-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wider text-blue-800">Sedang dipanggil</p>
                    <p id="now-serving" class="mt-3 text-4xl font-black text-gray-900">{{ dashboard.nowServing }}</p>
                    <p class="mt-1 text-xs text-gray-500">Pasien di ruang periksa dokter</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-600">Menunggu</p>
                    <p id="waiting-count" class="mt-3 text-4xl font-black text-gray-900">{{ dashboard.waitingCount }}</p>
                    <p class="mt-1 text-xs text-gray-500">Pasien berada di ruang tunggu</p>
                </article>
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-wider text-gray-600">Terdaftar hari ini</p>
                    <p id="registered-count" class="mt-3 text-4xl font-black text-gray-900">{{ dashboard.entries.length }}</p>
                    <p class="mt-1 text-xs text-gray-500">Total nomor antrean terbit hari ini</p>
                </article>
            </div>

            <section
                v-if="dashboard.activeEntry"
                class="rounded-xl border border-blue-300 bg-blue-50/50 p-4 shadow-sm"
                aria-label="Pasien yang sedang dipanggil"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="text-sm font-bold text-blue-900">
                            Nomor {{ dashboard.activeEntry.number_label }} · {{ dashboard.activeEntry.patient?.name }}
                        </span>
                        <p class="text-xs text-gray-600">
                            Panggilan ke-{{ dashboard.activeEntry.call_count }} · {{ dashboard.activeEntry.patient?.complaint }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="min-h-10 rounded-lg bg-green-700 px-3.5 py-2 text-sm font-bold text-white disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.doneEntry(dashboard.activeEntry)">Selesai pemeriksaan</button>
                        <button class="min-h-10 rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-bold text-white disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.callSpecific(dashboard.activeEntry)">Panggil ulang</button>
                        <button class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.skipEntry(dashboard.activeEntry)">Lewati</button>
                        <button class="min-h-10 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-700 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.cancelEntry(dashboard.activeEntry)">Batalkan</button>
                    </div>
                </div>

            </section>
                </template>

                <section v-else class="flex flex-col gap-5 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="history-title">
                    <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 id="history-title" class="text-xl font-bold text-gray-900 sm:text-2xl">Riwayat pasien</h2>
                            <p class="mt-1 text-sm text-gray-500">Daftar antrean dari {{ historyPeriodLabel }}.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="historyIsExportDisabled"
                                @click="exportHistory('excel')"
                            >
                                {{ historyExporting === 'excel' ? 'Menyiapkan Excel...' : 'Ekspor Excel' }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-10 items-center justify-center rounded-lg bg-blue-700 px-3.5 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="historyIsExportDisabled"
                                @click="exportHistory('pdf')"
                            >
                                {{ historyExporting === 'pdf' ? 'Menyiapkan PDF...' : 'Ekspor PDF' }}
                            </button>
                        </div>
                    </div>

                    <form class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyHistorySearch">
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-gray-600">
                            Dari tanggal
                            <input
                                v-model="historyFilters.from"
                                type="date"
                                :min="historyAvailableFrom"
                                :max="historyAvailableTo"
                                class="min-h-10 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @change="adjustHistoryDateRange('from')"
                            >
                        </label>
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-gray-600">
                            Sampai tanggal
                            <input
                                v-model="historyFilters.to"
                                type="date"
                                :min="historyAvailableFrom"
                                :max="historyAvailableTo"
                                class="min-h-10 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @change="adjustHistoryDateRange('to')">
                        </label>
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-gray-600">
                            Status
                            <select
                                v-model="historyFilters.status"
                                class="min-h-10 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="all">Semua status</option>
                                <option value="waiting">Menunggu</option>
                                <option value="called">Dipanggil</option>
                                <option value="done">Selesai</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1.5 text-xs font-semibold text-gray-600 sm:col-span-2 xl:col-span-1">
                            Cari pasien
                            <input
                                v-model="historySearchDraft"
                                type="search"
                                maxlength="100"
                                placeholder="Nama, alamat, keluhan..."
                                class="min-h-10 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-1">
                            <button type="submit" class="min-h-10 flex-1 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                Cari
                            </button>
                            <button
                                type="button"
                                class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                @click="resetHistoryFilters()">
                                Reset
                            </button>
                        </div>
                    </form>

                    <div class="flex flex-col gap-3 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                        <p>Data tersedia maksimal 30 hari. Total: <strong class="text-gray-800">{{ historyMeta.total }}</strong> pasien</p>
                        <label class="flex items-center gap-2">
                            Baris per halaman
                            <select
                                v-model.number="historyFilters.perPage"
                                class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option :value="10">10</option>
                                <option :value="20">20</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </label>
                    </div>

                    <div class="overflow-x-auto">
                        <table id="patient-history-list" class="w-full min-w-[850px] text-left text-sm">
                            <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                                <tr v-for="headerGroup in historyTable.getHeaderGroups()" :key="headerGroup.id">
                                    <th v-for="header in headerGroup.headers" :key="header.id" class="px-3 py-3">
                                        <button
                                            v-if="header.column.getCanSort()"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 hover:text-gray-900"
                                            @click="header.column.getToggleSortingHandler()?.($event)">
                                            <FlexRender :header="header" />
                                            <span aria-hidden="true">
                                                {{ { asc: '↑', desc: '↓' }[header.column.getIsSorted()] || '↕' }}
                                            </span>
                                        </button>
                                        <FlexRender v-else :header="header" />
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in historyTable.getRowModel().rows" :key="row.id">
                                    <td v-for="cell in row.getAllCells()" :key="cell.id" class="px-3 py-3 align-top">
                                        <span v-if="cell.column.id === 'queue_date'" class="whitespace-nowrap font-medium text-gray-700">
                                            {{ formatDate(row.original.queue_date) }}
                                        </span>
                                        <span v-else-if="cell.column.id === 'number_label'" class="font-black text-gray-900">
                                            {{ row.original.number_label }}
                                        </span>
                                        <div v-else-if="cell.column.id === 'patient'" class="max-w-md">
                                            <p class="font-semibold text-gray-900">{{ row.original.patient?.name }}</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ row.original.patient?.age }} thn · {{ row.original.patient?.address }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-600">{{ row.original.patient?.complaint }}</p>
                                        </div>
                                        <span
                                            v-else
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': row.original.status === 'waiting',
                                                'bg-blue-100 text-blue-800': row.original.status === 'called',
                                                'bg-green-100 text-green-800': row.original.status === 'done',
                                                'bg-red-100 text-red-800': row.original.status === 'cancelled',
                                            }"
                                        >
                                            {{ statusLabel(row.original.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="historyRows.length === 0 && !historyIsFetching">
                                    <td colspan="4" class="px-3 py-12 text-center text-gray-500">
                                        Tidak ada riwayat pasien yang cocok dengan filter.
                                    </td>
                                </tr>
                                <tr v-if="historyRows.length === 0 && historyIsFetching">
                                    <td colspan="4" class="px-3 py-12 text-center text-gray-500">Memuat riwayat pasien...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            Menampilkan {{ historyMeta.from ?? 0 }}–{{ historyMeta.to ?? 0 }}
                            dari {{ historyMeta.total }} pasien
                        </p>
                        <div class="flex items-center gap-3">
                            <span>Halaman {{ historyMeta.current_page }} dari {{ historyMeta.last_page }}</span>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="historyMeta.current_page <= 1 || historyIsFetching"
                                @click="changeHistoryPage(historyMeta.current_page - 1)">
                                Sebelumnya
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="historyMeta.current_page >= historyMeta.last_page || historyIsFetching"
                                @click="changeHistoryPage(historyMeta.current_page + 1)">
                                Berikutnya
                            </button>
                        </div>
                    </div>
                    </section>

            <template v-if="activeView === 'dashboard'">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 lg:col-span-2" aria-labelledby="queue-title">
                    <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 id="queue-title" class="text-xl font-bold text-gray-900 sm:text-2xl">Antrian hari ini</h2>
                            <p class="mt-1 text-sm text-gray-500">Daftar antrean dan tindakan pelayanan pasien klinik.</p>
                        </div>
                        <div class="flex w-full flex-nowrap gap-2 lg:w-auto lg:gap-3">
                            <input
                                v-model="searchQuery"
                                type="search"
                                aria-label="Cari pasien atau nomor antrean"
                                placeholder="Cari pasien / no antrian..."
                                class="min-h-10 min-w-0 flex-1 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 lg:w-40 lg:flex-none"
                            >
                            <select
                                id="status-filter"
                                v-model="statusFilter"
                                aria-label="Filter status antrean"
                                class="min-h-10 min-w-0 flex-1 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 lg:w-32 lg:flex-none"
                            >
                                <option value="all">Semua antrian</option>
                                <option value="waiting">Menunggu</option>
                                <option value="called">Dipanggil</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table id="queue-list" class="w-full min-w-[720px] text-left text-sm">
                            <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                                <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                    <th v-for="header in headerGroup.headers" :key="header.id" class="px-3 py-3">
                                        <button
                                            v-if="header.column.getCanSort()"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 hover:text-gray-900"
                                            @click="header.column.getToggleSortingHandler()?.($event)"
                                        >
                                            <FlexRender :header="header" />
                                            <span aria-hidden="true">
                                                {{ { asc: '↑', desc: '↓' }[header.column.getIsSorted()] || '↕' }}
                                            </span>
                                        </button>
                                        <FlexRender v-else :header="header" />
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="row in table.getRowModel().rows" :key="row.id">
                                    <td
                                        v-for="cell in row.getAllCells()"
                                        :key="cell.id"
                                        class="px-3 py-3"
                                    >
                                        <span v-if="cell.column.id === 'number_label'" class="font-black text-gray-900">
                                            {{ row.original.number_label }}
                                        </span>
                                        <div v-else-if="cell.column.id === 'patient'">
                                            <p class="font-semibold text-gray-900">{{ row.original.patient?.name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ row.original.patient?.age }} thn · {{ row.original.patient?.address }} · {{ row.original.patient?.complaint }}
                                            </p>
                                        </div>
                                        <div v-else-if="cell.column.id === 'status'">
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                                :class="row.original.status === 'waiting' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'"
                                            >
                                                {{ row.original.status === 'waiting' ? 'Menunggu' : 'Dipanggil' }}
                                            </span>
                                            <span v-if="row.original.status === 'called'" class="mt-1 block text-xs text-gray-500">
                                                Panggilan ke-{{ row.original.call_count }}
                                            </span>
                                        </div>
                                        <div v-else-if="cell.column.id === 'actions'" class="flex flex-wrap gap-2">
                                            <template v-if="row.original.status === 'waiting'">
                                                <button class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.callSpecific(row.original)">Panggil</button>
                                                <button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.openMoveModal(row.original)">Geser</button>
                                            </template>
                                            <template v-else>
                                                <button class="rounded-lg bg-green-700 px-3 py-2 text-xs font-bold text-white disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.doneEntry(row.original)">Selesai</button>
                                                <button class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.skipEntry(row.original)">Lewati</button>
                                            </template>
                                            <button class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-700 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.cancelEntry(row.original)">Batal</button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="table.getRowModel().rows.length === 0">
                                    <td colspan="4" class="px-3 py-10 text-center text-gray-500">
                                        {{ activeEntries.length === 0 ? 'Belum ada antrean pasien.' : 'Data antrean tidak ditemukan.' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            Menampilkan
                            {{ table.getRowCount() === 0 ? 0 : pagination.pageIndex * pagination.pageSize + 1 }}
                            –
                            {{ Math.min((pagination.pageIndex + 1) * pagination.pageSize, table.getRowCount()) }}
                            dari {{ table.getRowCount() }} antrean
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <label for="queue-page-size" class="flex items-center gap-2">
                                Baris per halaman
                                <select
                                    id="queue-page-size"
                                    :value="pagination.pageSize"
                                    class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    @change="table.setPageSize(Number($event.target.value))"
                                >
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                    <option :value="50">50</option>
                                </select>
                            </label>
                            <span>Halaman {{ pagination.pageIndex + 1 }} dari {{ Math.max(table.getPageCount(), 1) }}</span>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!table.getCanPreviousPage()"
                                @click="table.previousPage()"
                            >
                                Sebelumnya
                            </button>
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!table.getCanNextPage()"
                                @click="table.nextPage()"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col gap-4">
                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="done-title">
                        <div class="border-b border-gray-200 pb-3">
                            <h2 id="done-title" class="text-base font-bold text-gray-900">Sudah diperiksa ({{ doneEntries.length }})</h2>
                        </div>
                        <ul class="mt-3 flex flex-col gap-2">
                            <li v-for="entry in doneEntries" :key="`done-${entry.id}`" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                                <span class="text-sm font-semibold text-gray-900">{{ entry.number_label }} · {{ entry.patient?.name }}</span>
                                <button type="button" class="text-xs font-semibold text-red-700 hover:text-red-900 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.deleteEntry(entry)">Hapus</button>
                            </li>
                            <li v-if="doneEntries.length === 0" class="py-4 text-center text-xs text-gray-400">Belum ada pasien selesai.</li>
                        </ul>
                    </section>

                    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="cancelled-title">
                        <div class="border-b border-gray-200 pb-3">
                            <h2 id="cancelled-title" class="text-base font-bold text-gray-900">Dibatalkan ({{ cancelledEntries.length }})</h2>
                        </div>
                        <ul class="mt-3 flex flex-col gap-2">
                            <li v-for="entry in cancelledEntries" :key="`cancelled-${entry.id}`" class="flex items-center justify-between gap-3 rounded-lg border border-red-200 p-3">
                                <span class="text-sm font-semibold text-gray-900">{{ entry.number_label }} · {{ entry.patient?.name }}</span>
                                <button type="button" class="text-xs font-semibold text-red-700 hover:text-red-900 disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.deleteEntry(entry)">Hapus</button>
                            </li>
                            <li v-if="cancelledEntries.length === 0" class="py-4 text-center text-xs text-gray-400">Tidak ada antrean dibatalkan.</li>
                        </ul>
                    </section>
                </div>
            </div>
            </template>
            </div>
            <nav
                class="staff-mobile-nav fixed bottom-2 left-1/2 z-40 flex w-48 -translate-x-1/2 flex-row items-center justify-center gap-2 rounded-full border border-gray-100 bg-white/95 px-2 py-1 shadow-md backdrop-blur lg:hidden"
                aria-label="Navigasi utama"
            >
                <button
                    id="staff-mobile-nav-dashboard"
                    type="button"
                    aria-label="Dashboard"
                    title="Dashboard"
                    class="flex h-10 w-10 items-center justify-center rounded-full transition-colors"
                    :class="activeView === 'dashboard' ? 'bg-red-50 text-red-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'"
                    :aria-current="activeView === 'dashboard' ? 'page' : undefined"
                    @click="activeView = 'dashboard'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                    </svg>
                </button>
                <button
                    id="staff-mobile-nav-history"
                    type="button"
                    aria-label="Riwayat pasien"
                    title="Riwayat pasien"
                    class="flex h-10 w-10 items-center justify-center rounded-full transition-colors"
                    :class="activeView === 'history' ? 'bg-red-50 text-red-700' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'"
                    :aria-current="activeView === 'history' ? 'page' : undefined"
                    @click="activeView = 'history'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
                <a
                    id="staff-mobile-nav-profile"
                    :href="props.config.profileUrl"
                    aria-label="Profile"
                    title="Profile"
                    class="flex h-10 w-10 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-800"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.88 17.8M15 9a3 3 0 11-6 0 3 3 0 016 0zm-3 12a8.96 8.96 0 005.879-2.196A6 6 0 006.12 18.8 8.96 8.96 0 0012 21z" />
                    </svg>
                </a>
            </nav>
        </section>

        <div
            v-if="dashboard.moveModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
            role="presentation"
            @click.self="dashboard.closeMoveModal()"
            @keydown.esc.window="dashboard.closeMoveModal()"
        >
            <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 text-slate-950 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="move-title">
                <h2 id="move-title" class="text-lg font-semibold tracking-tight">Geser antrean</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Nomor {{ dashboard.moveTargetEntry?.number_label }} · {{ dashboard.moveTargetEntry?.patient?.name }}
                </p>
                <label for="move-steps-input" class="mt-5 flex flex-col gap-2 text-sm font-medium text-slate-800">
                    Geser berapa langkah ke bawah?
                    <select id="move-steps-input" v-model="dashboard.moveSteps" class="h-10 rounded-md border-slate-200 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="1">1 urutan ke bawah</option>
                        <option value="2">2 urutan ke bawah (standar)</option>
                        <option value="3">3 urutan ke bawah</option>
                        <option value="5">5 urutan ke bawah</option>
                        <option value="10">10 urutan ke bawah</option>
                    </select>
                </label>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="h-10 rounded-md border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="dashboard.closeMoveModal()">Batal</button>
                    <button type="button" class="h-10 rounded-md bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 disabled:pointer-events-none disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.confirmMove()">Konfirmasi geser</button>
                </div>
            </section>
        </div>

        <div
            v-if="dashboard.cancelModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4"
            role="presentation"
            @click.self="dashboard.closeCancelModal()"
            @keydown.esc.window="dashboard.closeCancelModal()"
        >
            <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 text-slate-950 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
                <h2 id="cancel-title" class="text-lg font-semibold tracking-tight">Batalkan antrean pasien?</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Yakin membatalkan nomor {{ dashboard.cancelTargetEntry?.number_label }} untuk {{ dashboard.cancelTargetEntry?.patient?.name }}?
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="h-10 rounded-md border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="dashboard.closeCancelModal()">Tidak, kembali</button>
                    <button type="button" class="h-10 rounded-md bg-red-600 px-4 text-sm font-medium text-white hover:bg-red-700 disabled:pointer-events-none disabled:opacity-50" :disabled="dashboard.busy" @click="dashboard.confirmCancel()">Ya, batalkan</button>
                </div>
            </section>
        </div>
    </div>
</template>
