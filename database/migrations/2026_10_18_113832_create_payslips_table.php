<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // Pay period
            $table->string('cutoff')->default('15th');          // 15th | 30th
            $table->date('transfer_date')->nullable();
            $table->string('step_name')->nullable();            // e.g. "Step 3"

            // Attendance summary
            $table->decimal('days_worked', 6, 2)->default(0);
            $table->decimal('hours_worked', 7, 2)->default(0);
            $table->unsignedInteger('late_minutes')->default(0);

            // Earnings
            $table->decimal('basic_pay', 12, 2)->default(0);
            $table->decimal('daily_wage', 12, 2)->default(0);
            $table->decimal('honorarium', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);

            // Deductions
            $table->decimal('malasakitan', 12, 2)->default(50);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('absences_deduction', 12, 2)->default(0);
            $table->decimal('sss', 12, 2)->default(0);
            $table->decimal('sss_loan', 12, 2)->default(0);
            $table->decimal('philhealth', 12, 2)->default(0);
            $table->decimal('peraa_ceap', 12, 2)->default(0);
            $table->decimal('peraa_ceap_loan', 12, 2)->default(0);
            $table->decimal('hdmf', 12, 2)->default(0);
            $table->decimal('hdmf_loan', 12, 2)->default(0);
            $table->decimal('cash_advance', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            $table->decimal('net_pay', 12, 2)->default(0);

            // Workflow: Accounting -> Admin / HR approval -> Employee release
            $table->timestamp('sent_to_admin_at')->nullable();   // null = not sent by Accounting yet

            $table->string('admin_status')->default('Pending');  // Pending | Approved | Rejected
            $table->text('admin_rejection_reason')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('hr_status')->default('Pending');
            $table->text('hr_rejection_reason')->nullable();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->foreignId('hr_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('released_at')->nullable();        // sent to the employee

            $table->timestamps();

            $table->unique(['employee_id', 'transfer_date', 'cutoff']);
            $table->index('admin_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};