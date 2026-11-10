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
        Schema::create('platform_report_card_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('education_level', 30);
            $table->string('term_result_mode')->default('independent');
            $table->json('layout')->nullable();
            $table->json('grading_bands');
            $table->json('traits');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['education_level', 'is_active'], 'prct_level_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_report_card_templates');
    }
};
