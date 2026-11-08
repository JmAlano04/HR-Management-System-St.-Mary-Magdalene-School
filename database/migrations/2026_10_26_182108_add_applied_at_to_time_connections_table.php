<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_corrections', function (Blueprint $table) {
            // Marks that the correction was already written to attendance
            $table->timestamp('applied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('time_corrections', function (Blueprint $table) {
            $table->dropColumn('applied_at');
        });
    }
};