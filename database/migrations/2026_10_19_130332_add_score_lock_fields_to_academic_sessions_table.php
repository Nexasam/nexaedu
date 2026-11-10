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
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->boolean('score_entries_locked')->default(false)->after('is_current');
            $table->timestamp('score_lock_at')->nullable()->after('score_entries_locked');
            $table->timestamp('score_unlock_at')->nullable()->after('score_lock_at');
            $table->index(['school_id', 'score_entries_locked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'score_entries_locked']);
            $table->dropColumn(['score_entries_locked', 'score_lock_at', 'score_unlock_at']);
        });
    }
};
