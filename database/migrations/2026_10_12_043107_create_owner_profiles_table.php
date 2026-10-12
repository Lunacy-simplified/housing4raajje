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
        Schema::create('owner_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->comment('Same id as the user this profile belongs to')->constrained('users')->restrictOnDelete();
            $table->string('national_id_or_passport', 30)->unique()->comment('Owner National ID card or passport number');
            $table->string('bank_name', 80)->nullable()->comment('Bank that receives the owner payouts');
            $table->string('bank_account_name', 120)->nullable()->comment('Name on the payout bank account');
            $table->string('bank_account_no', 40)->nullable()->comment('Account number that receives the owner payouts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
