<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvisionnements', function (Blueprint $table) {
            $table->enum('source_type', ['directe', 'indirecte'])->nullable()->after('motif');
            $table->string('compte_bancaire')->nullable()->after('source_type');
            $table->enum('mode_reglement', ['cheque', 'espece'])->nullable()->after('compte_bancaire');
            $table->string('numero_cheque')->nullable()->after('mode_reglement');
            $table->string('depose_par')->nullable()->after('numero_cheque');
        });
    }

    public function down(): void
    {
        Schema::table('approvisionnements', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'compte_bancaire', 'mode_reglement', 'numero_cheque', 'depose_par']);
        });
    }
};
