<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'first_name')) {
                $table->string('first_name')->nullable()->after('zkteco_user_id');
            }
            if (!Schema::hasColumn('employees', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('employees', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }
        });

        // Backfill: last word = last name, everything before it = first name
        DB::table('employees')
            ->whereNull('last_name')
            ->orderBy('id')
            ->each(function ($e) {
                $parts = preg_split('/\s+/', trim($e->name));
                $last  = count($parts) > 1 ? array_pop($parts) : null;
                $first = implode(' ', $parts);

                DB::table('employees')->where('id', $e->id)->update([
                    'first_name' => $first,
                    'last_name'  => $last,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_name', 'last_name']);
        });
    }
};