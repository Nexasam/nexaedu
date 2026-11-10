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
        Schema::create('student_report_card_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_trait_definition_id');
            $table->foreign('report_card_trait_definition_id', 'srcr_trait_definition_fk')
                ->references('id')
                ->on('report_card_trait_definitions')
                ->cascadeOnDelete();
            $table->string('rating', 50);
            $table->timestamps();

            $table->unique(['student_report_card_id', 'report_card_trait_definition_id'], 'srcr_card_trait_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_report_card_ratings');
    }
};
