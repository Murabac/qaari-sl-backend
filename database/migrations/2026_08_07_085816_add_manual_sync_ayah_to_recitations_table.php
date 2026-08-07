<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recitations', function (Blueprint $table) {
            $table->unsignedSmallInteger('manual_sync_ayah')->nullable()->after('sync_method');
        });
    }

    public function down(): void
    {
        Schema::table('recitations', function (Blueprint $table) {
            $table->dropColumn('manual_sync_ayah');
        });
    }
};
