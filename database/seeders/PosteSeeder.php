<?php

namespace Database\Seeders;

use App\Models\Poste;
use Illuminate\Database\Seeder;

class PosteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Poste::insert([
            ['nom' => 'Assistante de direction', 'slug' => 'assistante-direction', 'role' => 'gestionnaire'],
            ['nom' => 'Commercial', 'slug' => 'commercial', 'role' => 'demandeur'],
            ['nom' => 'Technicien', 'slug' => 'technicien', 'role' => 'demandeur'],
            ['nom' => 'Comptable', 'slug' => 'comptable', 'role' => 'demandeur'],
            ['nom' => 'Développeur frontend', 'slug' => 'dev-frontend', 'role' => 'demandeur'],
            ['nom' => 'Développeur fullstack', 'slug' => 'dev-backend', 'role' => 'demandeur'],
            ['nom' => 'Directeur Général', 'slug' => 'directeur-général', 'role' => 'superviseur'],

        ]);
    }
}
