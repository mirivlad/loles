<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_matching_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('left_text');
            $table->string('right_text');
            $table->integer('match_score')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_matching_pairs');
    }
};
