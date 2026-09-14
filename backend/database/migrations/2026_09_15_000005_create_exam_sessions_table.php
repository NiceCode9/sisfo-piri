<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_token_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('expected_end_at');
            $table->dateTime('finished_at')->nullable();
            $table->dateTime('last_heartbeat_at')->nullable();
            $table->enum('status', ['ongoing', 'finished', 'expired', 'disqualified'])->default('ongoing');
            $table->enum('finish_reason', ['manual', 'time_up', 'violation_limit', 'admin_force'])->nullable();
            $table->unsignedInteger('violation_count')->default(0);
            $table->decimal('score', 6, 2)->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['exam_token_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
