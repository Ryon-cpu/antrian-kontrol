<?php

return [
    // Nomor antrian per hari: 01 s.d. max_number, besok mulai lagi dari 01.
    'max_number' => (int) env('QUEUE_MAX_NUMBER', 99),

    'enforce_single_active_queue_per_device' => env('QUEUE_ENFORCE_SINGLE_ACTIVE_PER_DEVICE', true),

    // Berapa urutan pasien digeser ke bawah saat "Lewati" / auto-geser.
    'shift_steps' => (int) env('QUEUE_SHIFT_STEPS', 3),

    // Pendamping diberi peringatan saat sisa antrian di depannya <= nilai ini.
    'near_threshold' => (int) env('QUEUE_NEAR_THRESHOLD', 2),

    // Dipanggil maksimal N kali, lalu tunggu M menit sebelum otomatis digeser.
    'max_calls' => (int) env('QUEUE_MAX_CALLS', 3),
    'recall_wait_minutes' => (int) env('QUEUE_RECALL_WAIT_MINUTES', 2),

    // Data antrian (data pasien) disimpan sekian hari lalu dihapus otomatis.
    'retention_days' => (int) env('DATA_RETENTION_DAYS', 30),

    'admin_password' => env('ADMIN_PASSWORD'),

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public' => env('VAPID_PUBLIC_KEY'),
        'private' => env('VAPID_PRIVATE_KEY'),
    ],
];
