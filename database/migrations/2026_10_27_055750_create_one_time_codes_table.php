<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('one_time_codes', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key');
            $table->foreignId('user_id')->comment('User the code was sent to')->constrained('users')->cascadeOnDelete();
            $table->string('purpose', 30)->comment('mfa_login or phone_verification');
            $table->string('channel', 10)->comment('email or sms');
            $table->string('code_hash', 255)->comment('Hash of the 6-digit code; the plain code is never stored');
            $table->smallInteger('attempts')->default(0)->comment('Wrong guesses so far; the code stops working at 5');
            $table->timestampTz('expires_at')->comment('10 minutes after the code is sent');
            $table->timestampTz('consumed_at')->nullable()->comment('Set when the code is used or replaced by a newer one');
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });

        DB::statement("ALTER TABLE one_time_codes ADD CONSTRAINT one_time_codes_purpose_check CHECK (purpose IN ('mfa_login', 'phone_verification'))");
        DB::statement("ALTER TABLE one_time_codes ADD CONSTRAINT one_time_codes_channel_check CHECK (channel IN ('email', 'sms'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_time_codes');
    }
};
