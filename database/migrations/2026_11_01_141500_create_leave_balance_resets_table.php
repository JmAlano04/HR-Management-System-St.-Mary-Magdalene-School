<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balance_resets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->timestamp('reset_at');
            $table->foreignId('reset_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['year', 'reset_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_resets');
    }
};
