<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // The request
            $table->string('loan_type');                       // Salary Loan, Emergency Loan, etc.
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();                // purpose of the loan
            $table->timestamp('requested_at')->useCurrent();

            // Stage 1: HR review          (pending | approved | rejected)
            $table->string('hr_status')->default('pending');
            $table->text('hr_rejection_reason')->nullable();
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->foreignId('hr_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            // Stage 2: Accounting review  (opens only after HR approves)
            $table->string('accounting_status')->default('pending');
            $table->text('accounting_rejection_reason')->nullable();
            $table->timestamp('accounting_reviewed_at')->nullable();
            $table->foreignId('accounting_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            // Stage 3: Admin review       (opens only after HR + Accounting approve)
            $table->string('admin_status')->default('pending');
            $table->text('admin_rejection_reason')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('requested_at');
            $table->index(['hr_status', 'accounting_status', 'admin_status'], 'loan_requests_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_requests');
    }
};