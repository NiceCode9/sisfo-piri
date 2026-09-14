<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_image')->nullable();
            $table->enum('type', ['single_choice', 'multiple_choice', 'essay'])->default('single_choice');
            $table->json('options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->unsignedInteger('score')->default(1);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
