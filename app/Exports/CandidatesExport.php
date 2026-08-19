<?php

namespace App\Exports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Database\Eloquent\Builder;

class CandidatesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $ids;

    public function __construct(array $ids = null)
    {
        $this->ids = $ids;
    }

    public function query(): Builder
    {
        $q = Candidate::with(['skills', 'experiences', 'relevantJobs']);

        if (is_array($this->ids) && count($this->ids) > 0) {
            $q->whereIn('id', $this->ids);
        }

        return $q;
    }

    public function map($candidate): array
    {
        // Skills as semicolon-separated
        $skills = $candidate->skills->pluck('skill')->filter()->unique()->values()->all();
        $skillsStr = implode("; ", $skills);

        // Relevant jobs titles
        $rjobs = $candidate->relevantJobs->pluck('title')->filter()->unique()->values()->all();
        $rjobsStr = implode("; ", $rjobs);

        // Experiences - combine into readable blocks
        $exps = $candidate->experiences->map(function ($e) {
            $parts = [];
            if (!empty($e->job_title)) $parts[] = $e->job_title;
            if (!empty($e->company)) $parts[] = '@ ' . $e->company;
            if (!empty($e->duration)) $parts[] = '(' . $e->duration . ')';
            $desc = !empty($e->description) ? ': ' . str_replace("\r\n", " ", $e->description) : '';
            return trim(implode(' ', $parts)) . $desc;
        })->filter()->values()->all();

        $expsStr = implode("\n", $exps);

        return [
            $candidate->id,
            $candidate->full_name,
            $candidate->email,
            $candidate->phone,
            $candidate->profession,
            $candidate->experience,
            is_array($candidate->education) ? implode('; ', $candidate->education) : $candidate->education,
            $candidate->cv_original_name,
            $candidate->cv_mime_type,
            $candidate->cv_file,
            $skillsStr,
            $expsStr,
            $rjobsStr,
            optional($candidate->created_at)->toDateTimeString(),
            optional($candidate->updated_at)->toDateTimeString(),
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Full Name',
            'Email',
            'Phone',
            'Profession',
            'Experience',
            'Education',
            'CV Original Name',
            'CV MIME Type',
            'CV File Path',
            'Skills',
            'Work Experiences',
            'Relevant Jobs',
            'Created At',
            'Updated At',
        ];
    }
}
