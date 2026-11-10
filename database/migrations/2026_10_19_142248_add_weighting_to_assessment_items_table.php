<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessment_items', function (Blueprint $table) {
            $table->decimal('raw_max_score', 10, 2)->nullable()->after('max_score');
            $table->decimal('weight', 8, 2)->nullable()->after('raw_max_score');
        });

        DB::table('assessment_items')->update([
            'raw_max_score' => DB::raw('max_score'),
            'weight' => DB::raw('max_score'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_items', function (Blueprint $table) {
            $table->dropColumn(['raw_max_score', 'weight']);
        });
    }
};
