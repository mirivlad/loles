<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('test_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('answer_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('text_response')->nullable();
            $table->json('matching_response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->integer('score')->default(0);
            $table->timestamps();
            
            $table->index('attempt_id');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_responses');
    }
};
