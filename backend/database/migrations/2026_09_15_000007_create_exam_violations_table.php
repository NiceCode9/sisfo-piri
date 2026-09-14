<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['fullscreen_exit', 'tab_blur', 'visibility_hidden', 'devtools_suspected', 'copy_paste_attempt', 'connection_lost']);
            $table->json('meta')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['exam_session_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
    }
};
