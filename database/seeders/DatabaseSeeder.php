<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hanya membuat bila belum ada; untuk reset password gunakan `php artisan admin:user`
        User::query()->firstOrCreate(
            ['email' => config('app.admin.email')],
            ['name' => config('app.admin.name'), 'password' => config('app.admin.password')],
        );

        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
