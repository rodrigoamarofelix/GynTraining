<?php

namespace Database\Seeders;

use App\Enums\GymStatus;
use App\Models\Gym;
use Illuminate\Database\Seeder;

class GymSeeder extends Seeder
{
    public function run(): void
    {
        Gym::query()->updateOrCreate(
            ['slug' => 'academia-central'],
            [
                'name' => 'Academia Central',
                'description' => 'Academia principal de demonstração',
                'address' => 'Rua das Flores, 100',
                'phone' => '11999990000',
                'email' => 'contato@academiacentral.local',
                'status' => GymStatus::Active,
            ],
        );
    }
}
