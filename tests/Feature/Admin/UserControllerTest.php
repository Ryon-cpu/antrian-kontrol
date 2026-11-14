<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns 409 when the only active admin tries to deactivate their own account', function () {
    $admin = User::create([
        'name' => 'Administrator',
        'username' => 'sole-admin-deactivate',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->putJson("/api/admin/users/{$admin->id}", ['is_active' => false])
        ->assertConflict()
        ->assertJsonPath('message', 'Anda tidak bisa menonaktifkan atau mengubah role akun sendiri.');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'role' => 'admin',
        'is_active' => true,
    ]);
});

it('returns 409 when the only active admin tries to demote their own account', function () {
    $admin = User::create([
        'name' => 'Administrator',
        'username' => 'sole-admin-demote',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    Sanctum::actingAs($admin, ['admin']);

    $this->putJson("/api/admin/users/{$admin->id}", ['role' => 'petugas'])
        ->assertConflict()
        ->assertJsonPath('message', 'Anda tidak bisa menonaktifkan atau mengubah role akun sendiri.');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'role' => 'admin',
        'is_active' => true,
    ]);
});

it('keeps one active admin when an admin deactivates another admin', function () {
    $activeAdmin = User::create([
        'name' => 'Administrator Satu',
        'username' => 'admin-satu',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);
    $otherAdmin = User::create([
        'name' => 'Administrator Dua',
        'username' => 'admin-dua',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    Sanctum::actingAs($activeAdmin, ['admin']);

    $this->putJson("/api/admin/users/{$otherAdmin->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('users', [
        'id' => $activeAdmin->id,
        'role' => 'admin',
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $otherAdmin->id,
        'role' => 'admin',
        'is_active' => false,
    ]);
});

it('keeps one active admin when an admin demotes another admin', function () {
    $activeAdmin = User::create([
        'name' => 'Administrator Satu',
        'username' => 'admin-satu-demote',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);
    $otherAdmin = User::create([
        'name' => 'Administrator Dua',
        'username' => 'admin-dua-demote',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    Sanctum::actingAs($activeAdmin, ['admin']);

    $this->putJson("/api/admin/users/{$otherAdmin->id}", ['role' => 'petugas'])
        ->assertOk()
        ->assertJsonPath('data.role', 'petugas');

    $this->assertDatabaseHas('users', [
        'id' => $activeAdmin->id,
        'role' => 'admin',
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $otherAdmin->id,
        'role' => 'petugas',
        'is_active' => true,
    ]);
});

it('returns 409 when a concurrent update would remove the last active admin', function () {
    $staleAdmin = User::create([
        'name' => 'Administrator Satu',
        'username' => 'admin-stale',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);
    $lastActiveAdmin = User::create([
        'name' => 'Administrator Dua',
        'username' => 'admin-last-active',
        'password' => 'password-rahasia',
        'role' => 'admin',
        'is_active' => true,
    ]);

    Sanctum::actingAs($staleAdmin, ['admin']);

    User::query()->whereKey($staleAdmin->id)->update([
        'role' => 'petugas',
        'is_active' => false,
    ]);

    $this->putJson("/api/admin/users/{$lastActiveAdmin->id}", ['is_active' => false])
        ->assertConflict()
        ->assertJsonPath('message', 'Sistem harus memiliki minimal satu akun admin aktif.');

    $this->assertDatabaseHas('users', [
        'id' => $lastActiveAdmin->id,
        'role' => 'admin',
        'is_active' => true,
    ]);
});
