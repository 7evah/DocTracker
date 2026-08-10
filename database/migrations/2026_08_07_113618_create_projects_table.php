<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Engineering projects (§10).
     *
     * Soft-deleted: a project owns documents that carry approval history, so
     * removing one must never destroy the audit trail (§34).
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 32)->unique();
            $table->string('name');
            $table->string('client')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();

            // Manager may be reassigned or leave; keep the project on deletion.
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 20)->default('planning');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('manager_id');
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
