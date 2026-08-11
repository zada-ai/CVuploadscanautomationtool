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
| Laravel Fortify login ke baad agar /home par bhej raha hai,
| to /home ko admin dashboard par redirect kar denge.
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
| Ye routes sirf authenticated users ke liye hain.
|
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/admin', function () {

        $candidates = Candidate::with([
            'skills',
            'experiences'
        ])
            ->latest()
            ->get();

        return view('admin', compact('candidates'));

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