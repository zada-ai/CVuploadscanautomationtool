<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateExperience extends Model
{
    protected $fillable = [
        'candidate_id',
        'company',
        'designation',
        'duration',
        'description',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}