<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // The case (filed by the employee)
            $table->string('reason');                          // Resignation, End of contract, etc.
            $table->date('last_working_day');
            $table->text('employee_message')->nullable();

            // Progress of the case
            $table->string('status')->default('Open');         // Open | Closed
            $table->unsignedTinyInteger('progress')->default(0);

            // HR hand-off
            $table->boolean('sent_to_admin')->default(false);  // HR approved documents and sent to Admin
            $table->timestamp('sent_to_admin_at')->nullable();

            // Admin final approval
            $table->boolean('admin_final_approved')->default(false);
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['sent_to_admin', 'admin_final_approved']);
        });

        Schema::create('offboarding_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offboarding_case_id')->constrained('offboarding_cases')->cascadeOnDelete();

            $table->string('document_label');                  // Clearance Form, Exit Interview, etc.
            $table->string('file_name');
            $table->string('file_path');                       // private disk

            // HR review of this document
            $table->string('review_status')->default('Under review');   // Under review | Approved | Rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_documents');
        Schema::dropIfExists('offboarding_cases');
    }
};