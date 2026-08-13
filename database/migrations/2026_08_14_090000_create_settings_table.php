<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runtime-editable system settings (§29).
     *
     * Deliberately narrow: only values an administrator should be able to
     * change without a deploy live here. Credentials, disk choices and mail
     * transport stay in .env, where they belong (§52).
     *
     * `type` lets a value round-trip through a text column without callers
     * having to remember whether a setting is an integer or a boolean.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
