<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recitations', function (Blueprint $table) {
            $table->string('sync_status', 32)->default('pending')->after('created_by');
            $table->timestamp('synced_at')->nullable()->after('sync_status');
            $table->text('sync_error')->nullable()->after('synced_at');
            $table->string('sync_method', 64)->nullable()->after('sync_error');
        });

        Schema::create('recitation_ayah_timings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recitation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedInteger('start_ms');
            $table->unsignedInteger('end_ms');
            $table->timestamps();

            $table->unique(['recitation_id', 'ayah_number']);
            $table->index(['recitation_id', 'start_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitation_ayah_timings');

        Schema::table('recitations', function (Blueprint $table) {
            $table->dropColumn(['sync_status', 'synced_at', 'sync_error', 'sync_method']);
        });
    }
};
