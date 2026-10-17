<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // optional link to a login account

            $table->string('employee_id')->unique();     // e.g. HR-321942
            $table->string('name')->index();
            $table->string('position');
            $table->string('department');                // elementary | highschool | seniorhigh
            $table->string('role_type');                 // teaching | nonteaching
            $table->string('email')->unique();
            $table->date('birthday');

            // Sensitive fields: encrypted with AES-256 (Laravel "encrypted" cast), so they must be TEXT
            $table->text('address');
            $table->text('emergency_name');
            $table->text('emergency_contact');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};