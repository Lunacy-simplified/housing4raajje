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
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->comment('Same id as the user this profile belongs to')->constrained('users')->restrictOnDelete();
            $table->string('staff_code', 20)->unique()->comment('Staff reference shown on viewing logs and leases, e.g. H4R-STF-0042');
            $table->foreignId('branch_id')->nullable()->comment('Home branch; empty only for the system administrator')->index()->constrained('branches')->restrictOnDelete();
            $table->string('job_title', 80)->nullable()->comment('Job title shown on the staff profile');
            $table->date('hired_on')->nullable()->comment('Date the staff member joined Housing_4_Raajje');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
