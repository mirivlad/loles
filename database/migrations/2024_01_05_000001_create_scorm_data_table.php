<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorm_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('package_id');
            $table->string('sco_id');
            $table->json('data');
            $table->timestamp('last_access')->useCurrent();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('course_id');
            $table->index('package_id');
            $table->index(['user_id', 'course_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorm_data');
    }
};
