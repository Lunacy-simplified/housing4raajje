<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the users table');
            $table->foreignId('role_id')->comment('Role that decides what this user can access')->index()->constrained('roles')->restrictOnDelete();
            $table->string('name', 255)->comment('Full name of the user');
            $table->string('email', 255)->nullable()->comment('Login email address; optional, unique when present');
            $table->timestampTz('email_verified_at')->nullable()->comment('When the user confirmed their email address; empty until verified');
            $table->string('phone', 20)->nullable()->unique()->comment('Contact number; attached to an owner\'s listings once verified');
            $table->timestampTz('phone_verified_at')->nullable()->comment('When the phone number was verified; empty until verified');
            $table->string('password', 255)->comment('Hashed password');
            $table->boolean('is_active')->default(true)->comment('False deactivates the account without deleting its history');
            $table->timestampTz('last_login_at')->nullable()->comment('When the user last signed in; empty if never');
            $table->rememberToken()->comment('Laravel “remember me” login token');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        DB::statement('CREATE UNIQUE INDEX users_email_lower_unique ON users (lower(email))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
