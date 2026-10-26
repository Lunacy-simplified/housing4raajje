<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_invite_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Email address associated with the staff invitation.');
            $table->string('token')->comment('Hashed staff invitation token.');
            $table->timestamp('created_at')->nullable()->comment('Time the staff invitation token was created.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invite_tokens');
    }
};
