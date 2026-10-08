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
        Schema::create('branches', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the branches table');
            $table->smallInteger('branch_no')->unique()->comment('Internal unique branch sequence number, must be positive');
            $table->string('code', 3)->unique()->comment('3-letter uppercase identifier code e.g., MLE, HLM, VIL');
            $table->string('name', 60)->unique()->comment('Official location name of the regional housing branch office');
            $table->string('phone', 20)->nullable()->comment('Primary public contact phone number for this office branch');
            $table->boolean('is_active')->default(true)->comment('Toggle status to enable or disable branch routing operations');
            $table->timestamps();
        });
        
        DB::statement('ALTER TABLE branches ADD CONSTRAINT branches_branch_no_positive CHECK (branch_no > 0)');
        DB::statement("ALTER TABLE branches ADD CONSTRAINT branches_code_format CHECK (code ~ '^[A-Z]{3}$')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
