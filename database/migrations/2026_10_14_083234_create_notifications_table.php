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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Unique id of the notification');
            $table->string('type')->comment('Notification class that created it');
            $table->string('notifiable_type')->comment('Model the notification is for, normally App\Models\User');
            $table->unsignedBigInteger('notifiable_id')->comment('Id of that user; no foreign key because Laravel links it by type + id');
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->text('data')->comment('Message content as JSON');
            $table->timestampTz('read_at')->nullable()->comment('When the user read it; empty while unread');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
