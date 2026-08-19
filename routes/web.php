<?php

use Illuminate\Support\Facades\Route;
use App\Models\Candidate;
use App\Http\Controllers\CvController;
use App\Http\Controllers\CandidateSearchController;
use App\Http\Controllers\CandidateDownloadController;
use OpenAI\Laravel\Facades\OpenAI;

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
})->name('home');


/*
|--------------------------------------------------------------------------
| Home Redirect
|--------------------------------------------------------------------------
|
| Fortify login ke baad agar /home par redirect kare,
| to authenticated user ko admin dashboard par bhej denge.
|
*/

Route::get('/home', function () {
    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('home.redirect');


/*
|--------------------------------------------------------------------------
| Public CV Upload
|--------------------------------------------------------------------------
|
| User login ke baghair CV upload kar sakta hai.
|
*/

Route::post('/cv/upload', [CvController::class, 'upload'])
    ->name('cv.upload');


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Ye tamam routes sirf authenticated users ke liye hain.
|
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/admin', function (\Illuminate\Http\Request $request) {

        $query = Candidate::with([
            'skills',
            'experiences',
            'relevantJobs'
        ])->latest();

        // Search filter
        $search = trim($request->input('search', ''));
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

        $candidates = $query->paginate(10)->withQueryString();

        $todayCount = Candidate::whereDate('created_at', today())->count();

        return view('admin', compact('candidates', 'todayCount'));

    })->name('admin.dashboard');


    /*
    |--------------------------------------------------------------------------
    | Candidate Profession Search
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/search', [
        CandidateSearchController::class,
        'search'
    ])->name('admin.search');


    /*
    |--------------------------------------------------------------------------
    | Download Selected CVs as ZIP
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/admin/candidates/download-zip',
        [CandidateDownloadController::class, 'downloadZip']
    )->name('admin.candidates.download.zip');

    Route::post(
        '/admin/candidates/export',
        [CandidateDownloadController::class, 'exportSelected']
    )->name('admin.candidates.export.selected');

    Route::get(
        '/admin/candidates/export-all',
        [CandidateDownloadController::class, 'exportAll']
    )->name('admin.candidates.export.all');


    /*
    |--------------------------------------------------------------------------
    | Delete Selected Candidates
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/admin/candidates/delete',
        [CandidateDownloadController::class, 'deleteSelected']
    )->name('admin.candidates.delete');

    // Get candidate JSON for editing
    Route::get('/admin/candidates/{id}', [CandidateDownloadController::class, 'getCandidate'])->name('admin.candidates.get');

    // Update candidate (AJAX)
    Route::post('/admin/candidates/{id}/update', [CandidateDownloadController::class, 'updateCandidate'])->name('admin.candidates.update');

});


/*
|--------------------------------------------------------------------------
| OpenAI Test
|--------------------------------------------------------------------------
|
| Temporary testing route.
|
*/

Route::get('/test-openai', function () {

    set_time_limit(120);

    try {

        $response = OpenAI::chat()->create([

            'model' => 'gpt-5-mini',

            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Reply with exactly: OpenAI connection successful',
                ],
            ],

        ]);

        return response(
            $response->choices[0]->message->content
        );

    } catch (\Throwable $e) {

        return response()->json([
            'error' => $e->getMessage(),
            'type' => get_class($e),
        ], 500);
    }

});