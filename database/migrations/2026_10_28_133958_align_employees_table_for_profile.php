<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'emergency_phone') && ! Schema::hasColumn('employees', 'emergency_contact')) {
            DB::statement('ALTER TABLE `employees` CHANGE `emergency_phone` `emergency_contact` TEXT NOT NULL');
        }

        if (! Schema::hasColumn('employees', 'civil_status')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('civil_status')->nullable()->after('gender');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'civil_status')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('civil_status');
            });
        }

        if (Schema::hasColumn('employees', 'emergency_contact') && ! Schema::hasColumn('employees', 'emergency_phone')) {
            DB::statement('ALTER TABLE `employees` CHANGE `emergency_contact` `emergency_phone` TEXT NOT NULL');
        }
    }
};