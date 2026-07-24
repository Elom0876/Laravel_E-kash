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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->nullable()->constrained('entreprises');
            $table->foreignId('poste_id')->nullable()->constrained('postes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('poste_id');
            $table->dropForeign('entreprise_id');
            $table->dropColumn('poste_id, entreprise_id');
        });
    }
};
