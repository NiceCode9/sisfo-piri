<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_question_id')->constrained()->cascadeOnDelete();
            $table->json('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score_obtained', 6, 2)->default(0);
            $table->dateTime('answered_at');
            $table->timestamps();
            $table->unique(['exam_session_id', 'exam_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
