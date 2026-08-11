<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'profession',
        'experience',
        'education',
        'cv_file',
        'cv_original_name',
        'cv_mime_type',
    ];

    public function skills(): HasMany
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(CandidateExperience::class);
    }
}