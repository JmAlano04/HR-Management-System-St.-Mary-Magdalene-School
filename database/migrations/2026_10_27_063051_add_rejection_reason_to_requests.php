<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The spec says rejection notices must include the reason, but
 * leave_requests and time_corrections have no column to store one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        Schema::table('time_corrections', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('hr_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', fn (Blueprint $t) => $t->dropColumn('rejection_reason'));
        Schema::table('time_corrections', fn (Blueprint $t) => $t->dropColumn('rejection_reason'));
    }
};