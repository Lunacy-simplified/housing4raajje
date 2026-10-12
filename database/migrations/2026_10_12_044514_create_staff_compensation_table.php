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
        Schema::create('staff_compensation', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the staff_compensation table');
            $table->foreignId('staff_user_id')->comment('Staff member this salary belongs to')->index()->constrained('staff_profiles', 'user_id')->restrictOnDelete();
            $table->decimal('monthly_salary', 12, 2)->comment('Monthly salary in MVR; never negative');
            $table->date('effective_from')->comment('First day this salary applies');
            $table->date('effective_to')->nullable()->comment('Last day this salary applies; empty while it is the current salary');
            $table->foreignId('recorded_by_user_id')->comment('Branch manager or administrator who recorded this salary')->index()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE staff_compensation ADD CONSTRAINT staff_compensation_salary_not_negative CHECK (monthly_salary >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_compensation');
    }
};
