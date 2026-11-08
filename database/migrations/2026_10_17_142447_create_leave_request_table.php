<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot ng employee info sa oras ng request
            $table->string('employee_code')->nullable();   // e.g. EMP-234482
            $table->string('employee_name');
            $table->string('department')->nullable();

            $table->string('type');                         // Sick Leave, Vacation Leave, etc.
            $table->date('requested_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('leave_balance')->nullable();    // e.g. "8 days remaining"

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('admin_archived')->default(false);

            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['admin_archived', 'status']);
            $table->index('requested_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};