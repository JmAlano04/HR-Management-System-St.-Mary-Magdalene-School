<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // The request
            $table->date('correction_date');
            $table->string('correction_type');            // Time In | Time Out
            $table->time('incorrect_time')->nullable();   // null = no punch was recorded
            $table->time('correct_time');
            $table->text('note');
            $table->string('proof_path')->nullable();     // image proof (private disk)

            // Admin review
            $table->string('admin_status')->default('Pending');   // Pending | Approved | Rejected
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            // HR review
            $table->string('hr_status')->default('Pending');
            $table->timestamp('hr_reviewed_at')->nullable();
            $table->foreignId('hr_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('correction_date');
            $table->index('admin_status');
            $table->index('hr_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_corrections');
    }
};