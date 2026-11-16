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
        Schema::create('impersonation_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('impersonator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('impersonator_name');
            $table->string('impersonator_email');
            $table->string('target_name');
            $table->string('target_email');
            $table->string('school_name')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('start_ip', 45)->nullable();
            $table->string('end_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['impersonator_user_id', 'status'], 'imp_audits_actor_status_idx');
            $table->index(['target_user_id', 'status'], 'imp_audits_target_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_audits');
    }
};
