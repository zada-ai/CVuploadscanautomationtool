<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_relevant_job', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidate_id')
                ->constrained('candidates')
                ->cascadeOnDelete();

            $table->foreignId('relevant_job_id')
                ->constrained('relevant_jobs')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'candidate_id',
                'relevant_job_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_relevant_job');
    }
};