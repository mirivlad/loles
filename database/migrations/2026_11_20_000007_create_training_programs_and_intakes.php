<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('training_program_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['training_program_id', 'course_id'], 'program_course_unique');
        });

        Schema::create('training_intakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('application_deadline');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('capacity');
            $table->unsignedTinyInteger('required_progress_percent')->default(100);
            $table->string('status', 16)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['starts_on', 'ends_on']);
        });

        // Preserve the curriculum used by an intake when a reusable program is edited later.
        Schema::create('training_intake_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_intake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['training_intake_id', 'course_id'], 'intake_course_unique');
        });

        Schema::create('training_intake_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_intake_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['training_intake_id', 'name'], 'intake_group_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_intake_groups');
        Schema::dropIfExists('training_intake_courses');
        Schema::dropIfExists('training_intakes');
        Schema::dropIfExists('training_program_courses');
        Schema::dropIfExists('training_programs');
    }
};
