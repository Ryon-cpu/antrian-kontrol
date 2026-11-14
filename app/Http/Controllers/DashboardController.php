<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\SettingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SettingService $settings): View
    {
        $user = auth()->user();

        if ($user && $user->role === UserRole::Admin) {
            return view('admin.dashboard', [
                'appName' => $settings->get('app_name', 'Antrian Pasien'),
            ]);
        }

        return view('dashboard', [
            'appName' => $settings->get('app_name', 'Antrian Pasien'),
        ]);
    }
}
