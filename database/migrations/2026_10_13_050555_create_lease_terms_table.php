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
        Schema::create('lease_terms', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the lease_terms table');
            $table->smallInteger('months')->unique()->comment('Lease length in months; must be above 0');
            $table->string('label', 30)->comment('Name shown to clients');
            $table->boolean('is_active')->default(true)->comment('False hides the term from new applications without changing existing leases');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE lease_terms ADD CONSTRAINT lease_terms_months_check CHECK (months > 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_terms');
    }
};
