<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\CandidateExperience;
use App\Exports\CandidatesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CandidateDownloadController extends Controller
{
    /**
     * Download selected CVs as ZIP
     */
    public function downloadZip(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'integer|exists:candidates,id',
        ]);

        $candidates = Candidate::whereIn(
            'id',
            $request->candidate_ids
        )->get();

        if ($candidates->isEmpty()) {
            return back()->with(
                'error',
                'No CVs were selected.'
            );
        }

        $zipName = 'selected-cvs-' . now()->format('Y-m-d-H-i-s') . '.zip';

        $zipPath = storage_path('app/' . $zipName);

        $zip = new ZipArchive();

        if (
            $zip->open(
                $zipPath,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            ) !== true
        ) {
            return back()->with(
                'error',
                'Unable to create ZIP file.'
            );
        }

        $addedFiles = 0;

        foreach ($candidates as $candidate) {

            if (!$candidate->cv_file) {
                continue;
            }

            $filePath = storage_path(
                'app/public/' . $candidate->cv_file
            );

            if (!file_exists($filePath)) {
                continue;
            }

            $originalName = $candidate->cv_original_name
                ?: basename($filePath);

            /*
             * Prevent duplicate filenames inside ZIP.
             */
            $zipFileName = $originalName;

            $counter = 1;

            while ($zip->locateName($zipFileName) !== false) {

                $extension = pathinfo(
                    $originalName,
                    PATHINFO_EXTENSION
                );

                $name = pathinfo(
                    $originalName,
                    PATHINFO_FILENAME
                );

                $zipFileName = $name
                    . '-' . $counter
                    . ($extension ? '.' . $extension : '');

                $counter++;
            }

            $zip->addFile(
                $filePath,
                $zipFileName
            );

            $addedFiles++;
        }

        $zip->close();

        if ($addedFiles === 0) {

            if (file_exists($zipPath)) {
                unlink($zipPath);
            }

            return back()->with(
                'error',
                'None of the selected CV files were found.'
            );
        }

        return response()
            ->download(
                $zipPath,
                $zipName
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * Return single candidate JSON (for edit modal)
     */
    public function getCandidate($id)
    {
        $candidate = Candidate::with(['skills', 'experiences', 'relevantJobs'])->find($id);

        if (!$candidate) {
            return response()->json(['error' => 'Candidate not found.'], 404);
        }

        return response()->json([
            'id' => $candidate->id,
            'full_name' => $candidate->full_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'profession' => $candidate->profession,
            'education' => $candidate->education,
            'remarks' => $candidate->remarks,
            'cv_file' => $candidate->cv_file,
            'cv_original_name' => $candidate->cv_original_name,
            'skills' => $candidate->skills->map(function ($s) { return $s->skill; })->values(),
            'experiences' => $candidate->experiences->map(function ($e) {
                return [
                    'job_title' => $e->job_title,
                    'company' => $e->company,
                    'duration' => $e->duration,
                    'description' => $e->description,
                ];
            })->values(),
            'relevantJobs' => $candidate->relevantJobs->map(function ($j) { return $j->title; })->values(),
        ]);
    }


    /**
     * Update candidate fields via AJAX
     */
    public function updateCandidate(Request $request, $id)
    {
        $candidate = Candidate::find($id);

        if (!$candidate) {
            return response()->json(['error' => 'Candidate not found.'], 404);
        }

        $data = $request->only([
            'full_name',
            'email',
            'phone',
            'profession',
            'education',
            'remarks'
        ]);

        // Prevent saving remarks if the DB column doesn't exist yet
        try {
            if (isset($data['remarks']) && !\Illuminate\Support\Facades\Schema::hasColumn('candidates', 'remarks')) {
                unset($data['remarks']);
            }
        } catch (\Throwable $e) {
            // If Schema check fails for any reason, ensure we don't block update
            unset($data['remarks']);
        }

        // additional fields
        $skillsRaw = $request->input('skills'); // comma separated
        $relevantRaw = $request->input('relevant_jobs'); // comma separated

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:100',
            'profession' => 'nullable|string|max:255',
            'education' => 'nullable',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $candidate->fill($data);
            $candidate->save();
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Unable to update candidate', 'message' => $e->getMessage()], 500);
        }

        // Update skills if provided
        if (is_string($skillsRaw)) {
            // Normalize and split
            $skillTitles = array_values(array_filter(array_map('trim', explode(',', $skillsRaw))));

            // Delete old skills
            \App\Models\CandidateSkill::where('candidate_id', $candidate->id)->delete();

            foreach ($skillTitles as $title) {
                if ($title === '') continue;
                \App\Models\CandidateSkill::create([
                    'candidate_id' => $candidate->id,
                    'skill' => $title,
                ]);
            }
        }

        // Update relevant jobs if provided
        if (is_string($relevantRaw)) {
            $titles = array_values(array_filter(array_map('trim', explode(',', $relevantRaw))));

            $jobIds = [];

            foreach ($titles as $t) {
                if ($t === '') continue;
                $job = \App\Models\RelevantJob::firstOrCreate(['title' => $t]);
                $jobIds[] = $job->id;
            }

            // sync pivot
            $candidate->relevantJobs()->sync($jobIds);
        }

        // Update experiences if provided (array of objects)
        $experiencesInput = $request->input('experiences');
        if (is_array($experiencesInput)) {
            // delete old experiences
            CandidateExperience::where('candidate_id', $candidate->id)->delete();

            foreach ($experiencesInput as $exp) {
                $jobTitle = isset($exp['job_title']) ? trim($exp['job_title']) : null;
                $company = isset($exp['company']) ? trim($exp['company']) : null;
                $duration = isset($exp['duration']) ? trim($exp['duration']) : null;
                $description = isset($exp['description']) ? trim($exp['description']) : null;

                // skip empty rows
                if (!$jobTitle && !$company && !$duration && !$description) continue;

                CandidateExperience::create([
                    'candidate_id' => $candidate->id,
                    'job_title' => $jobTitle,
                    'company' => $company,
                    'duration' => $duration,
                    'description' => $description,
                ]);
            }
        }

        return response()->json(['success' => true, 'candidate' => $candidate]);
    }


    /**
     * Export selected candidates to Excel
     */
    public function exportSelected(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'integer|exists:candidates,id',
        ]);

        $ids = array_values(array_unique($request->candidate_ids));

        $fileName = 'selected-candidates-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new CandidatesExport($ids), $fileName);
    }


    /**
     * Export all candidates to Excel
     */
    public function exportAll()
    {
        $fileName = 'candidates-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new CandidatesExport(null), $fileName);
    }


    /**
     * Permanently delete selected candidates,
     * their skills, experiences and uploaded CV files.
     */
    public function deleteSelected(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'integer|exists:candidates,id',
        ]);

        $candidates = Candidate::whereIn(
            'id',
            $request->candidate_ids
        )->get();

        if ($candidates->isEmpty()) {
            return back()->with(
                'error',
                'No candidates were selected.'
            );
        }

        try {

            DB::transaction(function () use ($candidates) {

                foreach ($candidates as $candidate) {

                    /*
                     * Delete uploaded CV file.
                     *
                     * Example:
                     * storage/app/public/cvs/example.pdf
                     */
                    if ($candidate->cv_file) {

                        Storage::disk('public')->delete(
                            $candidate->cv_file
                        );
                    }


                    /*
                     * Delete candidate skills.
                     */
                    CandidateSkill::where(
                        'candidate_id',
                        $candidate->id
                    )->delete();


                    /*
                     * Delete candidate experiences.
                     */
                    CandidateExperience::where(
                        'candidate_id',
                        $candidate->id
                    )->delete();


                    /*
                     * Delete candidate record.
                     */
                    $candidate->delete();
                }
            });

            return back()->with(
                'success',
                $candidates->count() .
                ' CV(s) and all related candidate data deleted successfully.'
            );

        } catch (\Throwable $e) {

            report($e);

            return back()->with(
                'error',
                'Unable to delete the selected CVs. Please try again.'
            );
        }
    }
}