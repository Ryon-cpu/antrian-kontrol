<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\View\View;

class PetugasController extends Controller
{
    public function index(SettingService $settings): View
    {
        return view('petugas.dashboard', [
            'appName' => $settings->get('app_name', 'Antrian Pasien'),
        ]);
    }
}
