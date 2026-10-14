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
        Schema::create('viewings', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the viewings table');
            $table->foreignId('property_id')->comment('Property being viewed')->index()->constrained('properties')->restrictOnDelete();
            $table->foreignId('client_user_id')->comment('Client who requested the viewing')->index()->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('requested')->comment('requested, scheduled, completed, cancelled, declined or no_show');
            $table->timestampTz('requested_at')->useCurrent()->comment('When the client asked for the viewing; filled in automatically');
            $table->timestampTz('scheduled_for')->nullable()->comment('Date and time the viewing is booked for');
            $table->foreignId('scheduled_by_user_id')->nullable()->comment('Receptionist or staff member who booked the slot')->index()->constrained('users')->restrictOnDelete();
            $table->foreignId('feedback_category_id')->nullable()->comment('Main reason recorded after the viewing; required once completed')->index()->constrained('feedback_categories')->restrictOnDelete();
            $table->text('staff_note')->nullable()->comment('Staff comment on the viewing; shown to the owner without the client name');
            $table->foreignId('logged_by_user_id')->nullable()->comment('Staff member who logged the outcome; required once completed')->index()->constrained('users')->restrictOnDelete();
            $table->timestampTz('logged_at')->nullable()->comment('When the outcome was logged; required once completed');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE viewings ADD CONSTRAINT viewings_status_check CHECK (status IN ('requested', 'scheduled', 'completed', 'cancelled', 'declined', 'no_show'))");

        DB::statement("ALTER TABLE viewings ADD CONSTRAINT viewings_completed_needs_feedback_check CHECK (status <> 'completed' OR (feedback_category_id IS NOT NULL AND logged_by_user_id IS NOT NULL AND logged_at IS NOT NULL))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viewings');
    }
};
