<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitation_review_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('audio_url');
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('caption')->nullable();
            $table->string('status_at_time', 32);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitation_review_notes');
    }
};
