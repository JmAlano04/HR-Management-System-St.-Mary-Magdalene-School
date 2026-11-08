<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->timestamps();
        });

        DB::table('job_openings')->insert([
            ['title' => 'Teaching Position',    'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Administration Staff', 'created_at' => now(), 'updated_at' => now()],
            ['title' => 'Support Staff',        'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('job_openings');
    }
};