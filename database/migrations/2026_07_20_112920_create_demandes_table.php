<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->string('motif');
            $table->decimal('montant_estime', 12, 2);
            $table->enum('statut', [
                'en_attente',
                'acceptee',
                'rejetee',
                'preuve_envoyee',
                'terminee',
                'preuve_rejetee',
            ])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
