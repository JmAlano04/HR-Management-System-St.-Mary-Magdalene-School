<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('step_no')->unique(); // 1..30
            $table->string('name');                            // e.g. "Step 1"
            $table->decimal('daily_wage', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_steps');
    }
};