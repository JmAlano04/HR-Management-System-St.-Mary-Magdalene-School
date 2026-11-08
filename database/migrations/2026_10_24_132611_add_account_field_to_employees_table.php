<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Adds the "My Account" fields that are not in `employees` yet.
 * (name, email, birthday, address, emergency_name and emergency_contact already exist.)
 *
 * phone and the government IDs are stored encrypted (see the casts in the Employee model),
 * so they are TEXT columns — encrypted values are much longer than the plain value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('birthday');
            $table->string('civil_status')->nullable()->after('gender');
            $table->text('phone')->nullable()->after('email');          // encrypted
            $table->json('education')->nullable()->after('address');    // tertiary / senior_high / junior_high / primary

            // Government IDs (encrypted)
            $table->text('sss_number')->nullable();
            $table->text('philhealth_number')->nullable();
            $table->text('pagibig_number')->nullable();
            $table->text('tin')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'civil_status',
                'phone',
                'education',
                'sss_number',
                'philhealth_number',
                'pagibig_number',
                'tin',
            ]);
        });
    }
};