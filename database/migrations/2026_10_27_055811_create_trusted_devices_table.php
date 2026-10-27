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
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the trusted device record.');

            $table->foreignId('user_id')
                ->comment('User who registered this trusted device.')
                ->constrained()
                ->cascadeOnDelete();

            $table->char('token_hash', 64)
                ->unique()
                ->comment('SHA-256 hash of the trusted-device token.');

            $table->string('user_agent', 255)
                ->nullable()
                ->comment('Browser or device user-agent string.');

            $table->timestampTz('last_used_at')
                ->nullable()
                ->comment('Date and time this trusted device was last used.');

            $table->timestampTz('expires_at')
                ->comment('Date and time this trusted device expires.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
