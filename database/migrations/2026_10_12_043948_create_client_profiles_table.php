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
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->comment('Same id as the user this profile belongs to')->constrained('users')->restrictOnDelete();
            $table->string('national_id_or_passport', 30)->nullable()->unique()->comment('Client National ID card or passport number');
            $table->string('sex', 10)->nullable()->comment('Sex as entered on the client profile');
            $table->date('date_of_birth')->nullable()->comment('Client date of birth');
            $table->decimal('max_monthly_rent', 12, 2)->nullable()->comment('Highest monthly rent in MVR the client will pay; used by search and the matchmaker');
            $table->smallInteger('preferred_rooms')->nullable()->comment('Number of rooms the client is looking for');
            $table->smallInteger('preferred_term_months')->nullable()->comment('Lease length the client prefers, in months');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
