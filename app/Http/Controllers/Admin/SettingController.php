<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /** Pengaturan yang boleh diubah admin. */
    private const KEYS = [
        'app_name', 'place_type', 'place_name', 'place_address', 'place_phone',
        'place_description', 'practice_hours_note',
        'doctor_name', 'doctor_title', 'doctor_bio',
    ];

    public function __construct(private readonly SettingService $settings) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->settings->many(self::KEYS)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_name' => ['sometimes', 'string', 'max:100'],
            'place_type' => ['sometimes', Rule::in(['klinik', 'puskesmas', 'rumah_sakit'])],
            'place_name' => ['sometimes', 'string', 'max:150'],
            'place_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'place_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'place_description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'practice_hours_note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'doctor_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'doctor_title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'doctor_bio' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);

        foreach ($data as $key => $value) {
            $this->settings->set($key, $value);
        }

        return $this->show();
    }

    public function uploadDoctorPhoto(Request $request): JsonResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);

        $old = $this->settings->get('doctor_photo');
        $path = $request->file('photo')->store('doctor', 'public');
        $this->settings->set('doctor_photo', $path);

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return response()->json(['photo_url' => Storage::disk('public')->url($path)]);
    }
}
