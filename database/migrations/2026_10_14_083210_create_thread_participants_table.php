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
        Schema::create('thread_participants', function (Blueprint $table) {
            $table->foreignId('thread_id')->comment('Conversation the user takes part in')->constrained('message_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->comment('User taking part')->index()->constrained('users')->cascadeOnDelete();
            $table->timestampTz('last_read_at')->nullable()->comment('When the user last opened the conversation; used for unread counts');
            $table->timestamps();

            $table->primary(['thread_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_participants');
    }
};
