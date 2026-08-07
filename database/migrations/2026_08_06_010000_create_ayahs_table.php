<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->text('text_uthmani');
            $table->timestamps();

            $table->unique(['surah_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayahs');
    }
};
