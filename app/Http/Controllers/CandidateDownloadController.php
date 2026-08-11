<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateSkill;
use App\Models\CandidateExperience;
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