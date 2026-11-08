<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->string('resume_original_name')->nullable();
            $table->string('coverletter_original_name')->nullable();
            $table->string('appletter_original_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn([
                'resume_original_name',
                'coverletter_original_name',
                'appletter_original_name',
            ]);
        });
    }
};
