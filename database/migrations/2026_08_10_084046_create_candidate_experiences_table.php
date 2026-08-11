<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_experiences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidate_id')
                ->constrained('candidates')
                ->cascadeOnDelete();

            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('duration')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_experiences');
    }
};