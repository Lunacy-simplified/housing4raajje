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
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the audit_log table');
            $table->foreignId('user_id')->nullable()->comment('User who did the action; empty for automatic system actions')->index()->constrained('users')->restrictOnDelete();
            $table->string('action', 60)->comment('What happened, e.g. property.approve, lease.execute');
            $table->string('table_name', 60)->nullable()->comment('Table that was changed');
            $table->unsignedBigInteger('record_id')->nullable()->comment('Id of the changed row');
            $table->jsonb('old_values')->nullable()->comment('Values before the change');
            $table->jsonb('new_values')->nullable()->comment('Values after the change');
            $table->string('ip_address', 45)->nullable()->comment('IP address the action came from');
            $table->timestamp('created_at')->useCurrent()->comment('When it happened; entries are never changed');
        });

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_log_block_changes() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'audit_log is append-only: % is not allowed', TG_OP;
            END;
            $$;

            CREATE TRIGGER audit_log_no_update_delete BEFORE UPDATE OR DELETE ON audit_log
                FOR EACH ROW EXECUTE FUNCTION audit_log_block_changes();

            CREATE TRIGGER audit_log_no_truncate BEFORE TRUNCATE ON audit_log
                FOR EACH STATEMENT EXECUTE FUNCTION audit_log_block_changes();
            SQL);
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log');
        DB::unprepared('DROP FUNCTION IF EXISTS audit_log_block_changes()');
    }
};
