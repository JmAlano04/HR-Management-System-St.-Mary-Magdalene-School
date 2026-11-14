<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('time_corrections', 'proof_original_name')) {
            Schema::table('time_corrections', function (Blueprint $table): void {
                $table->string('proof_original_name')->nullable()->after('proof_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('time_corrections', 'proof_original_name')) {
            Schema::table('time_corrections', function (Blueprint $table): void {
                $table->dropColumn('proof_original_name');
            });
        }
    }
};