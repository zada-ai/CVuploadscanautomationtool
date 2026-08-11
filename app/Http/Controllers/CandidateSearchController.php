<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateSearchController extends Controller
{
    public function search(Request $request)
    {
        $searchBy = $request->input('search_by', 'profession');
        $search = trim($request->input('search', ''));

        /*
        |--------------------------------------------------------------------------
        | Only allow these search types
        |--------------------------------------------------------------------------
        */

        if (!in_array($searchBy, ['profession', 'skills'])) {
            $searchBy = 'profession';
        }

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        */

        $query = Candidate::with('skills')
            ->latest();

        /*
        |--------------------------------------------------------------------------
        | Profession Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '' && $searchBy === 'profession') {

            $query->where(
                'profession',
                'LIKE',
                '%' . $search . '%'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Skills Search
        |--------------------------------------------------------------------------
        */

        if ($search !== '' && $searchBy === 'skills') {

            $query->whereHas('skills', function ($skillQuery) use ($search) {

                $skillQuery->where(
                    'skill',
                    'LIKE',
                    '%' . $search . '%'
                );

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Get Candidates
        |--------------------------------------------------------------------------
        */

        $candidates = $query->get();

        /*
        |--------------------------------------------------------------------------
        | Return JSON
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'count' => $candidates->count(),

            'search_by' => $searchBy,

            'search' => $search,

            'candidates' => $candidates->map(function ($candidate) {

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
                ];
            })->values(),
        ]);
    }
}