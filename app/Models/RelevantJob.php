<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RelevantJob extends Model
{
    protected $fillable = [
        'title',
    ];

    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(
            Candidate::class,
            'candidate_relevant_job'
        )->withTimestamps();
    }
}