<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Discussion attached to a review (§25).
     *
     * page / position_x / position_y are nullable on purpose: PDF annotation
     * is a later feature (§33), and the columns exist now so adding it does
     * not require a migration against live comment data.
     */
    public function up(): void
    {
        Schema::create('review_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Self-reference for threaded replies.
            $table->foreignId('parent_id')->nullable()->constrained('review_comments')->cascadeOnDelete();

            $table->text('comment');

            $table->unsignedInteger('page')->nullable();
            $table->decimal('position_x', 8, 4)->nullable();
            $table->decimal('position_y', 8, 4)->nullable();

            $table->boolean('resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('review_id');
            $table->index(['review_id', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_comments');
    }
};
