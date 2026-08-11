<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();

            // Basic candidate information
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // CV information extracted from uploaded file
            $table->string('profession')->nullable();
            $table->string('experience')->nullable();
            $table->text('education')->nullable();

            // Original uploaded CV
            $table->string('cv_file');
            $table->string('cv_original_name')->nullable();
            $table->string('cv_mime_type')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};