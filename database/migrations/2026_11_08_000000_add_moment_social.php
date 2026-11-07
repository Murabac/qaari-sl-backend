<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moments', function (Blueprint $table): void {
            $table->unsignedInteger('saves_count')->default(0)->after('likes_count');
            $table->unsignedInteger('comments_count')->default(0)->after('saves_count');
        });

        Schema::create('reciter_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reciter_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'reciter_id']);
        });

        Schema::create('moment_saves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moment_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'moment_id']);
        });

        Schema::create('moment_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moment_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->timestamps();
            $table->index(['moment_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moment_comments');
        Schema::dropIfExists('moment_saves');
        Schema::dropIfExists('reciter_follows');

        Schema::table('moments', function (Blueprint $table): void {
            $table->dropColumn(['saves_count', 'comments_count']);
        });
    }
};
