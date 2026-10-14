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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the support_tickets table');

            $table->foreignId('inquiry_id')->nullable()->comment('Inquiry this ticket was created from, if any')->index()->constrained('inquiries')->restrictOnDelete();
            $table->foreignId('branch_id')->comment('Branch responsible for the ticket')->index()->constrained('branches')->restrictOnDelete();
            $table->foreignId('opened_by_user_id')->comment('Staff member who opened the ticket')->index()->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->comment('Specialist the ticket was routed to')->index()->constrained('users')->restrictOnDelete();

            $table->string('subject', 150)->comment('Short summary of the issue');
            $table->text('description')->nullable()->comment('Full description of the issue');
            $table->string('status', 20)->default('open')->comment('Progress of the ticket');
            $table->timestampTz('resolved_at')->nullable()->comment('When the ticket was resolved');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE support_tickets ADD CONSTRAINT support_tickets_status_check CHECK (status IN ('open', 'in_progress', 'resolved', 'closed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
