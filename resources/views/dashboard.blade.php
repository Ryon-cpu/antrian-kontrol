<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 pb-8 pt-4 sm:px-6 lg:px-8">
        @include('petugas.partials.queue-dashboard', [
            'authMode' => 'session',
            'staffName' => auth()->user()->name,
        ])
    </div>
</x-app-layout>
