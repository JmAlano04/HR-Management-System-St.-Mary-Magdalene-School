<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_working_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 100);
            $table->string('category', 40);            // National Holiday | Local Holiday | School Suspension
            $table->string('pay_type', 10)->default('unpaid'); // paid | unpaid
            $table->decimal('paid_days', 3, 2)->default(0);
            $table->decimal('paid_hours', 4, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_working_days');
    }
};