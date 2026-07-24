<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;

class EntrepriseSeeder extends Seeder
{
    public function run(): void
    {
        Entreprise::insert([
            ['nom' => 'GreenPay', 'slug' => 'green-pay'],
            ['nom' => 'DA Digit All', 'slug' => 'da-digit-all'],
        ]);
    }
}
