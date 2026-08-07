<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Caisse;
use App\Models\Entreprise;

class CaisseSeeder extends Seeder
{
    public function run(): void
    {
        Caisse::create([
            'entreprise_id' => Entreprise::where('slug', 'green-pay')->first()->id,
            'nom' => 'Caisse GreenPay',
            'solde' => 100000,
        ]);

        Caisse::create([
            'entreprise_id' => Entreprise::where('slug', 'da-digit-all')->first()->id,
            'nom' => 'Caisse DA Digit All',
            'solde' => 50000,
        ]);
    }
}
