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
        Schema::create('system_parameters', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the system_parameters table');
            $table->string('key', 60)->unique()->comment('Setting name used in the code');
            $table->string('value', 255)->comment('Current value of the setting, stored as text');
            $table->string('description', 255)->nullable()->comment('What the setting controls, shown on the admin configuration screen');
            $table->foreignId('updated_by_user_id')->nullable()->comment('Administrator who last changed this setting; empty if never changed')->index()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_parameters');
    }
};
