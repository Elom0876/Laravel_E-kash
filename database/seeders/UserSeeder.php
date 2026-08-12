<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Poste;
use App\Models\Entreprise;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Aïcha',
            'email' => 'aicha@greenpay.com',
            'password' => bcrypt('password'),
            'poste_id' => Poste::where('slug', 'assistante-direction')->first()->id,
            'entreprise_id' => Entreprise::where('slug', 'green-pay')->first()->id,
        ]);

        User::create([
            'name' => 'Karim',
            'email' => 'karim@greenpay.com',
            'password' => bcrypt('password'),
            'poste_id' => Poste::where('slug', 'commercial')->first()->id,
            'entreprise_id' => Entreprise::where('slug', 'green-pay')->first()->id,
        ]);

        User::create([
            'name' => 'Fatou',
            'email' => 'fatou@dadigitall.com',
            'password' => bcrypt('password'),
            'poste_id' => Poste::where('slug', 'technicien')->first()->id,
            'entreprise_id' => Entreprise::where('slug', 'da-digit-all')->first()->id,
        ]);
        User::create([
            'name' => 'Moussa',
            'email' => 'moussa@greenpay.com',
            'password' => bcrypt('password'),
            'poste_id' => Poste::where('slug', 'directeur-général')->first()->id,
            'entreprise_id' => Entreprise::where('slug', 'da-digit-all')->first()->id,
        ]);
    }
}
