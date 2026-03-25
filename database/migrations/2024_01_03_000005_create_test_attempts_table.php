<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('score')->nullable();
            $table->integer('correct_answers')->nullable();
            $table->integer('total_questions')->nullable();
            $table->boolean('passed')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->index('test_id');
            $table->index('user_id');
            $table->index('started_at');
            $table->index('passed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
