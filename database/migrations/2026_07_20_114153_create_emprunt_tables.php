<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprunts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_preteuse_id')->constrained('caisses')->cascadeOnDelete();
            $table->foreignId('caisse_emprunteuse_id')->constrained('caisses')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('motif');
            $table->foreignId('enregistre_par')->constrained('users')->cascadeOnDelete();
            $table->enum('statut', ['en_cours', 'rembourse'])->default('en_cours');
            $table->timestamp('date_emprunt')->useCurrent();
            $table->timestamp('date_remboursement')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprunts');
    }
};
