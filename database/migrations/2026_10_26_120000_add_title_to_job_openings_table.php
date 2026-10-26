<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('job_openings', 'title')) {
            Schema::table('job_openings', function (Blueprint $table) {
                $table->string('title')->nullable();
            });
        }

        DB::table('job_openings')
            ->whereNull('title')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $opening): void {
                DB::table('job_openings')
                    ->where('id', $opening->id)
                    ->update(['title' => 'Position ' . $opening->id]);
            });

        if (! DB::table('job_openings')->exists()) {
            DB::table('job_openings')->insert([
                ['title' => 'Teaching Position', 'created_at' => now(), 'updated_at' => now()],
                ['title' => 'Administration Staff', 'created_at' => now(), 'updated_at' => now()],
                ['title' => 'Support Staff', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('job_openings', 'title')) {
            Schema::table('job_openings', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
