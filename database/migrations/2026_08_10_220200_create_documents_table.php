<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The logical document (§6). Files never live here — each uploaded file is
     * a row in document_versions, so a revision is never overwritten.
     *
     * `current_revision` denormalises the latest revision label (A, B, C…) so
     * listings do not need a join or subquery per row (§40).
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipline_id')->constrained()->restrictOnDelete();

            $table->string('document_number', 64);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('current_revision', 8)->nullable();
            $table->string('status', 20)->default('draft');

            // Preserve authorship even if the account is removed (§34).
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // A document number identifies a document within its project (§10).
            $table->unique(['project_id', 'document_number']);

            $table->index('document_number');
            $table->index('status');
            $table->index('discipline_id');
            $table->index('created_by');
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
