<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reciter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('surah_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('ayah_number')->nullable();
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->string('video_url');
            $table->string('poster_url')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['reciter_id', 'status']);
        });

        Schema::create('moment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'moment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moment_likes');
        Schema::dropIfExists('moments');
    }
};
