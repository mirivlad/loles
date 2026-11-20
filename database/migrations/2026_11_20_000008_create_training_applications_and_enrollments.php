<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_intake_id')->constrained()->restrictOnDelete();
            $table->string('applicant_type', 16); // organization, individual
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('contact_name');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('payment_status', 16)->default('unpaid');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['training_intake_id', 'status']);
        });

        Schema::create('training_application_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_application_id')->constrained()->restrictOnDelete();
            $table->string('full_name');
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_intake_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('training_application_student_id')->nullable()->unique('enrollment_application_student_unique')
                ->constrained('training_application_students')->restrictOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('training_intake_group_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('enrolled');
            $table->timestamp('enrolled_at');
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['training_intake_id', 'user_id'], 'intake_student_unique');
            $table->index(['training_intake_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_application_students');
        Schema::dropIfExists('training_applications');
    }
};
