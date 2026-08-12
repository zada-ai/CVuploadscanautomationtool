<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use App\Jobs\ProcessCv;
use Illuminate\Support\Facades\Log;

class RetryFailedCvs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cv:retry-failed {--limit=100 : How many candidates to retry per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry processing for CVs that previously failed';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Searching for failed CVs (limit={$limit})...");

        Candidate::where('full_name', 'Processing Failed')
            ->whereNotNull('cv_file')
            ->limit($limit)
            ->get()
            ->each(function (Candidate $candidate) {

                // mark candidate as queued for processing
                $candidate->update(['full_name' => 'Processing...']);

                ProcessCv::dispatch($candidate->id);

                Log::info('Re-dispatched ProcessCv for failed candidate', [
                    'candidate_id' => $candidate->id,
                ]);

                $this->line('Re-dispatched candidate ' . $candidate->id);
            });

        $this->info('Done.');

        return 0;
    }
}
