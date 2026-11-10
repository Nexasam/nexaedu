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
        Schema::table('platform_report_card_templates', function (Blueprint $table) {
            $table->json('education_levels')->nullable()->after('education_level');
        });

        DB::table('platform_report_card_templates')
            ->select(['id', 'education_level'])
            ->orderBy('id')
            ->each(function (object $template): void {
                DB::table('platform_report_card_templates')
                    ->where('id', $template->id)
                    ->update(['education_levels' => json_encode([$template->education_level], JSON_THROW_ON_ERROR)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_report_card_templates', function (Blueprint $table) {
            $table->dropColumn('education_levels');
        });
    }
};
