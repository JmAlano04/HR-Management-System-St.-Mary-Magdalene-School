<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('zkteco.storage.table', 'zkteco_attendances'), function (Blueprint $table) {
            $table->id();
            // Unique per punch, so repeated polls can never insert a duplicate.
            $table->string('fingerprint', 64)->unique();
            $table->string('device')->index();
            $table->string('user_id')->index();
            $table->unsignedInteger('uid')->nullable();
            // Wall-clock time as the device recorded it.
            $table->dateTime('punched_at')->index();
            $table->string('timezone', 64)->nullable();
            $table->string('direction', 32);
            $table->string('verify_method', 32);
            $table->unsignedSmallInteger('raw_direction')->default(0);
            $table->unsignedSmallInteger('raw_verify')->default(0);
            $table->unsignedInteger('work_code')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'punched_at']);
        });

        Schema::create('zkteco_push_devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('name')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->string('ip_address', 45)->nullable();
            $table->json('details')->nullable();
            $table->json('stamps')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('zkteco_push_commands', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->index();
            $table->text('command');
            $table->string('status', 16)->default('pending');
            $table->integer('return_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['serial_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zkteco_push_commands');
        Schema::dropIfExists('zkteco_push_devices');
        Schema::dropIfExists(config('zkteco.storage.table', 'zkteco_attendances'));
    }
};
