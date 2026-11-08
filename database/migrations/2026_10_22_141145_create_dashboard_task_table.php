<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Personal to-do list shown on each employee's dashboard calendar
        Schema::create('dashboard_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('task_date');
            $table->string('title');
            $table->timestamps();

            $table->index(['user_id', 'task_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_tasks');
    }
};