<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем ordering и убираем input, single_choice
        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('multiple_choice', 'matching', 'ordering') NOT NULL");
        
        // Создаём таблицу для ordering элементов
        Schema::create('question_ordering_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('item_text');
            $table->integer('correct_order');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_ordering_items');
        DB::statement("ALTER TABLE questions MODIFY COLUMN type ENUM('single_choice', 'multiple_choice', 'input', 'matching') NOT NULL");
    }
};
