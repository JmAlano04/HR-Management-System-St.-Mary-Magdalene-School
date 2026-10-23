<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employee "Archive" tab (soft-hide, restorable)
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('admin_reviewed_by');
            $table->index(['employee_id', 'archived_at'], 'loan_requests_employee_archive_index');
        });

        // Payslip deductions that pay a loan down. Payroll inserts rows here;
        // the "My loan balances" cards = amount - SUM(repayments).
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_request_id')->constrained('loan_requests')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->unsignedBigInteger('payslip_id')->nullable(); // link to your payslips table if you have one
            $table->timestamps();

            $table->index(['loan_request_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');

        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropIndex('loan_requests_employee_archive_index');
            $table->dropColumn('archived_at');
        });
    }
};