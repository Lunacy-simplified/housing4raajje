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
        Schema::create('districts', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the districts table');
            $table->string('name', 80)->unique()->comment('Official name of the district');
            $table->foreignId('branch_id')->index()->constrained('branches')->onDelete('restrict')->comment('Foreign key linking the district to its managing parent branch office');
            $table->boolean('is_active')->default(true)->comment('Status flag to determine if the district is active for housing routing operations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
