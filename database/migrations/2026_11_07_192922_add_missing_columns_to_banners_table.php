<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds only the columns that are missing, so it is safe on any copy of the database.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'placement')) {
                $table->string('placement')->default('admin')->index();
            }
            if (! Schema::hasColumn('banners', 'alt')) {
                $table->string('alt')->nullable();
            }
            if (! Schema::hasColumn('banners', 'is_default')) {
                $table->boolean('is_default')->default(false);
            }
            if (! Schema::hasColumn('banners', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
            if (! Schema::hasColumn('banners', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: these columns may have existed before this migration.
    }
};