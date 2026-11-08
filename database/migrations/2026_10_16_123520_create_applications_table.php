<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Step 1: personal information
            $table->string('fullname');
            $table->string('email');
            $table->text('phone');          // encrypted
            $table->text('home_address');   // encrypted
            $table->date('birth_date');
            $table->string('gender');
            $table->string('civil_status');
            $table->string('education');
            $table->string('job_type');
            $table->string('position');

            // Step 2: documents (file paths)
            $table->string('resume');
            $table->string('coverletter');
            $table->string('appletter');

            // Step 3: interview (filled in by HR)
            $table->date('interview_date')->nullable();
            $table->string('interview_time')->nullable();
            $table->string('interview_location')->nullable();

            // Step 4: status (set by Admin/HR): pending | qualified | rejected
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};