<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A package belongs to the reusable library; course_id is its original
        // importing course and must not govern the package's lifecycle.
        Schema::table('course_packages', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });
        Schema::table('course_packages', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->change();
            $table->json('tags')->nullable();
        });
        Schema::table('course_packages', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });

        Schema::table('course_modules', function (Blueprint $table) {
            $table->foreignId('course_package_id')->nullable()->constrained('course_packages')->nullOnDelete();
        });

        // Previously uploaded packages become normal materials in their old
        // course; the original archive and its checksum are unchanged.
        DB::table('course_packages')->orderBy('id')->chunkById(100, function ($packages) {
            foreach ($packages as $package) {
                if (!$package->course_id) continue;
                $sort = (int) DB::table('course_modules')
                    ->where('course_id', $package->course_id)
                    ->whereNull('parent_id')
                    ->max('sort_order') + 10;
                DB::table('course_modules')->insert([
                    'course_id' => $package->course_id,
                    'course_package_id' => $package->id,
                    'title' => $package->title ?: $package->original_filename,
                    'type' => $package->format,
                    'sort_order' => $sort,
                    'is_active' => true,
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Retain the linked materials rather than destroying user-authored data.
        // Downgrade is unsafe if packages were shared or their importing course deleted.
        if (DB::table('course_packages')->whereNull('course_id')->exists()) {
            throw new RuntimeException('Невозможно откатить миграцию: пакеты используются независимо от исходных курсов.');
        }
        DB::table('course_modules')->whereIn('type', ['scorm', 'h5p'])->whereNotNull('course_package_id')->delete();
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_package_id');
        });
        Schema::table('course_packages', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('tags');
            $table->foreignId('course_id')->nullable(false)->change();
        });
        Schema::table('course_packages', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
    }
};
