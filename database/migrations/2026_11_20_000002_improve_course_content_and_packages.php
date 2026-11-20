<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Previously a MySQL ENUM exposed only lesson/test/scorm/h5p/assignment,
        // while the manager form saved section/video/file/link. Never drop old data.
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('type', 32)->default('lesson')->change();
            $table->string('video_url', 2048)->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('test_id')->nullable()->constrained('tests')->nullOnDelete();
        });

        Schema::create('course_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('format', ['scorm', 'h5p']);
            $table->string('standard_version', 40)->nullable();
            $table->string('title')->nullable();
            $table->string('original_filename');
            $table->string('storage_path')->unique();
            $table->string('sha256', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['course_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_packages');
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('test_id');
            $table->dropColumn(['video_url', 'external_url', 'file_path']);
            // Do not restore the broken ENUM or truncate existing content types.
        });
    }
};
