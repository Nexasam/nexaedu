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
        Schema::table('school_users', function (Blueprint $table) {
            $table->foreignId('school_role_id')
                ->nullable()
                ->after('role')
                ->constrained('school_roles')
                ->nullOnDelete();
            $table->string('invite_token')->nullable()->after('status');
            $table->timestamp('invited_at')->nullable()->after('invite_token');
            $table->timestamp('invite_accepted_at')->nullable()->after('invited_at');
            $table->timestamp('invite_expires_at')->nullable()->after('invite_accepted_at');

            $table->index(['school_id', 'school_role_id']);
            $table->unique('invite_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_users', function (Blueprint $table) {
            $table->dropUnique(['invite_token']);
            $table->dropIndex(['school_id', 'school_role_id']);
            $table->dropConstrainedForeignId('school_role_id');
            $table->dropColumn([
                'invite_token',
                'invited_at',
                'invite_accepted_at',
                'invite_expires_at',
            ]);
        });
    }
};
