<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->nullable()->constrained('course_modules')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['probationary', 'final', 'intermediate'])->default('intermediate');
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('passing_score')->default(70);
            $table->integer('max_attempts')->default(3);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('course_id');
            $table->index('module_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
