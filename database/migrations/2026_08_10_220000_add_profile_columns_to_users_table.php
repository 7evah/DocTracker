<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the DocFlow profile fields described in the product spec (§10).
     *
     * Roles are intentionally NOT stored here — Spatie Permission owns them,
     * so a user can hold several roles (e.g. Reviewer + Approver) which is
     * common on smaller engineering teams.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('email');
            $table->string('phone', 32)->nullable()->after('department');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('job_title')->nullable()->after('avatar_path');
            $table->string('locale', 5)->default('fr')->after('job_title');
            $table->string('status', 20)->default('active')->after('locale');
            $table->timestamp('last_active_at')->nullable()->after('status');

            $table->index('status');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['department']);

            $table->dropColumn([
                'department',
                'phone',
                'avatar_path',
                'job_title',
                'locale',
                'status',
                'last_active_at',
            ]);
        });
    }
};
