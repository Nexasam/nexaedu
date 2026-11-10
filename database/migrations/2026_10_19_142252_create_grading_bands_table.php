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
        Schema::create('grading_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scheme_id')->constrained()->cascadeOnDelete();
            $table->decimal('minimum_score', 8, 2);
            $table->decimal('maximum_score', 8, 2);
            $table->string('grade', 20);
            $table->string('label')->nullable();
            $table->string('remark')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['grading_scheme_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_bands');
    }
};
