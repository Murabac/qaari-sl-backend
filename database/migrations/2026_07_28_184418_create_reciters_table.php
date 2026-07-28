<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reciters', function (Blueprint $table) {
            $table->id();
            $table->string('name_somali');
            $table->string('name_arabic');
            $table->string('name_english');
            $table->text('bio_somali')->nullable();
            $table->text('bio_arabic')->nullable();
            $table->text('bio_english')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('region')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reciters');
    }
};
