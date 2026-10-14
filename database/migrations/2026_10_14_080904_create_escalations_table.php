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
        Schema::create('escalations', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the escalations table');

            $table->foreignId('branch_id')->comment('Branch whose manager handles the escalation')->index()->constrained('branches')->restrictOnDelete();
            $table->foreignId('raised_by_user_id')->comment('Staff member who escalated the issue')->index()->constrained('users')->restrictOnDelete();

            $table->foreignId('lease_id')->nullable()->comment('Lease in dispute; exactly one subject is set')->index()->constrained('leases')->restrictOnDelete();
            $table->foreignId('maintenance_request_id')->nullable()->comment('Maintenance request in dispute; exactly one subject is set')->index()->constrained('maintenance_requests')->restrictOnDelete();
            $table->foreignId('application_id')->nullable()->comment('Application in dispute; exactly one subject is set')->index()->constrained('applications')->restrictOnDelete();

            $table->text('reason')->comment('Why the issue was escalated');
            $table->string('status', 10)->default('open')->comment('open or resolved');

            $table->foreignId('resolved_by_user_id')->nullable()->comment('Branch manager who resolved it')->index()->constrained('users')->restrictOnDelete();
            $table->text('resolution')->nullable()->comment('How the escalation was resolved');
            $table->timestampTz('resolved_at')->nullable()->comment('When the escalation was resolved');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE escalations ADD CONSTRAINT escalations_status_check CHECK (status IN ('open', 'resolved'))");

        DB::statement("ALTER TABLE escalations ADD CONSTRAINT escalations_exactly_one_subject_check CHECK (num_nonnulls(lease_id, maintenance_request_id, application_id) = 1)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escalations');
    }
};
