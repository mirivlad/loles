<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Email-shaped logins may exceed the original 64-character username.
        // Preserve the existing unique index and all account values.
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 255)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('users')->whereRaw('CHAR_LENGTH(username) > 64')->exists()) {
            throw new \RuntimeException('Cannot restore a 64-character username column while longer logins exist.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable(false)->change();
        });
    }
};
