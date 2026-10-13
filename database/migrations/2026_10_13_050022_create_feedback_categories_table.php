<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_categories', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the feedback_categories table');
            $table->string('label', 60)->unique()->comment('Reason staff pick after a viewing');
            $table->boolean('is_rejection')->default(true)->comment('True if this reason counts in the owner rejection charts; false for Client proceeding');
            $table->smallInteger('sort_order')->default(0)->comment('Display order in the feedback list and charts');
            $table->boolean('is_active')->default(true)->comment('False hides the category from new viewings without deleting old feedback');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_categories');
    }
};
