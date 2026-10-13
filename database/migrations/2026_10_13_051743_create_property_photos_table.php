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
        Schema::create('property_photos', function (Blueprint $table) {
            $table->id()->comment('Auto-incrementing primary key for the property_photos table');
            $table->foreignId('property_id')->comment('Property this photo belongs to; photos are deleted with the property')->index()->constrained('properties')->cascadeOnDelete();
            $table->string('file_path', 255)->comment('Where the image file is stored, relative to the storage disk');
            $table->smallInteger('sort_order')->default(0)->comment('Display order in the photo gallery; 0 comes first');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_photos');
    }
};
