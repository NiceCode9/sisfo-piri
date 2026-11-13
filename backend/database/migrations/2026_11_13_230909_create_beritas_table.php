<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beritas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kategori_berita_id')->nullable()->constrained('kategori_beritas')->nullOnDelete();
            $table->string('judul', 200);
            $table->string('slug', 220)->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('konten');
            $table->string('gambar_utama', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beritas');
    }
};
