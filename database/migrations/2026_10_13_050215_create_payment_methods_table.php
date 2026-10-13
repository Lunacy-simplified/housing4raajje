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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the payment_methods table');
            $table->string('code', 30)->unique()->comment('Short code used in the app');
            $table->string('name', 60)->comment('Name shown to users, e.g. Bank deposit');
            $table->boolean('is_active')->default(true)->comment('False hides the method from new leases and payments without changing old records');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
