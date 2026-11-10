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
        Schema::create('school_user_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_user_id')->constrained()->cascadeOnDelete();
            $table->string('module');
            $table->string('access_level');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['school_user_id', 'module']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_user_permission_overrides');
    }
};
