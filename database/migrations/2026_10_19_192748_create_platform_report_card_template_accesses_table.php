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
        Schema::create('platform_report_card_template_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id');
            $table->foreignId('platform_report_card_template_id');
            $table->string('status')->default('granted');
            $table->string('payment_method')->default('offline');
            $table->string('payment_reference')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->text('payment_note')->nullable();
            $table->foreignId('granted_by')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('school_id', 'prcta_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('platform_report_card_template_id', 'prcta_template_fk')->references('id')->on('platform_report_card_templates')->cascadeOnDelete();
            $table->foreign('granted_by', 'prcta_granter_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['school_id', 'platform_report_card_template_id'], 'prcta_school_template_unique');
            $table->index(['school_id', 'status', 'revoked_at'], 'prcta_school_status_idx');
        });

        DB::table('school_report_card_template_selections')->get()->each(function (object $selection): void {
            DB::table('platform_report_card_template_accesses')->insert([
                'school_id' => $selection->school_id,
                'platform_report_card_template_id' => $selection->platform_report_card_template_id,
                'status' => 'granted',
                'payment_method' => 'offline',
                'payment_note' => 'Migrated from an existing school template selection.',
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_report_card_template_accesses');
    }
};
