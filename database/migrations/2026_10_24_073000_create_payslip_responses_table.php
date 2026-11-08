<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * The employee's approve / reject decision on a released payslip.
     * Kept in its own table so the existing `payslips` table is not changed.
     * Requires: payslips (2026_10_18_113832) and employees.
     */
    public function up(): void
    {
        Schema::create('payslip_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->unique()->constrained('payslips')->cascadeOnDelete();   // one decision per payslip
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('status');                       // Approved | Rejected
            $table->text('reason')->nullable();             // required when Rejected
            $table->timestamp('reviewed_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_responses');
    }
};