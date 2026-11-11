<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => User::orderBy('name')->get()->map(fn (User $u) => $this->data($u)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['nullable', Rule::enum(UserRole::class)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => $data['password'],
            'role' => $data['role'] ?? UserRole::Petugas->value,
            'is_active' => true,
        ]);

        return response()->json(['data' => $this->data($user)], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'username' => ['sometimes', 'string', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['sometimes', 'string', Password::min(8)],
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($user->is($request->user())) {
            if (($data['is_active'] ?? true) === false || (isset($data['role']) && $data['role'] !== $user->role->value)) {
                throw new ApiException('Anda tidak bisa menonaktifkan atau mengubah role akun sendiri.', 409);
            }
        }

        $user->fill($data)->save();

        // Akun dinonaktifkan atau password diganti → semua sesi login lama dicabut.
        if (($data['is_active'] ?? true) === false || isset($data['password'])) {
            $user->tokens()->delete();
        }

        return response()->json(['data' => $this->data($user)]);
    }

    private function data(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'username' => $u->username,
            'role' => $u->role->value,
            'is_active' => $u->is_active,
        ];
    }
}
