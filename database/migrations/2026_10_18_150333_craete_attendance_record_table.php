<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->date('date');
            $table->time('time_in')->nullable();             // null = no punch (e.g. Absent)
            $table->time('time_out')->nullable();
            $table->decimal('hours_worked', 5, 2)->nullable();
            $table->string('status');                        // Present | Absent | Late | Half Day

            $table->boolean('archived')->default(false);     // moved to the Archive by Admin

            $table->timestamps();

            $table->unique(['employee_id', 'date']);         // one record per employee per day
            $table->index(['archived', 'date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};