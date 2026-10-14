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
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the message_threads table');
            $table->foreignId('branch_id')->comment('Branch whose staff take part in the conversation')->index()->constrained('branches')->restrictOnDelete();
            $table->foreignId('property_id')->nullable()->comment('Property the conversation is about, if any')->index()->constrained('properties')->restrictOnDelete();
            $table->string('subject', 150)->comment('Topic of the conversation');
            $table->foreignId('created_by_user_id')->comment('User who started the conversation')->index()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
