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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the maintenance_requests table');

            $table->foreignId('property_id')->comment('Property that needs the repair')->index()->constrained('properties')->restrictOnDelete();
            $table->foreignId('lease_id')->nullable()->comment('Lease the request was raised under, if any')->index()->constrained('leases')->restrictOnDelete();
            $table->foreignId('raised_by_user_id')->comment('Owner or client who reported the problem')->index()->constrained('users')->restrictOnDelete();

            $table->string('category', 30)->comment('Type of problem, e.g. plumbing or electrical');
            $table->text('description')->comment('What is wrong, as described by the person reporting it');

            $table->string('priority', 10)->default('medium')->comment('How urgent the repair is');
            $table->string('status', 20)->default('submitted')->comment('Progress of the request');

            $table->foreignId('assigned_to_user_id')->nullable()->comment('Staff member handling the repair')->index()->constrained('users')->restrictOnDelete();
            $table->timestampTz('resolved_at')->nullable()->comment('When the repair was completed');

            $table->timestamps();
        });

        DB::statement("ALTER TABLE maintenance_requests ADD CONSTRAINT maintenance_requests_priority_check CHECK (priority IN ('low', 'medium', 'high', 'urgent'))");
        DB::statement("ALTER TABLE maintenance_requests ADD CONSTRAINT maintenance_requests_status_check CHECK (status IN ('submitted', 'triaged', 'assigned', 'in_progress', 'resolved', 'closed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
