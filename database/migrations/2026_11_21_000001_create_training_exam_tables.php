<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_exam_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_intake_id')->constrained()->restrictOnDelete();
            $table->foreignId('test_id')->constrained('tests')->restrictOnDelete();
            $table->string('kind', 16); // trial, official
            $table->string('pass_mode', 24); // allowed_errors, percent
            $table->unsignedInteger('allowed_errors')->nullable();
            $table->unsignedTinyInteger('passing_percent')->nullable();
            $table->date('opens_on');
            $table->date('closes_on');
            $table->unsignedInteger('time_limit_minutes');
            $table->unsignedInteger('max_attempts');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_enabled')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['training_intake_id', 'test_id', 'kind'], 'exam_rule_intake_test_kind_unique');
            $table->index(['training_intake_id', 'is_enabled']);
        });

        Schema::create('training_exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_exam_rule_id')->constrained()->restrictOnDelete();
            $table->foreignId('training_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 16)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('rule_snapshot');
            $table->unsignedInteger('correct_count')->nullable();
            $table->unsignedInteger('error_count')->nullable();
            $table->unsignedInteger('total_count')->nullable();
            $table->unsignedTinyInteger('score_percent')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();
            $table->unique(['training_exam_rule_id', 'training_enrollment_id', 'attempt_number'],
                'exam_attempt_number_unique');
            $table->index(['training_enrollment_id', 'status']);
        });

        Schema::create('training_exam_attempt_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_exam_attempt_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->unsignedInteger('sort_order');
            $table->text('question_text');
            $table->json('options_snapshot');
            $table->json('selected_ids')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();
            $table->unique(['training_exam_attempt_id', 'sort_order'], 'exam_attempt_question_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_exam_attempt_questions');
        Schema::dropIfExists('training_exam_attempts');
        Schema::dropIfExists('training_exam_rules');
    }
};
