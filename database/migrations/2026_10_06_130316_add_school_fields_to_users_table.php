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
        if (! Schema::hasColumn('users', 'global_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('global_role')->default('user')->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'current_school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('current_school_id')
                    ->nullable()
                    ->after('global_role')
                    ->constrained('schools')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'current_school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('current_school_id');
            });
        }

        if (Schema::hasColumn('users', 'global_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('global_role');
            });
        }
    }
};
