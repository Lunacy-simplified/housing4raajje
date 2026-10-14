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
        Schema::create('messages', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the messages table');
            $table->foreignId('thread_id')->comment('Conversation the message belongs to')->index()->constrained('message_threads')->restrictOnDelete();
            $table->foreignId('sender_user_id')->comment('User who wrote the message')->index()->constrained('users')->restrictOnDelete();
            $table->text('body')->comment('Message text');
            $table->timestamp('created_at')->useCurrent()->comment('When the message was sent; messages are never edited');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
