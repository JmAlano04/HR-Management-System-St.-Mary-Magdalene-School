<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offboarding_cases', function (Blueprint $table) {
            $table->timestamp('requirements_sent_at')->nullable();
            $table->json('required_documents')->nullable();
            $table->json('completed_interviews')->nullable();
            $table->text('rejection_reason')->nullable();
        });

        Schema::table('offboarding_documents', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('offboarding_documents', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::table('offboarding_cases', function (Blueprint $table) {
            $table->dropColumn([
                'requirements_sent_at',
                'required_documents',
                'completed_interviews',
                'rejection_reason',
            ]);
        });
    }
};
