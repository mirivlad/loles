<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('course_categories')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('type', ['standard', 'scorm', 'h5p'])->default('standard');
            $table->string('scorm_package_path')->nullable();
            $table->string('h5p_package_path')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->boolean('has_certificate')->default(false);
            $table->integer('passing_score')->default(70);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index('category_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('slug');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
