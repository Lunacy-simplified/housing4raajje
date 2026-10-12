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
        Schema::create('client_preferred_branches', function (Blueprint $table) {
            $table->foreignId('client_user_id')->comment('Client who prefers this branch')->constrained('client_profiles', 'user_id')->cascadeOnDelete();
            $table->foreignId('branch_id')->comment('Branch area the client would like to live in')->constrained('branches')->cascadeOnDelete();

            $table->primary(['client_user_id', 'branch_id']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_preferred_branches');
    }
};
