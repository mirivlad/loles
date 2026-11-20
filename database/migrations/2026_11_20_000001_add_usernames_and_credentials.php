<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->after('name');
            $table->string('email')->nullable()->change();
        });

        // Preserve existing accounts: login becomes the local part of their email,
        // or a unique fallback if the local part is invalid or already taken.
        DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $stem = Str::lower((string) preg_replace(
                    '/[^a-z0-9._-]/i', '',
                    Str::ascii(explode('@', (string) ($user->email ?? ''))[0])
                ));
                $stem = mb_substr(trim($stem, '._-'), 0, 44);
                if (strlen($stem) < 3) {
                    $stem = 'user' . $user->id;
                }
                $candidate = $stem;
                $suffix = 0;

                while (DB::table('users')->where('username', $candidate)->exists()) {
                    $suffix++;
                    $candidate = mb_substr($stem, 0, 44) . '_' . $user->id . ($suffix > 1 ? '_' . $suffix : '');
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable(false)->change();
            $table->unique('username');
        });

        // Recoverable onboarding credentials are deliberately separate from users.
        // They are encrypted at rest, never returned by the normal user APIs.
        Schema::create('user_credentials', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->text('secret');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credentials');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
            // Keep email nullable: rows without an email cannot safely be reverted.
        });
    }
};
