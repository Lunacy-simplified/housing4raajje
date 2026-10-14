<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the inquiries table');

            $table->foreignId('branch_id')->comment('Branch that received the inquiry')->index()->constrained('branches')->restrictOnDelete();
            $table->foreignId('logged_by_user_id')->comment('Receptionist who logged it')->index()->constrained('users')->restrictOnDelete();

            $table->string('channel', 10)->comment('How the person got in touch');
            $table->string('contact_name', 120)->comment('Name of the person asking');
            $table->string('contact_phone', 20)->nullable()->comment('Phone number to call back, if given');

            $table->foreignId('related_user_id')->nullable()->comment('Matching account, if the person is already registered')->index()->constrained('users')->restrictOnDelete();

            $table->string('subject', 150)->comment('Short summary of the inquiry');
            $table->text('details')->nullable()->comment('Full notes from the conversation');
            $table->string('status', 10)->default('open')->comment('open, routed to a specialist, or closed');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE inquiries ADD CONSTRAINT inquiries_channel_check CHECK (channel IN ('walk_in', 'phone', 'email'))");
        DB::statement("ALTER TABLE inquiries ADD CONSTRAINT inquiries_status_check CHECK (status IN ('open', 'routed', 'closed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
