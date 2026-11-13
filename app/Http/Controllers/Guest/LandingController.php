<?php

namespace App\Http\Controllers\Guest;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\DoctorAchievement;
use App\Models\QueueEntry;
use App\Services\QueueService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(SettingService $settings, QueueService $queue): View
    {
        return view('landing', $this->landingData($settings, $queue));
    }

    public function show(SettingService $settings, QueueService $queue): JsonResponse
    {
        return response()->json($this->landingData($settings, $queue));
    }

    private function landingData(SettingService $settings, QueueService $queue): array
    {
        $photo = $settings->get('doctor_photo');
        $issuedCount = QueueEntry::today()->count();

        return [
            'app_name' => $settings->get('app_name', 'Antrian Pasien'),
            'place' => [
                'type' => $settings->get('place_type'),            // klinik | puskesmas | rumah_sakit
                'name' => $settings->get('place_name'),
                'address' => $settings->get('place_address'),
                'phone' => $settings->get('place_phone'),
                'description' => $settings->get('place_description'),
                'practice_hours_note' => $settings->get('practice_hours_note'),
            ],
            'doctor' => [
                'name' => $settings->get('doctor_name'),
                'title' => $settings->get('doctor_title'),          // gelar
                'bio' => $settings->get('doctor_bio'),
                'photo_url' => $photo ? Storage::url($photo) : null,
            ],
            'achievements' => DoctorAchievement::orderBy('sort_order')->orderBy('id')->get()->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'image_url' => $a->image_url,
            ]),
            'practice' => $queue->practiceStatus(),
            'queue' => [
                'now_serving' => $queue->nowServing()?->number_label,
                'waiting_count' => QueueEntry::today()->where('status', QueueStatus::Waiting->value)->count(),
                'issued_count' => $issuedCount,
                'max_number' => config('antrian.max_number'),
                'is_full' => $issuedCount >= config('antrian.max_number'),
            ],
            'vapid_public_key' => config('antrian.vapid.public'),
        ];
    }
}
