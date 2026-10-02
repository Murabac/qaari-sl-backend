<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moments', function (Blueprint $table) {
            $table->dropForeign(['reciter_id']);
        });

        Schema::table('moments', function (Blueprint $table) {
            $table->foreignId('reciter_id')->nullable()->change();
            $table->foreign('reciter_id')->references('id')->on('reciters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('moments', function (Blueprint $table) {
            $table->dropForeign(['reciter_id']);
        });

        Schema::table('moments', function (Blueprint $table) {
            $table->foreignId('reciter_id')->nullable(false)->change();
            $table->foreign('reciter_id')->references('id')->on('reciters')->cascadeOnDelete();
        });
    }
};
