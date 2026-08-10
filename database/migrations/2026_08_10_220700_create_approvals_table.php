<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A concrete approval step instantiated onto a revision (§8).
     *
     * `step` carries the ordering, so the system always knows which stage is
     * active: the lowest-numbered step that is still open.
     */
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedSmallInteger('step')->default(1);
            $table->string('role', 64)->nullable();
            $table->string('status', 20)->default('pending');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('comment')->nullable();

            $table->timestamps();

            // One approver slot per step, per revision.
            $table->unique(['document_version_id', 'step']);

            $table->index('approver_id');
            $table->index('status');
            $table->index(['approver_id', 'status', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
