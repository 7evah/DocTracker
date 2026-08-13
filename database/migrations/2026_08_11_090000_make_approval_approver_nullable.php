<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A workflow step can end up with nobody to sign it — no active user
     * holds its role. Such a step is marked Skipped rather than left pending
     * forever, which requires approver_id to be nullable.
     *
     * The foreign key also moves to nullOnDelete: removing a user must not
     * erase the record that an approval step existed (§34).
     */
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropForeign(['approver_id']);
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->foreignId('approver_id')->nullable()->change();
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->foreign('approver_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropForeign(['approver_id']);
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->foreignId('approver_id')->nullable(false)->change();
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->foreign('approver_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
