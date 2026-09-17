<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreignId('question_bank_id')->nullable()->after('exam_id')->constrained('question_banks')->nullOnDelete();
            $table->foreignId('source_question_id')->nullable()->after('question_bank_id')->constrained('exam_questions')->nullOnDelete();
            $table->index('question_bank_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeign(['question_bank_id']);
            $table->dropForeign(['source_question_id']);
            $table->dropIndex(['question_bank_id']);
            $table->dropColumn(['question_bank_id', 'source_question_id']);
        });
    }
};
