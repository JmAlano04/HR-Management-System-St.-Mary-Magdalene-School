<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->timestamp('staff_archived_at')->nullable()->after('archived_at')->index();
            $table->foreignId('staff_archived_by')->nullable()->after('staff_archived_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_archived_by');
            $table->dropColumn('staff_archived_at');
        });
    }
};