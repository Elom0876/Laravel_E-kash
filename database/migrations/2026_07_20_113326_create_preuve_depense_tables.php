<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preuves_depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->constrained('demandes')->cascadeOnDelete();
            $table->string('chemin_fichier');
            $table->decimal('montant_declare', 12, 2);
            $table->foreignId('soumis_par')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('soumis_at')->useCurrent();
            $table->string('statut')->default('en_attente_verification');
            $table->timestamp('verifie_at')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preuves_depenses');
    }
};
