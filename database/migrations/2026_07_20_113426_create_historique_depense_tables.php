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
        Schema::create('historique_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->constrained('demandes')->onDelete('cascade');
            $table->foreignId('casse_id')->constrained('casses')->onDelete('cascade');
            $table->enum('sens', ['entree', 'sortie']);
            $table->string('type');
            $table->string('montant', 12)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historique_tables');
    }
};
