<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('course_modules')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->enum('type', ['lesson', 'test', 'scorm', 'h5p', 'assignment'])->default('lesson');
            $table->integer('sort_order')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('course_id');
            $table->index('parent_id');
            $table->index('type');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_modules');
    }
};
