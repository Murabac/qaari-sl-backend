<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reciter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('surah_id')->constrained()->cascadeOnDelete();
            $table->string('audio_url');
            $table->unsignedInteger('duration')->nullable()->comment('Duration in seconds');
            $table->unsignedBigInteger('file_size')->nullable()->comment('File size in bytes');
            $table->timestamps();

            $table->unique(['reciter_id', 'surah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitations');
    }
};
