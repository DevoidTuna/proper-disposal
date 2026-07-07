<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Popula o banco com o usuário da equipe e os pontos de coleta.
     */
    public function run(): void
    {
        // Usuário da equipe (login do admin). Protótipo: admin / admin.
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Equipe EcoDescarte',
                'email' => 'admin@ecodescarte.local',
                'password' => Hash::make('admin'),
            ],
        );

        $this->call(CollectionPointSeeder::class);
    }
}
