<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A reviewer's assignment against one revision (§10).
     *
     * Attached to the version, not the document: re-issuing revision B for
     * review must not disturb the record of what happened on revision A.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status', 24)->default('pending');
            $table->string('priority', 16)->default('medium');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('summary')->nullable();

            $table->timestamps();

            // The same reviewer is assigned to a given revision only once.
            $table->unique(['document_version_id', 'reviewer_id']);

            $table->index('reviewer_id');
            $table->index('status');
            $table->index('deadline');
            // Serves the "my overdue reviews" query without a filesort (§41).
            $table->index(['reviewer_id', 'status', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
