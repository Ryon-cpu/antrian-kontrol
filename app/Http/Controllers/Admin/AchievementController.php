<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AchievementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => DoctorAchievement::orderBy('sort_order')->orderBy('id')->get()->map(fn ($a) => $this->data($a)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $achievement = DoctorAchievement::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('achievements', 'public') : null,
        ]);

        return response()->json(['data' => $this->data($achievement)], 201);
    }

    /** Gunakan POST + field _method=PUT bila mengirim file (multipart). */
    public function update(Request $request, DoctorAchievement $achievement): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($achievement->image_path) {
                Storage::disk('public')->delete($achievement->image_path);
            }
            $data['image_path'] = $request->file('image')->store('achievements', 'public');
        }
        unset($data['image']);

        $achievement->fill($data)->save();

        return response()->json(['data' => $this->data($achievement)]);
    }

    public function destroy(DoctorAchievement $achievement): JsonResponse
    {
        if ($achievement->image_path) {
            Storage::disk('public')->delete($achievement->image_path);
        }
        $achievement->delete();

        return response()->json(['message' => 'Prestasi dihapus.']);
    }

    private function data(DoctorAchievement $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'description' => $a->description,
            'sort_order' => $a->sort_order,
            'image_url' => $a->image_url,
        ];
    }
}
