<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->string('hr_status')->default('pending');
            $table->foreignId('hr_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_decided_at')->nullable();
            $table->string('admin_status')->default('pending');
            $table->foreignId('admin_decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_decided_at')->nullable();
        });

        DB::table('leave_requests')
            ->whereIn('status', ['approved', 'rejected'])
            ->update([
                'hr_status' => DB::raw('status'),
                'admin_status' => DB::raw('status'),
            ]);
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hr_decided_by');
            $table->dropColumn(['hr_status', 'hr_decided_at']);
            $table->dropConstrainedForeignId('admin_decided_by');
            $table->dropColumn(['admin_status', 'admin_decided_at']);
        });
    }
};
