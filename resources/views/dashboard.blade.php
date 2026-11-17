<x-app-layout>
    <x-slot name="header">
        <div data-vue-dashboard-title data-title="Dashboard Antrian">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard Antrian
            </h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @include('petugas.partials.queue-dashboard', [
            'authMode' => 'session',
            'staffName' => auth()->user()->name,
        ])
    </div>
</x-app-layout>
