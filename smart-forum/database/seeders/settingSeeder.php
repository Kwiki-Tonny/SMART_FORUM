<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        DB::table('settings')->insert([
            ['key' => 'inactivity_days', 'value' => '14', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'blacklist_duration', 'value' => '30', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}