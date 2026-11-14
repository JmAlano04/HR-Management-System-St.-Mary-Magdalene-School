<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_records', 'philhealth_loan')) {
            Schema::table('payroll_records', function (Blueprint $table) {
                $table->decimal('philhealth_loan', 10, 2)->default(0)->after('philhealth');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_records', 'philhealth_loan')) {
            Schema::table('payroll_records', function (Blueprint $table) {
                $table->dropColumn('philhealth_loan');
            });
        }
    }
};
