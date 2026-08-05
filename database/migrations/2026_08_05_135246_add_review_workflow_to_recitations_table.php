<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recitations', function (Blueprint $table): void {
            $table->string('status', 32)->default('draft')->after('file_size');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->index('status');
        });

        DB::table('recitations')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('recitations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'submitted_at', 'reviewed_at']);
        });
    }
};
