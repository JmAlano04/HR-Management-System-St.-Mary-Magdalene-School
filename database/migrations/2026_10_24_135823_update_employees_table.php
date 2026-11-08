<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('employees', function (Blueprint $table) {
        // Add a column
        $table->string('nickname')->nullable()->after('name');

        // Change an existing column (restate its full definition)
        $table->string('position', 150)->nullable()->change();

        // Rename a column
        $table->renameColumn('emergency_contact', 'emergency_phone');

        // Drop a column
        $table->dropColumn('civil_status');
    });
}

public function down(): void
{
    Schema::table('employees', function (Blueprint $table) {
        $table->dropColumn('nickname');
        $table->string('position')->nullable(false)->change();
        $table->renameColumn('emergency_phone', 'emergency_contact');
        $table->string('civil_status')->nullable();
    });
}
};
