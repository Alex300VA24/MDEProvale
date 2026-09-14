<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\MonthClosureService;
use Illuminate\Database\Seeder;

class CierreMesSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put(MonthClosureService::SETTING_CLOSED, '2026-08');
    }
}