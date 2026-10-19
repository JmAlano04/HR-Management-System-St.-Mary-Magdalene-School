<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Working table for the Payroll Processing page (Accounting).
 * One row per employee, per month, per cutoff, holding the numbers Accounting edits.
 *
 * It does NOT hold the finished payslip: "Transfer all to Salary Monitoring"
 * writes the computed result into the existing `payslips` table.
 *
 * Requires the employees table (2026_10_17_102132_create_employee_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // Which payroll run
            $table->string('period', 7);   // "2026-09"
            $table->string('cutoff', 4);   // "15th" | "30th"

            // Inputs edited by Accounting
            $table->unsignedSmallInteger('step_no')->default(1);
            $table->decimal('days_worked', 5, 2)->default(22);
            $table->decimal('hours_worked', 6, 2)->default(176);
            $table->decimal('late_minutes', 8, 2)->default(0);
            $table->decimal('honorarium', 10, 2)->default(0);

            // Deductions entered by Accounting.
            // cash_advance is the MONTHLY amount; half of it is deducted per cutoff.
            $table->decimal('sss', 10, 2)->default(0);
            $table->decimal('sss_loan', 10, 2)->default(0);
            $table->decimal('philhealth', 10, 2)->default(0);
            $table->decimal('peraa_ceap', 10, 2)->default(0);
            $table->decimal('peraa_ceap_loan', 10, 2)->default(0);
            $table->decimal('hdmf', 10, 2)->default(0);
            $table->decimal('hdmf_loan', 10, 2)->default(0);
            $table->decimal('cash_advance', 10, 2)->default(0);

            $table->timestamps();

            $table->unique(['period', 'cutoff', 'employee_id']);
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};