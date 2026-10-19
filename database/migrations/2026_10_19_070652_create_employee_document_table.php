<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Both document tables live in ONE migration so submission_boxes is always created
 * before employee_documents (which has a foreign key to it).
 * Requires the employees table (2026_10_17_102132_create_employee_table) to exist first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // e.g. "2026 NBI Clearance"
            $table->string('type');                         // e.g. "NBI"
            $table->date('deadline')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_box_id')->nullable()->constrained('submission_boxes')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->string('name');                         // original file name
            $table->string('type');                         // document type, e.g. "NBI"
            $table->string('file_path')->nullable();        // private disk, e.g. employee-documents/12/abc.pdf
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            $table->string('status')->default('Pending');   // Pending | Approved | Rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();                           // created_at = uploaded on

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('submission_boxes');
    }
};