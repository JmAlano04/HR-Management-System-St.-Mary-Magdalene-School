<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'placement')) {
                // Existing rows came from the old single-banner setup, so treat them as dashboard banners.
                $table->string('placement')->default('admin')->after('id')->index();
            }

            if (! Schema::hasColumn('banners', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('alt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'is_default')) {
                $table->dropColumn('is_default');
            }

            if (Schema::hasColumn('banners', 'placement')) {
                $table->dropIndex(['placement']);
                $table->dropColumn('placement');
            }
        });
    }
};