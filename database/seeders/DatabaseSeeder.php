<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Usuario::factory()->create([
            'name' => 'Usuário de teste',
            'email' => 'teste@example.com',
        ]);
    }
}
