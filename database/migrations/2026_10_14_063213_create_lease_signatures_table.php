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
        Schema::create('lease_signatures', function (Blueprint $table) {
            $table->foreignId('lease_id')->comment('Lease being signed')->constrained('leases')->restrictOnDelete();
            $table->string('party', 10)->comment('Who signed: client or owner');
            $table->foreignId('signed_by_user_id')->comment('User who signed')->index()->constrained('users')->restrictOnDelete();
            $table->timestampTz('signed_at')->comment('When the lease was signed');
            $table->string('verification_method', 20)->comment('How the signer proved who they are');
            $table->string('ip_address', 45)->nullable()->comment('IP address the signature came from');

            $table->timestamp('created_at')->useCurrent()->comment('When this signature was saved; signatures are never edited');

            $table->primary(['lease_id', 'party']);
        });

        DB::statement("ALTER TABLE lease_signatures ADD CONSTRAINT lease_signatures_party_check CHECK (party IN ('client', 'owner'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_signatures');
    }
};
