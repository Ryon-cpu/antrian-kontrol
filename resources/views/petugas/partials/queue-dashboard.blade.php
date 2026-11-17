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

    $dashboardConfig = [
        'authMode' => $authMode,
        'staffName' => $staffName ?? '',
        'initialEntries' => $initialEntriesData,
        'initialPractice' => $initialPractice,
        'initialNowServing' => $initialNowServing,
    ];
@endphp

<div
    id="staff-dashboard-root"
    data-staff-dashboard
    data-auth-mode="{{ $authMode }}"
    data-dashboard-config="{{ json_encode($dashboardConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
></div>
