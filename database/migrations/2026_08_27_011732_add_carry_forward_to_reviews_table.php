<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks a review as a standing assignment (§23).
 *
 * A reviewer is normally picked for one revision, and the manager chooses
 * again for the next. On a long document that is the same choice over and
 * over, so an assignment can instead be marked as carrying forward: when a
 * later revision goes into review, the same reviewers are assigned again
 * automatically.
 *
 * The flag lives on the review rather than the document because it is a
 * property of one person's assignment — two reviewers on the same revision
 * can differ, one standing and one brought in for that revision only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('carry_forward')->default(false)->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('carry_forward');
        });
    }
};
