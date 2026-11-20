<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_studied_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            // Keep the historical record if a lesson is later deleted.
            $table->foreignId('course_module_id')->nullable()->constrained('course_modules')->nullOnDelete();
            $table->string('material_title');
            $table->timestamp('studied_at');
            $table->timestamps();
            $table->unique(['training_enrollment_id', 'course_module_id'], 'studied_enrollment_module_unique');
            $table->index(['training_enrollment_id', 'course_id'], 'studied_enrollment_course_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_studied_materials');
    }
};
