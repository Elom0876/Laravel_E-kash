<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emprunt_tables', function (Blueprint $table) {
            $table->id();
            $table->string('montant', 12)->index();
            $table->foreignId('caisse_pretteuse_id')->constrained('caisses')->cascadeOnDelete();
            $table->foreignId('caisse_passeuse_id')->constrained('caisses')->cascadeOnDelete();
            $table->string('motif');
            $table->enum('statut', ['en cours', 'rembourse'])->default('en cours');
            $table->timestamps();
            $table->timestamp('rembourse_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emprunt_tables');
    }
};
