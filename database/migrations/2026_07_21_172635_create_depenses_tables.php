<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->unique()->constrained('demandes')->cascadeOnDelete();
            $table->foreignId('caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->decimal('montant_reel', 12, 2);
            $table->foreignId('enregistre_par')->constrained('users')->cascadeOnDelete();
            $table->timestamp('date_depense')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
