<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reciters', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('region')->constrained('users')->nullOnDelete();
        });

        Schema::table('recitations', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
        });

        $ownerId = DB::table('users')->where('email', 'admin@qaarisl.com')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($ownerId) {
            DB::table('reciters')->whereNull('created_by')->update(['created_by' => $ownerId]);
            DB::table('recitations')->whereNull('created_by')->update(['created_by' => $ownerId]);
        }
    }

    public function down(): void
    {
        Schema::table('recitations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('reciters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
