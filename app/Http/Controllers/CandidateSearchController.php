<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateSearchController extends Controller
{
    public function search(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Candidate::with([
            'skills',
            'experiences',
            'relevantJobs',
        ])->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('profession', 'LIKE', "%{$search}%")
                    ->orWhere('experience', 'LIKE', "%{$search}%")
                    ->orWhere('education', 'LIKE', "%{$search}%")
                    ->orWhereHas('skills', function ($skillQuery) use ($search) {
                        $skillQuery->where('skill', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('relevantJobs', function ($jobQuery) use ($search) {
                        $jobQuery->where('title', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Today filter
        if ($request->input('filter') === 'today') {
            $query->whereDate('created_at', today());
        }

        $paginator = $query->paginate(10)->withQueryString();

        $items = $paginator->items();

        return response()->json([
            'count' => $paginator->total(),
            'candidates' => collect($items)->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'full_name' => $candidate->full_name,
                    'email' => $candidate->email,
                    'phone' => $candidate->phone,
                    'profession' => $candidate->profession,
                    'experience' => $candidate->experience,
                    'education' => $candidate->education,
                    'created_at' => $candidate->created_at
                        ? $candidate->created_at->format('d M Y')
                        : '',
                    'cv_file' => $candidate->cv_file,
                    'cv_original_name' => $candidate->cv_original_name,
                    'skills' => $candidate->skills
                        ->map(function ($skill) {
                            return $skill->skill;
                        })
                        ->values(),

                    'experiences' => $candidate->experiences
                        ->map(function ($experience) {
                            return [
                                'job_title' => $experience->job_title,
                                'company' => $experience->company,
                                'duration' => $experience->duration,
                                'description' => $experience->description,
                            ];
                        })
                        ->values(),

                    'relevant_jobs' => $candidate->relevantJobs
                        ->map(function ($job) {
                            return ['title' => $job->title];
                        })
                        ->values(),
                ];
            })->values(),
            'pagination' => view('partials.pagination-ajax', ['paginator' => $paginator])->render(),
        ]);
    }
}