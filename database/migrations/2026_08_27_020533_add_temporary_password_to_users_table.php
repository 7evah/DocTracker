<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Temporary passwords for the "mot de passe oublié" flow (§4).
 *
 * Stored *beside* the real password rather than replacing it. Overwriting the
 * password on request would let anyone lock a colleague out of their account
 * by submitting their address on the forgot-password form; keeping both means
 * the existing password stays valid until the temporary one is actually used.
 *
 * Hashed like any other credential — the plain text exists only long enough
 * to be put in the e-mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('temporary_password')->nullable()->after('password');
            $table->timestamp('temporary_password_expires_at')->nullable()->after('temporary_password');

            // Set when a temporary password is used to sign in, cleared once a
            // real one is chosen. Drives the forced-change screen.
            $table->boolean('must_change_password')->default(false)->after('temporary_password_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'temporary_password',
                'temporary_password_expires_at',
                'must_change_password',
            ]);
        });
    }
};
