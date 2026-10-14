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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the payouts table');
            $table->foreignId('owner_user_id')->comment('Owner receiving the payout')->constrained('users')->restrictOnDelete();
            $table->date('period_month')->comment('Month covered, stored as its first day');

            $table->decimal('gross_amount', 12, 2)->comment('Rent collected for the month in MVR, before the fee');
            $table->decimal('fee_amount', 12, 2)->comment('Management fee taken in MVR');
            $table->decimal('net_amount', 12, 2)->comment('Amount paid to the owner: gross minus fee');
            $table->decimal('fee_percent', 5, 2)->comment('Management fee percentage at the time, kept so old statements stay correct');

            $table->string('status', 10)->default('pending')->comment('pending or paid');
            $table->timestampTz('paid_at')->nullable()->comment('When the payout was sent to the owner');
            $table->string('bank_reference', 60)->nullable()->comment('Bank transfer reference for the payout');

            $table->timestamps();

            $table->unique(['owner_user_id', 'period_month']);
        });

        DB::statement("ALTER TABLE payouts ADD CONSTRAINT payouts_status_check CHECK (status IN ('pending', 'paid'))");

        DB::statement("ALTER TABLE payouts ADD CONSTRAINT payouts_net_calculation_check CHECK (net_amount = gross_amount - fee_amount)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
