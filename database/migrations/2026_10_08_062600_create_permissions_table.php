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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the permissions table');
            $table->string('code', 60)->unique()->comment('Unique action permission identifier code');
            $table->string('description', 255)->nullable()->comment('Detailed explanation outlining what specific access permissions this code grants to a user');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
